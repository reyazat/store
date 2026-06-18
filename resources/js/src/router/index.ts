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

const routes: RouteRecordRaw[] = [
  ...homeRoutes,
  ...productRoutes,
  ...categoryRoutes,
  ...cartRoutes,
  ...authRoutes,
  ...userRoutes,
  ...adminRoutes,
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

  // Check if route requires admin role
  if (to.meta.requiresAdmin && !authStore.isAdmin) {
    return next({ name: 'not-found' })
  }

  // Super admin cannot access customer-only routes (user panel, checkout, payment)
  if (to.meta.requiresCustomer && authStore.isSuperAdmin) {
    return next({ name: 'admin-dashboard' })
  }

  // Check if route is for guests only (login, register)
  // Super admin is redirected to admin dashboard, regular users to home
  if (to.meta.guest && authStore.isAuthenticated) {
    return next(authStore.isSuperAdmin ? { name: 'admin-dashboard' } : { name: 'home' })
  }

  next()
})

export default router
