<script setup lang="ts">
import { ref, computed, nextTick, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { Mail, Smartphone, Loader2, CheckCircle2, ShieldCheck, ArrowLeft, Lock, Eye, EyeOff, Check } from 'lucide-vue-next'
import { authService } from '../services'

const router = useRouter()

// Steps: 'input' -> 'verify' -> 'reset' -> 'success'
const step = ref<'input' | 'verify' | 'reset' | 'success'>('input')
const identifier = ref('')
const otpCode = ref(['', '', '', '', '', ''])
const resetToken = ref('')
const newPassword = ref('')
const confirmPassword = ref('')
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)

const error = ref('')
const successMessage = ref('')
const isLoading = ref(false)
const countdown = ref(0)
const attemptsRemaining = ref<number | null>(null)
const channels = ref<string[]>([])
let countdownInterval: ReturnType<typeof setInterval> | null = null

const otpInputRefs = ref<(HTMLInputElement | null)[]>([])

// WebOTP API abort controller (Android Chrome auto-read SMS)
let webOtpAbortController: AbortController | null = null

const isIdentifierValid = computed(() => {
  const val = identifier.value.trim()
  // Email validation
  if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
    return true
  }
  // Iranian phone validation: supports 09XXXXXXXXX or +989XXXXXXXXX
  const digits = val.replace(/\s/g, '')
  return /^09\d{9}$/.test(digits) || /^\+989\d{9}$/.test(digits)
})

const isOtpComplete = computed(() => otpCode.value.every(digit => digit.length === 1))

const isPasswordValid = computed(() => {
  return newPassword.value.length >= 8 && newPassword.value === confirmPassword.value
})

const formattedCountdown = computed(() => {
  const m = Math.floor(countdown.value / 60)
  const s = countdown.value % 60
  return `${m}:${s.toString().padStart(2, '0')}`
})

const maskIdentifier = (id: string): string => {
  if (id.includes('@')) {
    const [user, domain] = id.split('@')
    return user.slice(0, 2) + '***@' + domain
  }
  const digits = id.replace(/\D/g, '')
  return digits.slice(0, 4) + '****' + digits.slice(-3)
}

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

const handleOtpInput = (index: number, event: Event) => {
  const input = event.target as HTMLInputElement
  const value = input.value.replace(/\D/g, '')
  if (value) {
    otpCode.value[index] = value.slice(-1)
    if (index < 5) {
      nextTick(() => otpInputRefs.value[index + 1]?.focus())
    } else if (index === 5 && isOtpComplete.value) {
      // Bug fix: auto-submit when last digit entered
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
    digits.split('').forEach((digit, i) => {
      if (i < 6) otpCode.value[i] = digit
    })
    // Auto-submit if all 6 digits pasted
    if (digits.length === 6) {
      nextTick(() => verifyOtp())
    }
  }
}

// WebOTP API: auto-read OTP from SMS on Android Chrome / Samsung Internet
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

const sendResetCode = async () => {
  if (!isIdentifierValid.value) {
    error.value = 'لطفا ایمیل یا شماره موبایل معتبر وارد کنید'
    return
  }

  isLoading.value = true
  error.value = ''
  successMessage.value = ''

  try {
    const cleanIdentifier = identifier.value.trim()
    const response = await authService.forgotPassword(cleanIdentifier)

    if (response.success) {
      channels.value = response.data?.channels || []
      // Always show a neutral success message (avoids revealing if account exists)
      const channelMsg = channels.value.includes('sms')
        ? 'ایمیل و شماره موبایل'
        : 'ایمیل'
      successMessage.value = `در صورت وجود حساب، کد بازنشانی رمز عبور به ${channelMsg} شما ارسال شده است`
      step.value = 'verify'
      if (response.data?.expires_at) {
        const diff = Math.ceil((new Date(response.data.expires_at).getTime() - Date.now()) / 1000)
        startCountdown(Math.max(diff, 0))
      } else {
        startCountdown(900)
      }
      await nextTick()
      otpInputRefs.value[0]?.focus()
      // Start WebOTP auto-read on Android Chrome / Samsung Internet
      startWebOtp()
    } else {
      error.value = response.message || 'خطا در ارسال کد. لطفا دوباره تلاش کنید'
    }
  } catch (err: any) {
    const status = err.response?.status
    const retryAfter = err.response?.data?.data?.retry_after

    if (status === 429) {
      const minutes = retryAfter ? Math.ceil(retryAfter / 60) : null
      error.value = minutes
        ? `تعداد درخواست‌های بیش از حد. لطفا ${minutes} دقیقه دیگر دوباره تلاش کنید.`
        : 'تعداد درخواست‌های بیش از حد. لطفا بعدا دوباره تلاش کنید.'
    } else {
      error.value = err.response?.data?.message || 'خطا در ارسال کد. لطفا دوباره تلاش کنید'
    }
  } finally {
    isLoading.value = false
  }
}

const verifyOtp = async () => {
  if (!isOtpComplete.value) {
    error.value = 'لطفا کد 6 رقمی را کامل وارد کنید'
    return
  }

  isLoading.value = true
  error.value = ''
  webOtpAbortController?.abort()

  try {
    const cleanIdentifier = identifier.value.trim()
    const code = otpCode.value.join('')

    const response = await authService.verifyPasswordResetOtp({
      identifier: cleanIdentifier,
      code
    })

    if (response.success && response.data?.reset_token) {
      // Store reset token for the reset password step
      resetToken.value = response.data.reset_token
      step.value = 'reset'
    } else {
      error.value = response.message || 'کد وارد شده معتبر نیست'
      if (response.data?.attempts_remaining != null) {
        attemptsRemaining.value = response.data.attempts_remaining
      }
    }
  } catch (err: any) {
    const status = err.response?.status
    if (status === 423) {
      error.value = 'تعداد تلاش‌های نامعتبر بیش از حد. لطفا دوباره کد جدید درخواست کنید.'
    } else {
      error.value = err.response?.data?.message || 'خطا در تایید کد. لطفا دوباره تلاش کنید.'
    }
  } finally {
    isLoading.value = false
  }
}

const resetPassword = async () => {
  if (!isPasswordValid.value) {
    if (newPassword.value.length < 8) {
      error.value = 'رمز عبور باید حداقل 8 کاراکتر باشد'
    } else {
      error.value = 'رمز عبور و تکرار آن مطابقت ندارند'
    }
    return
  }

  if (!resetToken.value) {
    error.value = 'خطا در تایید هویت. لطفا دوباره کد را وارد کنید.'
    return
  }

  isLoading.value = true
  error.value = ''

  try {
    const cleanIdentifier = identifier.value.trim()

    await authService.resetPasswordWithOtp({
      identifier: cleanIdentifier,
      reset_token: resetToken.value,
      password: newPassword.value,
      password_confirmation: confirmPassword.value
    })

    // Clear sensitive data
    resetToken.value = ''
    otpCode.value = ['', '', '', '', '', '']

    step.value = 'success'
  } catch (err: any) {
    error.value = err.response?.data?.message || 'خطا در تغییر رمز عبور. لطفا دوباره تلاش کنید.'
  } finally {
    isLoading.value = false
  }
}

const goBack = () => {
  if (step.value === 'verify') {
    step.value = 'input'
    otpCode.value = ['', '', '', '', '', '']
  } else if (step.value === 'reset') {
    step.value = 'verify'
  }
  error.value = ''
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-secondary-50 to-secondary-100 flex items-center justify-center py-12 px-4" dir="rtl">
    <div class="max-w-md w-full">
      <!-- Logo -->
      <div class="text-center mb-8">
        <RouterLink to="/" class="inline-flex items-center gap-3">
          <div class="w-16 h-16 bg-gradient-to-br from-primary-500 to-primary-700 rounded-2xl flex items-center justify-center shadow-lg shadow-primary-200">
            <svg class="w-10 h-10 text-white" viewBox="0 0 24 24" fill="currentColor">
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

      <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
        <!-- Step progress indicator -->
        <div class="flex items-center px-6 py-4 border-b border-secondary-100 gap-2">
          <!-- Step 1 -->
          <div class="flex items-center gap-2 flex-1">
            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0 transition-colors"
              :class="step === 'input' ? 'bg-primary-500 text-white' : 'bg-emerald-500 text-white'">
              <Check v-if="step !== 'input'" class="w-4 h-4" />
              <span v-else>۱</span>
            </div>
            <span class="text-xs font-medium hidden sm:block" :class="step === 'input' ? 'text-primary-600' : 'text-emerald-600'">شناسایی</span>
          </div>
          <div class="h-px flex-1 max-w-8 transition-colors" :class="step !== 'input' ? 'bg-emerald-400' : 'bg-secondary-200'" />
          <!-- Step 2 -->
          <div class="flex items-center gap-2 flex-1">
            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0 transition-colors"
              :class="step === 'verify' ? 'bg-primary-500 text-white' : (step === 'reset' || step === 'success' ? 'bg-emerald-500 text-white' : 'bg-secondary-200 text-secondary-400')">
              <Check v-if="step === 'reset' || step === 'success'" class="w-4 h-4" />
              <span v-else>۲</span>
            </div>
            <span class="text-xs font-medium hidden sm:block"
              :class="step === 'verify' ? 'text-primary-600' : (step === 'reset' || step === 'success' ? 'text-emerald-600' : 'text-secondary-400')">تایید کد</span>
          </div>
          <div class="h-px flex-1 max-w-8 transition-colors" :class="step === 'reset' || step === 'success' ? 'bg-emerald-400' : 'bg-secondary-200'" />
          <!-- Step 3 -->
          <div class="flex items-center gap-2 flex-1">
            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0 transition-colors"
              :class="step === 'reset' ? 'bg-primary-500 text-white' : (step === 'success' ? 'bg-emerald-500 text-white' : 'bg-secondary-200 text-secondary-400')">
              <Check v-if="step === 'success'" class="w-4 h-4" />
              <span v-else>۳</span>
            </div>
            <span class="text-xs font-medium hidden sm:block"
              :class="step === 'reset' ? 'text-primary-600' : (step === 'success' ? 'text-emerald-600' : 'text-secondary-400')">رمز جدید</span>
          </div>
        </div>

        <div class="p-8">
          <!-- STEP 1: Input identifier -->
          <div v-if="step === 'input'">
            <div class="text-center mb-6">
              <h1 class="text-xl font-bold text-secondary-800 mb-2">فراموشی رمز عبور</h1>
              <p class="text-secondary-500 text-sm">ایمیل یا شماره موبایل خود را وارد کنید</p>
            </div>

            <div v-if="error" class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-5 text-sm">
              {{ error }}
            </div>

            <div class="space-y-5">
              <div>
                <label class="block text-sm font-medium text-secondary-700 mb-2">ایمیل یا شماره موبایل</label>
                <div class="relative">
                  <input
                    v-model="identifier"
                    type="text"
                    placeholder="example@email.com یا 09123456789"
                    class="w-full h-12 pl-4 pr-12 rounded-xl border-2 border-secondary-200 focus:border-primary-500 focus:ring-4 focus:ring-primary-100 outline-none transition"
                    dir="ltr"
                    :disabled="isLoading"
                    @keyup.enter="sendResetCode"
                  />
                  <Mail v-if="identifier.includes('@')" class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400" />
                  <Smartphone v-else class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400" />
                </div>
                <p class="mt-2 text-xs text-secondary-400">کد بازنشانی به ایمیل و شماره موبایل شما ارسال خواهد شد</p>
              </div>

              <button
                type="button"
                @click="sendResetCode"
                :disabled="isLoading || !isIdentifierValid"
                class="w-full h-14 bg-primary-500 hover:bg-primary-600 disabled:bg-primary-300 disabled:cursor-not-allowed text-white font-bold text-lg rounded-xl transition flex items-center justify-center gap-2 shadow-lg shadow-primary-200"
              >
                <Loader2 v-if="isLoading" class="w-5 h-5 animate-spin" />
                <span v-else>دریافت کد بازنشانی</span>
              </button>
            </div>
          </div>

          <!-- STEP 2: Verify OTP -->
          <div v-else-if="step === 'verify'">
            <button type="button" @click="goBack" class="flex items-center gap-1 text-sm text-secondary-500 hover:text-secondary-700 mb-4">
              <ArrowLeft class="w-4 h-4" />بازگشت
            </button>

            <div class="text-center mb-6">
              <div class="w-16 h-16 bg-primary-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <ShieldCheck class="w-8 h-8 text-primary-500" />
              </div>
              <h1 class="text-xl font-bold text-secondary-800 mb-2">کد تایید را وارد کنید</h1>
              <p class="text-secondary-500 text-sm">
                کد ۶ رقمی به <span class="font-medium text-secondary-700" dir="ltr">{{ maskIdentifier(identifier) }}</span> ارسال شد
              </p>
            </div>

            <div v-if="successMessage" class="bg-emerald-50 border border-emerald-200 text-emerald-600 px-4 py-3 rounded-xl mb-5 text-sm">
              {{ successMessage }}
            </div>

            <div v-if="error" class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-5 text-sm">
              {{ error }}
              <p v-if="attemptsRemaining !== null" class="mt-1 text-xs">تعداد تلاش باقی‌مانده: {{ attemptsRemaining }}</p>
            </div>

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
              <p class="mt-3 text-center text-xs text-secondary-400">کد دریافتی از پیامک یا ایمیل را وارد کنید</p>
            </div>

            <button
              type="button"
              @click="verifyOtp"
              :disabled="isLoading || !isOtpComplete"
              class="w-full h-14 bg-primary-500 hover:bg-primary-600 disabled:bg-primary-300 disabled:cursor-not-allowed text-white font-bold text-lg rounded-xl transition flex items-center justify-center gap-2 shadow-lg shadow-primary-200 mb-4"
            >
              <Loader2 v-if="isLoading" class="w-5 h-5 animate-spin" />
              <span v-else>تایید کد</span>
            </button>

            <div class="text-center">
              <p v-if="countdown > 0" class="text-sm text-secondary-500">
                ارسال مجدد کد پس از <span class="font-mono font-medium" dir="ltr">{{ formattedCountdown }}</span>
              </p>
              <button
                v-else
                type="button"
                @click="sendResetCode"
                :disabled="isLoading"
                class="text-sm text-primary-600 hover:text-primary-700 font-medium"
              >
                ارسال مجدد کد
              </button>
            </div>
          </div>

          <!-- STEP 3: Reset password -->
          <div v-else-if="step === 'reset'">
            <button type="button" @click="goBack" class="flex items-center gap-1 text-sm text-secondary-500 hover:text-secondary-700 mb-4">
              <ArrowLeft class="w-4 h-4" />بازگشت
            </button>

            <div class="text-center mb-6">
              <div class="w-16 h-16 bg-primary-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <Lock class="w-8 h-8 text-primary-500" />
              </div>
              <h1 class="text-xl font-bold text-secondary-800 mb-2">رمز عبور جدید</h1>
              <p class="text-secondary-500 text-sm">رمز عبور جدید خود را وارد کنید</p>
            </div>

            <div v-if="error" class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-5 text-sm">
              {{ error }}
            </div>

            <div class="space-y-5">
              <div>
                <label class="block text-sm font-medium text-secondary-700 mb-2">رمز عبور جدید</label>
                <div class="relative">
                  <input
                    v-model="newPassword"
                    :type="showNewPassword ? 'text' : 'password'"
                    placeholder="حداقل 8 کاراکتر"
                    class="w-full h-12 pl-12 pr-12 rounded-xl border-2 border-secondary-200 focus:border-primary-500 focus:ring-4 focus:ring-primary-100 outline-none transition"
                    :disabled="isLoading"
                  />
                  <Lock class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400" />
                  <button
                    type="button"
                    @click="showNewPassword = !showNewPassword"
                    class="absolute left-4 top-1/2 -translate-y-1/2 text-secondary-400 hover:text-secondary-600"
                  >
                    <EyeOff v-if="showNewPassword" class="w-5 h-5" />
                    <Eye v-else class="w-5 h-5" />
                  </button>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-secondary-700 mb-2">تکرار رمز عبور</label>
                <div class="relative">
                  <input
                    v-model="confirmPassword"
                    :type="showConfirmPassword ? 'text' : 'password'"
                    placeholder="رمز عبور را دوباره وارد کنید"
                    class="w-full h-12 pl-12 pr-12 rounded-xl border-2 border-secondary-200 focus:border-primary-500 focus:ring-4 focus:ring-primary-100 outline-none transition"
                    :disabled="isLoading"
                    @keyup.enter="resetPassword"
                  />
                  <Lock class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400" />
                  <button
                    type="button"
                    @click="showConfirmPassword = !showConfirmPassword"
                    class="absolute left-4 top-1/2 -translate-y-1/2 text-secondary-400 hover:text-secondary-600"
                  >
                    <EyeOff v-if="showConfirmPassword" class="w-5 h-5" />
                    <Eye v-else class="w-5 h-5" />
                  </button>
                </div>
              </div>

              <button
                type="button"
                @click="resetPassword"
                :disabled="isLoading || !isPasswordValid"
                class="w-full h-14 bg-primary-500 hover:bg-primary-600 disabled:bg-primary-300 disabled:cursor-not-allowed text-white font-bold text-lg rounded-xl transition flex items-center justify-center gap-2 shadow-lg shadow-primary-200"
              >
                <Loader2 v-if="isLoading" class="w-5 h-5 animate-spin" />
                <span v-else>تغییر رمز عبور</span>
              </button>
            </div>
          </div>

          <!-- STEP 4: Success -->
          <div v-else-if="step === 'success'" class="text-center py-4">
            <div class="w-16 h-16 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-4">
              <CheckCircle2 class="w-9 h-9 text-emerald-500" />
            </div>
            <h1 class="text-xl font-bold text-secondary-800 mb-2">رمز عبور تغییر یافت</h1>
            <p class="text-secondary-500 text-sm leading-relaxed mb-6">
              رمز عبور شما با موفقیت تغییر کرد. اکنون می‌توانید با رمز جدید وارد شوید.
            </p>
            <RouterLink
              to="/login"
              class="inline-flex items-center gap-2 px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white font-medium rounded-xl transition"
            >
              <ArrowRight class="w-4 h-4" />
              ورود به حساب
            </RouterLink>
          </div>
        </div>
      </div>

      <p class="mt-6 text-center text-sm text-secondary-500">
        رمز عبور خود را به خاطر آوردید؟
        <RouterLink to="/login" class="text-primary-600 hover:text-primary-700 font-medium">ورود</RouterLink>
      </p>

      <p class="mt-4 text-center">
        <RouterLink to="/" class="text-secondary-400 hover:text-secondary-600 text-sm">بازگشت به صفحه اصلی</RouterLink>
      </p>
    </div>
  </div>
</template>
