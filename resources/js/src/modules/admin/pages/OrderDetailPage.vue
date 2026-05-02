<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowRight, Check, AlertCircle, MapPin, CreditCard, Package, CheckCircle, XCircle, Clock, FileImage } from 'lucide-vue-next'
import { orderService, type AdminOrderDetail } from '../services/orderService'
import { orderStatusService } from '../services/paymentMethodService'
import type { OrderStatus } from '../types/payment'

const route  = useRoute()
const router = useRouter()

const order     = ref<AdminOrderDetail | null>(null)
const statuses  = ref<OrderStatus[]>([])
const isLoading = ref(true)
const errorMsg  = ref<string | null>(null)
const toast     = ref<{ text: string; type: 'success' | 'error' } | null>(null)
const confirmingReceipt = ref(false)
const rejectingReceipt  = ref(false)

const showToast = (text: string, type: 'success' | 'error') => {
  toast.value = { text, type }
  setTimeout(() => (toast.value = null), 3500)
}

onMounted(async () => {
  try {
    ;[order.value, statuses.value] = await Promise.all([
      orderService.getById(route.params.id as string),
      orderStatusService.getAll(),
    ])
  } catch {
    errorMsg.value = 'خطا در بارگذاری جزئیات سفارش'
  } finally {
    isLoading.value = false
  }
})

const formatPrice = (n: number) => new Intl.NumberFormat('fa-IR').format(n)

const updateStatus = async (status: string) => {
  if (!order.value) return
  try {
    await orderService.updateStatus(order.value.id, status)
    order.value.status = status
    const found = statuses.value.find(s => s.key === status)
    if (found) { order.value.status_label = found.label; order.value.status_color = found.color }
    showToast('وضعیت سفارش به‌روزرسانی شد', 'success')
  } catch {
    showToast('خطا در به‌روزرسانی وضعیت', 'error')
  }
}

const confirmReceipt = async () => {
  if (!order.value?.latest_transaction) return
  confirmingReceipt.value = true
  try {
    const updated = await orderService.confirmReceipt(order.value.latest_transaction.id)
    order.value = updated
    showToast('رسید تأیید شد و سفارش به مرحله پردازش منتقل گردید', 'success')
  } catch {
    showToast('خطا در تأیید رسید', 'error')
  } finally {
    confirmingReceipt.value = false
  }
}

const rejectReceipt = async () => {
  if (!order.value?.latest_transaction) return
  if (!confirm('آیا از رد رسید اطمینان دارید؟ وضعیت سفارش به "خطا در پرداخت" تغییر خواهد کرد.')) return
  rejectingReceipt.value = true
  try {
    const updated = await orderService.rejectReceipt(order.value.latest_transaction.id)
    order.value = updated
    showToast('رسید رد شد', 'success')
  } catch {
    showToast('خطا در رد رسید', 'error')
  } finally {
    rejectingReceipt.value = false
  }
}

const driverLabel = (driver: string) => {
  const map: Record<string, string> = { card_to_card: 'کارت به کارت', zarinpal: 'زرین‌پال' }
  return map[driver] ?? driver
}

const txStatusLabel = (s: string) => {
  const map: Record<string, string> = { pending: 'در انتظار تأیید', paid: 'پرداخت شده', failed: 'ناموفق', cancelled: 'لغو شده' }
  return map[s] ?? s
}

const txStatusClass = (s: string) => {
  const map: Record<string, string> = { pending: 'bg-amber-100 text-amber-700', paid: 'bg-green-100 text-green-700', failed: 'bg-red-100 text-red-700', cancelled: 'bg-gray-100 text-gray-600' }
  return map[s] ?? 'bg-gray-100 text-gray-600'
}
</script>

<template>
  <div>
    <div class="flex items-center gap-4 mb-6">
      <button @click="router.back()" class="p-2 hover:bg-secondary-100 rounded-lg" aria-label="بازگشت">
        <ArrowRight class="w-5 h-5" />
      </button>
      <h1 class="text-2xl font-bold text-secondary-800">جزئیات سفارش</h1>
    </div>

    <transition name="fade">
      <div
        v-if="toast"
        :class="toast.type === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'"
        class="flex items-center gap-2 mb-4 px-4 py-3 border rounded-lg text-sm"
      >
        <Check v-if="toast.type === 'success'" class="w-4 h-4 flex-shrink-0" />
        <AlertCircle v-else class="w-4 h-4 flex-shrink-0" />
        {{ toast.text }}
      </div>
    </transition>

    <div v-if="isLoading" class="bg-white rounded-xl p-8 text-center">
      <div class="animate-spin w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full mx-auto"></div>
    </div>

    <div v-else-if="errorMsg" class="bg-white rounded-xl p-8 text-center">
      <p class="text-red-500">{{ errorMsg }}</p>
    </div>

    <div v-else-if="order" class="space-y-6">

      <!-- Order Header -->
      <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div>
            <p class="text-xs text-secondary-500 mb-1">شماره سفارش</p>
            <p class="font-bold text-secondary-800">{{ order.order_number }}</p>
          </div>
          <div>
            <p class="text-xs text-secondary-500 mb-1">تاریخ ثبت</p>
            <p class="text-sm text-secondary-700">{{ order.created_at }}</p>
          </div>
          <div>
            <p class="text-xs text-secondary-500 mb-1">مبلغ کل</p>
            <p class="font-bold text-primary-600 text-sm">{{ formatPrice(order.total) }} تومان</p>
          </div>
          <div>
            <p class="text-xs text-secondary-500 mb-2">وضعیت سفارش</p>
            <select
              :value="order.status"
              class="w-full px-3 py-1.5 rounded-lg border border-secondary-200 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
              @change="updateStatus(($event.target as HTMLSelectElement).value)"
            >
              <option v-for="s in statuses" :key="s.key" :value="s.key">{{ s.label }}</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Customer Info -->
      <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-base font-bold text-secondary-800 mb-4">اطلاعات مشتری</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div>
            <p class="text-secondary-500 mb-0.5">نام</p>
            <p class="font-medium">{{ order.customer_name || order.user?.name || '—' }}</p>
          </div>
          <div>
            <p class="text-secondary-500 mb-0.5">ایمیل</p>
            <p class="font-medium">{{ order.user?.email || '—' }}</p>
          </div>
        </div>
      </div>

      <!-- Address -->
      <div v-if="order.address" class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-base font-bold text-secondary-800 mb-4 flex items-center gap-2">
          <MapPin class="w-4 h-4 text-primary-500" /> آدرس تحویل
        </h2>
        <div class="text-sm text-secondary-700 space-y-1">
          <p class="font-medium">{{ order.address.first_name }} {{ order.address.last_name }}</p>
          <p>{{ order.address.province }}، {{ order.address.city }}، {{ order.address.address }}</p>
          <p>کد پستی: {{ order.address.postal_code }} — تلفن: {{ order.address.phone }}</p>
        </div>
      </div>

      <!-- Order Items -->
      <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-base font-bold text-secondary-800 mb-4 flex items-center gap-2">
          <Package class="w-4 h-4 text-primary-500" /> اقلام سفارش
        </h2>
        <div class="space-y-3">
          <div v-for="item in order.items" :key="item.id" class="flex items-center justify-between p-3 bg-secondary-50 rounded-lg">
            <div>
              <p class="font-medium text-secondary-800 text-sm">{{ item.product_name }}</p>
              <p class="text-xs text-secondary-500 mt-0.5">تعداد: {{ item.quantity }}</p>
            </div>
            <p class="font-semibold text-secondary-800 text-sm">{{ formatPrice(item.subtotal) }} تومان</p>
          </div>
        </div>
        <div class="mt-4 pt-4 border-t border-secondary-100 space-y-1.5 text-sm">
          <div v-if="order.discount > 0" class="flex justify-between text-green-600">
            <span>تخفیف</span><span>- {{ formatPrice(order.discount) }} تومان</span>
          </div>
          <div class="flex justify-between text-secondary-600">
            <span>هزینه ارسال</span>
            <span>{{ order.shipping > 0 ? formatPrice(order.shipping) + ' تومان' : 'رایگان' }}</span>
          </div>
          <div class="flex justify-between font-bold text-base text-secondary-800 pt-2 border-t border-secondary-100">
            <span>مبلغ کل</span>
            <span class="text-primary-600">{{ formatPrice(order.total) }} تومان</span>
          </div>
        </div>
      </div>

      <!-- Payment / Transaction Info -->
      <div v-if="order.latest_transaction" class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-base font-bold text-secondary-800 mb-4 flex items-center gap-2">
          <CreditCard class="w-4 h-4 text-primary-500" /> اطلاعات پرداخت
        </h2>
        <div class="grid md:grid-cols-2 gap-4 text-sm mb-4">
          <div class="flex justify-between border-b border-secondary-50 pb-2">
            <span class="text-secondary-500">روش پرداخت</span>
            <span class="font-medium">{{ driverLabel(order.latest_transaction.driver) }}</span>
          </div>
          <div class="flex justify-between border-b border-secondary-50 pb-2">
            <span class="text-secondary-500">وضعیت تراکنش</span>
            <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', txStatusClass(order.latest_transaction.status)]">
              {{ txStatusLabel(order.latest_transaction.status) }}
            </span>
          </div>
          <div v-if="order.latest_transaction.gateway_ref" class="flex justify-between border-b border-secondary-50 pb-2">
            <span class="text-secondary-500">کد پیگیری</span>
            <span class="font-mono text-xs">{{ order.latest_transaction.gateway_ref }}</span>
          </div>
          <div v-if="order.latest_transaction.card_last_four" class="flex justify-between border-b border-secondary-50 pb-2">
            <span class="text-secondary-500">۴ رقم آخر کارت مبدأ</span>
            <span class="font-mono">****{{ order.latest_transaction.card_last_four }}</span>
          </div>
          <div v-if="order.latest_transaction.paid_at" class="flex justify-between border-b border-secondary-50 pb-2">
            <span class="text-secondary-500">تاریخ پرداخت</span>
            <span>{{ order.latest_transaction.paid_at }}</span>
          </div>
        </div>

        <!-- Card-to-Card Receipt Section -->
        <div
          v-if="order.latest_transaction.driver === 'card_to_card'"
          class="mt-4 pt-4 border-t border-secondary-100"
        >
          <h3 class="text-sm font-semibold text-secondary-700 mb-3 flex items-center gap-2">
            <FileImage class="w-4 h-4" /> رسید پرداخت
          </h3>

          <div v-if="order.latest_transaction.receipt_path">
            <a :href="order.latest_transaction.receipt_path" target="_blank" rel="noopener" class="inline-block">
              <img
                :src="order.latest_transaction.receipt_path"
                alt="رسید پرداخت"
                class="max-h-64 rounded-xl border border-secondary-200 object-contain mb-4"
              />
            </a>

            <!-- Pending receipt — confirm / reject buttons -->
            <div v-if="order.latest_transaction.status === 'pending'" class="flex items-center gap-3">
              <button
                :disabled="confirmingReceipt || rejectingReceipt"
                @click="confirmReceipt"
                class="flex items-center gap-2 px-5 py-2 bg-green-500 hover:bg-green-600 disabled:opacity-60 text-white rounded-lg text-sm font-medium transition"
              >
                <CheckCircle class="w-4 h-4" />
                {{ confirmingReceipt ? 'در حال تأیید...' : 'تأیید رسید' }}
              </button>
              <button
                :disabled="confirmingReceipt || rejectingReceipt"
                @click="rejectReceipt"
                class="flex items-center gap-2 px-5 py-2 bg-red-500 hover:bg-red-600 disabled:opacity-60 text-white rounded-lg text-sm font-medium transition"
              >
                <XCircle class="w-4 h-4" />
                {{ rejectingReceipt ? 'در حال رد...' : 'رد رسید' }}
              </button>
            </div>

            <p v-else-if="order.latest_transaction.status === 'paid'" class="text-sm text-green-600 flex items-center gap-1.5">
              <CheckCircle class="w-4 h-4" /> رسید تأیید شده است.
            </p>
            <p v-else-if="order.latest_transaction.status === 'failed'" class="text-sm text-red-600 flex items-center gap-1.5">
              <XCircle class="w-4 h-4" /> رسید رد شده است.
            </p>
          </div>

          <div v-else class="flex items-center gap-2 text-sm text-secondary-400 bg-secondary-50 rounded-lg px-4 py-3">
            <Clock class="w-4 h-4" /> هنوز رسیدی آپلود نشده است.
          </div>
        </div>
      </div>

      <!-- Admin Notes -->
      <div v-if="order.admin_notes" class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-base font-bold text-secondary-800 mb-2">یادداشت ادمین</h2>
        <p class="text-sm text-secondary-600">{{ order.admin_notes }}</p>
      </div>

    </div>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
