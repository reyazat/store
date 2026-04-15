<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ShoppingCart, Users, Package, DollarSign, TrendingUp, TrendingDown } from 'lucide-vue-next'
import api from '@shared/utils/api'

interface DashboardStats {
  total_orders: number
  total_revenue: number
  total_products: number
  total_users: number
  orders_today: number
  revenue_today: number
}

const stats = ref<DashboardStats | null>(null)
const recentOrders = ref<any[]>([])
const isLoading = ref(true)

onMounted(async () => {
  try {
    const response = await api.get('/admin/dashboard')
    stats.value = response.data.data.stats
    recentOrders.value = response.data.data.recent_orders || []
  } catch (error) {
    console.error('Failed to load dashboard:', error)
  } finally {
    isLoading.value = false
  }
})

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('fa-IR').format(price)
}

const statCards = [
  { title: 'کل سفارشات', key: 'total_orders', icon: ShoppingCart, color: 'bg-blue-500' },
  { title: 'کل درآمد', key: 'total_revenue', icon: DollarSign, color: 'bg-green-500', isCurrency: true },
  { title: 'محصولات', key: 'total_products', icon: Package, color: 'bg-purple-500' },
  { title: 'کاربران', key: 'total_users', icon: Users, color: 'bg-orange-500' },
]
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold text-secondary-800 mb-6">داشبورد</h1>

    <!-- Loading -->
    <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div v-for="i in 4" :key="i" class="bg-white rounded-xl p-6 animate-pulse">
        <div class="h-4 bg-secondary-200 rounded w-1/2 mb-4"></div>
        <div class="h-8 bg-secondary-200 rounded w-3/4"></div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div 
        v-for="card in statCards" 
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
            {{ formatPrice(stats?.[card.key as keyof DashboardStats] as number || 0) }}
            <span class="text-sm font-normal text-secondary-500">تومان</span>
          </template>
          <template v-else>
            {{ formatPrice(stats?.[card.key as keyof DashboardStats] as number || 0) }}
          </template>
        </p>
      </div>
    </div>

    <!-- Today Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
      <div class="bg-white rounded-xl p-6 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-secondary-500 text-sm mb-1">سفارشات امروز</p>
            <p class="text-3xl font-bold text-secondary-800">{{ stats?.orders_today || 0 }}</p>
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
              {{ formatPrice(stats?.revenue_today || 0) }}
              <span class="text-sm font-normal text-secondary-500">تومان</span>
            </p>
          </div>
          <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
            <DollarSign class="w-6 h-6 text-blue-600" />
          </div>
        </div>
      </div>
    </div>

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
            <tr v-if="recentOrders.length === 0">
              <td colspan="5" class="px-6 py-8 text-center text-secondary-500">
                سفارشی یافت نشد
              </td>
            </tr>
            <tr v-for="order in recentOrders" :key="order.id" class="hover:bg-secondary-50">
              <td class="px-6 py-4 text-sm text-secondary-800">{{ order.order_number }}</td>
              <td class="px-6 py-4 text-sm text-secondary-600">{{ order.customer_name }}</td>
              <td class="px-6 py-4 text-sm text-secondary-800 font-medium">
                {{ formatPrice(order.total) }} تومان
              </td>
              <td class="px-6 py-4">
                <span 
                  :class="[
                    'px-2 py-1 text-xs rounded-full',
                    order.status === 'delivered' ? 'bg-green-100 text-green-700' :
                    order.status === 'processing' ? 'bg-blue-100 text-blue-700' :
                    order.status === 'shipped' ? 'bg-purple-100 text-purple-700' :
                    order.status === 'cancelled' ? 'bg-red-100 text-red-700' :
                    'bg-yellow-100 text-yellow-700'
                  ]"
                >
                  {{ order.status_label }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-secondary-500">{{ order.created_at }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
