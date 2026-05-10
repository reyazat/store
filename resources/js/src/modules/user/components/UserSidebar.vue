<script setup lang="ts">
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { LayoutDashboard, Package, MapPin, Heart, User, LogOut } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { authService } from '@/modules/auth/services'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const menuItems = [
  { path: '/user/dashboard', label: 'داشبورد', icon: LayoutDashboard },
  { path: '/user/orders', label: 'سفارشات', icon: Package },
  { path: '/user/addresses', label: 'آدرس‌ها', icon: MapPin },
  { path: '/user/wishlist', label: 'علاقه‌مندی‌ها', icon: Heart },
  { path: '/user/profile', label: 'پروفایل', icon: User },
]

const isActive = (path: string) => route.path === path

const handleLogout = async () => {
  try {
    const refreshToken = localStorage.getItem('refresh_token') || ''
    if (refreshToken) {
      await authService.logout(refreshToken)
    }
  } catch {
    // Proceed with local logout even if API call fails
  } finally {
    authStore.logout()
    router.push('/login')
  }
}
</script>

<template>
  <aside class="bg-white rounded-lg shadow p-4">
    <nav class="space-y-1">
      <RouterLink 
        v-for="item in menuItems" 
        :key="item.path"
        :to="item.path"
        :class="[
          'flex items-center gap-3 px-4 py-3 rounded-lg transition',
          isActive(item.path) 
            ? 'bg-primary-50 text-primary-600' 
            : 'text-gray-700 hover:bg-gray-100'
        ]"
      >
        <component :is="item.icon" class="w-5 h-5" />
        {{ item.label }}
      </RouterLink>
      
      <hr class="my-2" />
      
      <button
        @click="handleLogout"
        class="flex items-center gap-3 px-4 py-3 rounded-lg text-red-600 hover:bg-red-50 w-full transition"
      >
        <LogOut class="w-5 h-5" />
        خروج
      </button>
    </nav>
  </aside>
</template>
