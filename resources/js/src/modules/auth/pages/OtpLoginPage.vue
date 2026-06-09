<script setup lang="ts">
import { ref, computed, onUnmounted, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Smartphone, ArrowLeft, Loader2, AlertCircle, CheckCircle, RefreshCw } from 'lucide-vue-next'
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

// OTP login is SMS-only (phone number required)
const phone = ref('')
const otpCode = ref(['', '', '', '', '', ''])

// UI state
const isLoading = ref(false)
const error = ref('')
const successMessage = ref('')
const attemptsRemaining = ref<number | null>(null)

// Countdown timer
const countdown = ref(0)
let countdownInterval: ReturnType<typeof setInterval> | null = null

// OTP input refs for focus management
const otpInputRefs = ref<(HTMLInputElement | null)[]>([])

// WebOTP API abort controller
let webOtpAbortController: AbortController | null = null

// ── Computed ──────────────────────────────────────────────────────────────────

const isPhoneValid = computed(() => {
  const digits = phone.value.replace(/\s/g, '')
  return /^09\d{9}$/.test(digits) || /^\+989\d{9}$/.test(digits)
})

const isOtpComplete = computed(() => otpCode.value.every(d => d.length === 1))

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
  webOtpAbortController?.abort()
})

// ── Helpers ───────────────────────────────────────────────────────────────────

const maskPhone = (p: string): string => {
  const digits = p.replace(/\D/g, '')
  return digits.slice(0, 4) + '****' + digits.slice(-3)
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

// ── OTP input handling ────────────────────────────────────────────────────────

const handleOtpInput = (index: number, event: Event) => {
  const input = event.target as HTMLInputElement
  const value = input.value.replace(/\D/g, '')
  if (value) {
    otpCode.value[index] = value.slice(-1)
    if (index < 5) {
      nextTick(() => otpInputRefs.value[index + 1]?.focus())
    } else if (index === 5 && isOtpComplete.value) {
      // Auto-submit when last digit is entered
      nextTick(() => verifyOtp())
    }
  }
}

const handleOtpKeydown = (index: number, event: KeyboardEvent) => {
  if (event.key === 'Backspace' && !otpCode.value[index] && index > 0) {
    otpCode.value[index - 1] = ''
    nextTick(() => otpInputRefs.value[index - 1]?.focus())
  } else if (event.key === 'ArrowLeft' && index > 0) {
    nextTick(() => otpInputRefs.value[index - 1]?.focus())
  } else if (event.key === 'ArrowRight' && index < 5) {
    nextTick(() => otpInputRefs.value[index + 1]?.focus())
  }
}

const handleOtpPaste = (event: ClipboardEvent) => {
  event.preventDefault()
  const digits = (event.clipboardData?.getData('text') || '').replace(/\D/g, '').slice(0, 6)
  if (digits) {
    digits.split('').forEach((d, i) => {
      if (i < 6) otpCode.value[i] = d
    })
    if (digits.length === 6) {
      nextTick(() => verifyOtp())
    }
  }
}

// WebOTP API: auto-read OTP from SMS on supported mobile browsers
const startWebOtp = async () => {
  if (!('OTPCredential' in window)) return
  webOtpAbortController = new AbortController()
  try {
    const credential = await (navigator.credentials as any).get({
      otp: { transport: ['sms'] },
      signal: webOtpAbortController.signal,
    })
    if (credential?.code) {
      const digits = credential.code.replace(/\D/g, '').slice(0, 6)
      digits.split('').forEach((d: string, i: number) => {
        if (i < 6) otpCode.value[i] = d
      })
      if (digits.length === 6) {
        nextTick(() => verifyOtp())
      }
    }
  } catch {
    // Aborted or not supported — silently ignore
  }
}

// ── Actions ───────────────────────────────────────────────────────────────────

const sendOtp = async () => {
  if (!isPhoneValid.value) {
    error.value = 'لطفاً شماره موبایل را با فرمت 09123456789 وارد کنید'
    return
  }

  isLoading.value = true
  error.value = ''
  successMessage.value = ''

  try {
    const cleanPhone = phone.value.replace(/\s/g, '')
    const response = await authService.sendOtp({
      identifier: cleanPhone,
      type: 'login_verification',
      channel: 'sms',
    })

    if (response.success) {
      successMessage.value = `کد به ${maskPhone(cleanPhone)} ارسال شد`
      attemptsRemaining.value = (response.data?.attempts_remaining as number | undefined) ?? 3
      step.value = 'verify'

      if (response.data?.expires_at) {
        const diff = Math.ceil((new Date(response.data.expires_at).getTime() - Date.now()) / 1000)
        startCountdown(Math.max(diff, 0))
      } else {
        startCountdown(300)
      }

      await nextTick()
      otpInputRefs.value[0]?.focus()
      startWebOtp()
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
  if (!isOtpComplete.value) {
    error.value = 'لطفاً کد ۶ رقمی را کامل وارد کنید'
    return
  }

  isLoading.value = true
  error.value = ''

  try {
    webOtpAbortController?.abort()

    const cleanPhone = phone.value.replace(/\s/g, '')
    const response = await authService.verifyOtp({
      identifier: cleanPhone,
      code: otpCode.value.join(''),
      type: 'login_verification',
      channel: 'sms',
    })

    if (response.success && response.data?.tokens) {
      authStore.setToken(response.data.tokens.access_token)
      localStorage.setItem('refresh_token', response.data.tokens.refresh_token)

      const expiresAt = Date.now() + response.data.tokens.expires_in * 1000
      localStorage.setItem('token_expires_at', expiresAt.toString())
      scheduleTokenRefresh(response.data.tokens.expires_in)

      // OTP login confirms identity — use user from response if available
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
        is_admin: user.is_super_admin || roles.some((r: string) => ['superadmin', 'admin', 'owner'].includes(r)),
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
      otpCode.value = ['', '', '', '', '', '']
      await nextTick()
      otpInputRefs.value[0]?.focus()
    }
  } catch (err: unknown) {
    const e = err as { response?: { status?: number; data?: { message?: string; data?: { attempts_remaining?: number } } } }
    if (e.response?.status === 423) {
      error.value = 'تعداد تلاش‌های نامعتبر بیش از حد. لطفاً دوباره کد جدید درخواست کنید.'
    } else {
      error.value = handleApiError(e)
    }
    otpCode.value = ['', '', '', '', '', '']
    await nextTick()
    otpInputRefs.value[0]?.focus()
  } finally {
    isLoading.value = false
  }
}

const resendOtp = async () => {
  if (!canResend.value) return
  otpCode.value = ['', '', '', '', '', '']
  attemptsRemaining.value = null
  error.value = ''
  await sendOtp()
}

const goBack = () => {
  step.value = 'input'
  otpCode.value = ['', '', '', '', '', '']
  error.value = ''
  successMessage.value = ''
  attemptsRemaining.value = null
  if (countdownInterval) clearInterval(countdownInterval)
  countdown.value = 0
  webOtpAbortController?.abort()
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
        <RouterLink to="/">
          <img src="/images/Logo/tejarat-logo-500.png" alt="لوگو فروشگاه" class="h-12 w-auto object-contain">
        </RouterLink>
      </div>

      <!-- Card -->
      <div class="bg-white rounded-2xl shadow-xl p-8">

        <!-- ═══════════════════ STEP 1: Enter phone ═══════════════════ -->
        <template v-if="step === 'input'">
          <div class="text-center mb-7">
            <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <Smartphone class="w-8 h-8 text-primary-600" />
            </div>
            <h1 class="text-2xl font-bold text-secondary-800">ورود با شماره موبایل</h1>
            <p class="text-secondary-500 mt-2 text-sm">
              کد یکبار مصرف به شماره موبایل شما ارسال می‌شود
            </p>
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

          <!-- Phone form -->
          <form @submit.prevent="sendOtp" novalidate>
            <div class="mb-5">
              <label for="otp-phone" class="block text-sm font-medium text-secondary-700 mb-2">
                شماره موبایل
              </label>
              <div class="relative">
                <input
                  id="otp-phone"
                  v-model="phone"
                  type="tel"
                  placeholder="09123456789"
                  class="w-full h-12 pl-4 pr-12 rounded-xl border-2 border-secondary-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition text-left"
                  dir="ltr"
                  autocomplete="tel"
                  inputmode="tel"
                  :disabled="isLoading"
                  :aria-invalid="phone.length > 0 && !isPhoneValid"
                />
                <Smartphone
                  class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400 pointer-events-none"
                />
              </div>
              <p class="text-xs text-secondary-500 mt-1.5">مثال: 09123456789</p>
            </div>

            <button
              type="submit"
              :disabled="isLoading || !isPhoneValid"
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
              کد ۶ رقمی ارسال شده به
              <strong class="text-secondary-700" dir="ltr">{{ maskPhone(phone) }}</strong>
            </p>

            <!-- Countdown badge -->
            <div v-if="countdown > 0" class="mt-3 inline-flex items-center gap-1.5 text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded-full px-3 py-1">
              <span>کد تا</span>
              <span class="font-mono font-bold" dir="ltr">{{ formattedCountdown }}</span>
              <span>دیگر منقضی می‌شود</span>
            </div>
            <div v-else class="mt-3 inline-flex items-center gap-1.5 text-xs text-red-600 bg-red-50 border border-red-200 rounded-full px-3 py-1">
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
            <p v-if="attemptsRemaining !== null" class="mt-1 text-xs mr-6">
              تعداد تلاش باقی‌مانده: {{ attemptsRemaining }}
            </p>
          </div>

          <!-- OTP inputs -->
          <form @submit.prevent="verifyOtp" novalidate>
            <div class="mb-6">
              <div class="flex gap-2 justify-center" dir="ltr">
                <input
                  v-for="(digit, index) in 6"
                  :key="index"
                  :ref="el => { if (el) otpInputRefs[index] = el as HTMLInputElement }"
                  v-model="otpCode[index]"
                  type="text"
                  inputmode="numeric"
                  pattern="[0-9]"
                  maxlength="1"
                  :autocomplete="index === 0 ? 'one-time-code' : 'off'"
                  class="w-12 h-14 text-center text-2xl font-bold rounded-xl border-2 border-secondary-200 focus:border-primary-500 focus:ring-4 focus:ring-primary-100 outline-none transition"
                  :class="{ 'border-primary-500 ring-2 ring-primary-200': otpCode[index] }"
                  :disabled="isLoading"
                  @input="handleOtpInput(index, $event)"
                  @keydown="handleOtpKeydown(index, $event)"
                  @paste="handleOtpPaste($event)"
                />
              </div>
              <p class="mt-3 text-center text-xs text-secondary-400">کد دریافتی از پیامک را وارد کنید</p>
            </div>

            <button
              type="submit"
              :disabled="isLoading || !isOtpComplete"
              class="w-full h-12 bg-primary-500 hover:bg-primary-600 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold rounded-xl transition flex items-center justify-center gap-2 mb-4"
            >
              <Loader2 v-if="isLoading" class="w-5 h-5 animate-spin" />
              <span>{{ isLoading ? 'در حال تایید...' : 'ورود' }}</span>
            </button>
          </form>

          <!-- Resend / back row -->
          <div class="flex items-center justify-between">
            <button
              type="button"
              @click="goBack"
              class="text-sm text-secondary-500 hover:text-secondary-700 flex items-center gap-1"
            >
              <ArrowLeft class="w-4 h-4" />
              تغییر شماره
            </button>

            <div class="text-sm">
              <span v-if="countdown > 0" class="text-secondary-400">
                ارسال مجدد پس از <span class="font-mono font-medium" dir="ltr">{{ formattedCountdown }}</span>
              </span>
              <button
                v-else
                type="button"
                :disabled="isLoading"
                @click="resendOtp"
                class="text-primary-600 hover:text-primary-700 font-medium flex items-center gap-1 disabled:opacity-50"
              >
                <RefreshCw class="w-3.5 h-3.5" />
                ارسال مجدد کد
              </button>
            </div>
          </div>
        </template>

      </div>
    </div>
  </div>
</template>
