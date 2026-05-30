<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { CheckCircle, XCircle, AlertCircle, ShoppingBag, RefreshCw, PackageSearch, Copy, Check } from 'lucide-vue-next'

const route = useRoute()

const status  = computed(() => route.query.status as string)
const orderId = computed(() => route.query.order_id as string | undefined)
const orderNumber = computed(() => route.query.order_number as string | undefined)
const refId   = computed(() => route.query.ref_id as string | undefined)

const isSuccess   = computed(() => status.value === 'success')
const isCancelled = computed(() => status.value === 'cancelled')

const copied = ref(false)

const copyTrackingCode = async () => {
  if (!orderNumber.value) return
  try {
    await navigator.clipboard.writeText(orderNumber.value)
    copied.value = true
    setTimeout(() => (copied.value = false), 2000)
  } catch {
    /* ignore */
  }
}
</script>

<template>
  <div class="container mx-auto px-4 py-16 max-w-lg text-center">

    <!-- Success -->
    <div v-if="isSuccess" class="bg-white rounded-2xl shadow-sm p-10">
      <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-5 animate-bounce-once">
        <CheckCircle class="w-11 h-11 text-green-500" />
      </div>
      <h1 class="text-2xl font-bold text-gray-800 mb-2">پرداخت موفق!</h1>
      <p class="text-gray-500 mb-6">پرداخت شما با موفقیت انجام شد و سفارش در حال پردازش است.</p>

      <!-- Tracking Code Card -->
      <div v-if="orderNumber" class="bg-gradient-to-r from-primary-50 to-primary-100/50 rounded-xl p-5 mb-6 border border-primary-100">
        <p class="text-sm text-gray-600 mb-3">کد رهگیری سفارش شما</p>
        <div class="flex items-center justify-center gap-3">
          <span class="text-2xl font-bold text-gray-900 font-mono tracking-wider">{{ orderNumber }}</span>
          <button
            @click="copyTrackingCode"
            class="p-2 text-gray-400 hover:text-primary-600 hover:bg-white rounded-lg transition-all shadow-sm"
            :title="copied ? 'کپی شد!' : 'کپی کد'"
          >
            <Check v-if="copied" class="w-5 h-5 text-green-500" />
            <Copy v-else class="w-5 h-5" />
          </button>
        </div>
        <p class="text-xs text-gray-500 mt-3">این کد را ذخیره کنید تا بتوانید وضعیت سفارش را پیگیری کنید</p>
      </div>

      <!-- Gateway Ref ID -->
      <p v-if="refId" class="text-sm text-gray-400 mb-6">
        شماره پیگیری پرداخت: <span class="font-mono font-semibold text-gray-600 bg-gray-100 px-2 py-0.5 rounded">{{ refId }}</span>
      </p>

      <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <RouterLink
          v-if="orderId"
          :to="`/user/orders/${orderId}`"
          class="flex items-center justify-center gap-2 px-6 py-2.5 bg-primary-500 hover:bg-primary-600 text-white rounded-xl font-semibold transition text-sm"
        >
          <ShoppingBag class="w-4 h-4" /> مشاهده سفارش
        </RouterLink>
        <RouterLink
          v-if="orderNumber"
          :to="{ name: 'order-tracking', query: { code: orderNumber } }"
          class="flex items-center justify-center gap-2 px-6 py-2.5 bg-green-50 hover:bg-green-100 text-green-700 border border-green-200 rounded-xl font-semibold transition text-sm"
        >
          <PackageSearch class="w-4 h-4" /> پیگیری سفارش
        </RouterLink>
      </div>
    </div>

    <!-- Cancelled -->
    <div v-else-if="isCancelled" class="bg-white rounded-2xl shadow-sm p-10">
      <div class="w-20 h-20 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-5">
        <AlertCircle class="w-11 h-11 text-amber-500" />
      </div>
      <h1 class="text-2xl font-bold text-gray-800 mb-2">پرداخت لغو شد</h1>
      <p class="text-gray-500 mb-2">پرداخت شما لغو شد. سفارش ثبت شده است و می‌توانید دوباره پرداخت کنید.</p>
      <div class="flex flex-col sm:flex-row gap-3 justify-center mt-6">
        <RouterLink
          v-if="orderId"
          :to="`/user/orders/${orderId}`"
          class="flex items-center justify-center gap-2 px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-semibold transition text-sm"
        >
          <RefreshCw class="w-4 h-4" /> رفتن به سفارش برای پرداخت مجدد
        </RouterLink>
        <RouterLink
          to="/products"
          class="px-6 py-2.5 border border-gray-200 text-gray-600 hover:bg-gray-50 rounded-xl font-medium transition text-sm"
        >
          ادامه خرید
        </RouterLink>
      </div>
    </div>

    <!-- Failed / Error -->
    <div v-else class="bg-white rounded-2xl shadow-sm p-10">
      <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-5">
        <XCircle class="w-11 h-11 text-red-500" />
      </div>
      <h1 class="text-2xl font-bold text-gray-800 mb-2">پرداخت ناموفق</h1>
      <p class="text-gray-500 mb-2">متأسفانه پرداخت انجام نشد.</p>
      <p class="text-xs text-gray-400 mb-6">در صورت کسر مبلغ از حساب، طی ۷۲ ساعت کاری بازگشت داده می‌شود.</p>
      <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <RouterLink
          v-if="orderId"
          :to="`/user/orders/${orderId}`"
          class="flex items-center justify-center gap-2 px-6 py-2.5 bg-primary-500 hover:bg-primary-600 text-white rounded-xl font-semibold transition text-sm"
        >
          <RefreshCw class="w-4 h-4" /> تلاش مجدد برای پرداخت
        </RouterLink>
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
