import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'login',
    component: () => import('../pages/LoginPage.vue'),
    meta: { guest: true },
  },
  {
    path: '/mfa-verification',
    name: 'mfa-verification',
    component: () => import('../pages/MfaVerificationPage.vue'),
    meta: { guest: true },
  },
  {
    path: '/otp-login',
    name: 'otp-login',
    component: () => import('../pages/OtpLoginPage.vue'),
    meta: { guest: true },
  },
  {
    path: '/force-password-change',
    name: 'force-password-change',
    component: () => import('../pages/ForcePasswordChangePage.vue'),
    meta: { guest: true },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('../pages/RegisterPage.vue'),
    meta: { guest: true },
  },
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: () => import('../pages/ForgotPasswordPage.vue'),
    meta: { guest: true },
  },
  {
    path: '/reset-password',
    name: 'reset-password',
    component: () => import('../pages/ResetPasswordPage.vue'),
    meta: { guest: true },
  },
]

export default routes
