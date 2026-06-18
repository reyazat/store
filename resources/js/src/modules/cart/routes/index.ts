import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/cart/checkout',
    name: 'checkout',
    component: () => import('../pages/CheckoutPage.vue'),
    meta: { requiresAuth: true, requiresCustomer: true },
  },
  {
    path: '/payment/result',
    name: 'payment-result',
    component: () => import('../pages/PaymentResultPage.vue'),
    meta: { requiresCustomer: true },
  },
]

export default routes
