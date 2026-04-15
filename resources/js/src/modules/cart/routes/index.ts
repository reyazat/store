import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/cart/checkout',
    name: 'checkout',
    component: () => import('../pages/CheckoutPage.vue'),
    meta: { requiresAuth: true },
  },
]

export default routes
