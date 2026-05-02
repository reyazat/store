<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { ArrowRight, MapPin, CreditCard, Package, CheckCircle, Clock, XCircle, AlertCircle } from 'lucide-vue-next'
import { Breadcrumb } from '@shared/components'
import { userService } from '../services'
import { formatPrice, formatDate } from '@shared/utils/format'
import UserSidebar from '../components/UserSidebar.vue'

interface OrderItem {
  product_name: string
  vendor_name: string | null
  sku: string | null
  quantity: number
  price: number
  subtotal: number
}

interface OrderAddress {
  first_name: string
  last_name: string
  phone: string
  address: string
  city: string
  province: string
  postal_code: string
}

interface LatestTransaction {
  id: number
  driver: string
  gateway_ref: string | null
  status: 'pending' | 'paid' | 'failed' | 'cancelled'
  amount: number
  card_last_four: string | null
  receipt_path: string | null
  paid_at: string | null
}

interface OrderDetail {
  id: number
  order_number: string
  status: string
  status_label: string
  status_color: string
  payment_status: string
  payment_method: string | null
  subtotal: number
  tax: number
  shipping: number
  discount: number
  total: number
  notes: string | null
  items: OrderItem[]
  address: OrderAddress | null
  latest_transaction: LatestTransaction | null
  created_at: string
  paid_at: string | null
  shipped_at: string | null
  delivered_at: string | null
}

const route  = useRoute()
const router = useRouter()

const order     = ref<OrderDetail | null>(null)
const isLoading = ref(true)
const errorMsg  = ref<string | null>(null)

onMounted(async () => {
  try {
    const response = await userService.getOrder(Number(route.params.id))
    order.value = response.data || null
  } catch {
    errorMsg.value = 'خطا در بارگذاری جزئیات سفارش'
  } finally {
    isLoading.value = false
  }
})

const driverLabel = (driver: string) => {
  const labels: Record<string, string> = {
    card_to_card: 'کارت به کارت',
    zarinpal: 'زرین‌پال',
  }
  return labels[driver] ?? driver
}

const transactionStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    pending:   'در انتظار تأیید',
    paid:      'پرداخت شده',
    failed:    'ناموفق',
    cancelled: 'لغو شده',
  }
  return labels[status] ?? status
}

const transactionStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    pending:   'bg-amber-100 text-amber-700',
    paid:      'bg-green-100 text-green-700',
    failed:    'bg-red-100 text-red-700',
    cancelled: 'bg-gray-100 text-gray-600',
  }
  return classes[status] ?? 'bg-gray-100 text-gray-600'
}

const paymentStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    pending: 'در انتظار پرداخت',
    paid:    'پرداخت شده',
    failed:  'خطا در پرداخت',
  }
  return labels[status] ?? status
}
</script>

<template>
  <div class="container mx-auto px-4 py-8">
    <Breadcrumb :items="[
      { title: 'پنل کاربری', url: '/user/dashboard' },
      { title: 'سفارشات', url: '/user/orders' },
      { title: 'جزئیات سفارش' }
    ]" />
    <h1 class="text-2xl font-bold mb-8">جزئیات سفارش</h1>

    <div class="grid lg:grid-cols-4 gap-8">
      <UserSidebar />

      <div class="lg:col-span-3 space-y-6">

        <div v-if="isLoading" class="space-y-4">
          <div v-for="i in 3" :key="i" class="animate-pulse h-32 bg-gray-100 rounded-xl"></div>
        </div>

        <div v-else-if="errorMsg" class="bg-white rounded-xl shadow-sm p-8 text-center">
          <AlertCircle class="w-12 h-12 text-red-400 mx-auto mb-3" />
          <p class="text-red-500">{{ errorMsg }}</p>
          <button @click="router.back()" class="mt-4 text-primary-600 hover:underline text-sm">بازگشت</button>
        </div>

        <template v-else-if="order">
          <!-- Order Header -->
          <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
              <div>
                <p class="text-sm text-gray-500 mb-1">شماره سفارش</p>
                <p class="text-lg font-bold text-gray-800">{{ order.order_number }}</p>
                <p class="text-sm text-gray-400 mt-1">تاریخ ثبت: {{ formatDate(order.created_at) }}</p>
              </div>
              <div class="text-left">
                <span
                  class="inline-block px-3 py-1.5 rounded-full text-sm font-medium"
                  :style="{ backgroundColor: order.status_color + '22', color: order.status_color }"
                >
                  {{ order.status_label }}
                </span>
                <p class="text-xs text-gray-400 mt-1.5">وضعیت پرداخت: {{ paymentStatusLabel(order.payment_status) }}</p>
              </div>
            </div>
          </div>

          <!-- Order Items -->
          <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-base font-bold text-gray-800 mb-4 flex items-center gap-2">
              <Package class="w-5 h-5 text-primary-500" /> اقلام سفارش
            </h2>
            <div class="divide-y divide-gray-100">
              <div v-for="(item, i) in order.items" :key="i" class="flex items-center justify-between py-3">
                <div>
                  <p class="font-medium text-gray-800 text-sm">{{ item.product_name }}</p>
                  <p v-if="item.sku" class="text-xs text-gray-400 mt-0.5">کد: {{ item.sku }}</p>
                  <p class="text-xs text-gray-500">تعداد: {{ item.quantity }} × {{ formatPrice(item.price) }} تومان</p>
                </div>
                <p class="font-semibold text-gray-800 text-sm">{{ formatPrice(item.subtotal) }} تومان</p>
              </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100 space-y-2 text-sm">
              <div class="flex justify-between text-gray-600">
                <span>جمع اقلام</span>
                <span>{{ formatPrice(order.subtotal) }} تومان</span>
              </div>
              <div v-if="order.discount > 0" class="flex justify-between text-green-600">
                <span>تخفیف</span>
                <span>- {{ formatPrice(order.discount) }} تومان</span>
              </div>
              <div class="flex justify-between text-gray-600">
                <span>هزینه ارسال</span>
                <span>{{ order.shipping > 0 ? formatPrice(order.shipping) + ' تومان' : 'رایگان' }}</span>
              </div>
              <div class="flex justify-between font-bold text-base text-gray-800 pt-2 border-t border-gray-100">
                <span>مبلغ کل</span>
                <span class="text-primary-600">{{ formatPrice(order.total) }} تومان</span>
              </div>
            </div>
          </div>

          <!-- Delivery Address -->
          <div v-if="order.address" class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-base font-bold text-gray-800 mb-4 flex items-center gap-2">
              <MapPin class="w-5 h-5 text-primary-500" /> آدرس تحویل
            </h2>
            <p class="text-sm font-medium text-gray-800">{{ order.address.first_name }} {{ order.address.last_name }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ order.address.province }}، {{ order.address.city }}، {{ order.address.address }}</p>
            <p class="text-sm text-gray-500 mt-0.5">کد پستی: {{ order.address.postal_code }} — تلفن: {{ order.address.phone }}</p>
          </div>

          <!-- Payment Info -->
          <div v-if="order.latest_transaction" class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-base font-bold text-gray-800 mb-4 flex items-center gap-2">
              <CreditCard class="w-5 h-5 text-primary-500" /> اطلاعات پرداخت
            </h2>
            <div class="space-y-3 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-500">روش پرداخت</span>
                <span class="font-medium">{{ driverLabel(order.latest_transaction.driver) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">وضعیت تراکنش</span>
                <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', transactionStatusClass(order.latest_transaction.status)]">
                  {{ transactionStatusLabel(order.latest_transaction.status) }}
                </span>
              </div>
              <div v-if="order.latest_transaction.gateway_ref" class="flex justify-between">
                <span class="text-gray-500">کد پیگیری</span>
                <span class="font-mono font-medium text-gray-800">{{ order.latest_transaction.gateway_ref }}</span>
              </div>
              <div v-if="order.latest_transaction.card_last_four" class="flex justify-between">
                <span class="text-gray-500">۴ رقم آخر کارت</span>
                <span class="font-mono">****{{ order.latest_transaction.card_last_four }}</span>
              </div>
              <div v-if="order.latest_transaction.paid_at" class="flex justify-between">
                <span class="text-gray-500">تاریخ پرداخت</span>
                <span>{{ formatDate(order.latest_transaction.paid_at) }}</span>
              </div>
            </div>

            <!-- Receipt image (card-to-card) -->
            <div v-if="order.latest_transaction.driver === 'card_to_card' && order.latest_transaction.receipt_path" class="mt-4 pt-4 border-t border-gray-100">
              <p class="text-sm text-gray-500 mb-2">رسید آپلود شده:</p>
              <a :href="order.latest_transaction.receipt_path" target="_blank" rel="noopener">
                <img
                  :src="order.latest_transaction.receipt_path"
                  alt="رسید پرداخت"
                  class="max-h-48 rounded-lg border border-gray-200 object-contain"
                />
              </a>
              <p v-if="order.latest_transaction.status === 'pending'" class="mt-2 text-xs text-amber-600 flex items-center gap-1">
                <Clock class="w-3.5 h-3.5" /> رسید ارسال شده و در انتظار تأیید مدیر است.
              </p>
              <p v-else-if="order.latest_transaction.status === 'paid'" class="mt-2 text-xs text-green-600 flex items-center gap-1">
                <CheckCircle class="w-3.5 h-3.5" /> رسید تأیید شد.
              </p>
              <p v-else-if="order.latest_transaction.status === 'failed'" class="mt-2 text-xs text-red-600 flex items-center gap-1">
                <XCircle class="w-3.5 h-3.5" /> رسید رد شد. لطفاً با پشتیبانی تماس بگیرید.
              </p>
            </div>
          </div>

          <div class="flex justify-end">
            <RouterLink to="/user/orders" class="flex items-center gap-2 text-sm text-primary-600 hover:underline">
              <ArrowRight class="w-4 h-4" /> بازگشت به لیست سفارشات
            </RouterLink>
          </div>
        </template>

      </div>
    </div>
  </div>
</template>
