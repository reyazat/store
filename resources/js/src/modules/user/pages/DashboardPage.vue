<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { Package, MapPin, Heart } from 'lucide-vue-next'
import { Breadcrumb } from '@shared/components'
import { userService } from '../services'
import { formatPrice, formatDate } from '@shared/utils/format'
import UserSidebar from '../components/UserSidebar.vue'

const stats = ref({
  ordersCount: 0,
  addressesCount: 0,
  wishlistCount: 0,
})
const recentOrders = ref<any[]>([])
const isLoading = ref(true)

onMounted(async () => {
  try {
    const [ordersRes, addressesRes, wishlistRes] = await Promise.all([
      userService.getOrders({ per_page: 5 }),
      userService.getAddresses(),
      userService.getWishlist(),
    ])
    
    recentOrders.value = ordersRes.data || []
    stats.value = {
      ordersCount: ordersRes.meta?.total || recentOrders.value.length,
      addressesCount: addressesRes.data?.length || 0,
      wishlistCount: wishlistRes.data?.length || 0,
    }
  } catch (error) {
    console.error('Failed to load dashboard:', error)
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
    <Breadcrumb :items="[{ title: 'پنل کاربری' }]" />
    
    <h1 class="text-2xl font-bold mb-8">پنل کاربری</h1>
    
    <div class="grid lg:grid-cols-4 gap-8">
      <UserSidebar />
      
      <div class="lg:col-span-3 space-y-6">
        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-500 text-sm">سفارشات</p>
                <p class="text-2xl font-bold">{{ stats.ordersCount }}</p>
              </div>
              <div class="bg-blue-100 p-3 rounded-full">
                <Package class="w-6 h-6 text-blue-600" />
              </div>
            </div>
          </div>
          
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-500 text-sm">آدرس‌ها</p>
                <p class="text-2xl font-bold">{{ stats.addressesCount }}</p>
              </div>
              <div class="bg-green-100 p-3 rounded-full">
                <MapPin class="w-6 h-6 text-green-600" />
              </div>
            </div>
          </div>
          
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-500 text-sm">علاقه‌مندی‌ها</p>
                <p class="text-2xl font-bold">{{ stats.wishlistCount }}</p>
              </div>
              <div class="bg-red-100 p-3 rounded-full">
                <Heart class="w-6 h-6 text-red-600" />
              </div>
            </div>
          </div>
        </div>
        
        <!-- Recent Orders -->
        <div class="bg-white rounded-lg shadow">
          <div class="p-6 border-b flex justify-between items-center">
            <h2 class="text-lg font-bold">آخرین سفارشات</h2>
            <RouterLink to="/user/orders" class="text-primary-600 hover:underline text-sm">
              مشاهده همه
            </RouterLink>
          </div>
          
          <div class="p-6">
            <div v-if="isLoading" class="space-y-4">
              <div v-for="i in 3" :key="i" class="animate-pulse flex justify-between">
                <div class="h-4 bg-gray-200 rounded w-1/4"></div>
                <div class="h-4 bg-gray-200 rounded w-1/4"></div>
              </div>
            </div>
            
            <div v-else-if="recentOrders.length === 0" class="text-center py-8 text-gray-500">
              سفارشی ثبت نشده است
            </div>
            
            <div v-else class="space-y-4">
              <RouterLink 
                v-for="order in recentOrders" 
                :key="order.id"
                :to="`/user/orders/${order.id}`"
                class="flex items-center justify-between py-4 border-b last:border-b-0 hover:bg-gray-50 -mx-2 px-2 rounded"
              >
                <div>
                  <p class="font-medium">{{ order.order_number }}</p>
                  <p class="text-sm text-gray-500">{{ formatDate(order.created_at) }}</p>
                </div>
                <div class="text-left">
                  <p class="font-medium">{{ formatPrice(order.total) }} تومان</p>
                  <span :class="['inline-block px-2 py-1 text-xs rounded', getStatusColor(order.status)]">
                    {{ getStatusText(order.status) }}
                  </span>
                </div>
              </RouterLink>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
