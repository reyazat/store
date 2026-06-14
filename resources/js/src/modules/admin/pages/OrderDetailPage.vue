<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowRight, Check, AlertCircle, MapPin, CreditCard, Package,
  CheckCircle, XCircle, Clock, FileImage, Truck, Bell, Printer, History, Banknote,
} from 'lucide-vue-next'
import { orderService, type AdminOrderDetail, type AdminOrderHistoryEntry } from '../services/orderService'
import { orderStatusService } from '../services/paymentMethodService'
import type { OrderStatus } from '../types/payment'
import { formatDate, formatDateTime, formatPrice } from '@shared/utils/format'

const route  = useRoute()
const router = useRouter()

const order     = ref<AdminOrderDetail | null>(null)
const statuses  = ref<OrderStatus[]>([])
const isLoading = ref(true)
const errorMsg  = ref<string | null>(null)
const toast     = ref<{ text: string; type: 'success' | 'error' } | null>(null)

// Receipt confirm/reject
const confirmingReceipt = ref(false)
const rejectingReceipt  = ref(false)

// Notes
const adminNotesText = ref('')
const isSavingNotes  = ref(false)

// Shipping code
const shippingCodeInput = ref('')
const isSettingShipping = ref(false)
const editingShipping   = ref(false)

// Notification
const notificationMsg     = ref('')
const isSendingNotification = ref(false)
const showNotifyModal     = ref(false)

// Timeline tab
const activeTab = ref<'details' | 'history'>('details')

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
    initNotes()
    shippingCodeInput.value = order.value?.shipping_code ?? ''
  } catch {
    errorMsg.value = 'خطا در بارگذاری جزئیات سفارش'
  } finally {
    isLoading.value = false
  }
})

const apiError = (err: unknown, fallback: string): string =>
  (err as { response?: { data?: { message?: string } } })?.response?.data?.message || fallback

const updateStatus = async (status: string) => {
  if (!order.value) return
  try {
    // Bug 7 fix: use the full order returned by the server (single source of truth)
    order.value = await orderService.updateStatus(order.value.id, status)
    showToast('وضعیت سفارش به‌روزرسانی شد', 'success')
  } catch (err) {
    showToast(apiError(err, 'خطا در به‌روزرسانی وضعیت'), 'error')
  }
}

const confirmReceipt = async () => {
  if (!order.value?.latest_transaction) return
  confirmingReceipt.value = true
  try {
    const updated = await orderService.confirmReceipt(order.value.latest_transaction.id)
    order.value = updated
    showToast('رسید تأیید شد و سفارش به مرحله پردازش منتقل گردید', 'success')
  } catch (err) {
    showToast(apiError(err, 'خطا در تأیید رسید'), 'error')
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
  } catch (err) {
    showToast(apiError(err, 'خطا در رد رسید'), 'error')
  } finally {
    rejectingReceipt.value = false
  }
}

const initNotes = () => {
  adminNotesText.value = order.value?.admin_notes ?? ''
}

const saveNotes = async () => {
  if (!order.value) return
  isSavingNotes.value = true
  try {
    const updated = await orderService.updateNotes(order.value.id, adminNotesText.value)
    if (order.value) order.value.admin_notes = updated.admin_notes
    showToast('یادداشت ذخیره شد', 'success')
  } catch (err) {
    showToast(apiError(err, 'خطا در ذخیره یادداشت'), 'error')
  } finally {
    isSavingNotes.value = false
  }
}

const saveShippingCode = async () => {
  if (!order.value || !shippingCodeInput.value.trim()) return
  isSettingShipping.value = true
  try {
    const updated = await orderService.setShippingCode(order.value.id, shippingCodeInput.value.trim())
    order.value = updated
    editingShipping.value = false
    showToast('کد رهگیری ثبت شد', 'success')
  } catch (err) {
    showToast(apiError(err, 'خطا در ثبت کد رهگیری'), 'error')
  } finally {
    isSettingShipping.value = false
  }
}

const sendNotification = async () => {
  if (!order.value) return
  isSendingNotification.value = true
  try {
    const updated = await orderService.sendNotification(order.value.id, notificationMsg.value || undefined)
    order.value = updated
    showNotifyModal.value = false
    notificationMsg.value = ''
    showToast('اعلان با موفقیت ارسال شد', 'success')
  } catch (err) {
    showToast(apiError(err, 'خطا در ارسال اعلان'), 'error')
  } finally {
    isSendingNotification.value = false
  }
}

const printOrder = () => window.print()

const driverLabel = (driver: string) => {
  const map: Record<string, string> = { card_to_card: 'کارت به کارت', zarinpal: 'زرین‌پال' }
  return map[driver] ?? driver
}

const txStatusLabel = (s: string) => {
  const map: Record<string, string> = { pending: 'در انتظار تأیید', paid: 'پرداخت شده', failed: 'ناموفق', cancelled: 'لغو شده' }
  return map[s] ?? s
}

const txStatusClass = (s: string) => {
  const map: Record<string, string> = {
    pending: 'bg-amber-100 text-amber-700',
    paid: 'bg-green-100 text-green-700',
    failed: 'bg-red-100 text-red-700',
    cancelled: 'bg-gray-100 text-gray-600',
  }
  return map[s] ?? 'bg-gray-100 text-gray-600'
}

const historyIcon = (event: string) => {
  const map: Record<string, string> = {
    status_changed:         'status',
    shipping_code_set:      'truck',
    notification_sent:      'bell',
    payment_confirmed:      'check',
    payment_rejected:       'x',
    payment_status_changed: 'credit',
    order_created:          'package',
  }
  return map[event] ?? 'clock'
}

const historyIconClass = (event: string) => {
  const map: Record<string, string> = {
    status_changed:         'bg-blue-100 text-blue-600',
    shipping_code_set:      'bg-orange-100 text-orange-600',
    notification_sent:      'bg-purple-100 text-purple-600',
    payment_confirmed:      'bg-green-100 text-green-600',
    payment_rejected:       'bg-red-100 text-red-600',
    payment_status_changed: 'bg-indigo-100 text-indigo-600',
    order_created:          'bg-secondary-100 text-secondary-600',
  }
  return map[event] ?? 'bg-secondary-100 text-secondary-600'
}

const currentStatusNotifyMessage = computed(() => {
  const found = statuses.value.find(s => s.key === order.value?.status)
  return found?.notification_message || ''
})
</script>

<template>
  <div>
    <!-- Printable Header (visible only on print) -->
    <div class="hidden print:block text-center mb-6 border-b pb-4">
      <h1 class="text-xl font-bold">فاکتور سفارش</h1>
      <p class="text-sm text-gray-600 mt-1">{{ order?.order_number }}</p>
    </div>

    <!-- Page Actions (hidden on print) -->
    <div class="flex items-center gap-4 mb-6 print:hidden">
      <button @click="router.back()" class="p-2 hover:bg-secondary-100 rounded-lg" aria-label="بازگشت">
        <ArrowRight class="w-5 h-5" />
      </button>
      <h1 class="text-2xl font-bold text-secondary-800 flex-1">جزئیات سفارش</h1>

      <template v-if="order">
        <!-- Send Notification -->
        <button
          class="flex items-center gap-2 px-4 py-2 border border-secondary-200 rounded-lg text-sm text-secondary-700 hover:bg-secondary-50 transition"
          @click="showNotifyModal = true"
        >
          <Bell class="w-4 h-4" /> ارسال اعلان
        </button>

        <!-- Print -->
        <button
          class="flex items-center gap-2 px-4 py-2 border border-secondary-200 rounded-lg text-sm text-secondary-700 hover:bg-secondary-50 transition"
          @click="printOrder"
        >
          <Printer class="w-4 h-4" /> پرینت
        </button>
      </template>
    </div>

    <!-- Toast -->
    <transition name="fade">
      <div
        v-if="toast"
        :class="toast.type === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'"
        class="flex items-center gap-2 mb-4 px-4 py-3 border rounded-lg text-sm print:hidden"
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
            <p class="text-sm text-secondary-700">{{ formatDate(order.created_at) }}</p>
          </div>
          <div>
            <p class="text-xs text-secondary-500 mb-1">مبلغ کل</p>
            <p class="font-bold text-primary-600 text-sm">{{ formatPrice(order.total) }} تومان</p>
          </div>
          <div>
            <p class="text-xs text-secondary-500 mb-2">وضعیت سفارش</p>
            <!-- Current status badge with dynamic color -->
            <span
              class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium mb-2"
              :style="{ backgroundColor: (order.status_color || '#6b7280') + '22', color: order.status_color || '#6b7280' }"
            >
              {{ order.status_label || order.status }}
            </span>
            <!-- Status change selector -->
            <select
              :value="order.status"
              class="w-full px-3 py-1.5 rounded-lg border border-secondary-200 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 print:hidden"
              @change="updateStatus(($event.target as HTMLSelectElement).value)"
            >
              <option v-for="s in statuses" :key="s.key" :value="s.key">{{ s.label }}</option>
            </select>
            <p class="hidden print:block font-medium">{{ order.status_label || order.status }}</p>
          </div>
        </div>
      </div>

      <!-- Tabs: Details / History -->
      <div class="bg-white rounded-xl shadow-sm overflow-hidden print:hidden">
        <div class="flex border-b border-secondary-100">
          <button
            :class="['flex items-center gap-2 px-6 py-3 text-sm font-medium border-b-2 transition', activeTab === 'details' ? 'border-primary-500 text-primary-600' : 'border-transparent text-secondary-500 hover:text-secondary-700']"
            @click="activeTab = 'details'"
          >
            <Package class="w-4 h-4" /> جزئیات سفارش
          </button>
          <button
            :class="['flex items-center gap-2 px-6 py-3 text-sm font-medium border-b-2 transition', activeTab === 'history' ? 'border-primary-500 text-primary-600' : 'border-transparent text-secondary-500 hover:text-secondary-700']"
            @click="activeTab = 'history'"
          >
            <History class="w-4 h-4" /> تاریخچه سفارش
            <span v-if="order.histories?.length" class="bg-secondary-100 text-secondary-600 text-xs px-1.5 py-0.5 rounded-full">{{ order.histories.length }}</span>
          </button>
        </div>

        <!-- Details Tab -->
        <div v-if="activeTab === 'details'" class="p-6 space-y-6">

          <!-- Customer Info -->
          <div>
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
          <div v-if="order.address">
            <h2 class="text-base font-bold text-secondary-800 mb-3 flex items-center gap-2">
              <MapPin class="w-4 h-4 text-primary-500" /> آدرس تحویل
            </h2>
            <div class="text-sm text-secondary-700 space-y-1 bg-secondary-50 rounded-lg p-4">
              <p class="font-medium">{{ order.address.first_name }} {{ order.address.last_name }}</p>
              <p>{{ order.address.province }}، {{ order.address.city }}، {{ order.address.address }}</p>
              <p>کد پستی: {{ order.address.postal_code }} — تلفن: {{ order.address.phone }}</p>
            </div>
          </div>

          <!-- Order Items -->
          <div>
            <h2 class="text-base font-bold text-secondary-800 mb-3 flex items-center gap-2">
              <Package class="w-4 h-4 text-primary-500" /> اقلام سفارش
            </h2>
            <div class="space-y-2">
              <div
                v-for="item in order.items"
                :key="item.id"
                class="flex items-center justify-between p-3 rounded-lg bg-secondary-50 text-sm"
              >
                <div class="flex-1 min-w-0">
                  <p class="font-medium text-secondary-800 truncate">{{ item.product_name }}</p>
                  <p class="text-secondary-400 text-xs mt-0.5">{{ item.vendor_name }} — SKU: {{ item.sku || '—' }}</p>
                </div>
                <div class="flex items-center gap-6 mr-4 text-left shrink-0">
                  <span class="text-secondary-500">× {{ item.quantity }}</span>
                  <span class="font-medium text-secondary-700 w-28 text-left">{{ formatPrice(item.subtotal) }} تومان</span>
                </div>
              </div>
            </div>

            <!-- Financial Summary -->
            <div class="mt-4 border-t border-secondary-100 pt-4 space-y-2 text-sm">
              <div class="flex justify-between text-secondary-600">
                <span>جمع کالاها</span>
                <span>{{ formatPrice(order.subtotal) }} تومان</span>
              </div>
              <div v-if="order.tax" class="flex justify-between text-secondary-600">
                <span>مالیات</span>
                <span>{{ formatPrice(order.tax) }} تومان</span>
              </div>
              <div v-if="order.shipping" class="flex justify-between text-secondary-600">
                <span>هزینه ارسال</span>
                <span>{{ formatPrice(order.shipping) }} تومان</span>
              </div>
              <div v-if="order.discount" class="flex justify-between text-green-600">
                <span>تخفیف</span>
                <span>— {{ formatPrice(order.discount) }} تومان</span>
              </div>
              <div class="flex justify-between font-bold text-secondary-800 pt-2 border-t border-secondary-100">
                <span>مبلغ کل</span>
                <span>{{ formatPrice(order.total) }} تومان</span>
              </div>
            </div>
          </div>

          <!-- Shipping Code -->
          <div>
            <h2 class="text-base font-bold text-secondary-800 mb-3 flex items-center gap-2">
              <Truck class="w-4 h-4 text-primary-500" /> کد رهگیری ارسال
            </h2>
            <div v-if="!editingShipping && order.shipping_code" class="flex items-center gap-3">
              <div class="flex-1 bg-secondary-50 rounded-lg px-4 py-2.5 text-sm font-mono text-secondary-800 border border-secondary-200">
                {{ order.shipping_code }}
              </div>
              <button
                class="px-3 py-2 border border-secondary-200 rounded-lg text-sm text-secondary-600 hover:bg-secondary-50 transition"
                @click="editingShipping = true; shippingCodeInput = order.shipping_code ?? ''"
              >
                ویرایش
              </button>
            </div>
            <div v-else class="flex items-center gap-3">
              <input
                v-model="shippingCodeInput"
                type="text"
                dir="ltr"
                placeholder="کد رهگیری پست یا پیک را وارد کنید..."
                class="flex-1 h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm"
              />
              <button
                :disabled="isSettingShipping || !shippingCodeInput.trim()"
                class="px-4 py-2 bg-primary-500 text-white rounded-lg text-sm hover:bg-primary-600 disabled:opacity-50 transition"
                @click="saveShippingCode"
              >
                {{ isSettingShipping ? 'در حال ثبت...' : 'ثبت کد' }}
              </button>
              <button
                v-if="order.shipping_code"
                class="px-3 py-2 border border-secondary-200 rounded-lg text-sm text-secondary-500 hover:bg-secondary-50 transition"
                @click="editingShipping = false"
              >
                انصراف
              </button>
            </div>
            <p v-if="!order.shipping_code && !editingShipping" class="mt-2 text-xs text-secondary-400">
              هنوز کد رهگیری ثبت نشده است.
            </p>
          </div>

        </div>

        <!-- History Tab -->
        <div v-else-if="activeTab === 'history'" class="p-6">
          <div v-if="!order.histories?.length" class="text-center py-8 text-secondary-400 text-sm">
            هنوز تاریخچه‌ای ثبت نشده است.
          </div>
          <ol class="relative border-r border-secondary-200 mr-3 space-y-6">
            <li v-for="entry in order.histories" :key="entry.id" class="mr-6">
              <span
                :class="['absolute -right-3 flex items-center justify-center w-6 h-6 rounded-full ring-4 ring-white', historyIconClass(entry.event)]"
              >
                <!-- B9+B10 fix: added payment_status_changed icon (Banknote) -->
                <Truck       v-if="historyIcon(entry.event) === 'truck'"   class="w-3 h-3" />
                <Bell        v-else-if="historyIcon(entry.event) === 'bell'"    class="w-3 h-3" />
                <CheckCircle v-else-if="historyIcon(entry.event) === 'check'"   class="w-3 h-3" />
                <XCircle     v-else-if="historyIcon(entry.event) === 'x'"       class="w-3 h-3" />
                <Package     v-else-if="historyIcon(entry.event) === 'package'" class="w-3 h-3" />
                <Banknote    v-else-if="historyIcon(entry.event) === 'credit'"  class="w-3 h-3" />
                <Clock       v-else class="w-3 h-3" />
              </span>
              <div class="bg-secondary-50 rounded-lg p-4 text-sm">
                <div class="flex items-center justify-between gap-2 mb-1">
                  <span class="font-semibold text-secondary-800">{{ entry.event_label }}</span>
                  <!-- B8 fix: use formatDateTime so same-day events are distinguishable -->
                  <span class="text-xs text-secondary-400 shrink-0">{{ formatDateTime(entry.created_at) }}</span>
                </div>
                <!-- B3 fix: show human-readable labels, fall back to raw key if label not available -->
                <div v-if="entry.from_status || entry.to_status" class="text-xs text-secondary-500 mb-1">
                  <span v-if="entry.from_status">
                    از: <code class="bg-secondary-200 px-1 rounded">{{ entry.from_status_label || entry.from_status }}</code>
                  </span>
                  <span v-if="entry.to_status" class="mr-2">
                    به: <code class="bg-primary-100 text-primary-700 px-1 rounded">{{ entry.to_status_label || entry.to_status }}</code>
                  </span>
                </div>
                <p v-if="entry.note" class="text-secondary-600">{{ entry.note }}</p>
                <p v-if="entry.actor" class="text-xs text-secondary-400 mt-1">توسط: {{ entry.actor.name }}</p>
              </div>
            </li>
          </ol>
        </div>
      </div>

      <!-- Payment Info (always visible, even on print) -->
      <div v-if="order.latest_transaction" class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-base font-bold text-secondary-800 mb-4 flex items-center gap-2">
          <CreditCard class="w-4 h-4 text-primary-500" /> اطلاعات پرداخت
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
          <div>
            <p class="text-secondary-500 mb-0.5">روش پرداخت</p>
            <p class="font-medium">{{ driverLabel(order.latest_transaction.driver) }}</p>
          </div>
          <div>
            <p class="text-secondary-500 mb-0.5">مبلغ</p>
            <p class="font-medium">{{ formatPrice(order.latest_transaction.amount) }} تومان</p>
          </div>
          <div>
            <p class="text-secondary-500 mb-0.5">وضعیت</p>
            <span :class="['text-xs px-2 py-1 rounded-full font-medium', txStatusClass(order.latest_transaction.status)]">
              {{ txStatusLabel(order.latest_transaction.status) }}
            </span>
          </div>
          <div v-if="order.latest_transaction.gateway_ref">
            <p class="text-secondary-500 mb-0.5">کد مرجع</p>
            <p class="font-mono text-xs">{{ order.latest_transaction.gateway_ref }}</p>
          </div>
          <div v-if="order.latest_transaction.card_last_four">
            <p class="text-secondary-500 mb-0.5">چهار رقم آخر کارت</p>
            <p class="font-mono">**** {{ order.latest_transaction.card_last_four }}</p>
          </div>
          <div v-if="order.latest_transaction.paid_at">
            <p class="text-secondary-500 mb-0.5">تاریخ پرداخت</p>
            <p>{{ formatDate(order.latest_transaction.paid_at) }}</p>
          </div>
        </div>

        <!-- Receipt Image -->
        <div v-if="order.latest_transaction.receipt_path" class="mt-4">
          <p class="text-secondary-500 text-sm mb-2 flex items-center gap-1"><FileImage class="w-4 h-4" /> تصویر رسید</p>
          <a :href="order.latest_transaction.receipt_path" target="_blank" rel="noopener noreferrer">
            <img :src="order.latest_transaction.receipt_path" alt="تصویر رسید" class="max-h-64 rounded-lg border border-secondary-200 object-contain" />
          </a>
        </div>

        <!-- Card-to-card receipt actions -->
        <div
          v-if="order.latest_transaction.driver === 'card_to_card' && order.latest_transaction.status === 'pending'"
          class="mt-4 flex items-center gap-3 pt-4 border-t border-secondary-100 print:hidden"
        >
          <button
            :disabled="confirmingReceipt"
            class="flex items-center gap-2 px-4 py-2 bg-green-500 hover:bg-green-600 disabled:opacity-50 text-white rounded-lg text-sm transition"
            @click="confirmReceipt"
          >
            <CheckCircle class="w-4 h-4" />
            {{ confirmingReceipt ? 'در حال تأیید...' : 'تأیید رسید' }}
          </button>
          <button
            :disabled="rejectingReceipt"
            class="flex items-center gap-2 px-4 py-2 bg-red-500 hover:bg-red-600 disabled:opacity-50 text-white rounded-lg text-sm transition"
            @click="rejectReceipt"
          >
            <XCircle class="w-4 h-4" />
            {{ rejectingReceipt ? 'در حال رد...' : 'رد رسید' }}
          </button>
        </div>
      </div>

      <!-- Admin Notes -->
      <div class="bg-white rounded-xl shadow-sm p-6 print:hidden">
        <h2 class="text-base font-bold text-secondary-800 mb-3">یادداشت ادمین</h2>
        <textarea
          v-model="adminNotesText"
          rows="3"
          placeholder="یادداشت داخلی برای این سفارش..."
          class="w-full px-4 py-3 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm resize-none"
        ></textarea>
        <div class="mt-2 flex justify-end">
          <button
            :disabled="isSavingNotes"
            class="px-4 py-2 bg-primary-500 text-white rounded-lg text-sm hover:bg-primary-600 disabled:opacity-50 transition"
            @click="saveNotes"
          >
            {{ isSavingNotes ? 'در حال ذخیره...' : 'ذخیره یادداشت' }}
          </button>
        </div>
      </div>

    </div>

    <!-- Send Notification Modal -->
    <transition name="fade">
      <div
        v-if="showNotifyModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 print:hidden"
        @click.self="showNotifyModal = false"
      >
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
          <div class="flex items-center justify-between px-6 py-4 border-b border-secondary-100">
            <h2 class="font-semibold text-secondary-800 flex items-center gap-2">
              <Bell class="w-4 h-4 text-primary-500" /> ارسال اعلان به مشتری
            </h2>
            <button class="p-1 hover:bg-secondary-100 rounded" @click="showNotifyModal = false">
              <span class="sr-only">بستن</span>
              <AlertCircle class="w-5 h-5 text-secondary-400" />
            </button>
          </div>
          <div class="p-6 space-y-4">
            <p class="text-sm text-secondary-500">
              پیام زیر برای مشتری
              <strong>{{ order?.customer_name || order?.user?.name || '—' }}</strong>
              ارسال می‌شود.
            </p>
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">
                پیام اعلان
                <span class="text-secondary-400 font-normal">(اختیاری — پیام پیش‌فرض وضعیت جاری استفاده می‌شود)</span>
              </label>
              <textarea
                v-model="notificationMsg"
                rows="3"
                :placeholder="currentStatusNotifyMessage || 'وضعیت سفارش شما به‌روزرسانی شد.'"
                class="w-full px-4 py-3 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm resize-none"
              ></textarea>
            </div>
            <div class="flex items-center justify-end gap-3 pt-2">
              <button
                class="px-4 py-2 border border-secondary-200 rounded-lg text-sm text-secondary-600 hover:bg-secondary-50 transition"
                @click="showNotifyModal = false"
              >
                انصراف
              </button>
              <button
                :disabled="isSendingNotification"
                class="flex items-center gap-2 px-5 py-2 bg-primary-500 text-white rounded-lg text-sm hover:bg-primary-600 disabled:opacity-50 transition"
                @click="sendNotification"
              >
                <Bell class="w-4 h-4" />
                {{ isSendingNotification ? 'در حال ارسال...' : 'ارسال اعلان' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<style>
@media print {
  .print\:hidden { display: none !important; }
  .print\:block { display: block !important; }
}
</style>
