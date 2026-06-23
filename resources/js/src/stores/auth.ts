import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User } from '@shared/types'
import { clearScheduledRefresh, scheduleTokenRefresh } from '@shared/utils/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('token'))
  const initialized = ref(false)

  const isAuthenticated = computed(() => !!token.value && !!user.value)

  const isSuperAdmin = computed(() => {
    if (!user.value) return false
    if (user.value.is_super_admin) return true
    if (!user.value.roles) return false
    return user.value.roles.includes('superadmin')
  })

  // Platform admin panel access: only super admin flag or 'admin'/'superadmin' role.
  // 'owner' and 'editor' are organization-level roles, NOT platform admin roles.
  const isAdmin = computed(() => {
    if (!user.value) return false
    if (user.value.is_super_admin) return true
    if (!user.value.roles) return false
    return user.value.roles.some(role => ['superadmin', 'admin'].includes(role))
  })

  // Store panel access: user belongs to a store organization AND has a non-null
  // organization_id (buyers have null — they are not store members).
  // Super admins are explicitly excluded — they use the platform admin panel.
  //
  // All organization roles (owner, admin, editor, user, member) can access the store
  // panel. Write operations are gated per-page via useStorePermissions().
  const isStoreOwner = computed(() => {
    if (!user.value || isSuperAdmin.value) return false
    if (!user.value.organization_id) return false
    if (!user.value.roles) return false
    return user.value.roles.some(role =>
      ['owner', 'admin', 'editor', 'user', 'member'].includes(role)
    )
  })

  const hasRole = (role: string) => {
    return user.value?.roles?.includes(role) || false
  }

  const hasPermission = (permission: string) => {
    return user.value?.permissions?.includes(permission) || false
  }

  const setUser = (userData: User) => {
    user.value = userData
    localStorage.setItem('user', JSON.stringify(userData))
  }

  const setToken = (newToken: string) => {
    token.value = newToken
    localStorage.setItem('token', newToken)
  }

  const logout = () => {
    user.value = null
    token.value = null
    initialized.value = false
    localStorage.removeItem('token')
    localStorage.removeItem('refresh_token')
    localStorage.removeItem('token_expires_at')
    localStorage.removeItem('user')
    // Clear any scheduled token refresh
    clearScheduledRefresh()
  }

  const initialize = () => {
    const savedUser = localStorage.getItem('user')
    if (savedUser && token.value) {
      try {
        user.value = JSON.parse(savedUser)
        // Schedule token refresh if we have a valid token
        const expiresAt = localStorage.getItem('token_expires_at')
        if (expiresAt) {
          const expiresIn = Math.floor((parseInt(expiresAt) - Date.now()) / 1000)
          if (expiresIn > 60) {
            scheduleTokenRefresh(expiresIn)
          }
        }
      } catch {
        logout()
      }
    }
    initialized.value = true
  }

  // Initialize on store creation
  initialize()

  return {
    user,
    token,
    initialized,
    isAuthenticated,
    isAdmin,
    isSuperAdmin,
    isStoreOwner,
    hasRole,
    hasPermission,
    setUser,
    setToken,
    logout,
    initialize,
  }
})
