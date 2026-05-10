<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, RouterView, useRouter } from 'vue-router'
import { 
  LayoutDashboard, 
  ShoppingCart, 
  Package, 
  FolderTree, 
  Users, 
  Ticket, 
  Settings,
  LogOut, 
  Menu, 
  X,
  ChevronLeft,
  Bell,
  GalleryHorizontal,
  Link,
  CreditCard,
  ListChecks,
  ClipboardList,
  HelpCircle,
  MessageSquare
} from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { authService } from '@/modules/auth/services'

const router = useRouter()
const authStore = useAuthStore()
const isSidebarOpen = ref(true)
const isMobileSidebarOpen = ref(false)

const menuItems = [
  { name: 'داشبورد',       icon: LayoutDashboard,  path: '/admin/dashboard' },
  { name: 'سفارشات',       icon: ShoppingCart,     path: '/admin/orders' },
  { name: 'محصولات',       icon: Package,          path: '/admin/products' },
  { name: 'دسته‌بندی‌ها', icon: FolderTree,       path: '/admin/categories' },
  { name: 'کاربران',       icon: Users,            path: '/admin/users' },
  { name: 'کوپن‌ها',       icon: Ticket,           path: '/admin/coupons' },
  { name: 'اسلایدر',          icon: GalleryHorizontal, path: '/admin/sliders' },
  { name: 'لینک‌ها',          icon: Link,              path: '/admin/nav-links' },
  { name: 'روش‌های پرداخت',   icon: CreditCard,        path: '/admin/payment-methods' },
  { name: 'وضعیت‌های سفارش', icon: ListChecks,        path: '/admin/order-statuses' },
  { name: 'سوالات متداول',    icon: HelpCircle,        path: '/admin/faqs' },
  { name: 'نظرات کاربران',    icon: MessageSquare,     path: '/admin/reviews' },
  { name: 'لاگ‌های فعالیت',   icon: ClipboardList,     path: '/admin/audit-logs' },
  { name: 'تنظیمات',          icon: Settings,          path: '/admin/site-settings' },
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
  <div class="min-h-screen bg-secondary-100 flex">
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
        <RouterLink to="/admin/dashboard" class="flex items-center gap-2 min-w-0">
          <img
            src="/images/Logo/logo.png"
            alt="لوگو تجارت"
            class="h-9 w-auto object-contain flex-shrink-0"
          />
          <span v-if="isSidebarOpen" class="font-bold text-lg truncate">پنل مدیریت</span>
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
          <h1 class="text-lg font-bold text-secondary-800">پنل مدیریت اتکا</h1>
        </div>

        <div class="flex items-center gap-4">
          <!-- Notifications -->
          <button class="relative p-2 hover:bg-secondary-100 rounded-lg">
            <Bell class="w-5 h-5 text-secondary-600" />
            <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
          </button>

          <!-- User Info -->
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
              <span class="text-primary-600 font-bold text-sm">
                {{ authStore.user?.name?.charAt(0) || 'A' }}
              </span>
            </div>
            <span class="hidden sm:block text-sm font-medium text-secondary-700">
              {{ authStore.user?.name || 'مدیر' }}
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
