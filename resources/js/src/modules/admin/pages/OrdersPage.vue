<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { Eye, Search, Filter } from 'lucide-vue-next'
import api from '@shared/utils/api'

const orders = ref<any[]>([])
const isLoading = ref(true)
const searchQuery = ref('')
const statusFilter = ref('')

onMounted(async () => {
  await loadOrders()
})

const loadOrders = async () => {
  isLoading.value = true
  try {
    const response = await api.get('/admin/orders', {
      params: {
        search: searchQuery.value,
        status: statusFilter.value,
      }
    })
    orders.value = response.data.data || response.data || []
  } catch (error) {
    console.error('Failed to load orders:', error)
  } finally {
    isLoading.value = false
  }
}

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('fa-IR').format(price)
}

const statusOptions = [
  { value: '', label: 'همه' },
  { value: 'pending', label: 'در انتظار' },
  { value: 'processing', label: 'در حال پردازش' },
  { value: 'shipped', label: 'ارسال شده' },
  { value: 'delivered', label: 'تحویل شده' },
  { value: 'cancelled', label: 'لغو شده' },
]

const getStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-700',
    processing: 'bg-blue-100 text-blue-700',
    shipped: 'bg-purple-100 text-purple-700',
    delivered: 'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
  }
  return classes[status] || 'bg-secondary-100 text-secondary-700'
}
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-secondary-800">مدیریت سفارشات</h1>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
      <div class="flex flex-col md:flex-row gap-4">
        <div class="flex-1 relative">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="جستجو در سفارشات..."
            class="w-full h-10 pl-4 pr-10 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
            @keyup.enter="loadOrders"
          />
          <Search class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400" />
        </div>
        <select
          v-model="statusFilter"
          @change="loadOrders"
          class="h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
        >
          <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
            {{ opt.label }}
          </option>
        </select>
      </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-secondary-50">
            <tr>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">شماره سفارش</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">مشتری</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">تعداد اقلام</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">مبلغ کل</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">وضعیت</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">تاریخ</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">عملیات</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-secondary-100">
            <tr v-if="isLoading">
              <td colspan="7" class="px-6 py-8 text-center">
                <div class="animate-spin w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full mx-auto"></div>
              </td>
            </tr>
            <tr v-else-if="orders.length === 0">
              <td colspan="7" class="px-6 py-8 text-center text-secondary-500">
                سفارشی یافت نشد
              </td>
            </tr>
            <tr v-for="order in orders" :key="order.id" class="hover:bg-secondary-50">
              <td class="px-6 py-4 text-sm font-medium text-secondary-800">{{ order.order_number }}</td>
              <td class="px-6 py-4 text-sm text-secondary-600">{{ order.customer_name || order.user?.name }}</td>
              <td class="px-6 py-4 text-sm text-secondary-600">{{ order.items_count }} کالا</td>
              <td class="px-6 py-4 text-sm font-medium text-secondary-800">
                {{ formatPrice(order.total) }} تومان
              </td>
              <td class="px-6 py-4">
                <span :class="['px-2 py-1 text-xs rounded-full', getStatusClass(order.status)]">
                  {{ order.status_label || order.status }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-secondary-500">{{ order.created_at }}</td>
              <td class="px-6 py-4">
                <RouterLink 
                  :to="`/admin/orders/${order.id}`"
                  class="p-2 hover:bg-secondary-100 rounded-lg inline-flex"
                >
                  <Eye class="w-5 h-5 text-secondary-600" />
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
