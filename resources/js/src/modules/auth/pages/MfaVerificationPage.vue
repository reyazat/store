<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Lock, RefreshCw, AlertCircle, ArrowLeft, KeyRound } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { authService } from '../services'
import { scheduleTokenRefresh } from '@shared/utils/api'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const cartStore = useCartStore()

// Get temp token from query params (passed from LoginPage)
const tempToken = ref<string>((route.query.temp_token as string) || '')

// Form state
const totpCode = ref('')
const recoveryCode = ref('')
const activeTab = ref<'totp' | 'recovery'>('totp')
const isLoading = ref(false)
const error = ref('')
const attemptsRemaining = ref<number | null>(null)
const remainingCodes = ref<number | null>(null)

// Validation
const isTotpValid = computed(() => totpCode.value.length === 6 && /^\d{6}$/.test(totpCode.value))
const isRecoveryValid = computed(() => recoveryCode.value.length === 10 && /^[A-Za-z0-9]{10}$/.test(recoveryCode.value))

onMounted(() => {
  // Check if we have temp token
  if (!tempToken.value) {
    error.value = 'Invalid session. Please login again.'
    setTimeout(() => {
      router.push('/login')
    }, 2000)
  }
})

// Handle TOTP verification
const verifyTotp = async () => {
  if (!isTotpValid.value) {
    error.value = 'لطفاً کد 6 رقمی معتبر وارد کنید'
    return
  }

  isLoading.value = true
  error.value = ''
  attemptsRemaining.value = null

  try {
    const response = await authService.verifyMfaLogin({
      login_token: tempToken.value,
      code: totpCode.value,
      type: 'totp'
    })

    if (response.success && response.data?.tokens && response.data?.user) {
      // Store tokens
      authStore.setToken(response.data.tokens.access_token)
      localStorage.setItem('refresh_token', response.data.tokens.refresh_token)

      // Store token expiration for proactive refresh
      const expiresAt = Date.now() + (response.data.tokens.expires_in * 1000)
      localStorage.setItem('token_expires_at', expiresAt.toString())
      scheduleTokenRefresh(response.data.tokens.expires_in)

      // Get user roles
      const roles = await authService.getMyRoles()

      // Set user with roles
      authStore.setUser({
        ...response.data.user,
        roles,
        is_admin: response.data.user.is_super_admin || roles.some((r: string) => ['superadmin', 'admin', 'owner'].includes(r)),
      })

      // Merge guest cart
      await cartStore.mergeGuestCart()

      // Redirect
      const redirectTo = (route.query.redirect as string) || '/'
      router.push(redirectTo !== '/' && redirectTo ? redirectTo : (authStore.isAdmin ? '/admin/dashboard' : '/'))
    } else {
      error.value = response.message || 'خطا در تایید کد MFA'
      if (response.data?.attempts_remaining != null) {
        attemptsRemaining.value = response.data.attempts_remaining
      }
    }
  } catch (err: unknown) {
    const e = err as { response?: { data?: { message?: string; data?: { attempts_remaining?: number } } } }
    error.value = e.response?.data?.message || 'خطا در تایید کد. لطفاً دوباره تلاش کنید'
    if (e.response?.data?.data?.attempts_remaining != null) {
      attemptsRemaining.value = e.response.data.data.attempts_remaining as number
    }
  } finally {
    isLoading.value = false
  }
}

// Handle Recovery Code verification
const verifyRecoveryCode = async () => {
  if (!isRecoveryValid.value) {
    error.value = 'لطفاً کد بازیابی 10 کاراکتری معتبر وارد کنید'
    return
  }

  isLoading.value = true
  error.value = ''

  try {
    const response = await authService.verifyMfaRecovery({
      login_token: tempToken.value,
      recovery_code: recoveryCode.value.toUpperCase(),
    })

    if (response.success && response.data?.tokens && response.data?.user) {
      // Store tokens
      authStore.setToken(response.data.tokens.access_token)
      localStorage.setItem('refresh_token', response.data.tokens.refresh_token)

      // Store token expiration for proactive refresh
      const expiresAt = Date.now() + (response.data.tokens.expires_in * 1000)
      localStorage.setItem('token_expires_at', expiresAt.toString())
      scheduleTokenRefresh(response.data.tokens.expires_in)

      // Get user roles
      const roles = await authService.getMyRoles()

      // Set user with roles
      authStore.setUser({
        ...response.data.user,
        roles,
        is_admin: response.data.user.is_super_admin || roles.some((r: string) => ['superadmin', 'admin', 'owner'].includes(r)),
      })

      // Merge guest cart
      await cartStore.mergeGuestCart()

      // Show warning about used recovery code
      if (response.data.recovery_code_used && response.data.remaining_codes != null) {
        remainingCodes.value = response.data.remaining_codes
      }

      // Redirect after a short delay to show the warning
      setTimeout(() => {
        const redirectTo = (route.query.redirect as string) || '/'
        router.push(redirectTo !== '/' && redirectTo ? redirectTo : (authStore.isAdmin ? '/admin/dashboard' : '/'))
      }, remainingCodes.value !== null ? 3000 : 0)
    } else {
      error.value = response.message || 'خطا در تایید کد بازیابی'
      if (response.data?.remaining_codes != null) {
        remainingCodes.value = response.data.remaining_codes
      }
    }
  } catch (err: unknown) {
    const e = err as { response?: { data?: { message?: string; data?: { remaining_codes?: number } } } }
    error.value = e.response?.data?.message || 'خطا در تایید کد بازیابی. لطفاً دوباره تلاش کنید'
    if (e.response?.data?.data?.remaining_codes != null) {
      remainingCodes.value = e.response.data.data.remaining_codes as number
    }
  } finally {
    isLoading.value = false
  }
}

// Go back to login
const goBack = () => {
  router.push('/login')
}
</script>

<template>
  <div class="min-h-screen bg-secondary-50 flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full">
      <!-- Logo -->
      <div class="text-center mb-8">
        <RouterLink to="/">
          <img src="/images/Logo/tejarat-logo-500.png" alt="لوگو فروشگاه" class="h-12 w-auto object-contain">
        </RouterLink>
      </div>

      <!-- MFA Card -->
      <div class="bg-white rounded-2xl shadow-xl p-8">
        <!-- Header -->
        <div class="text-center mb-6">
          <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <Lock class="w-8 h-8 text-primary-600" />
          </div>
          <h1 class="text-2xl font-bold text-secondary-800">تایید هویت دو عاملی</h1>
          <p class="text-secondary-500 mt-2">
            لطفاً کد تایید را از اپلیکیشن authenticator خود وارد کنید
          </p>
        </div>

        <!-- Recovery Code Warning -->
        <div v-if="remainingCodes !== null" class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
          <div class="flex items-start gap-3">
            <AlertCircle class="w-5 h-5 text-amber-600 mt-0.5 flex-shrink-0" />
            <div>
              <p class="text-sm text-amber-800 font-medium">
                کد بازیابی استفاده شد!
              </p>
              <p class="text-sm text-amber-700 mt-1">
                {{ remainingCodes }} کد بازیابی باقی مانده است.
                لطفاً کدهای جدید تولید کنید.
              </p>
            </div>
          </div>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-6 text-sm">
          <div class="flex items-center gap-2">
            <AlertCircle class="w-4 h-4 flex-shrink-0" />
            <span>{{ error }}</span>
          </div>
          <p v-if="attemptsRemaining !== null" class="mt-1 mr-6 text-xs">
            تلاش‌های باقی مانده: {{ attemptsRemaining }}
          </p>
        </div>

        <!-- Tabs -->
        <div class="flex gap-2 mb-6 p-1 bg-secondary-100 rounded-xl">
          <button
            @click="activeTab = 'totp'"
            :class="[
              'flex-1 py-2.5 px-4 rounded-lg text-sm font-medium transition flex items-center justify-center gap-2',
              activeTab === 'totp'
                ? 'bg-white text-primary-600 shadow-sm'
                : 'text-secondary-600 hover:text-secondary-800'
            ]"
          >
            <KeyRound class="w-4 h-4" />
            کد TOTP
          </button>
          <button
            @click="activeTab = 'recovery'"
            :class="[
              'flex-1 py-2.5 px-4 rounded-lg text-sm font-medium transition flex items-center justify-center gap-2',
              activeTab === 'recovery'
                ? 'bg-white text-primary-600 shadow-sm'
                : 'text-secondary-600 hover:text-secondary-800'
            ]"
          >
            <RefreshCw class="w-4 h-4" />
            کد بازیابی
          </button>
        </div>

        <!-- TOTP Form -->
        <form v-if="activeTab === 'totp'" @submit.prevent="verifyTotp" class="space-y-5">
          <div>
            <label class="block text-sm font-medium text-secondary-700 mb-2">
              کد 6 رقمی TOTP
            </label>
            <div class="relative">
              <input
                v-model="totpCode"
                type="text"
                maxlength="6"
                inputmode="numeric"
                pattern="[0-9]*"
                placeholder="123456"
                class="w-full h-14 text-center text-2xl tracking-[0.5em] font-mono rounded-xl border-2 border-secondary-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-100 transition dir-ltr"
                dir="ltr"
                :disabled="isLoading || !tempToken"
                @input="totpCode = totpCode.replace(/\D/g, '')"
              />
            </div>
            <p class="text-xs text-secondary-500 mt-2 text-center">
              کد را از اپلیکیشن Google Authenticator یا Microsoft Authenticator وارد کنید
            </p>
          </div>

          <button
            type="submit"
            :disabled="isLoading || !isTotpValid || !tempToken"
            class="w-full h-12 bg-primary-500 hover:bg-primary-600 disabled:bg-primary-300 text-white font-bold rounded-xl transition flex items-center justify-center gap-2"
          >
            <RefreshCw v-if="isLoading" class="w-5 h-5 animate-spin" />
            <span>{{ isLoading ? 'در حال تایید...' : 'تایید و ورود' }}</span>
          </button>
        </form>

        <!-- Recovery Code Form -->
        <form v-else @submit.prevent="verifyRecoveryCode" class="space-y-5">
          <div>
            <label class="block text-sm font-medium text-secondary-700 mb-2">
              کد بازیابی
            </label>
            <div class="relative">
              <input
                v-model="recoveryCode"
                type="text"
                maxlength="10"
                placeholder="ABCDEF1234"
                class="w-full h-14 text-center text-xl tracking-[0.3em] font-mono uppercase rounded-xl border-2 border-secondary-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-100 transition dir-ltr"
                dir="ltr"
                :disabled="isLoading || !tempToken"
                @input="recoveryCode = recoveryCode.replace(/[^A-Za-z0-9]/g, '').toUpperCase()"
              />
            </div>
            <p class="text-xs text-secondary-500 mt-2 text-center">
              کد 10 کاراکتری بازیابی که هنگام فعال‌سازی MFA دریافت کردید
            </p>
          </div>

          <button
            type="submit"
            :disabled="isLoading || !isRecoveryValid || !tempToken"
            class="w-full h-12 bg-primary-500 hover:bg-primary-600 disabled:bg-primary-300 text-white font-bold rounded-xl transition flex items-center justify-center gap-2"
          >
            <RefreshCw v-if="isLoading" class="w-5 h-5 animate-spin" />
            <span>{{ isLoading ? 'در حال تایید...' : 'تایید با کد بازیابی' }}</span>
          </button>
        </form>

        <!-- Back Button -->
        <button
          @click="goBack"
          class="w-full mt-4 py-3 text-secondary-600 hover:text-secondary-800 text-sm font-medium flex items-center justify-center gap-2 transition"
        >
          <ArrowLeft class="w-4 h-4" />
          بازگشت به صفحه ورود
        </button>
      </div>

      <!-- Help Text -->
      <div class="mt-6 text-center">
        <p class="text-xs text-secondary-500">
          مشکل در ورود؟
          <RouterLink to="/forgot-password" class="text-primary-600 hover:text-primary-700">
            بازیابی رمز عبور
          </RouterLink>
        </p>
      </div>
    </div>
  </div>
</template>
