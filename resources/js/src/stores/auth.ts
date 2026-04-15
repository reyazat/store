import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User } from '@shared/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('token'))
  const initialized = ref(false)

  const isAuthenticated = computed(() => !!token.value && !!user.value)
  
  const isAdmin = computed(() => {
    if (!user.value) return false
    if ((user.value as any).is_super_admin) return true
    if (!user.value.roles) return false
    return user.value.roles.some(role =>
      ['superadmin', 'admin', 'owner', 'editor'].includes(role)
    )
  })

  const isSuperAdmin = computed(() => {
    if (!user.value) return false
    if ((user.value as any).is_super_admin) return true
    if (!user.value.roles) return false
    return user.value.roles.includes('superadmin')
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
    localStorage.removeItem('user')
  }

  const initialize = () => {
    const savedUser = localStorage.getItem('user')
    if (savedUser && token.value) {
      try {
        user.value = JSON.parse(savedUser)
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
    hasRole,
    hasPermission,
    setUser,
    setToken,
    logout,
    initialize,
  }
})
