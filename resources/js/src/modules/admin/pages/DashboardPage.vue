<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import {
  ShoppingCart,
  Users,
  Package,
  DollarSign,
  TrendingUp,
  Store,
  Star,
  MessageSquareWarning,
  Building2,
  ArrowUpRight,
  AlertCircle,
} from 'lucide-vue-next'
import api from '@shared/utils/api'
import { formatPrice } from '@shared/utils/format'
import SimpleBarChart from '@shared/components/SimpleBarChart.vue'
import type { BarDataPoint } from '@shared/components/SimpleBarChart.vue'

interface DashboardStats {
  total_orders: number
  total_revenue: number
  total_products: number
  total_users: number
  total_stores: number
  active_stores: number
  inactive_stores: number
  suspended_stores: number
  total_sellers: number
  total_buyers: number
  total_listings: number
  pending_reviews: number
  pending_orders: number
  processing_orders: number
  shipped_orders: number
  orders_today: number
  revenue_today: number
  aov: number
}

interface RecentOrder {
  id: number
  order_number: string
  customer_name: string | null
  total: number
  status: string
  status_label: string
  created_at: string
}

interface TopProduct {
  id: number
  name: string
  sales_count: number
}

interface TopStore {
  id: number
  uuid: string
  name: string
  status: string
  status_label: string
  gmv: number
  products_count: number
}

interface PendingReview {
  id: number
  rating: number
  title: string | null
  body: string
  product_name: string | null
  customer_name: string | null
  created_at: string
}

interface OrderStatusBreakdown {
  status: string
  count: number
}

interface MonthlyRevenuePoint {
  month: string
  revenue: number
}

interface StoreGrowthPoint {
  month: string
  count: number
}

interface UserGrowthPoint {
  month: string
  buyers: number
  sellers: number
}

interface DashboardData {
  stats: DashboardStats
  recent_orders: RecentOrder[]
  top_products: TopProduct[]
  monthly_revenue: MonthlyRevenuePoint[]
  order_status_breakdown: OrderStatusBreakdown[]
  top_stores: TopStore[]
  pending_reviews: PendingReview[]
  store_growth: StoreGrowthPoint[]
  user_growth: UserGrowthPoint[]
}

const router = useRouter()
const data = ref<DashboardData | null>(null)
const isLoading = ref(true)
const errorMsg = ref('')

const stats = computed(() => data.value?.stats ?? null)

const primaryStats = computed(() => [
  { title: 'GMV کل پلتفرم', key: 'total_revenue', icon: DollarSign, color: 'bg-green-500', isCurrency: true },
  { title: 'کل سفارشات', key: 'total_orders', icon: ShoppingCart, color: 'bg-blue-500' },
  { title: 'میانگین سبد خرید', key: 'aov', icon: TrendingUp, color: 'bg-purple-500', isCurrency: true },
  { title: 'فروشگاه‌ها', key: 'total_stores', icon: Store, color: 'bg-orange-500' },
])

const marketplaceStats = computed(() => [
  { title: 'فروشندگان', key: 'total_sellers', icon: Building2, color: 'bg-indigo-500' },
  { title: 'خریداران', key: 'total_buyers', icon: Users, color: 'bg-cyan-500' },
  { title: 'محصولات', key: 'total_products', icon: Package, color: 'bg-pink-500' },
  { title: 'لیستینگ‌ها', key: 'total_listings', icon: Package, color: 'bg-teal-500' },
])

const statusLabels: Record<string, string> = {
  pending: 'در انتظار',
  processing: 'در حال پردازش',
  shipped: 'ارسال شده',
  delivered: 'تحویل شده',
  cancelled: 'لغو شده',
  refunded: 'مسترد شده',
}

const statusClasses: Record<string, string> = {
  pending: 'bg-yellow-100 text-yellow-700',
  processing: 'bg-blue-100 text-blue-700',
  shipped: 'bg-purple-100 text-purple-700',
  delivered: 'bg-green-100 text-green-700',
  cancelled: 'bg-red-100 text-red-700',
  refunded: 'bg-secondary-100 text-secondary-600',
}

const statusBarColors: Record<string, string> = {
  pending: 'bg-yellow-500',
  processing: 'bg-blue-500',
  shipped: 'bg-purple-500',
  delivered: 'bg-green-500',
  cancelled: 'bg-red-500',
  refunded: 'bg-secondary-500',
}

const storeStatusClasses: Record<string, string> = {
  active: 'bg-green-100 text-green-700',
  inactive: 'bg-secondary-100 text-secondary-600',
  suspended: 'bg-red-100 text-red-700',
}

const formatMonthLabel = (month: string): string => {
  const [year, monthNum] = month.split('-')
  return `${monthNum}/${year.slice(2)}`
}

const monthlyRevenueChart = computed<BarDataPoint[]>(() => {
  if (!data.value?.monthly_revenue) return []
  return data.value.monthly_revenue.map((item) => ({
    label: formatMonthLabel(item.month),
    value: item.revenue,
  }))
})

const storeGrowthChart = computed<BarDataPoint[]>(() => {
  if (!data.value?.store_growth) return []
  return data.value.store_growth.map((item) => ({
    label: formatMonthLabel(item.month),
    value: item.count,
    color: 'bg-blue-500',
  }))
})

const userGrowthChart = computed<BarDataPoint[]>(() => {
  if (!data.value?.user_growth) return []
  return data.value.user_growth.map((item) => ({
    label: formatMonthLabel(item.month),
    value: item.buyers + item.sellers,
    color: 'bg-purple-500',
  }))
})

const orderStatusMax = computed(() => {
  const list = data.value?.order_status_breakdown ?? []
  if (!list.length) return 1
  return Math.max(...list.map((s) => s.count))
})

const statusBarWidth = (count: number): string => {
  if (orderStatusMax.value === 0) return '0%'
  return `${(count / orderStatusMax.value) * 100}%`
}

const navigateToStore = (uuid: string) => {
  router.push({ name: 'admin-store-overview', params: { uuid } })
}

onMounted(async () => {
  try {
    const response = await api.get('/admin/dashboard')
    data.value = response.data.data
  } catch (error) {
    errorMsg.value = 'خطا در بارگذاری داشبورد'
    console.error('Failed to load dashboard:', error)
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-secondary-800">داشبورد مارکت‌پلیس</h1>
      <span class="text-sm text-secondary-500">نمای کلی سلامت و عملکرد پلتفرم</span>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div v-for="i in 4" :key="i" class="bg-white rounded-xl p-6 animate-pulse">
          <div class="h-4 bg-secondary-200 rounded w-1/2 mb-4"></div>
          <div class="h-8 bg-secondary-200 rounded w-3/4"></div>
        </div>
      </div>
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div v-for="i in 2" :key="`c-${i}`" class="bg-white rounded-xl p-6 animate-pulse h-64"></div>
      </div>
    </div>

    <!-- Error -->
    <div
      v-else-if="errorMsg"
      class="flex items-center gap-3 bg-red-50 text-red-600 rounded-xl p-5"
    >
      <AlertCircle class="w-5 h-5 flex-shrink-0" />
      {{ errorMsg }}
    </div>

    <template v-else-if="stats">
      <!-- Primary Stats -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
        <div
          v-for="card in primaryStats"
          :key="card.key"
          class="bg-white rounded-xl p-6 shadow-sm"
        >
          <div class="flex items-center justify-between mb-4">
            <span class="text-secondary-500 text-sm">{{ card.title }}</span>
            <div :class="['w-10 h-10 rounded-lg flex items-center justify-center', card.color]">
              <component :is="card.icon" class="w-5 h-5 text-white" />
            </div>
          </div>
          <p class="text-2xl font-bold text-secondary-800">
            <template v-if="card.isCurrency">
              {{ formatPrice(stats[card.key as keyof DashboardStats] as number) }}
              <span class="text-sm font-normal text-secondary-500">تومان</span>
            </template>
            <template v-else>
              {{ formatPrice(stats[card.key as keyof DashboardStats] as number) }}
            </template>
          </p>
        </div>
      </div>

      <!-- Marketplace Stats -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
        <div
          v-for="card in marketplaceStats"
          :key="card.key"
          class="bg-white rounded-xl p-6 shadow-sm"
        >
          <div class="flex items-center justify-between mb-4">
            <span class="text-secondary-500 text-sm">{{ card.title }}</span>
            <div :class="['w-10 h-10 rounded-lg flex items-center justify-center', card.color]">
              <component :is="card.icon" class="w-5 h-5 text-white" />
            </div>
          </div>
          <p class="text-2xl font-bold text-secondary-800">
            {{ formatPrice(stats[card.key as keyof DashboardStats] as number) }}
          </p>
        </div>
      </div>

      <!-- Today & Stores Health -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-secondary-500 text-sm mb-1">سفارشات امروز</p>
              <p class="text-3xl font-bold text-secondary-800">{{ stats.orders_today }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
              <TrendingUp class="w-6 h-6 text-green-600" />
            </div>
          </div>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-secondary-500 text-sm mb-1">درآمد امروز</p>
              <p class="text-3xl font-bold text-secondary-800">
                {{ formatPrice(stats.revenue_today) }}
                <span class="text-sm font-normal text-secondary-500">تومان</span>
              </p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
              <DollarSign class="w-6 h-6 text-blue-600" />
            </div>
          </div>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm">
          <p class="text-secondary-500 text-sm mb-3">وضعیت فروشگاه‌ها</p>
          <div class="flex items-center gap-4">
            <div class="text-center">
              <p class="text-xl font-bold text-green-600">{{ stats.active_stores }}</p>
              <p class="text-xs text-secondary-500">فعال</p>
            </div>
            <div class="h-10 w-px bg-secondary-200"></div>
            <div class="text-center">
              <p class="text-xl font-bold text-secondary-600">{{ stats.inactive_stores }}</p>
              <p class="text-xs text-secondary-500">غیرفعال</p>
            </div>
            <div class="h-10 w-px bg-secondary-200"></div>
            <div class="text-center">
              <p class="text-xl font-bold text-red-600">{{ stats.suspended_stores }}</p>
              <p class="text-xs text-secondary-500">معلق</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl p-6 shadow-sm">
          <h2 class="text-lg font-bold text-secondary-800 mb-4">درآمد ماهانه (GMV)</h2>
          <SimpleBarChart
            :data="monthlyRevenueChart"
            :height="180"
            value-suffix=" تومان"
          />
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm">
          <h2 class="text-lg font-bold text-secondary-800 mb-4">رشد فروشگاه‌ها و کاربران</h2>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-secondary-500 mb-2">فروشگاه‌های جدید</p>
              <SimpleBarChart :data="storeGrowthChart" :height="120" />
            </div>
            <div>
              <p class="text-sm text-secondary-500 mb-2">رشد کاربران</p>
              <SimpleBarChart :data="userGrowthChart" :height="120" />
            </div>
          </div>
        </div>
      </div>

      <!-- Top Stores & Order Status -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Top Stores -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm">
          <div class="p-6 border-b border-secondary-100 flex items-center justify-between">
            <h2 class="text-lg font-bold text-secondary-800">برترین فروشگاه‌ها (GMV)</h2>
            <button
              @click="router.push({ name: 'admin-organizations' })"
              class="text-sm text-primary-600 hover:text-primary-700 flex items-center gap-1"
            >
              مشاهده همه <ArrowUpRight class="w-4 h-4" />
            </button>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-secondary-50">
                <tr>
                  <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">فروشگاه</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">وضعیت</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">محصولات</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">GMV</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-secondary-100">
                <tr v-if="data?.top_stores.length === 0">
                  <td colspan="4" class="px-6 py-8 text-center text-secondary-500">
                    فروشگاهی یافت نشد
                  </td>
                </tr>
                <tr
                  v-for="store in data?.top_stores"
                  :key="store.id"
                  class="hover:bg-secondary-50 cursor-pointer"
                  @click="navigateToStore(store.uuid)"
                >
                  <td class="px-6 py-4 text-sm font-medium text-secondary-800">{{ store.name }}</td>
                  <td class="px-6 py-4">
                    <span :class="['px-2 py-1 text-xs rounded-full', storeStatusClasses[store.status] ?? 'bg-secondary-100 text-secondary-600']">
                      {{ store.status_label }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-sm text-secondary-600">{{ store.products_count }}</td>
                  <td class="px-6 py-4 text-sm text-secondary-800 font-medium">
                    {{ formatPrice(store.gmv) }} تومان
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Order Status Breakdown -->
        <div class="bg-white rounded-xl shadow-sm p-6">
          <h2 class="text-lg font-bold text-secondary-800 mb-4">توزیع وضعیت سفارشات</h2>
          <div v-if="data?.order_status_breakdown.length === 0" class="text-secondary-500 text-sm text-center py-8">
            سفارشی یافت نشد
          </div>
          <div v-else class="space-y-4">
            <div
              v-for="status in data?.order_status_breakdown"
              :key="status.status"
              class="space-y-1"
            >
              <div class="flex items-center justify-between text-sm">
                <span class="text-secondary-700">{{ statusLabels[status.status] ?? status.status }}</span>
                <span class="font-medium text-secondary-800">{{ status.count }}</span>
              </div>
              <div class="h-2 bg-secondary-100 rounded-full overflow-hidden">
                <div
                  :class="['h-full rounded-full', statusBarColors[status.status] ?? 'bg-primary-500']"
                  :style="{ width: statusBarWidth(status.count) }"
                ></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bottom Row: Recent Orders & Pending Reviews -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Recent Orders -->
        <div class="bg-white rounded-xl shadow-sm">
          <div class="p-6 border-b border-secondary-100">
            <h2 class="text-lg font-bold text-secondary-800">آخرین سفارشات</h2>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-secondary-50">
                <tr>
                  <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">شماره سفارش</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">مشتری</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">مبلغ</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">وضعیت</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">تاریخ</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-secondary-100">
                <tr v-if="data?.recent_orders.length === 0">
                  <td colspan="5" class="px-6 py-8 text-center text-secondary-500">
                    سفارشی یافت نشد
                  </td>
                </tr>
                <tr v-for="order in data?.recent_orders" :key="order.id" class="hover:bg-secondary-50">
                  <td class="px-6 py-4 text-sm text-secondary-800">{{ order.order_number }}</td>
                  <td class="px-6 py-4 text-sm text-secondary-600">{{ order.customer_name ?? '—' }}</td>
                  <td class="px-6 py-4 text-sm text-secondary-800 font-medium">
                    {{ formatPrice(order.total) }} تومان
                  </td>
                  <td class="px-6 py-4">
                    <span :class="['px-2 py-1 text-xs rounded-full', statusClasses[order.status] ?? 'bg-secondary-100 text-secondary-600']">
                      {{ order.status_label }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-sm text-secondary-500">{{ order.created_at }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Pending Reviews -->
        <div class="bg-white rounded-xl shadow-sm">
          <div class="p-6 border-b border-secondary-100 flex items-center justify-between">
            <h2 class="text-lg font-bold text-secondary-800 flex items-center gap-2">
              <MessageSquareWarning class="w-5 h-5 text-amber-500" />
              نظرات در انتظار تایید
              <span
                v-if="stats.pending_reviews > 0"
                class="bg-amber-100 text-amber-700 text-xs px-2 py-0.5 rounded-full"
              >
                {{ stats.pending_reviews }}
              </span>
            </h2>
            <button
              @click="router.push({ name: 'admin-organizations' })"
              class="text-sm text-primary-600 hover:text-primary-700"
            >
              مدیریت
            </button>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-secondary-50">
                <tr>
                  <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">محصول</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">امتیاز</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">مشتری</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">تاریخ</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-secondary-100">
                <tr v-if="data?.pending_reviews.length === 0">
                  <td colspan="4" class="px-6 py-8 text-center text-secondary-500">
                    نظر در انتظاری وجود ندارد
                  </td>
                </tr>
                <tr v-for="review in data?.pending_reviews" :key="review.id" class="hover:bg-secondary-50">
                  <td class="px-6 py-4 text-sm text-secondary-800">{{ review.product_name ?? '—' }}</td>
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-1 text-amber-500">
                      <Star class="w-4 h-4 fill-current" />
                      <span class="text-sm font-medium">{{ review.rating }}</span>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-sm text-secondary-600">{{ review.customer_name ?? '—' }}</td>
                  <td class="px-6 py-4 text-sm text-secondary-500">{{ review.created_at }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Top Products -->
      <div class="bg-white rounded-xl shadow-sm">
        <div class="p-6 border-b border-secondary-100">
          <h2 class="text-lg font-bold text-secondary-800">محصولات پرفروش</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-secondary-50">
              <tr>
                <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">محصول</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">تعداد فروش</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-secondary-100">
              <tr v-if="data?.top_products.length === 0">
                <td colspan="2" class="px-6 py-8 text-center text-secondary-500">
                  محصولی یافت نشد
                </td>
              </tr>
              <tr v-for="product in data?.top_products" :key="product.id" class="hover:bg-secondary-50">
                <td class="px-6 py-4 text-sm text-secondary-800">{{ product.name }}</td>
                <td class="px-6 py-4 text-sm text-secondary-800 font-medium">
                  {{ product.sales_count }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>
