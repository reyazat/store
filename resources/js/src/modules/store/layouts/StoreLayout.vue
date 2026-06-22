<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, RouterView, useRouter } from 'vue-router'
import {
  LayoutDashboard,
  ShoppingCart,
  Package,
  Users,
  Ticket,
  LogOut,
  Menu,
  X,
  ChevronLeft,
  Bell,
  MessageSquare,
  Store,
  Settings,
  Star,
} from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { authService } from '@/modules/auth/services'

const router = useRouter()
const authStore = useAuthStore()
const isSidebarOpen = ref(true)
const isMobileSidebarOpen = ref(false)

const menuItems = [
  { name: 'داشبورد',         icon: LayoutDashboard, path: '/store/dashboard' },
  { name: 'سفارشات',         icon: ShoppingCart,    path: '/store/orders'    },
  { name: 'محصولات',         icon: Package,         path: '/store/products'  },
  { name: 'اعضای فروشگاه',   icon: Users,           path: '/store/members'   },
  { name: 'کوپن‌ها',         icon: Ticket,          path: '/store/coupons'   },
  { name: 'نظرات محصولات',   icon: Star,            path: '/store/reviews'   },
  { name: 'پروفایل فروشگاه', icon: Settings,        path: '/store/profile'   },
]

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
  <div class="min-h-screen bg-secondary-100 flex" dir="rtl">
    <!-- Sidebar -->
    <aside
      :class="[
        'fixed lg:static inset-y-0 right-0 z-50 bg-secondary-800 text-white transition-all duration-300',
        isSidebarOpen ? 'w-64' : 'w-20',
        isMobileSidebarOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'
      ]"
    >
      <!-- Logo -->
      <div class="h-16 flex items-center justify-between px-4 border-b border-secondary-700">
        <RouterLink to="/store/dashboard" class="flex items-center gap-2 min-w-0">
          <Store class="w-8 h-8 text-primary-400 flex-shrink-0" />
          <span v-if="isSidebarOpen" class="font-bold text-lg truncate">پنل فروشگاه</span>
        </RouterLink>
        <button
          @click="isSidebarOpen = !isSidebarOpen"
          class="hidden lg:block p-1 hover:bg-secondary-700 rounded"
        >
          <ChevronLeft :class="['w-5 h-5 transition-transform', !isSidebarOpen && 'rotate-180']" />
        </button>
        <button
          @click="isMobileSidebarOpen = false"
          class="lg:hidden p-1 hover:bg-secondary-700 rounded"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Menu -->
      <nav class="p-4 space-y-1">
        <RouterLink
          v-for="item in menuItems"
          :key="item.path"
          :to="item.path"
          class="flex items-center gap-3 px-3 py-3 rounded-lg text-secondary-300 hover:bg-secondary-700 hover:text-white transition"
          active-class="!bg-primary-600 !text-white"
          @click="isMobileSidebarOpen = false"
        >
          <component :is="item.icon" class="w-5 h-5 flex-shrink-0" />
          <span v-if="isSidebarOpen">{{ item.name }}</span>
        </RouterLink>
      </nav>

      <!-- Logout -->
      <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-secondary-700">
        <button
          @click="handleLogout"
          class="flex items-center gap-3 w-full px-3 py-3 rounded-lg text-secondary-300 hover:bg-red-600 hover:text-white transition"
        >
          <LogOut class="w-5 h-5 flex-shrink-0" />
          <span v-if="isSidebarOpen">خروج</span>
        </button>
      </div>
    </aside>

    <!-- Mobile Overlay -->
    <div
      v-if="isMobileSidebarOpen"
      @click="isMobileSidebarOpen = false"
      class="fixed inset-0 bg-black/50 z-40 lg:hidden"
    ></div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-h-screen">
      <!-- Top Bar -->
      <header class="h-16 bg-white shadow-sm flex items-center justify-between px-4 lg:px-6">
        <div class="flex items-center gap-4">
          <button
            @click="isMobileSidebarOpen = true"
            class="lg:hidden p-2 hover:bg-secondary-100 rounded-lg"
          >
            <Menu class="w-6 h-6" />
          </button>
          <h1 class="text-lg font-bold text-secondary-800">
            {{ authStore.user?.name ? `فروشگاه ${authStore.user.name}` : 'پنل فروشگاه' }}
          </h1>
        </div>

        <div class="flex items-center gap-4">
          <button class="relative p-2 hover:bg-secondary-100 rounded-lg">
            <Bell class="w-5 h-5 text-secondary-600" />
          </button>
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
              <span class="text-primary-600 font-bold text-sm">
                {{ authStore.user?.name?.charAt(0) || 'F' }}
              </span>
            </div>
            <span class="hidden sm:block text-sm font-medium text-secondary-700">
              {{ authStore.user?.name || 'مالک فروشگاه' }}
            </span>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-1 p-4 lg:p-6">
        <RouterView />
      </main>
    </div>
  </div>
</template>
