import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/admin',
    component: () => import('../layouts/AdminLayout.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
    children: [
      {
        path: '',
        redirect: '/admin/dashboard',
      },
      {
        path: 'dashboard',
        name: 'admin-dashboard',
        component: () => import('../pages/DashboardPage.vue'),
      },
      {
        path: 'orders',
        name: 'admin-orders',
        component: () => import('../pages/OrdersPage.vue'),
      },
      {
        path: 'orders/:id',
        name: 'admin-order-detail',
        component: () => import('../pages/OrderDetailPage.vue'),
      },
      {
        path: 'products',
        name: 'admin-products',
        component: () => import('../pages/ProductsPage.vue'),
      },
      {
        path: 'categories',
        name: 'admin-categories',
        component: () => import('../pages/CategoriesPage.vue'),
      },
      {
        path: 'users',
        name: 'admin-users',
        component: () => import('../pages/UsersPage.vue'),
      },
      {
        path: 'coupons',
        name: 'admin-coupons',
        component: () => import('../pages/CouponsPage.vue'),
      },
      {
        path: 'sliders',
        name: 'admin-sliders',
        component: () => import('../pages/SlidersPage.vue'),
      },
      {
        path: 'settings',
        name: 'admin-settings',
        component: () => import('../pages/SettingsPage.vue'),
      },
    ],
  },
]

export default routes
