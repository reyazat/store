import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'home',
    component: () => import('../pages/HomePage.vue'),
  },
  {
    path: '/order-tracking',
    name: 'order-tracking',
    component: () => import('../pages/OrderTrackingPage.vue'),
    meta: { title: 'پیگیری سفارش' },
  },
]

export default routes
