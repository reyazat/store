<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { Mail, Loader2, CheckCircle2, ArrowRight } from 'lucide-vue-next'
import { authService } from '../services'

const email = ref('')
const isLoading = ref(false)
const error = ref('')
const submitted = ref(false)

const handleSubmit = async () => {
  if (!email.value.trim()) {
    error.value = 'لطفاً آدرس ایمیل خود را وارد کنید'
    return
  }

  isLoading.value = true
  error.value = ''

  try {
    await authService.forgotPassword(email.value.trim())
    submitted.value = true
  } catch (err: any) {
    const message = err.response?.data?.message
    error.value = message || 'خطا در ارسال ایمیل. لطفاً دوباره تلاش کنید.'
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

      <div class="bg-white rounded-2xl shadow-xl p-8">
        <!-- Success state -->
        <div v-if="submitted" class="text-center py-4">
          <div class="w-16 h-16 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-4">
            <CheckCircle2 class="w-9 h-9 text-emerald-500" />
          </div>
          <h1 class="text-xl font-bold text-secondary-800 mb-2">ایمیل ارسال شد</h1>
          <p class="text-secondary-500 text-sm leading-relaxed mb-6">
            اگر این آدرس ایمیل در سیستم ثبت شده باشد، لینک بازنشانی رمز عبور برای
            <span class="font-medium text-secondary-700">{{ email }}</span>
            ارسال خواهد شد.
          </p>
          <p class="text-xs text-secondary-400 mb-6">
            ایمیل را دریافت نکردید؟ پوشه اسپم را بررسی کنید یا چند دقیقه صبر کنید.
          </p>
          <RouterLink
            to="/login"
            class="inline-flex items-center gap-2 text-primary-600 hover:text-primary-700 font-medium text-sm"
          >
            <ArrowRight class="w-4 h-4" />
            بازگشت به صفحه ورود
          </RouterLink>
        </div>

        <!-- Form state -->
        <template v-else>
          <h1 class="text-2xl font-bold text-secondary-800 text-center mb-2">فراموشی رمز عبور</h1>
          <p class="text-secondary-500 text-center text-sm mb-8">
            ایمیل حساب خود را وارد کنید تا لینک بازنشانی برایتان ارسال شود.
          </p>

          <!-- Error -->
          <div v-if="error" class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-6 text-sm">
            {{ error }}
          </div>

          <form @submit.prevent="handleSubmit" class="space-y-5">
            <div>
              <label for="forgot-email" class="block text-sm font-medium text-secondary-700 mb-2">ایمیل</label>
              <div class="relative">
                <input
                  id="forgot-email"
                  v-model="email"
                  type="email"
                  placeholder="example@email.com"
                  required
                  :disabled="isLoading"
                  class="w-full h-12 pl-4 pr-12 rounded-xl border-2 border-secondary-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-100 transition disabled:opacity-60"
                  dir="ltr"
                />
                <Mail class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400" />
              </div>
            </div>

            <button
              type="submit"
              :disabled="isLoading"
              class="w-full h-12 bg-primary-500 hover:bg-primary-600 disabled:bg-primary-300 text-white font-bold rounded-xl transition flex items-center justify-center gap-2"
            >
              <Loader2 v-if="isLoading" class="w-5 h-5 animate-spin" />
              <span>{{ isLoading ? 'در حال ارسال...' : 'ارسال لینک بازنشانی' }}</span>
            </button>
          </form>

          <p class="text-center mt-6 text-secondary-600 text-sm">
            رمز عبور خود را به خاطر آوردید؟
            <RouterLink to="/login" class="text-primary-600 hover:text-primary-700 font-medium">ورود</RouterLink>
          </p>
        </template>
      </div>

      <p class="text-center mt-6">
        <RouterLink to="/" class="text-secondary-500 hover:text-primary-600 text-sm">
          ← بازگشت به صفحه اصلی
        </RouterLink>
      </p>
    </div>
  </div>
</template>
