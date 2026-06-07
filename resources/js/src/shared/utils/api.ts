import axios from 'axios'
import type { AxiosInstance } from 'axios'

// Extend axios config type to include _retry flag
declare module 'axios' {
  export interface InternalAxiosRequestConfig {
    _retry?: boolean
  }
}

// Helper to update auth store token without circular dependency
function updateAuthStoreToken(newToken: string) {
  try {
    const { useAuthStore } = require('@/stores/auth')
    const store = useAuthStore()
    if (store && store.setToken) {
      store.setToken(newToken)
      return
    }
  } catch {
    // Store not available, fallback to localStorage only
  }
  localStorage.setItem('token', newToken)
}

// Calculate token expiration time and store it
function updateTokenExpiration(expiresIn: number): void {
  const expiresAt = Date.now() + (expiresIn * 1000)
  localStorage.setItem('token_expires_at', expiresAt.toString())
}

// Get token expiration time
function getTokenExpiration(): number | null {
  const expiresAt = localStorage.getItem('token_expires_at')
  return expiresAt ? parseInt(expiresAt, 10) : null
}

// Create a plain axios instance for refresh token (no interceptors to avoid circular dependency)
const plainAxios = axios.create({
  baseURL: '/api/v1',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// Main API instance with interceptors
const api: AxiosInstance = axios.create({
  baseURL: '/api/v1',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  paramsSerializer: (params) => {
    const parts: string[] = []
    for (const [key, value] of Object.entries(params)) {
      if (value === undefined || value === null) continue
      if (Array.isArray(value)) {
        value.forEach((v) => parts.push(`${encodeURIComponent(key)}[]=${encodeURIComponent(v)}`))
      } else {
        parts.push(`${encodeURIComponent(key)}=${encodeURIComponent(String(value))}`)
      }
    }
    return parts.join('&')
  },
})

// Track if a token refresh is in progress
let isRefreshing = false
// Queue of requests waiting for token refresh
let refreshSubscribers: ((token: string) => void)[] = []
// Track scheduled refresh timeout
let scheduledRefreshTimeout: ReturnType<typeof setTimeout> | null = null

// Subscribe to token refresh
function subscribeTokenRefresh(callback: (token: string) => void) {
  refreshSubscribers.push(callback)
}

// Notify all subscribers with new token
function onTokenRefreshed(newToken: string) {
  refreshSubscribers.forEach((callback) => callback(newToken))
  refreshSubscribers = []
}

// Perform token refresh using plain axios (no interceptor)
async function performTokenRefresh(): Promise<{ access_token: string; refresh_token: string; expires_in: number } | null> {
  const refreshToken = localStorage.getItem('refresh_token')

  if (!refreshToken) {
    return null
  }

  try {
    const response = await plainAxios.post('/auth/refresh', { refresh_token: refreshToken })
    return response.data.data
  } catch (error) {
    return null
  }
}

// Clear scheduled refresh timeout
function clearScheduledRefresh(): void {
  if (scheduledRefreshTimeout) {
    clearTimeout(scheduledRefreshTimeout)
    scheduledRefreshTimeout = null
  }
}

// Schedule proactive token refresh
function scheduleTokenRefresh(expiresIn: number): void {
  clearScheduledRefresh()

  // Refresh 1 minute before expiration
  const refreshDelay = (expiresIn - 60) * 1000

  if (refreshDelay > 0) {
    scheduledRefreshTimeout = setTimeout(async () => {
      try {
        const response = await performTokenRefresh()
        if (response) {
          updateAuthStoreToken(response.access_token)
          localStorage.setItem('refresh_token', response.refresh_token)
          updateTokenExpiration(response.expires_in)
          // Schedule next refresh
          scheduleTokenRefresh(response.expires_in)
        }
      } catch {
        // Silent fail - will retry on next request
      }
    }, refreshDelay)
  }
}

// Request interceptor
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Response interceptor
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config

    // If error is not 401 or request has no config, reject immediately
    if (error.response?.status !== 401 || !originalRequest) {
      return Promise.reject(error)
    }

    // If request is already marked for retry, reject to prevent infinite loop
    if (originalRequest._retry) {
      localStorage.removeItem('token')
      localStorage.removeItem('refresh_token')
      localStorage.removeItem('token_expires_at')
      localStorage.removeItem('user')
      clearScheduledRefresh()
      window.location.href = '/login'
      return Promise.reject(error)
    }

    // Mark request for retry
    originalRequest._retry = true

    // If refresh is already in progress, queue this request
    if (isRefreshing) {
      return new Promise((resolve) => {
        subscribeTokenRefresh((newToken: string) => {
          originalRequest.headers.Authorization = `Bearer ${newToken}`
          resolve(api(originalRequest))
        })
      })
    }

    isRefreshing = true

    try {
      const response = await performTokenRefresh()

      if (!response) {
        throw new Error('Refresh failed')
      }

      // Store new tokens
      updateAuthStoreToken(response.access_token)
      localStorage.setItem('refresh_token', response.refresh_token)
      updateTokenExpiration(response.expires_in)

      // Schedule proactive refresh
      scheduleTokenRefresh(response.expires_in)

      // Update authorization header for original request
      originalRequest.headers.Authorization = `Bearer ${response.access_token}`

      // Notify all queued requests
      onTokenRefreshed(response.access_token)

      isRefreshing = false

      // Retry the original request with new token
      return api(originalRequest)
    } catch (refreshError) {
      isRefreshing = false
      refreshSubscribers = []

      // Refresh failed, clear tokens and redirect to login
      localStorage.removeItem('token')
      localStorage.removeItem('refresh_token')
      localStorage.removeItem('token_expires_at')
      localStorage.removeItem('user')
      clearScheduledRefresh()
      window.location.href = '/login'

      return Promise.reject(refreshError)
    }
  }
)

// Initialize scheduled refresh on module load (if token exists)
const existingToken = localStorage.getItem('token')
const existingExpiresAt = getTokenExpiration()
if (existingToken && existingExpiresAt) {
  const expiresIn = Math.floor((existingExpiresAt - Date.now()) / 1000)
  if (expiresIn > 60) {
    scheduleTokenRefresh(expiresIn)
  }
}

export { api as default, plainAxios, clearScheduledRefresh, scheduleTokenRefresh }
