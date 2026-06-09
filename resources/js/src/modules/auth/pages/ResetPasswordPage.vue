<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { Lock, Eye, EyeOff, Loader2, CheckCircle2, AlertCircle } from 'lucide-vue-next'
import { authService } from '../services'

const route = useRoute()
const router = useRouter()

const form = ref({
  password: '',
  password_confirmation: '',
})

const showPassword = ref(false)
const showConfirm = ref(false)
const isLoading = ref(false)
const error = ref('')
const success = ref(false)
const tokenMissing = ref(false)

const token = computed(() => route.query.token as string | undefined)
const email = computed(() => route.query.email as string | undefined)

onMounted(() => {
  if (!token.value || !email.value) {
    tokenMissing.value = true
  }
})

const handleSubmit = async () => {
  error.value = ''

  if (!form.value.password || form.value.password.length < 8) {
    error.value = 'رمز عبور باید حداقل ۸ کاراکتر باشد'
    return
  }

  if (form.value.password !== form.value.password_confirmation) {
    error.value = 'رمز عبور جدید و تکرار آن مطابقت ندارند'
    return
  }

  isLoading.value = true

  try {
    await authService.resetPassword({
      token: token.value!,
      email: email.value!,
      password: form.value.password,
      password_confirmation: form.value.password_confirmation,
    })
    success.value = true
    setTimeout(() => router.push('/login'), 3000)
  } catch (err: any) {
    const data = err.response?.data
    if (data?.data && typeof data.data === 'object') {
      error.value = (Object.values(data.data).flat() as string[]).join('، ')
    } else {
      error.value = data?.message || 'خطا در بازنشانی رمز عبور. لینک ممکن است منقضی شده باشد.'
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
        <RouterLink to="/">
          <img src="/images/Logo/tejarat-logo-500.png" alt="لوگو فروشگاه" class="h-12 w-auto object-contain">
        </RouterLink>
      </div>

      <div class="bg-white rounded-2xl shadow-xl p-8">
        <!-- Invalid/missing token -->
        <div v-if="tokenMissing" class="text-center py-4">
          <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
            <AlertCircle class="w-9 h-9 text-red-400" />
          </div>
          <h1 class="text-xl font-bold text-secondary-800 mb-2">لینک نامعتبر</h1>
          <p class="text-secondary-500 text-sm mb-6">
            این لینک بازنشانی رمز عبور نامعتبر یا منقضی شده است.
          </p>
          <RouterLink
            to="/forgot-password"
            class="inline-block px-6 py-2.5 bg-primary-500 hover:bg-primary-600 text-white rounded-xl font-medium text-sm transition"
          >
            درخواست لینک جدید
          </RouterLink>
        </div>

        <!-- Success state -->
        <div v-else-if="success" class="text-center py-4">
          <div class="w-16 h-16 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-4">
            <CheckCircle2 class="w-9 h-9 text-emerald-500" />
          </div>
          <h1 class="text-xl font-bold text-secondary-800 mb-2">رمز عبور تغییر کرد</h1>
          <p class="text-secondary-500 text-sm mb-6">
            رمز عبور شما با موفقیت بازنشانی شد. در حال انتقال به صفحه ورود...
          </p>
          <RouterLink to="/login" class="text-primary-600 hover:text-primary-700 font-medium text-sm">
            ورود به حساب
          </RouterLink>
        </div>

        <!-- Reset form -->
        <template v-else>
          <h1 class="text-2xl font-bold text-secondary-800 text-center mb-2">بازنشانی رمز عبور</h1>
          <p class="text-secondary-500 text-center text-sm mb-8">رمز عبور جدید خود را وارد کنید.</p>

          <!-- Error -->
          <div v-if="error" class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-6 text-sm">
            {{ error }}
          </div>

          <form @submit.prevent="handleSubmit" class="space-y-5">
            <!-- New password -->
            <div>
              <label for="reset-password" class="block text-sm font-medium text-secondary-700 mb-2">
                رمز عبور جدید
              </label>
              <div class="relative">
                <input
                  id="reset-password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  placeholder="حداقل ۸ کاراکتر"
                  required
                  :disabled="isLoading"
                  class="w-full h-12 px-12 rounded-xl border-2 border-secondary-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-100 transition disabled:opacity-60"
                />
                <Lock class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400" />
                <button
                  type="button"
                  @click="showPassword = !showPassword"
                  class="absolute left-4 top-1/2 -translate-y-1/2 text-secondary-400 hover:text-secondary-600"
                  :aria-label="showPassword ? 'پنهان کردن رمز عبور' : 'نمایش رمز عبور'"
                >
                  <EyeOff v-if="showPassword" class="w-5 h-5" />
                  <Eye v-else class="w-5 h-5" />
                </button>
              </div>
            </div>

            <!-- Confirm password -->
            <div>
              <label for="reset-password-confirm" class="block text-sm font-medium text-secondary-700 mb-2">
                تکرار رمز عبور جدید
              </label>
              <div class="relative">
                <input
                  id="reset-password-confirm"
                  v-model="form.password_confirmation"
                  :type="showConfirm ? 'text' : 'password'"
                  placeholder="رمز عبور را دوباره وارد کنید"
                  required
                  :disabled="isLoading"
                  class="w-full h-12 px-12 rounded-xl border-2 border-secondary-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-100 transition disabled:opacity-60"
                />
                <Lock class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400" />
                <button
                  type="button"
                  @click="showConfirm = !showConfirm"
                  class="absolute left-4 top-1/2 -translate-y-1/2 text-secondary-400 hover:text-secondary-600"
                  :aria-label="showConfirm ? 'پنهان کردن تکرار رمز عبور' : 'نمایش تکرار رمز عبور'"
                >
                  <EyeOff v-if="showConfirm" class="w-5 h-5" />
                  <Eye v-else class="w-5 h-5" />
                </button>
              </div>
            </div>

            <button
              type="submit"
              :disabled="isLoading"
              class="w-full h-12 bg-primary-500 hover:bg-primary-600 disabled:bg-primary-300 text-white font-bold rounded-xl transition flex items-center justify-center gap-2"
            >
              <Loader2 v-if="isLoading" class="w-5 h-5 animate-spin" />
              <span>{{ isLoading ? 'در حال ذخیره...' : 'ذخیره رمز عبور جدید' }}</span>
            </button>
          </form>
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
