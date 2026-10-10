<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowRight, Package, User, MapPin, Loader2, AlertCircle, Clock } from 'lucide-vue-next'
import { formatDate, formatPrice } from '@shared/utils/format'
import api from '@shared/utils/api'

const route  = useRoute()
const router = useRouter()

// ── Types ──────────────────────────────────────────────────────────────────────
interface OrderItem {
  id: number
  product_name: string
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

interface HistoryEntry {
  id: number
  event_label: string
  to_status_label: string | null
  note: string | null
  actor: { name: string } | null
  created_at: string
}

interface OrderDetail {
  id: number
  order_number: string
  status: string
  status_label: string
  payment_status: string
  subtotal: number
  tax: number
  shipping: number
  discount: number
  total: number
  shipping_code: string | null
  notes: string | null
  created_at: string
  items: OrderItem[]
  address: OrderAddress | null
  histories: HistoryEntry[]
  user: { name: string; email: string; phone?: string } | null
}

// ── State ──────────────────────────────────────────────────────────────────────
const order     = ref<OrderDetail | null>(null)
const isLoading = ref(true)
const errorMsg  = ref('')

// ── Load ───────────────────────────────────────────────────────────────────────
const load = async () => {
  isLoading.value = true
  errorMsg.value  = ''
  try {
    const { data } = await api.get(`/store/orders/${route.params.id}`)
    order.value = data.data
  } catch (e: any) {
    errorMsg.value = e.response?.status === 403
      ? 'این سفارش متعلق به فروشگاه شما نیست.'
      : 'خطا در بارگذاری جزئیات سفارش'
  } finally {
    isLoading.value = false
  }
}

// ── Helpers ────────────────────────────────────────────────────────────────────
const statusClass: Record<string, string> = {
  pending:    'bg-amber-100 text-amber-700',
  processing: 'bg-blue-100 text-blue-700',
  shipped:    'bg-purple-100 text-purple-700',
  delivered:  'bg-green-100 text-green-700',
  cancelled:  'bg-red-100 text-red-600',
  refunded:   'bg-secondary-100 text-secondary-500',
}

const paymentStatusClass: Record<string, string> = {
  pending: 'bg-amber-50 text-amber-600 border-amber-200',
  paid:    'bg-green-50 text-green-600 border-green-200',
  failed:  'bg-red-50 text-red-500 border-red-200',
}

onMounted(load)
</script>

<template>
  <div dir="rtl">
    <!-- Back -->
    <button
      @click="router.push({ name: 'store-orders' })"
      class="flex items-center gap-2 text-sm text-secondary-500 hover:text-primary-600 mb-5 transition"
    >
      <ArrowRight class="w-4 h-4" />
      بازگشت به سفارشات
    </button>

    <!-- Loading -->
    <div v-if="isLoading" class="flex items-center justify-center py-24">
      <Loader2 class="w-8 h-8 animate-spin text-primary-500" />
    </div>

    <!-- Error -->
    <div v-else-if="errorMsg" class="flex items-center gap-3 bg-red-50 text-red-600 rounded-xl p-5 text-sm">
      <AlertCircle class="w-5 h-5 flex-shrink-0" />
      {{ errorMsg }}
    </div>

    <template v-else-if="order">
      <!-- Header -->
      <div class="flex flex-wrap items-center justify-between mb-6 gap-3">
        <div>
          <h2 class="text-2xl font-bold text-secondary-800">سفارش #{{ order.order_number }}</h2>
          <p class="text-sm text-secondary-500 mt-1">{{ formatDate(order.created_at) }}</p>
        </div>
        <div class="flex items-center gap-3">
          <span :class="['text-sm font-medium px-3 py-1.5 rounded-full', statusClass[order.status] || 'bg-secondary-100 text-secondary-500']">
            {{ order.status_label || order.status }}
          </span>
          <span :class="['text-sm font-medium px-3 py-1.5 rounded-full border', paymentStatusClass[order.payment_status] || 'bg-secondary-50 text-secondary-500 border-secondary-200']">
            {{ order.payment_status === 'paid' ? 'پرداخت شده' : order.payment_status === 'failed' ? 'ناموفق' : 'در انتظار پرداخت' }}
          </span>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- Left col: items + summary -->
        <div class="lg:col-span-2 space-y-4">

          <!-- Order Items -->
          <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="flex items-center gap-2 px-5 py-4 border-b border-secondary-100">
              <Package class="w-4 h-4 text-primary-600" />
              <h3 class="font-bold text-secondary-800">اقلام سفارش</h3>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-secondary-50 text-right">
                  <tr>
                    <th class="px-4 py-2 font-medium text-secondary-500">محصول</th>
                    <th class="px-4 py-2 font-medium text-secondary-500 text-center">تعداد</th>
                    <th class="px-4 py-2 font-medium text-secondary-500">قیمت واحد</th>
                    <th class="px-4 py-2 font-medium text-secondary-500">جمع</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-secondary-100">
                  <tr v-for="item in order.items" :key="item.id" class="hover:bg-secondary-50">
                    <td class="px-4 py-3">
                      <div class="font-medium text-secondary-800">{{ item.product_name }}</div>
                      <div v-if="item.sku" class="text-xs text-secondary-400 font-mono mt-0.5">{{ item.sku }}</div>
                    </td>
                    <td class="px-4 py-3 text-center text-secondary-600">{{ item.quantity }}</td>
                    <td class="px-4 py-3 text-secondary-600">{{ formatPrice(item.price) }}</td>
                    <td class="px-4 py-3 font-semibold text-secondary-800">{{ formatPrice(item.subtotal) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Totals -->
            <div class="border-t border-secondary-100 px-5 py-4 space-y-2">
              <div class="flex justify-between text-sm text-secondary-600">
                <span>جمع اقلام</span><span>{{ formatPrice(order.subtotal) }} تومان</span>
              </div>
              <div v-if="order.discount" class="flex justify-between text-sm text-green-600">
                <span>تخفیف</span><span>- {{ formatPrice(order.discount) }} تومان</span>
              </div>
              <div class="flex justify-between text-sm text-secondary-600">
                <span>هزینه ارسال</span><span>{{ formatPrice(order.shipping) }} تومان</span>
              </div>
              <div v-if="order.tax" class="flex justify-between text-sm text-secondary-600">
                <span>مالیات</span><span>{{ formatPrice(order.tax) }} تومان</span>
              </div>
              <div class="flex justify-between font-bold text-secondary-800 pt-2 border-t border-secondary-100">
                <span>مبلغ نهایی</span><span>{{ formatPrice(order.total) }} تومان</span>
              </div>
            </div>
          </div>

          <!-- Shipping code (read-only) -->
          <div v-if="order.shipping_code" class="bg-white rounded-xl shadow-sm px-5 py-4 flex items-center justify-between">
            <span class="text-sm text-secondary-600">کد رهگیری مرسوله</span>
            <span class="font-mono font-semibold text-secondary-800 bg-secondary-100 px-3 py-1 rounded-lg text-sm">{{ order.shipping_code }}</span>
          </div>

          <!-- Notes -->
          <div v-if="order.notes" class="bg-amber-50 border border-amber-200 rounded-xl px-5 py-4">
            <p class="text-sm font-medium text-amber-700 mb-1">یادداشت مشتری</p>
            <p class="text-sm text-amber-800">{{ order.notes }}</p>
          </div>
        </div>

        <!-- Right col: customer + address + history -->
        <div class="space-y-4">

          <!-- Customer info -->
          <div class="bg-white rounded-xl shadow-sm px-5 py-4">
            <div class="flex items-center gap-2 mb-3">
              <User class="w-4 h-4 text-primary-600" />
              <h3 class="font-bold text-secondary-800">مشتری</h3>
            </div>
            <div v-if="order.user" class="space-y-1 text-sm text-secondary-700">
              <p class="font-medium">{{ order.user.name }}</p>
              <p class="text-secondary-500">{{ order.user.email }}</p>
              <p v-if="order.user.phone" class="text-secondary-500">{{ order.user.phone }}</p>
            </div>
            <p v-else class="text-sm text-secondary-400">اطلاعات مشتری موجود نیست</p>
          </div>

          <!-- Delivery address -->
          <div class="bg-white rounded-xl shadow-sm px-5 py-4">
            <div class="flex items-center gap-2 mb-3">
              <MapPin class="w-4 h-4 text-primary-600" />
              <h3 class="font-bold text-secondary-800">آدرس تحویل</h3>
            </div>
            <div v-if="order.address" class="text-sm text-secondary-700 space-y-1">
              <p class="font-medium">{{ order.address.first_name }} {{ order.address.last_name }}</p>
              <p>{{ order.address.phone }}</p>
              <p class="text-secondary-500">{{ order.address.province }}، {{ order.address.city }}</p>
              <p class="text-secondary-500">{{ order.address.address }}</p>
              <p class="font-mono text-xs text-secondary-400">{{ order.address.postal_code }}</p>
            </div>
            <p v-else class="text-sm text-secondary-400">آدرسی ثبت نشده</p>
          </div>

          <!-- Order history -->
          <div v-if="order.histories?.length" class="bg-white rounded-xl shadow-sm px-5 py-4">
            <div class="flex items-center gap-2 mb-3">
              <Clock class="w-4 h-4 text-primary-600" />
              <h3 class="font-bold text-secondary-800">تاریخچه سفارش</h3>
            </div>
            <div class="space-y-3">
              <div v-for="entry in order.histories" :key="entry.id" class="text-xs text-secondary-600 border-r-2 border-primary-200 pr-3">
                <div class="font-medium text-secondary-700">{{ entry.event_label }}</div>
                <div v-if="entry.to_status_label" class="text-secondary-500">→ {{ entry.to_status_label }}</div>
                <div v-if="entry.note" class="text-secondary-400 italic mt-0.5">{{ entry.note }}</div>
                <div class="text-secondary-400 mt-0.5">{{ formatDate(entry.created_at) }} — {{ entry.actor?.name || 'سیستم' }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
