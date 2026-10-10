<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import {
  ShoppingCart, Package, Loader2, AlertCircle,
  TrendingUp, DollarSign, BarChart2, Users,
  Store, Star,
} from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import api from '@shared/utils/api'
import SimpleBarChart from '@shared/components/SimpleBarChart.vue'
import type { BarDataPoint } from '@shared/components/SimpleBarChart.vue'
import { useStorePermissions } from '../composables/useStorePermissions'

const authStore = useAuthStore()
const router    = useRouter()
const { canViewOrders, canManageReviews, currentRole } = useStorePermissions()

// ─── Types ────────────────────────────────────────────────────────────────────

interface StoreInfo {
  id: number; uuid: string; name: string; status: string; members_count: number
}
interface TeamBreakdown {
  owners: number; admins: number; editors: number; users: number; members: number
}
interface Stats {
  total_products: number; active_products: number
  total_orders: number; pending_orders: number; processing_orders: number; today_orders: number
  total_revenue: number; revenue_this_month: number; revenue_today: number; avg_order_value: number
  pending_reviews: number
}
interface RevenuePoint { date: string; label: string; revenue: number }
interface OrderStatusBreakdown { pending: number; processing: number; shipped: number; delivered: number; cancelled: number }
interface TopProduct { id: number; name: string; main_image?: string; price: number; sales_count: number; revenue: number }
interface RecentOrder { id: number; order_number: string; status: string; payment_status: string; total: number; customer_name?: string; created_at: string }

interface DashboardData {
  store: StoreInfo
  team: TeamBreakdown
  stats: Stats
  revenue_chart: RevenuePoint[]
  order_status_breakdown: OrderStatusBreakdown
  top_products: TopProduct[]
  recent_orders: RecentOrder[]
}

// ─── State ────────────────────────────────────────────────────────────────────

const data      = ref<DashboardData | null>(null)
const isLoading = ref(true)
const error     = ref('')

const load = async () => {
  isLoading.value = true
  error.value     = ''
  try {
    const res   = await api.get('/store/dashboard')
    data.value  = res.data?.data ?? null
  } catch {
    error.value = 'خطا در بارگذاری اطلاعات داشبورد'
  } finally {
    isLoading.value = false
  }
}

onMounted(load)

// ─── Computed helpers ─────────────────────────────────────────────────────────

const revenueChartData = computed<BarDataPoint[]>(() =>
  (data.value?.revenue_chart ?? []).map(p => ({ label: p.label, value: p.revenue }))
)

const totalOrdersForBreakdown = computed(() => {
  const s = data.value?.order_status_breakdown
  if (!s) return 0
  return s.pending + s.processing + s.shipped + s.delivered + s.cancelled
})

const breakdownBars = computed<{ label: string; color: string; value: number; pct: number }[]>(() => {
  const s = data.value?.order_status_breakdown
  const total = totalOrdersForBreakdown.value
  if (!s || total === 0) return []
  return [
    { label: 'در انتظار',     color: 'bg-amber-400',  value: s.pending,    pct: Math.round(s.pending    / total * 100) },
    { label: 'در پردازش',     color: 'bg-blue-400',   value: s.processing, pct: Math.round(s.processing / total * 100) },
    { label: 'ارسال شده',     color: 'bg-purple-400', value: s.shipped,    pct: Math.round(s.shipped    / total * 100) },
    { label: 'تحویل داده شده', color: 'bg-green-400', value: s.delivered,  pct: Math.round(s.delivered  / total * 100) },
    { label: 'لغو شده',       color: 'bg-red-400',    value: s.cancelled,  pct: Math.round(s.cancelled  / total * 100) },
  ]
})

// ─── Formatters ───────────────────────────────────────────────────────────────

const fmt = (n: number) => new Intl.NumberFormat('fa-IR').format(Math.round(n))
const fmtCompact = (n: number) => {
  if (n >= 1_000_000) return `${(n / 1_000_000).toFixed(1)}M`
  if (n >= 1_000)     return `${(n / 1_000).toFixed(1)}K`
  return fmt(n)
}

const statusLabel = (s: string) => ({
  pending: 'در انتظار', processing: 'در پردازش', shipped: 'ارسال شده',
  delivered: 'تحویل شده', cancelled: 'لغو شده',
}[s] ?? s)

const statusClass = (s: string) => ({
  pending:    'bg-amber-100 text-amber-700',
  processing: 'bg-blue-100  text-blue-700',
  shipped:    'bg-purple-100 text-purple-700',
  delivered:  'bg-green-100  text-green-700',
  cancelled:  'bg-red-100    text-red-600',
}[s] ?? 'bg-secondary-100 text-secondary-500')

const payBadge = (s: string) => ({
  paid:    'bg-green-100 text-green-700',
  pending: 'bg-amber-100 text-amber-600',
  failed:  'bg-red-100   text-red-600',
}[s] ?? 'bg-secondary-100 text-secondary-500')

const storeStatusClass = (s: string) => ({
  active:    'bg-green-100 text-green-700',
  inactive:  'bg-secondary-100 text-secondary-500',
  suspended: 'bg-red-100 text-red-600',
}[s] ?? '')

const storeStatusLabel = (s: string) => ({
  active: 'فعال', inactive: 'غیرفعال', suspended: 'معلق',
}[s] ?? s)

const shortDate = (iso: string) => {
  const d = new Date(iso)
  return d.toLocaleDateString('fa-IR', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
  <div dir="rtl">

    <!-- Loading -->
    <div v-if="isLoading" class="flex items-center justify-center py-28">
      <Loader2 class="w-9 h-9 animate-spin text-primary-500" />
    </div>

    <!-- Error -->
    <div v-else-if="error" class="flex items-center gap-3 bg-red-50 text-red-600 rounded-xl p-4 text-sm">
      <AlertCircle class="w-5 h-5 flex-shrink-0" />
      {{ error }}
    </div>

    <template v-else-if="data">

      <!-- ── Header ─────────────────────────────────────────────────────────── -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-2xl font-bold text-secondary-800">
            خوش آمدید، {{ authStore.user?.name }}
          </h2>
          <p class="text-secondary-500 mt-1 text-sm">
            <span class="font-semibold text-primary-600">{{ data.store.name }}</span>
            <span v-if="currentRole" class="mr-2 text-xs bg-secondary-100 text-secondary-600 px-2 py-0.5 rounded-full">{{ currentRole }}</span>
          </p>
        </div>
        <span :class="['text-xs font-medium px-3 py-1.5 rounded-full', storeStatusClass(data.store.status)]">
          {{ storeStatusLabel(data.store.status) }}
        </span>
      </div>

      <!-- ── Row 1 · Revenue KPIs (owner/admin/editor see revenue; limited roles see counts) ── -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">

        <!-- Total Revenue -->
        <div class="bg-white rounded-xl p-5 shadow-sm">
          <div class="flex items-center justify-between mb-2">
            <span class="text-secondary-500 text-xs">فروش کل</span>
            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
              <DollarSign class="w-4 h-4 text-green-600" />
            </div>
          </div>
          <p class="text-2xl font-bold text-secondary-800">{{ fmtCompact(data.stats.total_revenue) }}</p>
          <p class="text-xs text-secondary-400 mt-1">تومان · همه زمان‌ها</p>
        </div>

        <!-- This Month Revenue -->
        <div class="bg-white rounded-xl p-5 shadow-sm">
          <div class="flex items-center justify-between mb-2">
            <span class="text-secondary-500 text-xs">درآمد این ماه</span>
            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
              <TrendingUp class="w-4 h-4 text-blue-600" />
            </div>
          </div>
          <p class="text-2xl font-bold text-secondary-800">{{ fmtCompact(data.stats.revenue_this_month) }}</p>
          <p class="text-xs text-secondary-400 mt-1">تومان · ماه جاری</p>
        </div>

        <!-- Today Revenue -->
        <div class="bg-white rounded-xl p-5 shadow-sm">
          <div class="flex items-center justify-between mb-2">
            <span class="text-secondary-500 text-xs">درآمد امروز</span>
            <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center">
              <BarChart2 class="w-4 h-4 text-amber-600" />
            </div>
          </div>
          <p class="text-2xl font-bold text-secondary-800">{{ fmtCompact(data.stats.revenue_today) }}</p>
          <p class="text-xs text-secondary-400 mt-1">تومان · امروز</p>
        </div>

        <!-- AOV -->
        <div class="bg-white rounded-xl p-5 shadow-sm">
          <div class="flex items-center justify-between mb-2">
            <span class="text-secondary-500 text-xs">میانگین سبد</span>
            <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
              <ShoppingCart class="w-4 h-4 text-purple-600" />
            </div>
          </div>
          <p class="text-2xl font-bold text-secondary-800">{{ fmtCompact(data.stats.avg_order_value) }}</p>
          <p class="text-xs text-secondary-400 mt-1">تومان · هر سفارش</p>
        </div>
      </div>

      <!-- ── Row 2 · Operational KPIs ─────────────────────────────────────── -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">

        <button
          v-if="canViewOrders"
          @click="router.push('/store/orders')"
          class="bg-white rounded-xl p-4 shadow-sm text-right hover:shadow-md transition w-full"
        >
          <p class="text-xs text-secondary-500 mb-1">کل سفارشات</p>
          <p class="text-xl font-bold text-secondary-800">{{ fmt(data.stats.total_orders) }}</p>
        </button>

        <button
          v-if="canViewOrders"
          @click="router.push('/store/orders')"
          class="bg-amber-50 rounded-xl p-4 shadow-sm text-right hover:shadow-md transition w-full"
        >
          <p class="text-xs text-amber-600 mb-1">در انتظار</p>
          <p class="text-xl font-bold text-amber-700">{{ fmt(data.stats.pending_orders) }}</p>
        </button>

        <button
          v-if="canViewOrders"
          @click="router.push('/store/orders')"
          class="bg-blue-50 rounded-xl p-4 shadow-sm text-right hover:shadow-md transition w-full"
        >
          <p class="text-xs text-blue-600 mb-1">امروز</p>
          <p class="text-xl font-bold text-blue-700">{{ fmt(data.stats.today_orders) }}</p>
        </button>

        <button
          @click="router.push('/store/products')"
          class="bg-white rounded-xl p-4 shadow-sm text-right hover:shadow-md transition w-full"
        >
          <p class="text-xs text-secondary-500 mb-1">محصولات</p>
          <p class="text-xl font-bold text-secondary-800">{{ fmt(data.stats.total_products) }}</p>
          <p class="text-xs text-green-600 mt-0.5">{{ fmt(data.stats.active_products) }} فعال</p>
        </button>

        <button
          v-if="canManageReviews"
          @click="router.push('/store/reviews')"
          class="bg-purple-50 rounded-xl p-4 shadow-sm text-right hover:shadow-md transition w-full"
        >
          <p class="text-xs text-purple-600 mb-1">نظرات در انتظار</p>
          <p class="text-xl font-bold text-purple-700">{{ fmt(data.stats.pending_reviews) }}</p>
        </button>
      </div>

      <!-- ── Row 3 · Revenue chart + Order status ──────────────────────────── -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">

        <!-- Revenue chart (7 days) -->
        <div class="lg:col-span-2 bg-white rounded-xl p-5 shadow-sm">
          <div class="flex items-center gap-2 mb-4">
            <TrendingUp class="w-4 h-4 text-primary-600" />
            <h3 class="font-bold text-secondary-800 text-sm">فروش ۷ روز اخیر</h3>
          </div>
          <SimpleBarChart
            :data="revenueChartData"
            :height="160"
            :show-value="true"
            value-suffix=" ت"
          />
        </div>

        <!-- Order status breakdown -->
        <div class="bg-white rounded-xl p-5 shadow-sm">
          <div class="flex items-center gap-2 mb-4">
            <BarChart2 class="w-4 h-4 text-primary-600" />
            <h3 class="font-bold text-secondary-800 text-sm">توزیع وضعیت سفارشات</h3>
          </div>

          <div v-if="totalOrdersForBreakdown === 0" class="text-center text-secondary-400 text-sm py-8">
            سفارشی ثبت نشده
          </div>
          <div v-else class="space-y-3">
            <div v-for="row in breakdownBars" :key="row.label">
              <div class="flex items-center justify-between text-xs mb-1">
                <span class="text-secondary-600">{{ row.label }}</span>
                <span class="font-medium text-secondary-800">{{ row.value }} ({{ row.pct }}٪)</span>
              </div>
              <div class="w-full bg-secondary-100 rounded-full h-2">
                <div
                  :class="['h-2 rounded-full transition-all', row.color]"
                  :style="{ width: `${row.pct}%` }"
                ></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Row 4 · Top products + Recent orders ───────────────────────────── -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

        <!-- Top Products -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
          <div class="flex items-center gap-2 px-5 py-4 border-b border-secondary-100">
            <Star class="w-4 h-4 text-primary-600" />
            <h3 class="font-bold text-secondary-800 text-sm">برترین محصولات</h3>
          </div>
          <div v-if="!data.top_products.length" class="text-center text-secondary-400 text-sm py-10">
            داده‌ای موجود نیست
          </div>
          <table v-else class="w-full text-sm">
            <thead class="bg-secondary-50 text-secondary-500 text-right text-xs">
              <tr>
                <th class="px-4 py-2.5 font-medium">محصول</th>
                <th class="px-4 py-2.5 font-medium text-center">فروش</th>
                <th class="px-4 py-2.5 font-medium text-center">درآمد</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-secondary-50">
              <tr
                v-for="p in data.top_products"
                :key="p.id"
                class="hover:bg-secondary-50 cursor-pointer"
                @click="router.push('/store/products')"
              >
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <img
                      v-if="p.main_image"
                      :src="p.main_image"
                      :alt="p.name"
                      class="w-8 h-8 rounded object-cover flex-shrink-0 bg-secondary-100"
                    />
                    <div v-else class="w-8 h-8 bg-secondary-100 rounded flex items-center justify-center flex-shrink-0">
                      <Package class="w-4 h-4 text-secondary-400" />
                    </div>
                    <span class="truncate max-w-[120px] text-secondary-800 font-medium">{{ p.name }}</span>
                  </div>
                </td>
                <td class="px-4 py-3 text-center">
                  <span class="bg-blue-50 text-blue-700 text-xs font-medium px-2 py-0.5 rounded-full">
                    {{ fmt(p.sales_count) }}
                  </span>
                </td>
                <td class="px-4 py-3 text-center text-secondary-700 font-medium text-xs">
                  {{ fmtCompact(p.revenue) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Recent Orders -->
        <div v-if="canViewOrders" class="bg-white rounded-xl shadow-sm overflow-hidden">
          <div class="flex items-center justify-between px-5 py-4 border-b border-secondary-100">
            <div class="flex items-center gap-2">
              <ShoppingCart class="w-4 h-4 text-primary-600" />
              <h3 class="font-bold text-secondary-800 text-sm">آخرین سفارشات</h3>
            </div>
            <button
              @click="router.push('/store/orders')"
              class="text-xs text-primary-600 hover:underline"
            >مشاهده همه</button>
          </div>
          <div v-if="!data.recent_orders.length" class="text-center text-secondary-400 text-sm py-10">
            سفارشی ثبت نشده
          </div>
          <div v-else class="divide-y divide-secondary-50">
            <div
              v-for="order in data.recent_orders"
              :key="order.id"
              class="px-4 py-3 hover:bg-secondary-50 cursor-pointer"
              @click="router.push(`/store/orders/${order.id}`)"
            >
              <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                  <p class="text-xs font-medium text-secondary-800 truncate">{{ order.order_number }}</p>
                  <p v-if="order.customer_name" class="text-xs text-secondary-400 truncate">{{ order.customer_name }}</p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                  <span :class="['text-xs font-medium px-2 py-0.5 rounded-full', statusClass(order.status)]">
                    {{ statusLabel(order.status) }}
                  </span>
                  <span :class="['text-xs font-medium px-2 py-0.5 rounded-full', payBadge(order.payment_status)]">
                    {{ order.payment_status === 'paid' ? 'پرداخت شده' : order.payment_status === 'pending' ? 'در انتظار' : 'ناموفق' }}
                  </span>
                </div>
              </div>
              <div class="flex items-center justify-between mt-1">
                <span class="text-xs text-secondary-400">{{ shortDate(order.created_at) }}</span>
                <span class="text-xs font-bold text-secondary-700">{{ fmtCompact(order.total) }} ت</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Store & Team info (shown when orders panel is hidden for limited roles) -->
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <!-- Store Info -->
          <div class="bg-white rounded-xl p-5 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
              <Store class="w-4 h-4 text-primary-600" />
              <h3 class="font-bold text-secondary-800 text-sm">اطلاعات فروشگاه</h3>
            </div>
            <div class="space-y-2 text-sm">
              <div class="flex items-center justify-between">
                <span class="text-secondary-500">نام</span>
                <span class="font-medium text-secondary-800">{{ data.store.name }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-secondary-500">تعداد اعضا</span>
                <span class="font-medium text-secondary-800">{{ data.store.members_count }}</span>
              </div>
            </div>
          </div>

          <!-- Team Breakdown -->
          <div class="bg-white rounded-xl p-5 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
              <Users class="w-4 h-4 text-primary-600" />
              <h3 class="font-bold text-secondary-800 text-sm">تیم فروشگاه</h3>
            </div>
            <div class="grid grid-cols-2 gap-2">
              <div class="bg-purple-50 rounded-lg p-2 text-center">
                <p class="text-lg font-bold text-purple-700">{{ data.team.owners }}</p>
                <p class="text-xs text-purple-500">مالک</p>
              </div>
              <div class="bg-blue-50 rounded-lg p-2 text-center">
                <p class="text-lg font-bold text-blue-700">{{ data.team.admins }}</p>
                <p class="text-xs text-blue-500">مدیر</p>
              </div>
              <div class="bg-yellow-50 rounded-lg p-2 text-center">
                <p class="text-lg font-bold text-yellow-700">{{ data.team.editors }}</p>
                <p class="text-xs text-yellow-500">ویرایشگر</p>
              </div>
              <div class="bg-green-50 rounded-lg p-2 text-center">
                <p class="text-lg font-bold text-green-700">{{ data.team.members + data.team.users }}</p>
                <p class="text-xs text-green-500">عضو</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Row 5 · Store & team info (always visible to owner/admin) ───────── -->
      <div v-if="canViewOrders" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Store Info -->
        <div class="bg-white rounded-xl p-5 shadow-sm">
          <div class="flex items-center gap-2 mb-4">
            <Store class="w-4 h-4 text-primary-600" />
            <h3 class="font-bold text-secondary-800">اطلاعات فروشگاه</h3>
          </div>
          <div class="space-y-2 text-sm">
            <div class="flex items-center justify-between">
              <span class="text-secondary-500">نام فروشگاه</span>
              <span class="font-medium text-secondary-800">{{ data.store.name }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-secondary-500">وضعیت</span>
              <span :class="['text-xs font-medium px-2 py-0.5 rounded-full', storeStatusClass(data.store.status)]">
                {{ storeStatusLabel(data.store.status) }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-secondary-500">تعداد اعضا</span>
              <span class="font-medium text-secondary-800">{{ data.store.members_count }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-secondary-500">محصولات فعال</span>
              <span class="font-medium text-green-700">{{ fmt(data.stats.active_products) }}</span>
            </div>
          </div>
        </div>

        <!-- Team Breakdown -->
        <div class="bg-white rounded-xl p-5 shadow-sm">
          <div class="flex items-center gap-2 mb-4">
            <Users class="w-4 h-4 text-primary-600" />
            <h3 class="font-bold text-secondary-800">تیم فروشگاه</h3>
          </div>
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-purple-50 rounded-lg p-3 text-center">
              <p class="text-xl font-bold text-purple-700">{{ data.team.owners }}</p>
              <p class="text-xs text-purple-500 mt-0.5">مالک</p>
            </div>
            <div class="bg-blue-50 rounded-lg p-3 text-center">
              <p class="text-xl font-bold text-blue-700">{{ data.team.admins }}</p>
              <p class="text-xs text-blue-500 mt-0.5">مدیر</p>
            </div>
            <div class="bg-yellow-50 rounded-lg p-3 text-center">
              <p class="text-xl font-bold text-yellow-700">{{ data.team.editors }}</p>
              <p class="text-xs text-yellow-500 mt-0.5">ویرایشگر</p>
            </div>
            <div class="bg-green-50 rounded-lg p-3 text-center">
              <p class="text-xl font-bold text-green-700">{{ data.team.members + data.team.users }}</p>
              <p class="text-xs text-green-500 mt-0.5">عضو</p>
            </div>
          </div>
        </div>
      </div>

    </template>
  </div>
</template>
