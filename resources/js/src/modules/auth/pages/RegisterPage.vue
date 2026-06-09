<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { Mail, Lock, Eye, EyeOff, Loader2, User, Phone } from 'lucide-vue-next'
import { authService } from '../services'

const router = useRouter()

const form = ref({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
})

const showPassword = ref(false)
const isLoading = ref(false)
const error = ref('')
const success = ref(false)

const handleSubmit = async () => {
  if (!form.value.name || !form.value.email || !form.value.password) {
    error.value = 'لطفاً تمام فیلدها را پر کنید'
    return
  }

  if (form.value.password !== form.value.password_confirmation) {
    error.value = 'رمز عبور و تکرار آن مطابقت ندارند'
    return
  }

  if (form.value.password.length < 8) {
    error.value = 'رمز عبور باید حداقل ۸ کاراکتر باشد'
    return
  }

  isLoading.value = true
  error.value = ''

  try {
    await authService.register({
      name: form.value.name,
      email: form.value.email,
      password: form.value.password,
      password_confirmation: form.value.password_confirmation,
      phone: form.value.phone || undefined,
    })
    success.value = true
    setTimeout(() => {
      router.push('/login')
    }, 2000)
  } catch (err: any) {
    console.error('Register error:', err)
    const data = err.response?.data
    if (data?.data && typeof data.data === 'object') {
      const msgs = Object.values(data.data).flat()
      error.value = (msgs as string[]).join('، ')
    } else if (data?.message) {
      error.value = data.message
    } else {
      error.value = 'خطا در ثبت‌نام. لطفاً دوباره تلاش کنید'
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
          <div class="w-14 h-14 rounded-xl flex items-center justify-center">
                      <img src="/images/Logo/tejarat-logo-500.png" alt="لوگو فروشگاه" class="h-12 w-auto object-contain">
          </div>
          <div class="text-right">
            <span class="text-2xl font-bold text-primary-600">اتکا</span>
            <span class="block text-sm text-secondary-500">تجارت الکترونیک</span>
          </div>
        </RouterLink>
      </div>

      <!-- Register Card -->
      <div class="bg-white rounded-2xl shadow-xl p-8">
        <h1 class="text-2xl font-bold text-secondary-800 text-center mb-2">ثبت‌نام</h1>
        <p class="text-secondary-500 text-center mb-8">یک حساب کاربری جدید بسازید</p>

        <!-- Success Message -->
        <div v-if="success" class="bg-green-50 border border-green-200 text-green-600 px-4 py-3 rounded-xl mb-6 text-sm text-center">
          ثبت‌نام با موفقیت انجام شد! در حال انتقال به صفحه ورود...
        </div>

        <!-- Error Message -->
        <div v-if="error" class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-6 text-sm">
          {{ error }}
        </div>

        <form v-if="!success" @submit.prevent="handleSubmit" class="space-y-4">
          <!-- Name -->
          <div>
            <label class="block text-sm font-medium text-secondary-700 mb-2">نام و نام خانوادگی</label>
            <div class="relative">
              <input
                v-model="form.name"
                type="text"
                placeholder="نام کامل خود را وارد کنید"
                class="w-full h-12 pl-4 pr-12 rounded-xl border-2 border-secondary-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-100 transition"
              />
              <User class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400" />
            </div>
          </div>

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

          <!-- Phone -->
          <div>
            <label class="block text-sm font-medium text-secondary-700 mb-2">شماره موبایل (اختیاری)</label>
            <div class="relative">
              <input
                v-model="form.phone"
                type="tel"
                placeholder="۰۹۱۲۳۴۵۶۷۸۹"
                class="w-full h-12 pl-4 pr-12 rounded-xl border-2 border-secondary-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-100 transition"
              />
              <Phone class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400" />
            </div>
          </div>

          <!-- Password -->
          <div>
            <label class="block text-sm font-medium text-secondary-700 mb-2">رمز عبور</label>
            <div class="relative">
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="حداقل ۸ کاراکتر"
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

          <!-- Password Confirmation -->
          <div>
            <label class="block text-sm font-medium text-secondary-700 mb-2">تکرار رمز عبور</label>
            <div class="relative">
              <input
                v-model="form.password_confirmation"
                :type="showPassword ? 'text' : 'password'"
                placeholder="رمز عبور را دوباره وارد کنید"
                class="w-full h-12 pl-4 pr-12 rounded-xl border-2 border-secondary-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-100 transition"
              />
              <Lock class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400" />
            </div>
          </div>

          <!-- Terms -->
          <label class="flex items-start gap-2 cursor-pointer">
            <input type="checkbox" class="w-4 h-4 mt-1 rounded border-secondary-300 text-primary-600 focus:ring-primary-500" required />
            <span class="text-sm text-secondary-600">
              با <RouterLink to="/terms" class="text-primary-600">قوانین و مقررات</RouterLink> سایت موافقم
            </span>
          </label>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="isLoading"
            class="w-full h-12 bg-primary-500 hover:bg-primary-600 disabled:bg-primary-300 text-white font-bold rounded-xl transition flex items-center justify-center gap-2"
          >
            <Loader2 v-if="isLoading" class="w-5 h-5 animate-spin" />
            <span>{{ isLoading ? 'در حال ثبت‌نام...' : 'ثبت‌نام' }}</span>
          </button>
        </form>

        <!-- Login Link -->
        <p class="text-center mt-6 text-secondary-600">
          قبلاً ثبت‌نام کرده‌اید؟
          <RouterLink to="/login" class="text-primary-600 hover:text-primary-700 font-medium">وارد شوید</RouterLink>
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
