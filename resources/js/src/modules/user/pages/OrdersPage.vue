<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { Breadcrumb } from '@shared/components'
import { userService } from '../services'
import { formatPrice, formatDate } from '@shared/utils/format'
import UserSidebar from '../components/UserSidebar.vue'

const orders = ref<any[]>([])
const isLoading = ref(true)

onMounted(async () => {
  try {
    const response = await userService.getOrders()
    orders.value = response.data || []
  } catch (error) {
    console.error('Failed to load orders:', error)
  } finally {
    isLoading.value = false
  }
})

const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    processing: 'bg-blue-100 text-blue-800',
    shipped: 'bg-purple-100 text-purple-800',
    delivered: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

const getStatusText = (status: string) => {
  const texts: Record<string, string> = {
    pending: 'در انتظار',
    processing: 'در حال پردازش',
    shipped: 'ارسال شده',
    delivered: 'تحویل داده شده',
    cancelled: 'لغو شده',
  }
  return texts[status] || status
}
</script>

<template>
  <div class="container mx-auto px-4 py-8">
    <Breadcrumb :items="[{ title: 'پنل کاربری', url: '/user/dashboard' }, { title: 'سفارشات' }]" />
    
    <h1 class="text-2xl font-bold mb-8">سفارشات من</h1>
    
    <div class="grid lg:grid-cols-4 gap-8">
      <UserSidebar />
      
      <div class="lg:col-span-3">
        <div class="bg-white rounded-lg shadow">
          <div v-if="isLoading" class="p-6 space-y-4">
            <div v-for="i in 5" :key="i" class="animate-pulse flex justify-between">
              <div class="h-4 bg-gray-200 rounded w-1/4"></div>
              <div class="h-4 bg-gray-200 rounded w-1/4"></div>
            </div>
          </div>
          
          <div v-else-if="orders.length === 0" class="p-12 text-center text-gray-500">
            <p class="mb-4">هیچ سفارشی ثبت نشده است</p>
            <RouterLink to="/products" class="text-primary-600 hover:underline">
              مشاهده محصولات
            </RouterLink>
          </div>
          
          <div v-else class="divide-y">
            <RouterLink 
              v-for="order in orders" 
              :key="order.id"
              :to="`/user/orders/${order.id}`"
              class="flex items-center justify-between p-6 hover:bg-gray-50"
            >
              <div>
                <p class="font-medium">{{ order.order_number }}</p>
                <p class="text-sm text-gray-500">{{ formatDate(order.created_at) }}</p>
                <p class="text-sm text-gray-500">{{ order.items_count }} محصول</p>
              </div>
              <div class="text-left">
                <p class="font-bold text-primary-600">{{ formatPrice(order.total) }} تومان</p>
                <span :class="['inline-block px-2 py-1 text-xs rounded mt-1', getStatusColor(order.status)]">
                  {{ getStatusText(order.status) }}
                </span>
              </div>
            </RouterLink>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
