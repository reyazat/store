<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { CheckCircle, XCircle, AlertCircle, ArrowRight } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()

const status = computed(() => route.query.status as string)
const orderId = computed(() => route.query.order_id as string | undefined)
const refId = computed(() => route.query.ref_id as string | undefined)

const isSuccess = computed(() => status.value === 'success')
const isCancelled = computed(() => status.value === 'cancelled')
const isFailed = computed(() => status.value === 'failed' || status.value === 'error')
</script>

<template>
  <div class="container mx-auto px-4 py-16 max-w-lg text-center">

    <!-- Success -->
    <div v-if="isSuccess" class="bg-white rounded-2xl shadow-sm p-10">
      <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-5">
        <CheckCircle class="w-10 h-10 text-green-500" />
      </div>
      <h1 class="text-2xl font-bold text-gray-800 mb-2">پرداخت موفق</h1>
      <p class="text-gray-500 mb-1">پرداخت شما با موفقیت انجام شد.</p>
      <p v-if="refId" class="text-sm text-gray-400 mb-6">کد پیگیری: <span class="font-mono font-medium text-gray-600">{{ refId }}</span></p>
      <div class="flex flex-col sm:flex-row gap-3 justify-center mt-6">
        <RouterLink
          v-if="orderId"
          :to="`/user/orders/${orderId}`"
          class="flex items-center justify-center gap-2 px-6 py-2.5 bg-primary-500 hover:bg-primary-600 text-white rounded-xl font-medium transition text-sm"
        >
          <ArrowRight class="w-4 h-4" /> مشاهده سفارش
        </RouterLink>
        <RouterLink
          to="/products"
          class="px-6 py-2.5 border border-gray-200 text-gray-600 hover:bg-gray-50 rounded-xl font-medium transition text-sm"
        >
          ادامه خرید
        </RouterLink>
      </div>
    </div>

    <!-- Cancelled -->
    <div v-else-if="isCancelled" class="bg-white rounded-2xl shadow-sm p-10">
      <div class="w-20 h-20 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-5">
        <AlertCircle class="w-10 h-10 text-amber-500" />
      </div>
      <h1 class="text-2xl font-bold text-gray-800 mb-2">پرداخت لغو شد</h1>
      <p class="text-gray-500 mb-6">شما پرداخت را لغو کردید. سفارش شما ثبت شده اما پرداخت نشده است.</p>
      <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <RouterLink
          v-if="orderId"
          :to="`/user/orders/${orderId}`"
          class="flex items-center justify-center gap-2 px-6 py-2.5 bg-primary-500 hover:bg-primary-600 text-white rounded-xl font-medium transition text-sm"
        >
          <ArrowRight class="w-4 h-4" /> مشاهده سفارش
        </RouterLink>
        <RouterLink
          to="/cart"
          class="px-6 py-2.5 border border-gray-200 text-gray-600 hover:bg-gray-50 rounded-xl font-medium transition text-sm"
        >
          بازگشت به سبد خرید
        </RouterLink>
      </div>
    </div>

    <!-- Failed / Error -->
    <div v-else class="bg-white rounded-2xl shadow-sm p-10">
      <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-5">
        <XCircle class="w-10 h-10 text-red-500" />
      </div>
      <h1 class="text-2xl font-bold text-gray-800 mb-2">پرداخت ناموفق</h1>
      <p class="text-gray-500 mb-6">
        {{ isFailed ? 'متاسفانه پرداخت شما با خطا مواجه شد. در صورت کسر مبلغ، طی ۷۲ ساعت بازگشت داده می‌شود.' : 'خطایی در پردازش پرداخت رخ داد.' }}
      </p>
      <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <button
          @click="router.go(-1)"
          class="flex items-center justify-center gap-2 px-6 py-2.5 bg-primary-500 hover:bg-primary-600 text-white rounded-xl font-medium transition text-sm"
        >
          تلاش مجدد
        </button>
        <RouterLink
          to="/user/orders"
          class="px-6 py-2.5 border border-gray-200 text-gray-600 hover:bg-gray-50 rounded-xl font-medium transition text-sm"
        >
          سفارشات من
        </RouterLink>
      </div>
    </div>
  </div>
</template>
