import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/user/dashboard',
    name: 'user-dashboard',
    component: () => import('../pages/DashboardPage.vue'),
    meta: { requiresAuth: true, requiresCustomer: true },
  },
  {
    path: '/user/orders',
    name: 'user-orders',
    component: () => import('../pages/OrdersPage.vue'),
    meta: { requiresAuth: true, requiresCustomer: true },
  },
  {
    path: '/user/orders/:id',
    name: 'user-order-detail',
    component: () => import('../pages/OrderDetailPage.vue'),
    meta: { requiresAuth: true, requiresCustomer: true },
  },
  {
    path: '/user/addresses',
    name: 'user-addresses',
    component: () => import('../pages/AddressesPage.vue'),
    meta: { requiresAuth: true, requiresCustomer: true },
  },
  {
    path: '/user/wishlist',
    name: 'user-wishlist',
    component: () => import('../pages/WishlistPage.vue'),
    meta: { requiresAuth: true, requiresCustomer: true },
  },
  {
    path: '/user/profile',
    name: 'user-profile',
    component: () => import('../pages/ProfilePage.vue'),
    meta: { requiresAuth: true, requiresCustomer: true },
  },
]

export default routes
