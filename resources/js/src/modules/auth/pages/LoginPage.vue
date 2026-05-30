<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Mail, Lock, Eye, EyeOff, Loader2 } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { authService } from '../services'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const cartStore = useCartStore()

const form = ref({
  email: '',
  password: '',
})

const showPassword = ref(false)
const isLoading = ref(false)
const error = ref('')

const redirectTo = computed(() => {
  return (route.query.redirect as string) || '/'
})

const handleSubmit = async () => {
  if (!form.value.email || !form.value.password) {
    error.value = 'لطفاً ایمیل و رمز عبور را وارد کنید'
    return
  }

  isLoading.value = true
  error.value = ''

  try {
    const response = await authService.login(form.value)

    // IAM response: { user, tokens: { access_token, refresh_token, ... } }
    authStore.setToken(response.tokens.access_token)
    localStorage.setItem('refresh_token', response.tokens.refresh_token)

    // Get roles (not included in login response — fetch separately)
    const roles = await authService.getMyRoles()

    authStore.setUser({
      ...response.user,
      roles,
      is_admin: response.user.is_super_admin || roles.some(r => ['superadmin', 'admin', 'owner'].includes(r)),
    })

    // Merge guest cart with user cart
    await cartStore.mergeGuestCart()

    // Redirect based on role
    router.push(redirectTo.value !== '/' && redirectTo.value ? redirectTo.value : (authStore.isAdmin ? '/admin/dashboard' : '/'))
  } catch (err: any) {
    console.error('Login error:', err)
    if (err.response?.status === 401) {
      error.value = 'ایمیل یا رمز عبور اشتباه است'
    } else if (err.response?.data?.message) {
      error.value = err.response.data.message
    } else {
      error.value = 'خطا در ورود. لطفاً دوباره تلاش کنید'
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-secondary-50 flex items-center justify-center py-12 px-4">
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

        <!-- Error Message -->
        <div v-if="error" class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-6 text-sm">
          {{ error }}
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-5">
          <!-- Email -->
          <div>
            <label class="block text-sm font-medium text-secondary-700 mb-2">ایمیل</label>
            <div class="relative">
              <input
                v-model="form.email"
                type="email"
                placeholder="example@email.com"
                class="w-full h-12 pl-4 pr-12 rounded-xl border-2 border-secondary-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-100 transition text-left dir-ltr"
                dir="ltr"
              />
              <Mail class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400" />
            </div>
          </div>

          <!-- Password -->
          <div>
            <label class="block text-sm font-medium text-secondary-700 mb-2">رمز عبور</label>
            <div class="relative">
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="رمز عبور خود را وارد کنید"
                class="w-full h-12 px-12 rounded-xl border-2 border-secondary-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-100 transition"
              />
              <Lock class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400" />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute left-4 top-1/2 -translate-y-1/2 text-secondary-400 hover:text-secondary-600"
              >
                <EyeOff v-if="showPassword" class="w-5 h-5" />
                <Eye v-else class="w-5 h-5" />
              </button>
            </div>
          </div>

          <!-- Forgot -->
          <div class="flex justify-end">
            <RouterLink to="/forgot-password" class="text-sm text-primary-600 hover:text-primary-700">فراموشی رمز عبور؟</RouterLink>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="isLoading"
            class="w-full h-12 bg-primary-500 hover:bg-primary-600 disabled:bg-primary-300 text-white font-bold rounded-xl transition flex items-center justify-center gap-2"
          >
            <Loader2 v-if="isLoading" class="w-5 h-5 animate-spin" />
            <span>{{ isLoading ? 'در حال ورود...' : 'ورود' }}</span>
          </button>
        </form>

        <!-- Register Link -->
        <p class="text-center mt-6 text-secondary-600">
          حساب کاربری ندارید؟
          <RouterLink to="/register" class="text-primary-600 hover:text-primary-700 font-medium">ثبت‌نام کنید</RouterLink>
        </p>
      </div>

      <!-- Back to Home -->
      <p class="text-center mt-6">
        <RouterLink to="/" class="text-secondary-500 hover:text-primary-600 text-sm">
          ← بازگشت به صفحه اصلی
        </RouterLink>
      </p>
    </div>
  </div>
</template>
