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
