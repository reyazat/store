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
        path: 'options',
        name: 'admin-options',
        component: () => import('../pages/OptionsPage.vue'),
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
        path: 'site-features',
        name: 'admin-site-features',
        component: () => import('../pages/SiteFeaturesPage.vue'),
      },
      {
        path: 'promo-banners',
        name: 'admin-promo-banners',
        component: () => import('../pages/PromoBannersPage.vue'),
      },
      {
        path: 'site-settings',
        name: 'admin-site-settings',
        component: () => import('../pages/SiteSettingsPage.vue'),
      },
      {
        path: 'payment-methods',
        name: 'admin-payment-methods',
        component: () => import('../pages/PaymentMethodsPage.vue'),
      },
      {
        path: 'order-statuses',
        name: 'admin-order-statuses',
        component: () => import('../pages/OrderStatusesPage.vue'),
      },
      {
        path: 'nav-links',
        name: 'admin-nav-links',
        component: () => import('../pages/NavLinksPage.vue'),
      },
      {
        path: 'faqs',
        name: 'admin-faqs',
        component: () => import('../pages/FaqsPage.vue'),
      },
      {
        path: 'audit-logs',
        name: 'admin-audit-logs',
        component: () => import('../pages/AuditLogsPage.vue'),
      },
      {
        path: 'organizations',
        name: 'admin-organizations',
        component: () => import('../pages/OrganizationsPage.vue'),
        meta: { requiresSuperAdmin: true },
      },
      {
        path: 'organizations/:uuid/overview',
        name: 'admin-store-overview',
        component: () => import('../pages/StoreOverviewPage.vue'),
        meta: { requiresSuperAdmin: true },
      },
      {
        path: 'settings',
        redirect: '/admin/site-settings',
      },
    ],
  },
]

export default routes
