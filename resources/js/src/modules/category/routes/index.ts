import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/categories',
    name: 'categories',
    component: () => import('../pages/CategoryListPage.vue'),
  },
  {
    path: '/categories/:slug',
    name: 'category-detail',
    component: () => import('../pages/CategoryListPage.vue'),
  },
  {
    path: '/brands',
    name: 'brands',
    component: () => import('../pages/BrandsPage.vue'),
  },
]

export default routes
