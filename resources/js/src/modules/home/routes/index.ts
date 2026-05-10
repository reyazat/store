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
  {
    path: '/faq',
    name: 'faq',
    component: () => import('../pages/FaqPage.vue'),
    meta: { title: 'سوالات متداول' },
  },
  {
    path: '/privacy',
    name: 'privacy',
    component: () => import('../pages/PrivacyPage.vue'),
    meta: { title: 'حریم خصوصی' },
  },
  {
    path: '/terms',
    name: 'terms',
    component: () => import('../pages/TermsPage.vue'),
    meta: { title: 'قوانین و مقررات' },
  },
]

export default routes
