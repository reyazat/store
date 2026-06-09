<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { KeyRound, Eye, EyeOff, Loader2, AlertCircle, CheckCircle, XCircle } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { authService } from '../services'
import { scheduleTokenRefresh } from '@shared/utils/api'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const cartStore = useCartStore()

// Query params
const tempToken = ref<string>((route.query.temp_token as string) || '')
const email = ref<string>((route.query.email as string) || '')
const redirectTo = ref<string>((route.query.redirect as string) || '/')

// Form state
const newPassword = ref('')
const confirmPassword = ref('')
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)

// UI state
const isLoading = ref(false)
const error = ref('')
const fieldErrors = ref<string[]>([])
const passwordChanged = ref(false)

// ── Password strength ──────────────────────────────────────────────────────────

const passwordStrength = computed(() => {
  const p = newPassword.value
  let score = 0
  if (p.length >= 8) score++
  if (/[A-Z]/.test(p)) score++
  if (/[a-z]/.test(p)) score++
  if (/[0-9]/.test(p)) score++
  if (/[^A-Za-z0-9]/.test(p)) score++
  return score
})

const strengthMeta = computed(() => {
  const s = passwordStrength.value
  if (s <= 2) return { text: 'ضعیف', colorBar: 'bg-red-500', colorText: 'text-red-600' }
  if (s === 3) return { text: 'متوسط', colorBar: 'bg-yellow-500', colorText: 'text-yellow-600' }
  if (s === 4) return { text: 'قوی', colorBar: 'bg-blue-500', colorText: 'text-blue-600' }
  return { text: 'بسیار قوی', colorBar: 'bg-green-500', colorText: 'text-green-600' }
})

// ── Validation ──────────────────────────────────────────────────────────────────

const passwordChecks = computed(() => {
  const p = newPassword.value
  return [
    { label: 'حداقل ۸ کاراکتر', ok: p.length >= 8 },
    { label: 'حداقل یک حرف بزرگ', ok: /[A-Z]/.test(p) },
    { label: 'حداقل یک حرف کوچک', ok: /[a-z]/.test(p) },
    { label: 'حداقل یک عدد', ok: /[0-9]/.test(p) },
    { label: 'حداقل یک نماد (!@#$...)', ok: /[^A-Za-z0-9]/.test(p) },
  ]
})

const isPasswordValid = computed(() => passwordChecks.value.every((c) => c.ok))
const isConfirmValid = computed(() =>
  confirmPassword.value !== '' && newPassword.value === confirmPassword.value,
)
const isFormValid = computed(() => isPasswordValid.value && isConfirmValid.value)

// ── Guard ───────────────────────────────────────────────────────────────────────

onMounted(() => {
  if (!tempToken.value) {
    router.replace('/login')
  }
})

// ── Submit ──────────────────────────────────────────────────────────────────────

const handleSubmit = async () => {
  if (!isFormValid.value) {
    if (!isPasswordValid.value) error.value = 'رمز عبور جدید شرایط لازم را ندارد'
    else error.value = 'تکرار رمز عبور مطابقت ندارد'
    return
  }

  isLoading.value = true
  error.value = ''
  fieldErrors.value = []

  try {
    const response = await authService.forceChangePasswordLogin(
      {
        new_password: newPassword.value,
        new_password_confirmation: confirmPassword.value,
      },
      tempToken.value,
    )

    if (response.success && response.data?.tokens && response.data?.user) {
      passwordChanged.value = true

      // Complete login with new tokens — user doesn't have to log in again
      const { user, tokens } = response.data
      authStore.setToken(tokens.access_token)
      localStorage.setItem('refresh_token', tokens.refresh_token)
      const expiresAt = Date.now() + tokens.expires_in * 1000
      localStorage.setItem('token_expires_at', expiresAt.toString())
      scheduleTokenRefresh(tokens.expires_in)

      const roles = await authService.getMyRoles()
      authStore.setUser({
        ...user,
        roles,
        is_admin:
          user.is_super_admin ||
          roles.some((r: string) => ['superadmin', 'admin', 'owner'].includes(r)),
      })

      await cartStore.mergeGuestCart()

      // Short delay so the success screen is visible, then redirect
      setTimeout(() => {
        router.push(
          redirectTo.value !== '/' && redirectTo.value
            ? redirectTo.value
            : authStore.isAdmin
              ? '/admin/dashboard'
              : '/',
        )
      }, 2000)
    } else {
      error.value = response.message || 'خطا در تغییر رمز عبور'
    }
  } catch (err: unknown) {
    const e = err as {
      response?: {
        status?: number
        data?: {
          message?: string
          data?: { errors?: string[] }
          errors?: Record<string, string[]>
        }
      }
    }
    const status = e.response?.status
    const message = e.response?.data?.message

    if (status === 422) {
      // Validation errors from backend
      const backendErrors = e.response?.data?.data?.errors
      const validationErrors = e.response?.data?.errors

      if (backendErrors && backendErrors.length > 0) {
        fieldErrors.value = backendErrors
        error.value = 'رمز عبور شرایط امنیتی را برآورده نمی‌کند'
      } else if (validationErrors) {
        fieldErrors.value = Object.values(validationErrors).flat()
        error.value = 'اطلاعات وارد شده معتبر نیست'
      } else {
        error.value = message || 'اطلاعات وارد شده معتبر نیست'
      }
    } else if (status === 403) {
      error.value = 'توکن نامعتبر است. لطفاً دوباره وارد شوید.'
      setTimeout(() => router.replace('/login'), 2500)
    } else if (status === 401) {
      error.value = 'نشست منقضی شده است. لطفاً دوباره وارد شوید.'
      setTimeout(() => router.replace('/login'), 2500)
    } else if (message) {
      error.value = message
    } else {
      error.value = 'خطا در تغییر رمز عبور. لطفاً دوباره تلاش کنید'
    }
  } finally {
    isLoading.value = false
  }
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

        <!-- ══ Success State ══════════════════════════════════════════════ -->
        <template v-if="passwordChanged">
          <div class="text-center py-6">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-5">
              <CheckCircle class="w-10 h-10 text-green-600" />
            </div>
            <h2 class="text-xl font-bold text-green-700 mb-2">رمز عبور تغییر یافت!</h2>
            <p class="text-secondary-500 text-sm">در حال انتقال به صفحه اصلی…</p>
            <div class="mt-4 flex justify-center">
              <Loader2 class="w-5 h-5 animate-spin text-primary-500" />
            </div>
          </div>
        </template>

        <!-- ══ Form ══════════════════════════════════════════════════════ -->
        <template v-else>
          <div class="text-center mb-7">
            <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <KeyRound class="w-8 h-8 text-amber-600" />
            </div>
            <h1 class="text-2xl font-bold text-secondary-800">تغییر رمز عبور</h1>
            <p class="text-secondary-500 mt-2 text-sm">
              به دلایل امنیتی، رمز عبور جدیدی انتخاب کنید
              <span v-if="email" class="block text-xs mt-1 text-secondary-400" dir="ltr">{{ email }}</span>
            </p>
          </div>

          <!-- Error -->
          <div
            v-if="error"
            role="alert"
            class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-5 text-sm"
          >
            <div class="flex items-start gap-2">
              <AlertCircle class="w-4 h-4 mt-0.5 flex-shrink-0" />
              <div>
                <p>{{ error }}</p>
                <ul v-if="fieldErrors.length" class="mt-2 space-y-1 list-disc list-inside text-xs">
                  <li v-for="fe in fieldErrors" :key="fe">{{ fe }}</li>
                </ul>
              </div>
            </div>
          </div>

          <form @submit.prevent="handleSubmit" novalidate class="space-y-5">

            <!-- New Password -->
            <div>
              <label for="new-password" class="block text-sm font-medium text-secondary-700 mb-2">
                رمز عبور جدید
              </label>
              <div class="relative">
                <input
                  id="new-password"
                  v-model="newPassword"
                  :type="showNewPassword ? 'text' : 'password'"
                  placeholder="رمز عبور جدید"
                  autocomplete="new-password"
                  class="w-full h-12 pl-12 pr-12 rounded-xl border-2 border-secondary-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition"
                  :disabled="isLoading"
                />
                <KeyRound class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400 pointer-events-none" />
                <button
                  type="button"
                  @click="showNewPassword = !showNewPassword"
                  class="absolute left-4 top-1/2 -translate-y-1/2 text-secondary-400 hover:text-secondary-600"
                  :aria-label="showNewPassword ? 'پنهان' : 'نمایش'"
                >
                  <EyeOff v-if="showNewPassword" class="w-5 h-5" />
                  <Eye v-else class="w-5 h-5" />
                </button>
              </div>

              <!-- Strength bar -->
              <div v-if="newPassword" class="mt-2">
                <div class="flex justify-between text-xs mb-1">
                  <span class="text-secondary-500">قدرت رمز عبور</span>
                  <span :class="strengthMeta.colorText" class="font-medium">{{ strengthMeta.text }}</span>
                </div>
                <div class="h-1.5 bg-secondary-100 rounded-full overflow-hidden">
                  <div
                    :class="strengthMeta.colorBar"
                    class="h-full rounded-full transition-all duration-300"
                    :style="{ width: `${(passwordStrength / 5) * 100}%` }"
                  />
                </div>
              </div>

              <!-- Requirements checklist -->
              <ul v-if="newPassword" class="mt-3 space-y-1">
                <li
                  v-for="check in passwordChecks"
                  :key="check.label"
                  class="flex items-center gap-1.5 text-xs"
                  :class="check.ok ? 'text-green-600' : 'text-secondary-500'"
                >
                  <CheckCircle v-if="check.ok" class="w-3.5 h-3.5" />
                  <XCircle v-else class="w-3.5 h-3.5 text-secondary-300" />
                  {{ check.label }}
                </li>
              </ul>
            </div>

            <!-- Confirm Password -->
            <div>
              <label for="confirm-password" class="block text-sm font-medium text-secondary-700 mb-2">
                تکرار رمز عبور
              </label>
              <div class="relative">
                <input
                  id="confirm-password"
                  v-model="confirmPassword"
                  :type="showConfirmPassword ? 'text' : 'password'"
                  placeholder="تکرار رمز عبور جدید"
                  autocomplete="new-password"
                  class="w-full h-12 pl-12 pr-12 rounded-xl border-2 transition outline-none focus:ring-2 focus:ring-primary-100"
                  :class="
                    confirmPassword
                      ? isConfirmValid
                        ? 'border-green-400 focus:border-green-500'
                        : 'border-red-300 focus:border-red-400'
                      : 'border-secondary-200 focus:border-primary-500'
                  "
                  :disabled="isLoading"
                />
                <KeyRound class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400 pointer-events-none" />
                <button
                  type="button"
                  @click="showConfirmPassword = !showConfirmPassword"
                  class="absolute left-4 top-1/2 -translate-y-1/2 text-secondary-400 hover:text-secondary-600"
                  :aria-label="showConfirmPassword ? 'پنهان' : 'نمایش'"
                >
                  <EyeOff v-if="showConfirmPassword" class="w-5 h-5" />
                  <Eye v-else class="w-5 h-5" />
                </button>
              </div>
              <p
                v-if="confirmPassword && !isConfirmValid"
                class="mt-1.5 text-xs text-red-500 flex items-center gap-1"
              >
                <XCircle class="w-3.5 h-3.5" />
                رمز عبور مطابقت ندارد
              </p>
              <p
                v-else-if="isConfirmValid"
                class="mt-1.5 text-xs text-green-600 flex items-center gap-1"
              >
                <CheckCircle class="w-3.5 h-3.5" />
                رمز عبور مطابقت دارد
              </p>
            </div>

            <!-- Submit -->
            <button
              type="submit"
              :disabled="isLoading || !isFormValid"
              class="w-full h-12 bg-amber-500 hover:bg-amber-600 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold rounded-xl transition flex items-center justify-center gap-2"
            >
              <Loader2 v-if="isLoading" class="w-5 h-5 animate-spin" />
              <span>{{ isLoading ? 'در حال ثبت...' : 'تغییر رمز عبور و ورود' }}</span>
            </button>
          </form>
        </template>
      </div>
    </div>
  </div>
</template>
