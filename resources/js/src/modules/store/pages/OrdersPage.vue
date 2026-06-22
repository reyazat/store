<script setup lang="ts">
import { ref, reactive, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Search, ShoppingCart, Eye, ChevronRight, ChevronLeft, Loader2 } from 'lucide-vue-next'
import { formatDate, formatPrice } from '@shared/utils/format'
import api from '@shared/utils/api'

const router = useRouter()

// ── Types ──────────────────────────────────────────────────────────────────────
interface Order {
  id: number
  order_number: string
  customer_name: string | null
  user: { name: string; email: string } | null
  items_count: number
  total: number
  status: string
  status_label: string
  payment_status: string
  created_at: string
}

interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

// ── State ──────────────────────────────────────────────────────────────────────
const orders    = ref<Order[]>([])
const isLoading = ref(false)
const errorMsg  = ref('')
const meta      = ref<PaginationMeta>({ current_page: 1, last_page: 1, per_page: 20, total: 0 })

const filters = reactive({
  search:         '',
  status:         '',
  payment_status: '',
  per_page:       20,
})

let searchTimer: ReturnType<typeof setTimeout> | null = null

// ── Load ───────────────────────────────────────────────────────────────────────
const load = async (page = 1) => {
  isLoading.value = true
  errorMsg.value  = ''
  try {
    const params: Record<string, string | number> = { per_page: filters.per_page, page }
    if (filters.search)         params.search         = filters.search
    if (filters.status)         params.status         = filters.status
    if (filters.payment_status) params.payment_status = filters.payment_status

    const { data } = await api.get('/store/orders', { params })
    orders.value = data.data
    meta.value   = data.meta
  } catch {
    errorMsg.value = 'خطا در بارگذاری سفارشات'
  } finally {
    isLoading.value = false
  }
}

const reload = () => load(1)

const debouncedReload = () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(reload, 350)
}

watch(() => [filters.status, filters.payment_status], reload)

// ── Helpers ────────────────────────────────────────────────────────────────────
const statusLabel: Record<string, string> = {
  pending:    'در انتظار',
  processing: 'در حال پردازش',
  shipped:    'ارسال شده',
  delivered:  'تحویل داده شده',
  cancelled:  'لغو شده',
  refunded:   'مسترد شده',
}

const statusClass: Record<string, string> = {
  pending:    'bg-amber-100 text-amber-700',
  processing: 'bg-blue-100 text-blue-700',
  shipped:    'bg-purple-100 text-purple-700',
  delivered:  'bg-green-100 text-green-700',
  cancelled:  'bg-red-100 text-red-600',
  refunded:   'bg-secondary-100 text-secondary-500',
}

const paymentStatusLabel: Record<string, string> = {
  pending: 'در انتظار',
  paid:    'پرداخت شده',
  failed:  'ناموفق',
}

const paymentStatusClass: Record<string, string> = {
  pending: 'bg-amber-50 text-amber-600',
  paid:    'bg-green-50 text-green-600',
  failed:  'bg-red-50 text-red-500',
}

onMounted(() => load())
</script>

<template>
  <div dir="rtl">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-secondary-800">سفارشات فروشگاه</h2>
        <p v-if="!isLoading" class="text-sm text-secondary-500 mt-1">{{ meta.total }} سفارش</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-4 mb-4 flex flex-wrap items-center gap-3">
      <div class="relative flex-1 min-w-48">
        <Search class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-secondary-400 pointer-events-none" />
        <input
          v-model="filters.search"
          type="text"
          placeholder="جستجو بر اساس شماره سفارش یا نام مشتری..."
          class="w-full pr-9 pl-4 h-10 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm"
          @input="debouncedReload"
        />
      </div>
      <select
        v-model="filters.status"
        class="h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 text-sm text-secondary-700"
      >
        <option value="">همه وضعیت‌ها</option>
        <option value="pending">در انتظار</option>
        <option value="processing">در حال پردازش</option>
        <option value="shipped">ارسال شده</option>
        <option value="delivered">تحویل داده شده</option>
        <option value="cancelled">لغو شده</option>
        <option value="refunded">مسترد شده</option>
      </select>
      <select
        v-model="filters.payment_status"
        class="h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 text-sm text-secondary-700"
      >
        <option value="">همه وضعیت‌های پرداخت</option>
        <option value="pending">در انتظار پرداخت</option>
        <option value="paid">پرداخت شده</option>
        <option value="failed">ناموفق</option>
      </select>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <!-- Loading -->
      <div v-if="isLoading" class="flex items-center justify-center py-20">
        <Loader2 class="w-8 h-8 animate-spin text-primary-500" />
      </div>

      <!-- Error -->
      <div v-else-if="errorMsg" class="p-6 text-center">
        <p class="text-red-500 mb-3">{{ errorMsg }}</p>
        <button class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm" @click="reload">تلاش مجدد</button>
      </div>

      <!-- Empty -->
      <div v-else-if="!orders.length" class="flex flex-col items-center justify-center py-20 text-secondary-400">
        <ShoppingCart class="w-12 h-12 mb-3" />
        <p>هیچ سفارشی یافت نشد</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-secondary-50 border-b border-secondary-100 text-right">
            <tr>
              <th class="px-4 py-3 font-medium text-secondary-500">شماره سفارش</th>
              <th class="px-4 py-3 font-medium text-secondary-500">مشتری</th>
              <th class="px-4 py-3 font-medium text-secondary-500 text-center">تعداد اقلام</th>
              <th class="px-4 py-3 font-medium text-secondary-500">مبلغ کل</th>
              <th class="px-4 py-3 font-medium text-secondary-500">وضعیت سفارش</th>
              <th class="px-4 py-3 font-medium text-secondary-500">وضعیت پرداخت</th>
              <th class="px-4 py-3 font-medium text-secondary-500">تاریخ</th>
              <th class="px-4 py-3 font-medium text-secondary-500">جزئیات</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-secondary-100">
            <tr
              v-for="order in orders"
              :key="order.id"
              class="hover:bg-secondary-50 transition"
            >
              <td class="px-4 py-3">
                <span class="font-mono text-xs font-semibold text-secondary-700">{{ order.order_number }}</span>
              </td>
              <td class="px-4 py-3 text-secondary-700">
                {{ order.user?.name || order.customer_name || '—' }}
              </td>
              <td class="px-4 py-3 text-center text-secondary-500">{{ order.items_count }}</td>
              <td class="px-4 py-3 font-medium text-secondary-800">
                {{ formatPrice(order.total) }} تومان
              </td>
              <td class="px-4 py-3">
                <span :class="['text-xs font-medium px-2 py-1 rounded-full', statusClass[order.status] || 'bg-secondary-100 text-secondary-500']">
                  {{ statusLabel[order.status] || order.status_label || order.status }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span :class="['text-xs font-medium px-2 py-1 rounded-full', paymentStatusClass[order.payment_status] || 'bg-secondary-100 text-secondary-500']">
                  {{ paymentStatusLabel[order.payment_status] || order.payment_status }}
                </span>
              </td>
              <td class="px-4 py-3 text-secondary-500 text-xs">{{ formatDate(order.created_at) }}</td>
              <td class="px-4 py-3">
                <button
                  @click="router.push({ name: 'store-order-detail', params: { id: order.id } })"
                  class="flex items-center gap-1 px-3 py-1.5 bg-primary-50 hover:bg-primary-100 text-primary-700 rounded-lg text-xs font-medium transition"
                >
                  <Eye class="w-3.5 h-3.5" />
                  مشاهده
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-secondary-100 bg-secondary-50 text-sm text-secondary-600">
        <span>صفحه {{ meta.current_page }} از {{ meta.last_page }}</span>
        <div class="flex items-center gap-1">
          <button
            :disabled="meta.current_page <= 1"
            @click="load(meta.current_page - 1)"
            class="p-1.5 rounded hover:bg-secondary-200 disabled:opacity-40 disabled:cursor-not-allowed transition"
          >
            <ChevronRight class="w-4 h-4" />
          </button>
          <button
            v-for="page in meta.last_page"
            :key="page"
            @click="load(page)"
            :class="[
              'w-8 h-8 rounded-lg transition text-sm',
              page === meta.current_page
                ? 'bg-primary-600 text-white font-semibold'
                : 'hover:bg-secondary-200 text-secondary-600'
            ]"
          >{{ page }}</button>
          <button
            :disabled="meta.current_page >= meta.last_page"
            @click="load(meta.current_page + 1)"
            class="p-1.5 rounded hover:bg-secondary-200 disabled:opacity-40 disabled:cursor-not-allowed transition"
          >
            <ChevronLeft class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
