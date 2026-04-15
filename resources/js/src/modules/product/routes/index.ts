import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/products',
    name: 'products',
    component: () => import('../pages/ProductListPage.vue'),
  },
  {
    path: '/products/:slug',
    name: 'product-detail',
    component: () => import('../pages/ProductDetailPage.vue'),
  },
  {
    path: '/search',
    name: 'search',
    component: () => import('../pages/ProductListPage.vue'),
  },
  {
    path: '/discounts',
    name: 'discounts',
    component: () => import('../pages/DiscountsPage.vue'),
  },
]

export default routes
