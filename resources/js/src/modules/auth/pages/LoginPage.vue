<script setup lang="ts">
import { ref, computed, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Mail, Lock, Eye, EyeOff, Loader2, Smartphone, AlertTriangle, Clock } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { authService } from '../services'
import type { User } from '@shared/types'
import { scheduleTokenRefresh } from '@shared/utils/api'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const cartStore = useCartStore()

interface LoginForm {
  email: string
  password: string
  rememberMe: boolean
}

const form = ref<LoginForm>({
  email: '',
  password: '',
  rememberMe: false,
})

const showPassword = ref(false)
const isLoading = ref(false)
const error = ref('')
const errorType = ref<'generic' | 'locked' | 'rate_limited'>('generic')
const showOtpOption = ref(false)

// Countdown for locked / rate-limited state
const lockCountdown = ref(0)
let lockInterval: ReturnType<typeof setInterval> | null = null

const redirectTo = computed(() => (route.query.redirect as string) || '/')

const formattedLockCountdown = computed(() => {
  const m = Math.floor(lockCountdown.value / 60)
  const s = lockCountdown.value % 60
  return `${m}:${s.toString().padStart(2, '0')}`
})

const startLockCountdown = (seconds: number) => {
  lockCountdown.value = seconds
  if (lockInterval) clearInterval(lockInterval)
  lockInterval = setInterval(() => {
    if (lockCountdown.value > 0) {
      lockCountdown.value--
    } else {
      clearInterval(lockInterval!)
      lockInterval = null
      // Clear error once countdown expires so the user can try again
      error.value = ''
      errorType.value = 'generic'
    }
  }, 1000)
}

onUnmounted(() => {
  if (lockInterval) clearInterval(lockInterval)
})

// ── Helpers ───────────────────────────────────────────────────────────────────

const completeLogin = async (
  user: User & { is_super_admin: boolean },
  tokens: { access_token: string; refresh_token: string; expires_in: number },
) => {
  authStore.setToken(tokens.access_token)
  localStorage.setItem('refresh_token', tokens.refresh_token)
  const expiresAt = Date.now() + tokens.expires_in * 1000
  localStorage.setItem('token_expires_at', expiresAt.toString())
  scheduleTokenRefresh(tokens.expires_in)

  const roles = await authService.getMyRoles()
  authStore.setUser({
    ...user,
    roles,
    is_admin: user.is_super_admin || roles.some((r: string) => ['superadmin', 'admin', 'owner'].includes(r)),
  })

  await cartStore.mergeGuestCart()

  router.push(
    redirectTo.value !== '/' && redirectTo.value
      ? redirectTo.value
      : authStore.isAdmin
        ? '/admin/dashboard'
        : '/',
  )
}

// ── Submit ────────────────────────────────────────────────────────────────────

const handleSubmit = async () => {
  if (!form.value.email || !form.value.password) {
    error.value = 'لطفاً ایمیل و رمز عبور را وارد کنید'
    return
  }

  isLoading.value = true
  error.value = ''
  errorType.value = 'generic'
  showOtpOption.value = false
  if (lockInterval) clearInterval(lockInterval)

  try {
    const response = await authService.login({
      ...form.value,
      remember_me: form.value.rememberMe,
    })

    // ── MFA required ──────────────────────────────────────────────────────
    if (response.action_required === 'mfa_verification' && response.temp_token) {
      router.push({
        name: 'mfa-verification',
        query: {
          temp_token: response.temp_token,
          mfa_methods: response.mfa_methods?.join(','),
          redirect: redirectTo.value,
        },
      })
      return
    }

    // ── Password change required ──────────────────────────────────────────
    if (response.action_required === 'change_password' && response.temp_token) {
      router.push({
        name: 'force-password-change',
        query: {
          temp_token: response.temp_token,
          email: form.value.email,
          redirect: redirectTo.value,
        },
      })
      return
    }

    // ── Normal login ──────────────────────────────────────────────────────
    if (response.tokens && response.user) {
      await completeLogin(response.user, response.tokens)
    } else {
      error.value = 'خطا در دریافت توکن. لطفاً دوباره تلاش کنید'
    }
  } catch (err: unknown) {
    const e = err as {
      response?: {
        status?: number
        data?: { message?: string; data?: { locked_until?: string; retry_after?: number } }
      }
    }

    const status = e.response?.status
    const responseData = e.response?.data
    const message = responseData?.message

    if (status === 400 || status === 401) {
      error.value = 'ایمیل یا رمز عبور اشتباه است'
    } else if (status === 403) {
      error.value = message || 'حساب کاربری غیرفعال است. لطفاً با پشتیبانی تماس بگیرید.'
    } else if (status === 423) {
      // Account locked — show live countdown
      errorType.value = 'locked'
      showOtpOption.value = true
      const lockedUntil = responseData?.data?.locked_until
      if (lockedUntil) {
        const remaining = Math.ceil((new Date(lockedUntil).getTime() - Date.now()) / 1000)
        if (remaining > 0) {
          startLockCountdown(remaining)
          error.value = 'حساب کاربری شما موقتاً قفل شده است.'
        } else {
          error.value = 'حساب کاربری به دلیل تلاش‌های ناموفق متعدد قفل شده است.'
        }
      } else {
        error.value = 'حساب کاربری به دلیل تلاش‌های ناموفق متعدد قفل شده است.'
      }
    } else if (status === 429) {
      errorType.value = 'rate_limited'
      const retryAfter = responseData?.data?.retry_after
      if (retryAfter) {
        startLockCountdown(retryAfter)
        error.value = 'تعداد تلاش‌های بیش از حد.'
      } else {
        error.value = 'تعداد تلاش‌های بیش از حد. لطفاً بعداً دوباره تلاش کنید.'
      }
    } else if (message) {
      error.value = message
    } else {
      error.value = 'خطا در ورود. لطفاً دوباره تلاش کنید'
    }
  } finally {
    isLoading.value = false
  }
}

const goToOtpLogin = () => {
  router.push({ name: 'otp-login', query: { redirect: redirectTo.value } })
}
</script>

<template>
  <div class="min-h-screen bg-secondary-50 flex items-center justify-center py-12 px-4" dir="rtl">
    <div class="max-w-md w-full">

      <!-- Logo -->
      <div class="text-center mb-8">
        <RouterLink to="/" class="inline-flex items-center gap-2">
          <div class="w-14 h-14 bg-gradient-to-br from-primary-500 to-primary-700 rounded-xl flex items-center justify-center">
            <svg class="w-9 h-9 text-white" viewBox="0 0 24 24" fill="currentColor">
              <circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.5"/>
              <circle cx="12" cy="12" r="6" fill="none" stroke="currentColor" stroke-width="1"/>
            </svg>
          </div>
          <div class="text-right">
            <span class="text-2xl font-bold text-primary-600">اتکا</span>
            <span class="block text-sm text-secondary-500">تجارت الکترونیک</span>
          </div>
        </RouterLink>
      </div>

      <!-- Login Card -->
      <div class="bg-white rounded-2xl shadow-xl p-8">
        <h1 class="text-2xl font-bold text-secondary-800 text-center mb-2">ورود به حساب کاربری</h1>
        <p class="text-secondary-500 text-center mb-8">خوش آمدید! لطفاً وارد شوید</p>

        <!-- Error / Lock Banner -->
        <div
          v-if="error"
          role="alert"
          :class="[
            'px-4 py-3 rounded-xl mb-6 text-sm border',
            errorType === 'locked' || errorType === 'rate_limited'
              ? 'bg-amber-50 border-amber-200 text-amber-800'
              : 'bg-red-50 border-red-200 text-red-700',
          ]"
        >
          <!-- Lock icon row -->
          <div class="flex items-start gap-2">
            <AlertTriangle class="w-4 h-4 mt-0.5 flex-shrink-0" />
            <div class="flex-1">
              <p>{{ error }}</p>

              <!-- Live countdown for locked/rate-limited -->
              <div
                v-if="(errorType === 'locked' || errorType === 'rate_limited') && lockCountdown > 0"
                class="mt-2 flex items-center gap-1.5 font-medium"
              >
                <Clock class="w-4 h-4" />
                <span>می‌توانید در </span>
                <span class="font-mono bg-amber-100 px-1.5 py-0.5 rounded text-amber-900" dir="ltr">
                  {{ formattedLockCountdown }}
                </span>
                <span> دوباره تلاش کنید</span>
              </div>
            </div>
          </div>

          <!-- OTP fallback for locked accounts -->
          <div v-if="showOtpOption" class="mt-3 pt-3 border-t border-amber-200">
            <button
              type="button"
              @click="goToOtpLogin"
              class="flex items-center gap-2 text-sm text-primary-600 hover:text-primary-700 font-medium"
            >
              <Smartphone class="w-4 h-4" />
              ورود با کد یکبار مصرف (OTP)
            </button>
          </div>
        </div>

        <form @submit.prevent="handleSubmit" novalidate class="space-y-5">
          <!-- Email -->
          <div>
            <label for="login-email" class="block text-sm font-medium text-secondary-700 mb-2">
              ایمیل
            </label>
            <div class="relative">
              <input
                id="login-email"
                v-model="form.email"
                type="email"
                placeholder="example@email.com"
                autocomplete="email"
                class="w-full h-12 pl-4 pr-12 rounded-xl border-2 border-secondary-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition text-left"
                dir="ltr"
                :disabled="isLoading"
              />
              <Mail class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400 pointer-events-none" />
            </div>
          </div>

          <!-- Password -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <label for="login-password" class="text-sm font-medium text-secondary-700">رمز عبور</label>
              <RouterLink
                to="/forgot-password"
                class="text-xs text-primary-600 hover:text-primary-700"
              >
                فراموشی رمز عبور؟
              </RouterLink>
            </div>
            <div class="relative">
              <input
                id="login-password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="رمز عبور خود را وارد کنید"
                autocomplete="current-password"
                class="w-full h-12 pl-12 pr-12 rounded-xl border-2 border-secondary-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition"
                :disabled="isLoading"
              />
              <Lock class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400 pointer-events-none" />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute left-4 top-1/2 -translate-y-1/2 text-secondary-400 hover:text-secondary-600"
                :aria-label="showPassword ? 'پنهان کردن رمز عبور' : 'نمایش رمز عبور'"
              >
                <EyeOff v-if="showPassword" class="w-5 h-5" />
                <Eye v-else class="w-5 h-5" />
              </button>
            </div>
          </div>

          <!-- Remember me -->
          <div class="flex items-center gap-2">
            <input
              id="remember-me"
              v-model="form.rememberMe"
              type="checkbox"
              class="w-4 h-4 rounded border-secondary-300 text-primary-600 focus:ring-primary-500"
            />
            <label for="remember-me" class="text-sm text-secondary-600 cursor-pointer select-none">
              مرا به خاطر بسپار
            </label>
          </div>

          <!-- Submit -->
          <button
            type="submit"
            :disabled="isLoading || lockCountdown > 0"
            class="w-full h-12 bg-primary-500 hover:bg-primary-600 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold rounded-xl transition flex items-center justify-center gap-2"
          >
            <Loader2 v-if="isLoading" class="w-5 h-5 animate-spin" />
            <span>{{ isLoading ? 'در حال ورود...' : 'ورود' }}</span>
          </button>
        </form>

        <!-- Divider & Alternatives -->
        <div class="mt-6 space-y-3">
          <div class="relative">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-secondary-200"></div>
            </div>
            <div class="relative flex justify-center text-sm">
              <span class="bg-white px-3 text-secondary-500">یا</span>
            </div>
          </div>

          <button
            type="button"
            @click="goToOtpLogin"
            class="w-full h-11 border-2 border-secondary-200 hover:border-primary-400 rounded-xl text-sm font-medium text-secondary-700 hover:text-primary-600 transition flex items-center justify-center gap-2"
          >
            <Smartphone class="w-4 h-4" />
            ورود با کد یکبار مصرف
          </button>
        </div>

        <!-- Register link -->
        <p class="mt-6 text-center text-sm text-secondary-500">
          حساب کاربری ندارید؟
          <RouterLink to="/register" class="text-primary-600 hover:text-primary-700 font-medium">
            ثبت‌نام کنید
          </RouterLink>
        </p>
      </div>
    </div>
  </div>
</template>
