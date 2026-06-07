<script setup lang="ts">
import { ref, computed, watch, onUnmounted, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Smartphone, Mail, ArrowLeft, Loader2, AlertCircle, CheckCircle, RefreshCw } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { authService } from '../services'
import { scheduleTokenRefresh } from '@shared/utils/api'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const cartStore = useCartStore()

// Step management
const step = ref<'input' | 'verify'>('input')

// Form state
const identifier = ref('')
const selectedChannel = ref<'email' | 'sms'>('email')
const otpCode = ref('')

// UI state
const isLoading = ref(false)
const error = ref('')
const successMessage = ref('')
const attemptsRemaining = ref<number | null>(null)

// Countdown timer
const countdown = ref(0)
let countdownInterval: ReturnType<typeof setInterval> | null = null

// Auto-focus OTP input
const otpInputRef = ref<HTMLInputElement | null>(null)

// Reset identifier when channel changes
watch(selectedChannel, () => {
  identifier.value = ''
  error.value = ''
})

// ── Computed ──────────────────────────────────────────────────────────────────

const isEmailChannel = computed(() => selectedChannel.value === 'email')

const isIdentifierValid = computed(() => {
  if (isEmailChannel.value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(identifier.value)
  }
  // Iranian mobile: 09XXXXXXXXX or +989XXXXXXXXX
  return /^09\d{9}$/.test(identifier.value) || /^\+989\d{9}$/.test(identifier.value)
})

const isOtpValid = computed(() => /^\d{6}$/.test(otpCode.value))

const canResend = computed(() => countdown.value === 0 && !isLoading.value)

const formattedCountdown = computed(() => {
  const m = Math.floor(countdown.value / 60)
  const s = countdown.value % 60
  return `${m}:${s.toString().padStart(2, '0')}`
})

// ── Timer ─────────────────────────────────────────────────────────────────────

const startCountdown = (seconds: number) => {
  countdown.value = seconds
  if (countdownInterval) clearInterval(countdownInterval)
  countdownInterval = setInterval(() => {
    if (countdown.value > 0) {
      countdown.value--
    } else {
      clearInterval(countdownInterval!)
      countdownInterval = null
    }
  }, 1000)
}

onUnmounted(() => {
  if (countdownInterval) clearInterval(countdownInterval)
})

// ── Helpers ───────────────────────────────────────────────────────────────────

const maskIdentifier = (id: string): string => {
  if (id.includes('@')) {
    const [user, domain] = id.split('@')
    return user.slice(0, 2) + '***@' + domain
  }
  return id.slice(0, 4) + '****' + id.slice(-3)
}

const handleApiError = (err: unknown): string => {
  const e = err as { response?: { status?: number; data?: { message?: string; data?: { retry_after?: number } } } }
  const status = e.response?.status
  const message = e.response?.data?.message
  const retryAfter = e.response?.data?.data?.retry_after

  if (status === 429) {
    const minutes = retryAfter ? Math.ceil(retryAfter / 60) : null
    return minutes
      ? `تعداد درخواست‌های بیش از حد. لطفاً ${minutes} دقیقه دیگر دوباره تلاش کنید.`
      : 'تعداد درخواست‌های بیش از حد. لطفاً بعداً دوباره تلاش کنید.'
  }
  if (status === 423) return 'حساب کاربری موقتاً قفل شده است. لطفاً بعداً تلاش کنید.'
  if (status === 403) return 'حساب کاربری غیرفعال است. با پشتیبانی تماس بگیرید.'
  return message || 'خطایی رخ داد. لطفاً دوباره تلاش کنید.'
}

// ── Actions ───────────────────────────────────────────────────────────────────

const sendOtp = async () => {
  if (!isIdentifierValid.value) {
    error.value = isEmailChannel.value
      ? 'لطفاً یک آدرس ایمیل معتبر وارد کنید'
      : 'لطفاً شماره موبایل را با فرمت 09123456789 وارد کنید'
    return
  }

  isLoading.value = true
  error.value = ''
  successMessage.value = ''

  try {
    const response = await authService.sendOtp({
      identifier: identifier.value,
      type: 'login_verification',
      channel: selectedChannel.value,
    })

    if (response.success) {
      successMessage.value = `کد به ${maskIdentifier(identifier.value)} ارسال شد`
      attemptsRemaining.value = (response.data?.attempts_remaining as number | undefined) ?? 3
      step.value = 'verify'

      // Start countdown from server-provided expiry or default 5 min (matching backend message)
      if (response.data?.expires_at) {
        const diff = Math.ceil((new Date(response.data.expires_at).getTime() - Date.now()) / 1000)
        startCountdown(Math.max(diff, 0))
      } else {
        startCountdown(300) // 5 minutes default
      }

      // Auto-focus OTP field after render
      await nextTick()
      otpInputRef.value?.focus()
    } else {
      error.value = response.message || 'خطا در ارسال کد. لطفاً دوباره تلاش کنید'
    }
  } catch (err) {
    error.value = handleApiError(err)
  } finally {
    isLoading.value = false
  }
}

const verifyOtp = async () => {
  if (!isOtpValid.value) {
    error.value = 'لطفاً کد 6 رقمی معتبر وارد کنید'
    return
  }

  isLoading.value = true
  error.value = ''

  try {
    // Bug fix: pass channel so backend finds the correct OTP record
    const response = await authService.verifyOtp({
      identifier: identifier.value,
      code: otpCode.value,
      type: 'login_verification',
      channel: selectedChannel.value,
    })

    if (response.success && response.data?.tokens) {
      // Store tokens
      authStore.setToken(response.data.tokens.access_token)
      localStorage.setItem('refresh_token', response.data.tokens.refresh_token)

      const expiresAt = Date.now() + response.data.tokens.expires_in * 1000
      localStorage.setItem('token_expires_at', expiresAt.toString())
      scheduleTokenRefresh(response.data.tokens.expires_in)

      // Fetch roles and set user
      const roles = await authService.getMyRoles()
      const user = response.data.user ?? (await authService.getMe().catch(() => null))
      if (!user) {
        authStore.logout()
        error.value = 'خطا در دریافت اطلاعات کاربر. لطفاً دوباره تلاش کنید.'
        return
      }
      authStore.setUser({
        ...user,
        roles,
        is_admin:
          user.is_super_admin ||
          roles.some((r: string) => ['superadmin', 'admin', 'owner'].includes(r)),
      })

      await cartStore.mergeGuestCart()

      const redirectTo = (route.query.redirect as string) || '/'
      router.push(
        redirectTo !== '/' && redirectTo
          ? redirectTo
          : authStore.isAdmin
            ? '/admin/dashboard'
            : '/'
      )
    } else {
      error.value = response.message || 'کد وارد شده معتبر نیست'
      if (response.data?.attempts_remaining != null) {
        attemptsRemaining.value = response.data.attempts_remaining as number
      }
      // Clear code for re-entry
      otpCode.value = ''
      await nextTick()
      otpInputRef.value?.focus()
    }
  } catch (err: unknown) {
    const e = err as { response?: { status?: number; data?: { message?: string; data?: { attempts_remaining?: number } } } }
    if (e.response?.status === 423) {
      error.value = 'تعداد تلاش‌های نامعتبر بیش از حد. لطفاً دوباره کد جدید درخواست کنید.'
    } else {
      error.value = e.response?.data?.message || 'خطا در تایید کد. لطفاً دوباره تلاش کنید'
    }
    if (e.response?.data?.data?.attempts_remaining != null) {
      attemptsRemaining.value = e.response.data.data.attempts_remaining as number
    }
    otpCode.value = ''
    await nextTick()
    otpInputRef.value?.focus()
  } finally {
    isLoading.value = false
  }
}

const resendOtp = async () => {
  if (!canResend.value) return
  otpCode.value = ''
  attemptsRemaining.value = null
  error.value = ''
  await sendOtp()
}

const goBack = () => {
  step.value = 'input'
  otpCode.value = ''
  error.value = ''
  successMessage.value = ''
  attemptsRemaining.value = null
  if (countdownInterval) clearInterval(countdownInterval)
  countdown.value = 0
}

const goToLogin = () => {
  router.push('/login')
}
</script>

<template>
  <div class="min-h-screen bg-secondary-50 flex items-center justify-center py-12 px-4" dir="rtl">
    <div class="max-w-md w-full">

      <!-- Logo -->
      <div class="text-center mb-8">
        <RouterLink to="/" class="inline-flex items-center gap-2">
          <div class="w-14 h-14 bg-gradient-to-br from-primary-500 to-primary-700 rounded-xl flex items-center justify-center">
            <Smartphone class="w-8 h-8 text-white" />
          </div>
          <div class="text-right">
            <span class="text-2xl font-bold text-primary-600">اتکا</span>
            <span class="block text-sm text-secondary-500">ورود با کد یکبار مصرف</span>
          </div>
        </RouterLink>
      </div>

      <!-- Card -->
      <div class="bg-white rounded-2xl shadow-xl p-8">

        <!-- ═══════════════════ STEP 1: Enter identifier ═══════════════════ -->
        <template v-if="step === 'input'">
          <div class="text-center mb-7">
            <div
              class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4 transition-all"
            >
              <Mail v-if="isEmailChannel" class="w-8 h-8 text-primary-600" />
              <Smartphone v-else class="w-8 h-8 text-primary-600" />
            </div>
            <h1 class="text-2xl font-bold text-secondary-800">ورود بدون رمز عبور</h1>
            <p class="text-secondary-500 mt-2 text-sm">
              کد یکبار مصرف به ایمیل یا موبایل شما ارسال می‌شود
            </p>
          </div>

          <!-- Channel Switcher -->
          <div class="flex gap-2 mb-6 p-1 bg-secondary-100 rounded-xl" role="group" aria-label="کانال ارسال">
            <button
              type="button"
              @click="selectedChannel = 'email'"
              :class="[
                'flex-1 py-2.5 rounded-lg text-sm font-medium transition-all flex items-center justify-center gap-2',
                isEmailChannel
                  ? 'bg-white text-primary-600 shadow-sm'
                  : 'text-secondary-600 hover:text-secondary-800',
              ]"
              :aria-pressed="isEmailChannel"
            >
              <Mail class="w-4 h-4" />
              ایمیل
            </button>
            <button
              type="button"
              @click="selectedChannel = 'sms'"
              :class="[
                'flex-1 py-2.5 rounded-lg text-sm font-medium transition-all flex items-center justify-center gap-2',
                !isEmailChannel
                  ? 'bg-white text-primary-600 shadow-sm'
                  : 'text-secondary-600 hover:text-secondary-800',
              ]"
              :aria-pressed="!isEmailChannel"
            >
              <Smartphone class="w-4 h-4" />
              پیامک
            </button>
          </div>

          <!-- Error -->
          <div
            v-if="error"
            role="alert"
            class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-5 text-sm flex items-center gap-2"
          >
            <AlertCircle class="w-4 h-4 flex-shrink-0" />
            <span>{{ error }}</span>
          </div>

          <!-- Identifier Form -->
          <form @submit.prevent="sendOtp" novalidate>
            <div class="mb-5">
              <label
                for="otp-identifier"
                class="block text-sm font-medium text-secondary-700 mb-2"
              >
                {{ isEmailChannel ? 'آدرس ایمیل' : 'شماره موبایل' }}
              </label>
              <div class="relative">
                <input
                  id="otp-identifier"
                  v-model="identifier"
                  :type="isEmailChannel ? 'email' : 'tel'"
                  :placeholder="isEmailChannel ? 'example@email.com' : '09123456789'"
                  class="w-full h-12 pl-4 pr-12 rounded-xl border-2 border-secondary-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition text-left"
                  dir="ltr"
                  autocomplete="username"
                  :disabled="isLoading"
                  :aria-invalid="identifier.length > 0 && !isIdentifierValid"
                />
                <Mail
                  v-if="isEmailChannel"
                  class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400 pointer-events-none"
                />
                <Smartphone
                  v-else
                  class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400 pointer-events-none"
                />
              </div>
              <p v-if="!isEmailChannel" class="text-xs text-secondary-500 mt-1.5">
                مثال: 09123456789
              </p>
            </div>

            <button
              type="submit"
              :disabled="isLoading || !isIdentifierValid"
              class="w-full h-12 bg-primary-500 hover:bg-primary-600 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold rounded-xl transition flex items-center justify-center gap-2"
            >
              <Loader2 v-if="isLoading" class="w-5 h-5 animate-spin" />
              <span>{{ isLoading ? 'در حال ارسال...' : 'ارسال کد' }}</span>
            </button>
          </form>

          <!-- Back to login -->
          <div class="mt-6 pt-6 border-t border-secondary-100 text-center">
            <button
              type="button"
              @click="goToLogin"
              class="text-sm text-secondary-500 hover:text-secondary-700 flex items-center justify-center gap-1.5 mx-auto"
            >
              <ArrowLeft class="w-4 h-4" />
              ورود با رمز عبور
            </button>
          </div>
        </template>

        <!-- ═══════════════════ STEP 2: Enter OTP ═══════════════════════════ -->
        <template v-else>
          <div class="text-center mb-6">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <CheckCircle class="w-8 h-8 text-green-600" />
            </div>
            <h1 class="text-2xl font-bold text-secondary-800">کد تایید را وارد کنید</h1>
            <p class="text-secondary-500 mt-2 text-sm">
              کد 6 رقمی ارسال شده به
              <strong class="text-secondary-700" dir="ltr">{{ maskIdentifier(identifier) }}</strong>
            </p>

            <!-- Countdown badge -->
            <div v-if="countdown > 0" class="mt-3 inline-flex items-center gap-1.5 text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded-full px-3 py-1">
              <span>کد تا</span>
              <span class="font-mono font-bold" dir="ltr">{{ formattedCountdown }}</span>
              <span>دیگر منقضی می‌شود</span>
            </div>
            <div v-else-if="step === 'verify'" class="mt-3 inline-flex items-center gap-1.5 text-xs text-red-600 bg-red-50 border border-red-200 rounded-full px-3 py-1">
              کد منقضی شده — لطفاً کد جدید درخواست کنید
            </div>
          </div>

          <!-- Success / resend confirmation -->
          <div
            v-if="successMessage"
            role="status"
            class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-5 text-sm flex items-center gap-2"
          >
            <CheckCircle class="w-4 h-4 flex-shrink-0" />
            <span>{{ successMessage }}</span>
          </div>

          <!-- Error -->
          <div
            v-if="error"
            role="alert"
            class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-5 text-sm"
          >
            <div class="flex items-center gap-2">
              <AlertCircle class="w-4 h-4 flex-shrink-0" />
              <span>{{ error }}</span>
            </div>
            <p v-if="attemptsRemaining !== null && attemptsRemaining > 0" class="mt-1 mr-6 text-xs">
              {{ attemptsRemaining }} تلاش دیگر باقی مانده است
            </p>
          </div>

          <!-- OTP Input -->
          <form @submit.prevent="verifyOtp" novalidate class="space-y-5">
            <div>
              <label for="otp-code" class="block text-sm font-medium text-secondary-700 mb-2">
                کد تایید
              </label>
              <input
                id="otp-code"
                ref="otpInputRef"
                v-model="otpCode"
                type="text"
                inputmode="numeric"
                pattern="[0-9]{6}"
                maxlength="6"
                placeholder="۱۲۳۴۵۶"
                autocomplete="one-time-code"
                class="w-full h-14 text-center text-2xl tracking-[0.6em] font-mono rounded-xl border-2 border-secondary-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition"
                dir="ltr"
                :disabled="isLoading"
                @input="otpCode = otpCode.replace(/\D/g, '').slice(0, 6)"
                :aria-invalid="otpCode.length > 0 && !isOtpValid"
              />
            </div>

            <button
              type="submit"
              :disabled="isLoading || !isOtpValid"
              class="w-full h-12 bg-primary-500 hover:bg-primary-600 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold rounded-xl transition flex items-center justify-center gap-2"
            >
              <Loader2 v-if="isLoading" class="w-5 h-5 animate-spin" />
              <span>{{ isLoading ? 'در حال تایید...' : 'تایید و ورود' }}</span>
            </button>
          </form>

          <!-- Resend / Back row -->
          <div class="flex items-center justify-between mt-6">
            <button
              type="button"
              @click="goBack"
              class="text-secondary-600 hover:text-secondary-800 text-sm font-medium flex items-center gap-1.5"
            >
              <ArrowLeft class="w-4 h-4" />
              بازگشت
            </button>

            <button
              type="button"
              @click="resendOtp"
              :disabled="!canResend"
              class="text-sm font-medium flex items-center gap-1.5 disabled:text-secondary-400 disabled:cursor-not-allowed text-primary-600 hover:text-primary-700"
            >
              <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': isLoading && countdown === 0 }" />
              {{
                canResend
                  ? 'ارسال مجدد کد'
                  : `ارسال مجدد (${formattedCountdown})`
              }}
            </button>
          </div>
        </template>
      </div>

      <!-- Footer help -->
      <p class="text-center mt-5 text-xs text-secondary-500">
        مشکل در ورود؟
        <RouterLink to="/forgot-password" class="text-primary-600 hover:text-primary-700">
          بازیابی رمز عبور
        </RouterLink>
      </p>
    </div>
  </div>
</template>
