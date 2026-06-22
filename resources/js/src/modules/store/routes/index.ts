import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/store',
    component: () => import('../layouts/StoreLayout.vue'),
    meta: { requiresAuth: true, requiresStoreOwner: true },
    children: [
      {
        path: '',
        redirect: '/store/dashboard',
      },
      {
        path: 'dashboard',
        name: 'store-dashboard',
        component: () => import('../pages/DashboardPage.vue'),
      },
      {
        path: 'orders',
        name: 'store-orders',
        component: () => import('../pages/OrdersPage.vue'),
      },
      {
        path: 'orders/:id',
        name: 'store-order-detail',
        component: () => import('../pages/OrderDetailPage.vue'),
      },
      {
        path: 'products',
        name: 'store-products',
        component: () => import('../pages/ProductsPage.vue'),
      },
      {
        path: 'coupons',
        name: 'store-coupons',
        component: () => import('../pages/CouponsPage.vue'),
      },
      {
        path: 'reviews',
        name: 'store-reviews',
        component: () => import('../pages/ReviewsPage.vue'),
      },
      {
        path: 'members',
        name: 'store-members',
        component: () => import('../pages/MembersPage.vue'),
      },
      {
        path: 'profile',
        name: 'store-profile',
        component: () => import('../pages/StoreProfilePage.vue'),
      },
    ],
  },
]

export default routes
