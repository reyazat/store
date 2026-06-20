import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

import homeRoutes from '@modules/home/routes'
import productRoutes from '@modules/product/routes'
import categoryRoutes from '@modules/category/routes'
import cartRoutes from '@modules/cart/routes'
import userRoutes from '@modules/user/routes'
import authRoutes from '@modules/auth/routes'
import adminRoutes from '@modules/admin/routes'
import storeRoutes from '@modules/store/routes'

const routes: RouteRecordRaw[] = [
  ...homeRoutes,
  ...productRoutes,
  ...categoryRoutes,
  ...cartRoutes,
  ...authRoutes,
  ...userRoutes,
  ...adminRoutes,
  ...storeRoutes,
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@shared/components/NotFound.vue'),
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(_to, _from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    }
    return { top: 0 }
  },
})

// Navigation Guards
router.beforeEach((to, _from, next) => {

  const authStore = useAuthStore()

  // Check if route requires authentication
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next({ name: 'login', query: { redirect: to.fullPath } })
  }

  // Admin panel is exclusively for super admins in this marketplace.
  // Store owners hitting admin routes → redirect to their store panel.
  if (to.meta.requiresAdmin && !authStore.isSuperAdmin) {
    if (authStore.isStoreOwner) return next({ name: 'store-dashboard' })
    return next({ name: 'not-found' })
  }

  // Check if route requires super admin (platform-only pages)
  if (to.meta.requiresSuperAdmin && !authStore.isSuperAdmin) {
    return next({ name: 'not-found' })
  }

  // Check if route requires store owner role
  if (to.meta.requiresStoreOwner && !authStore.isStoreOwner) {
    return next({ name: 'not-found' })
  }

  // Super admin cannot access customer-only routes (user panel, checkout, payment)
  if (to.meta.requiresCustomer && authStore.isSuperAdmin) {
    return next({ name: 'admin-dashboard' })
  }

  // Store owner cannot access customer-only routes
  if (to.meta.requiresCustomer && authStore.isStoreOwner) {
    return next({ name: 'store-dashboard' })
  }

  // Check if route is for guests only (login, register)
  if (to.meta.guest && authStore.isAuthenticated) {
    if (authStore.isSuperAdmin) return next({ name: 'admin-dashboard' })
    if (authStore.isStoreOwner) return next({ name: 'store-dashboard' })
    return next({ name: 'home' })
  }

  next()
})

export default router
