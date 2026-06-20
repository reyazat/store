<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Smartphone, Mail, Lock, Eye, EyeOff, Loader2, AlertTriangle, Clock, ArrowLeft, ShieldCheck } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { authService } from '../services'
import type { User } from '@shared/types'
import { scheduleTokenRefresh } from '@shared/utils/api'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const cartStore = useCartStore()

const activeTab = ref<'phone' | 'password'>('phone')
const phone = ref('')
const otpCode = ref(['', '', '', '', '', ''])
const phoneStep = ref<'input' | 'verify'>('input')
const phoneError = ref('')
const phoneSuccess = ref('')
const phoneLoading = ref(false)
const phoneAttemptsRemaining = ref<number | null>(null)
const phoneCountdown = ref(0)
let phoneCountdownInterval: ReturnType<typeof setInterval> | null = null
const otpInputRefs = ref<(HTMLInputElement | null)[]>([])

// WebOTP API abort controller
let webOtpAbortController: AbortController | null = null

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
const lockCountdown = ref(0)
let lockInterval: ReturnType<typeof setInterval> | null = null

const redirectTo = computed(() => (route.query.redirect as string) || '/')

const formattedLockCountdown = computed(() => {
  const m = Math.floor(lockCountdown.value / 60)
  const s = lockCountdown.value % 60
  return `${m}:${s.toString().padStart(2, '0')}`
})

const formattedPhoneCountdown = computed(() => {
  const m = Math.floor(phoneCountdown.value / 60)
  const s = phoneCountdown.value % 60
  return `${m}:${s.toString().padStart(2, '0')}`
})

// Normalize raw phone string to clean digits, converting +98 prefix to 09
const normalizePhone = (raw: string): string => {
  // Strip spaces, dashes, parentheses
  let v = raw.replace(/[\s\-().]/g, '')
  // Convert +989XXXXXXXXX → 09XXXXXXXXX so validation & display are uniform
  if (v.startsWith('+98')) v = '0' + v.slice(3)
  // Keep only digits
  return v.replace(/\D/g, '')
}

const isPhoneValid = computed(() => {
  const digits = normalizePhone(phone.value)
  // Exactly 11 digits starting with 09
  return /^09\d{9}$/.test(digits)
})

const isOtpComplete = computed(() => otpCode.value.every(digit => digit.length === 1))

// Format display as "0912 345 6789"
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

const startPhoneCountdown = (seconds: number) => {
  phoneCountdown.value = seconds
  if (phoneCountdownInterval) clearInterval(phoneCountdownInterval)
  phoneCountdownInterval = setInterval(() => {
    if (phoneCountdown.value > 0) {
      phoneCountdown.value--
    } else {
      clearInterval(phoneCountdownInterval!)
      phoneCountdownInterval = null
    }
  }, 1000)
}

onUnmounted(() => {
  if (lockInterval) clearInterval(lockInterval)
  if (phoneCountdownInterval) clearInterval(phoneCountdownInterval)
  webOtpAbortController?.abort()
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
      error.value = ''
      errorType.value = 'generic'
    }
  }, 1000)
}

const maskPhone = (phone: string): string => {
  const digits = phone.replace(/\D/g, '')
  return digits.slice(0, 4) + '****' + digits.slice(-3)
}

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
      nextTick(() => verifyPhoneOtp())
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
  const pastedData = event.clipboardData?.getData('text') || ''
  const digits = pastedData.replace(/\D/g, '').slice(0, 6)
  if (digits) {
    // Clear all first
    otpCode.value = ['', '', '', '', '', '']
    // Fill digits
    digits.split('').forEach((digit, i) => {
      if (i < 6) otpCode.value[i] = digit
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
        verifyPhoneOtp()
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
        nextTick(() => verifyPhoneOtp())
      }
    }
  } catch {
    // Aborted or not supported — silently ignore
  }
}

const sendPhoneOtp = async () => {
  if (!isPhoneValid.value) {
    phoneError.value = 'لطفا شماره موبایل را به درستی وارد کنید (مثال: 09123456789)'
    return
  }
  phoneLoading.value = true
  phoneError.value = ''
  phoneSuccess.value = ''
  try {
    const cleanPhone = normalizePhone(phone.value)
    const response = await authService.sendOtp({
      identifier: cleanPhone,
      type: 'login_verification',
      channel: 'sms',
    })
    if (response.success) {
      phoneSuccess.value = `کد به ${maskPhone(cleanPhone)} ارسال شد`
      phoneAttemptsRemaining.value = response.data?.attempts_remaining ?? 3
      phoneStep.value = 'verify'
      if (response.data?.expires_at) {
        const diff = Math.ceil((new Date(response.data.expires_at).getTime() - Date.now()) / 1000)
        startPhoneCountdown(Math.max(diff, 0))
      } else {
        startPhoneCountdown(300)
      }
      await nextTick()
      otpInputRefs.value[0]?.focus()
      // Try to auto-read OTP from SMS on supported mobile browsers (WebOTP API)
      startWebOtp()
    } else {
      phoneError.value = response.message || 'خطا در ارسال کد. لطفا دوباره تلاش کنید'
    }
  } catch (err: any) {
    const status = err.response?.status
    const retryAfter = err.response?.data?.data?.retry_after
    if (status === 429) {
      const minutes = retryAfter ? Math.ceil(retryAfter / 60) : null
      phoneError.value = minutes
        ? `تعداد درخواست های بیش از حد. لطفا ${minutes} دقیقه دیگر دوباره تلاش کنید.`
        : 'تعداد درخواست های بیش از حد. لطفا بعدا دوباره تلاش کنید.'
    } else if (status === 403) {
      phoneError.value = 'حساب کاربری غیرفعال است. با پشتیبانی تماس بگیرید.'
    } else {
      phoneError.value = err.response?.data?.message || 'خطایی رخ داد. لطفا دوباره تلاش کنید'
    }
  } finally {
    phoneLoading.value = false
  }
}

const verifyPhoneOtp = async () => {
  if (!isOtpComplete.value) {
    phoneError.value = 'لطفا کد 6 رقمی را کامل وارد کنید'
    return
  }
  phoneLoading.value = true
  phoneError.value = ''
  try {
    const cleanPhone = normalizePhone(phone.value)
    const code = otpCode.value.join('')
    const response = await authService.verifyOtp({
      identifier: cleanPhone,
      code,
      type: 'login_verification',
      channel: 'sms',
    })
    if (response.success && response.data?.tokens) {
      // Abort any pending WebOTP request
      webOtpAbortController?.abort()

      authStore.setToken(response.data.tokens.access_token)
      localStorage.setItem('refresh_token', response.data.tokens.refresh_token)
      const expiresAt = Date.now() + response.data.tokens.expires_in * 1000
      localStorage.setItem('token_expires_at', expiresAt.toString())
      scheduleTokenRefresh(response.data.tokens.expires_in)

      // OTP login already confirms identity — use user from response, avoid extra getMe call
      const roles = await authService.getMyRoles()
      const user = response.data.user ?? (await authService.getMe().catch(() => null))
      if (!user) {
        authStore.logout()
        phoneError.value = 'خطا در دریافت اطلاعات کاربر. لطفا دوباره تلاش کنید.'
        return
      }
      authStore.setUser({
        ...user,
        roles,
        is_admin: user.is_super_admin || roles.some((r: string) => ['superadmin', 'admin', 'owner'].includes(r)),
      })
      if (!authStore.isSuperAdmin && !authStore.isStoreOwner) {
        await cartStore.mergeGuestCart()
      }
      if (authStore.isSuperAdmin) router.push('/admin/dashboard')
      else if (authStore.isStoreOwner) router.push('/store/dashboard')
      else router.push(redirectTo !== '/' && redirectTo ? redirectTo : '/')
    } else {
      phoneError.value = response.message || 'کد وارد شده معتبر نیست'
      if (response.data?.attempts_remaining != null) {
        phoneAttemptsRemaining.value = response.data.attempts_remaining
      }
      otpCode.value = ['', '', '', '', '', '']
      await nextTick()
      otpInputRefs.value[0]?.focus()
    }
  } catch (err: any) {
    const status = err.response?.status
    if (status === 423) {
      phoneError.value = 'تعداد تلاش های نامعتبر بیش از حد. لطفا دوباره کد جدید درخواست کنید.'
    } else {
      phoneError.value = err.response?.data?.message || 'خطا در تایید کد. لطفا دوباره تلاش کنید.'
    }
  } finally {
    phoneLoading.value = false
  }
}

const goBackToPhoneInput = () => {
  phoneStep.value = 'input'
  otpCode.value = ['', '', '', '', '', '']
  phoneError.value = ''
  if (phoneCountdownInterval) {
    clearInterval(phoneCountdownInterval)
    phoneCountdownInterval = null
  }
  phoneCountdown.value = 0
}

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
  if (!authStore.isSuperAdmin && !authStore.isStoreOwner) {
    await cartStore.mergeGuestCart()
  }
  if (authStore.isSuperAdmin) router.push('/admin/dashboard')
  else if (authStore.isStoreOwner) router.push('/store/dashboard')
  else router.push(redirectTo.value !== '/' && redirectTo.value ? redirectTo.value : '/')
}

const handlePasswordSubmit = async () => {
  if (!form.value.email || !form.value.password) {
    error.value = 'لطفا ایمیل و رمز عبور را وارد کنید'
    return
  }
  isLoading.value = true
  error.value = ''
  errorType.value = 'generic'
  if (lockInterval) clearInterval(lockInterval)
  try {
    const response = await authService.login({
      ...form.value,
      remember_me: form.value.rememberMe,
    })
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
    if (response.tokens && response.user) {
      await completeLogin(response.user, response.tokens)
    } else {
      error.value = 'خطا در دریافت توکن. لطفا دوباره تلاش کنید'
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
      error.value = message || 'حساب کاربری غیرفعال است. لطفا با پشتیبانی تماس بگیرید.'
    } else if (status === 423) {
      errorType.value = 'locked'
      const lockedUntil = responseData?.data?.locked_until
      if (lockedUntil) {
        const remaining = Math.ceil((new Date(lockedUntil).getTime() - Date.now()) / 1000)
        if (remaining > 0) {
          startLockCountdown(remaining)
          error.value = 'حساب کاربری شما موقتا قفل شده است.'
        } else {
          error.value = 'حساب کاربری به دلیل تلاش های ناموفق متعدد قفل شده است.'
        }
      } else {
        error.value = 'حساب کاربری به دلیل تلاش های ناموفق متعدد قفل شده است.'
      }
    } else if (status === 429) {
      errorType.value = 'rate_limited'
      const retryAfter = responseData?.data?.retry_after
      if (retryAfter) {
        startLockCountdown(retryAfter)
        error.value = 'تعداد تلاش های بیش از حد.'
      } else {
        error.value = 'تعداد تلاش های بیش از حد. لطفا بعدا دوباره تلاش کنید.'
      }
    } else if (message) {
      error.value = message
    } else {
      error.value = 'خطا در ورود. لطفا دوباره تلاش کنید'
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-secondary-50 to-secondary-100 flex items-center justify-center py-12 px-4" dir="rtl">
    <div class="max-w-md w-full">
      <!-- Logo -->
      <div class="text-center mb-8">
        <RouterLink to="/" class="inline-flex items-center gap-3">
          <div class="w-16 h-16 rounded-2xl flex items-center justify-center shadow-lg shadow-primary-200">
                      <img src="/images/Logo/tejarat-logo-500.png" alt="لوگو فروشگاه" class="h-12 w-auto object-contain">
          </div>
          <div class="text-right">
            <span class="text-2xl font-bold text-primary-600">اتکا</span>
            <span class="block text-sm text-secondary-500">تجارت الکترونیک</span>
          </div>
        </RouterLink>
      </div>

      <!-- Main Card -->
      <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
        <!-- Header with tabs -->
        <div class="flex border-b border-secondary-100">
          <button
            type="button"
            @click="activeTab = 'phone'"
            class="flex-1 py-4 px-4 text-sm font-medium transition-colors relative"
            :class="activeTab === 'phone' ? 'text-primary-600' : 'text-secondary-500 hover:text-secondary-700'"
          >
            <span class="flex items-center justify-center gap-2">
              <Smartphone class="w-4 h-4" />
              ورود با موبایل
            </span>
            <div v-if="activeTab === 'phone'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary-500" />
          </button>
          <button
            type="button"
            @click="activeTab = 'password'"
            class="flex-1 py-4 px-4 text-sm font-medium transition-colors relative"
            :class="activeTab === 'password' ? 'text-primary-600' : 'text-secondary-500 hover:text-secondary-700'"
          >
            <span class="flex items-center justify-center gap-2">
              <Lock class="w-4 h-4" />
              ورود با رمز عبور
            </span>
            <div v-if="activeTab === 'password'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary-500" />
          </button>
        </div>

        <div class="p-8">
          <!-- PHONE LOGIN TAB -->
          <div v-if="activeTab === 'phone'">
            <!-- Phone Input Step -->
            <div v-if="phoneStep === 'input'">
              <div class="text-center mb-6">
                <h1 class="text-xl font-bold text-secondary-800 mb-2">ورود با شماره موبایل</h1>
                <p class="text-secondary-500 text-sm">کد یکبار مصرف برای شما ارسال خواهد شد</p>
              </div>

              <div v-if="phoneError" class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-5 text-sm">
                {{ phoneError }}
              </div>

              <div class="space-y-5">
                <div>
                  <label class="block text-sm font-medium text-secondary-700 mb-2">شماره موبایل</label>
                  <div class="relative">
                    <input
                      v-model="phone"
                      type="text"
                      inputmode="numeric"
                      placeholder="09xx xxx xxxx"
                      autocomplete="tel"
                      maxlength="14"
                      class="w-full h-14 pl-4 pr-14 text-lg rounded-xl border-2 border-secondary-200 focus:border-primary-500 focus:ring-4 focus:ring-primary-100 outline-none transition text-center tracking-widest font-mono"
                      dir="ltr"
                      :disabled="phoneLoading"
                      @keyup.enter="sendPhoneOtp"
                    />
                    <div class="absolute right-4 top-1/2 -translate-y-1/2 flex items-center gap-1">
                      <span class="text-sm text-secondary-400 font-medium">IR</span>
                      <span class="text-secondary-300">+98</span>
                    </div>
                  </div>
                  <p class="mt-2 text-xs text-secondary-400">شماره موبایل باید با ۰۹ شروع شود</p>
                </div>

                <button
                  type="button"
                  @click="sendPhoneOtp"
                  :disabled="phoneLoading || !isPhoneValid"
                  class="w-full h-14 bg-primary-500 hover:bg-primary-600 disabled:bg-primary-300 disabled:cursor-not-allowed text-white font-bold text-lg rounded-xl transition flex items-center justify-center gap-2 shadow-lg shadow-primary-200"
                >
                  <Loader2 v-if="phoneLoading" class="w-5 h-5 animate-spin" />
                  <span v-else>دریافت کد</span>
                </button>
              </div>
            </div>

            <!-- OTP Verification Step -->
            <div v-else>
              <button type="button" @click="goBackToPhoneInput" class="flex items-center gap-1 text-sm text-secondary-500 hover:text-secondary-700 mb-4">
                <ArrowLeft class="w-4 h-4" />بازگشت
              </button>

              <div class="text-center mb-6">
                <div class="w-16 h-16 bg-primary-50 rounded-full flex items-center justify-center mx-auto mb-4">
                  <ShieldCheck class="w-8 h-8 text-primary-500" />
                </div>
                <h1 class="text-xl font-bold text-secondary-800 mb-2">کد تایید را وارد کنید</h1>
                <p class="text-secondary-500 text-sm">
                  کد ۶ رقمی به <span class="font-medium text-secondary-700" dir="ltr">{{ maskPhone(phone) }}</span> ارسال شد
                </p>
              </div>

              <div v-if="phoneSuccess" class="bg-emerald-50 border border-emerald-200 text-emerald-600 px-4 py-3 rounded-xl mb-5 text-sm">
                {{ phoneSuccess }}
              </div>

              <div v-if="phoneError" class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-5 text-sm">
                {{ phoneError }}
                <p v-if="phoneAttemptsRemaining !== null" class="mt-1 text-xs">تعداد تلاش باقی‌مانده: {{ phoneAttemptsRemaining }}</p>
              </div>

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
                    :disabled="phoneLoading"
                    @input="handleOtpInput(n - 1, $event)"
                    @keydown="handleOtpKeydown(n - 1, $event)"
                    @paste="handleOtpPaste($event)"
                    @focus="($event.target as HTMLInputElement).select()"
                  />
                </div>
                <p class="mt-3 text-center text-xs text-secondary-400">کد دریافتی از پیامک را وارد کنید</p>
              </div>

              <button
                type="button"
                @click="verifyPhoneOtp"
                :disabled="phoneLoading || !isOtpComplete"
                class="w-full h-14 bg-primary-500 hover:bg-primary-600 disabled:bg-primary-300 disabled:cursor-not-allowed text-white font-bold text-lg rounded-xl transition flex items-center justify-center gap-2 shadow-lg shadow-primary-200 mb-4"
              >
                <Loader2 v-if="phoneLoading" class="w-5 h-5 animate-spin" />
                <span v-else>ورود</span>
              </button>

              <div class="text-center">
                <p v-if="phoneCountdown > 0" class="text-sm text-secondary-500">
                  ارسال مجدد کد پس از <span class="font-mono font-medium" dir="ltr">{{ formattedPhoneCountdown }}</span>
                </p>
                <button v-else type="button" @click="sendPhoneOtp" :disabled="phoneLoading" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                  ارسال مجدد کد
                </button>
              </div>
            </div>
          </div>

          <!-- PASSWORD LOGIN TAB -->
          <div v-else>
            <div class="text-center mb-6">
              <h1 class="text-xl font-bold text-secondary-800 mb-2">ورود با رمز عبور</h1>
              <p class="text-secondary-500 text-sm">ایمیل و رمز عبور خود را وارد کنید</p>
            </div>

            <div v-if="error" role="alert" :class="['px-4 py-3 rounded-xl mb-5 text-sm border', errorType === 'locked' || errorType === 'rate_limited' ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-red-50 border-red-200 text-red-700']">
              <div class="flex items-start gap-2">
                <AlertTriangle class="w-4 h-4 mt-0.5 flex-shrink-0" />
                <div class="flex-1">
                  <p>{{ error }}</p>
                  <div v-if="(errorType === 'locked' || errorType === 'rate_limited') && lockCountdown > 0" class="mt-2 flex items-center gap-1.5 font-medium">
                    <Clock class="w-4 h-4" />
                    <span>می‌توانید در </span>
                    <span class="font-mono bg-amber-100 px-1.5 py-0.5 rounded text-amber-900" dir="ltr">{{ formattedLockCountdown }}</span>
                    <span> دوباره تلاش کنید</span>
                  </div>
                </div>
              </div>
            </div>

            <form @submit.prevent="handlePasswordSubmit" novalidate class="space-y-5">
              <div>
                <label for="login-email" class="block text-sm font-medium text-secondary-700 mb-2">ایمیل</label>
                <div class="relative">
                  <input id="login-email" v-model="form.email" type="email" placeholder="example@email.com" autocomplete="email" class="w-full h-12 pl-4 pr-12 rounded-xl border-2 border-secondary-200 focus:border-primary-500 focus:ring-4 focus:ring-primary-100 outline-none transition text-left" dir="ltr" :disabled="isLoading" />
                  <Mail class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400 pointer-events-none" />
                </div>
              </div>

              <div>
                <div class="flex items-center justify-between mb-2">
                  <label for="login-password" class="text-sm font-medium text-secondary-700">رمز عبور</label>
                  <RouterLink to="/forgot-password" class="text-xs text-primary-600 hover:text-primary-700">فراموشی رمز؟</RouterLink>
                </div>
                <div class="relative">
                  <input id="login-password" v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="رمز عبور خود را وارد کنید" autocomplete="current-password" class="w-full h-12 pl-12 pr-12 rounded-xl border-2 border-secondary-200 focus:border-primary-500 focus:ring-4 focus:ring-primary-100 outline-none transition" :disabled="isLoading" />
                  <Lock class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400 pointer-events-none" />
                  <button type="button" @click="showPassword = !showPassword" class="absolute left-4 top-1/2 -translate-y-1/2 text-secondary-400 hover:text-secondary-600" :aria-label="showPassword ? 'پنهان کردن رمز عبور' : 'نمایش رمز عبور'">
                    <EyeOff v-if="showPassword" class="w-5 h-5" />
                    <Eye v-else class="w-5 h-5" />
                  </button>
                </div>
              </div>

              <div class="flex items-center gap-2">
                <input id="remember-me" v-model="form.rememberMe" type="checkbox" class="w-4 h-4 rounded border-secondary-300 text-primary-600 focus:ring-primary-500" />
                <label for="remember-me" class="text-sm text-secondary-600 cursor-pointer select-none">مرا به خاطر بسپار</label>
              </div>

              <button type="submit" :disabled="isLoading || lockCountdown > 0" class="w-full h-12 bg-primary-500 hover:bg-primary-600 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold rounded-xl transition flex items-center justify-center gap-2">
                <Loader2 v-if="isLoading" class="w-5 h-5 animate-spin" />
                <span>{{ isLoading ? 'در حال ورود...' : 'ورود' }}</span>
              </button>
            </form>
          </div>
        </div>
      </div>

      <p class="mt-6 text-center text-sm text-secondary-500">
        حساب کاربری ندارید؟
        <RouterLink to="/register" class="text-primary-600 hover:text-primary-700 font-medium">ثبت‌نام کنید</RouterLink>
      </p>

      <p class="mt-4 text-center">
        <RouterLink to="/" class="text-secondary-400 hover:text-secondary-600 text-sm">بازگشت به صفحه اصلی</RouterLink>
      </p>
    </div>
  </div>
</template>
