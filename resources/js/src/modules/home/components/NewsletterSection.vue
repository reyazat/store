<script setup lang="ts">
import { ref } from 'vue'
import { homeService } from '../services'

const email = ref('')
const submitting = ref(false)
const message = ref<{ type: 'success' | 'error'; text: string } | null>(null)

const submit = async (event: Event) => {
  event.preventDefault()
  message.value = null

  if (!email.value) {
    message.value = { type: 'error', text: 'لطفاً ایمیل را وارد کنید.' }
    return
  }

  submitting.value = true
  try {
    await homeService.subscribeNewsletter(email.value, 'home')
    message.value = { type: 'success', text: 'عضویت شما با موفقیت ثبت شد!' }
    email.value = ''
  } catch (error: unknown) {
    const text =
      (error as { response?: { data?: { message?: string } } })?.response?.data?.message
      ?? 'خطا در ثبت عضویت. لطفاً دوباره تلاش کنید.'
    message.value = { type: 'error', text }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <section class="py-12 bg-gradient-to-l from-primary-600 to-primary-800">
    <div class="container mx-auto px-4">
      <div class="max-w-2xl mx-auto text-center text-white">
        <h2 class="text-2xl md:text-3xl font-bold mb-4">از تخفیف‌ها باخبر شوید!</h2>
        <p class="text-primary-100 mb-6">با عضویت در خبرنامه، از جدیدترین تخفیف‌ها و محصولات مطلع شوید.</p>

        <form
          class="flex flex-col sm:flex-row gap-3 max-w-md mx-auto"
          novalidate
          @submit="submit"
        >
          <label for="newsletter-email" class="sr-only">ایمیل خبرنامه</label>
          <input
            id="newsletter-email"
            v-model="email"
            type="email"
            required
            :disabled="submitting"
            placeholder="ایمیل خود را وارد کنید"
            class="flex-1 px-5 py-3 rounded-full text-secondary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 disabled:opacity-60"
          />
          <button
            type="submit"
            :disabled="submitting"
            class="px-8 py-3 bg-white text-primary-600 rounded-full font-bold hover:bg-primary-50 transition disabled:opacity-60"
          >
            {{ submitting ? 'در حال ارسال...' : 'عضویت' }}
          </button>
        </form>

        <p
          v-if="message"
          :class="['mt-4 text-sm', message.type === 'success' ? 'text-emerald-100' : 'text-red-200']"
          role="status"
          aria-live="polite"
        >
          {{ message.text }}
        </p>
      </div>
    </div>
  </section>
</template>
