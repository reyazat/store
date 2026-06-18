<script setup lang="ts">
import { ref, computed, watch, onUnmounted, nextTick } from 'vue'
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

// ── Phone normalization (same logic as LoginPage) ──────────────────────────
const normalizePhone = (raw: string): string => {
  let v = raw.replace(/[\s\-().]/g, '')
  if (v.startsWith('+98')) v = '0' + v.slice(3)
  return v.replace(/\D/g, '')
}

const formatPhoneDisplay = (digits: string): string => {
  if (digits.startsWith('09') && digits.length >= 4) {
    const parts = [digits.slice(0, 4)]
    if (digits.length > 4) parts.push(digits.slice(4, 7))
    if (digits.length > 7) parts.push(digits.slice(7, 11))
    return parts.join(' ')
  }
  return digits
}

watch(phone, (newVal) => {
  const digits = normalizePhone(newVal)
  const capped = digits.slice(0, 11)
  const formatted = formatPhoneDisplay(capped)
  if (formatted !== newVal) {
    phone.value = formatted
  }
})

// ── Computed ──────────────────────────────────────────────────────────────────

const isPhoneValid = computed(() => {
  const digits = normalizePhone(phone.value)
  return /^09\d{9}$/.test(digits)
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
  if (status === 503) return message || 'سرویس پیامک در حال حاضر در دسترس نیست. لطفاً بعداً دوباره تلاش کنید.'
  return message || 'خطایی رخ داد. لطفاً دوباره تلاش کنید.'
}

// ── OTP input handling ────────────────────────────────────────────────────────

const handleOtpInput = (index: number, event: Event) => {
  const input = event.target as HTMLInputElement
  // Strip non-digits and take last character (handles replace case)
  const value = input.value.replace(/\D/g, '').slice(-1)
  if (value) {
    otpCode.value[index] = value
    if (index < 5) {
      nextTick(() => {
        const nextInput = otpInputRefs.value[index + 1]
        if (nextInput) {
          nextInput.focus()
          nextInput.select()
        }
      })
    } else if (index === 5 && isOtpComplete.value) {
      // Auto-submit when last digit is entered
      nextTick(() => verifyOtp())
    }
  }
}

const handleOtpKeydown = (index: number, event: KeyboardEvent) => {
  if (event.key === 'Backspace') {
    if (!otpCode.value[index] && index > 0) {
      // Current box is empty, go back and clear previous
      otpCode.value[index - 1] = ''
      nextTick(() => {
        const prevInput = otpInputRefs.value[index - 1]
        if (prevInput) {
          prevInput.focus()
          prevInput.select()
        }
      })
    } else {
      // Current box has value, just clear it
      otpCode.value[index] = ''
    }
  } else if (event.key === 'ArrowLeft' && index > 0) {
    event.preventDefault()
    nextTick(() => {
      const prevInput = otpInputRefs.value[index - 1]
      if (prevInput) {
        prevInput.focus()
        prevInput.select()
      }
    })
  } else if (event.key === 'ArrowRight' && index < 5) {
    event.preventDefault()
    nextTick(() => {
      const nextInput = otpInputRefs.value[index + 1]
      if (nextInput) {
        nextInput.focus()
        nextInput.select()
      }
    })
  }
}

const handleOtpPaste = (event: ClipboardEvent) => {
  event.preventDefault()
  const digits = (event.clipboardData?.getData('text') || '').replace(/\D/g, '').slice(0, 6)
  if (digits) {
    // Clear all first
    otpCode.value = ['', '', '', '', '', '']
    // Fill digits
    digits.split('').forEach((d, i) => {
      if (i < 6) otpCode.value[i] = d
    })
    // Focus the appropriate input and select
    nextTick(() => {
      const focusIndex = Math.min(digits.length, 5)
      const input = otpInputRefs.value[focusIndex]
      if (input) {
        input.focus()
        input.select()
      }
      // Auto-submit if all 6 digits pasted
      if (digits.length === 6) {
        verifyOtp()
      }
    })
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
    const cleanPhone = normalizePhone(phone.value)
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

    const cleanPhone = normalizePhone(phone.value)
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

      if (!authStore.isSuperAdmin) {
        await cartStore.mergeGuestCart()
      }

      const redirectTo = (route.query.redirect as string) || '/'
      router.push(
        authStore.isSuperAdmin
          ? '/admin/dashboard'
          : (redirectTo !== '/' && redirectTo ? redirectTo : '/')
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
                  type="text"
                  inputmode="numeric"
                  placeholder="09xx xxx xxxx"
                  autocomplete="tel"
                  maxlength="14"
                  class="w-full h-14 pl-4 pr-14 text-lg rounded-xl border-2 border-secondary-200 focus:border-primary-500 focus:ring-4 focus:ring-primary-100 outline-none transition text-center tracking-widest font-mono"
                  dir="ltr"
                  :disabled="isLoading"
                  :aria-invalid="phone.length > 0 && !isPhoneValid"
                />
                <div class="absolute right-4 top-1/2 -translate-y-1/2 flex items-center gap-1 pointer-events-none">
                  <span class="text-sm text-secondary-400 font-medium">IR</span>
                  <span class="text-secondary-300">+98</span>
                </div>
              </div>
              <p class="mt-2 text-xs text-secondary-400">شماره موبایل باید با ۰۹ شروع شود</p>
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
                  v-for="n in 6"
                  :key="n - 1"
                  :ref="el => { if (el) otpInputRefs[n - 1] = el as HTMLInputElement }"
                  v-model="otpCode[n - 1]"
                  type="text"
                  inputmode="numeric"
                  pattern="[0-9]*"
                  maxlength="1"
                  :autocomplete="(n - 1) === 0 ? 'one-time-code' : 'off'"
                  class="w-12 h-14 text-center text-2xl font-bold rounded-xl border-2 border-secondary-200 focus:border-primary-500 focus:ring-4 focus:ring-primary-100 outline-none transition bg-white"
                  :class="{ 'border-primary-500 ring-2 ring-primary-200': otpCode[n - 1] }"
                  :disabled="isLoading"
                  @input="handleOtpInput(n - 1, $event)"
                  @keydown="handleOtpKeydown(n - 1, $event)"
                  @paste="handleOtpPaste($event)"
                  @focus="($event.target as HTMLInputElement).select()"
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
