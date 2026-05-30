<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { Breadcrumb } from '@shared/components'
import { userService } from '../services'
import { formatPrice, formatDate } from '@shared/utils/format'
import UserSidebar from '../components/UserSidebar.vue'

interface OrderListItem {
  id: number
  order_number: string
  status: string
  status_label: string
  status_color: string
  payment_status: string
  total: number
  items_count: number
  created_at: string
}

const orders    = ref<OrderListItem[]>([])
const isLoading = ref(true)
const errorMsg  = ref<string | null>(null)
const currentPage = ref(1)
const lastPage    = ref(1)
const isLoadingMore = ref(false)

const fetchOrders = async (page = 1) => {
  try {
    const response = await userService.getOrders({ page, per_page: 10 })
    if (page === 1) {
      orders.value = response.data || []
    } else {
      orders.value = [...orders.value, ...(response.data || [])]
    }
    currentPage.value = response.meta?.current_page ?? page
    lastPage.value    = response.meta?.last_page ?? 1
  } catch {
    errorMsg.value = 'خطا در بارگذاری سفارشات'
  } finally {
    isLoading.value = false
  }
}

const loadMore = async () => {
  if (currentPage.value >= lastPage.value) return
  isLoadingMore.value = true
  await fetchOrders(currentPage.value + 1)
  isLoadingMore.value = false
}

onMounted(() => fetchOrders(1))
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

          <div v-else-if="errorMsg" class="p-8 text-center text-red-500 text-sm">{{ errorMsg }}</div>
          
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
              <div class="text-end">
                <p class="font-bold text-primary-600">{{ formatPrice(order.total) }} تومان</p>
                <span
                  class="inline-block px-2 py-1 text-xs rounded mt-1 font-medium"
                  :style="{ backgroundColor: order.status_color + '22', color: order.status_color }"
                >
                  {{ order.status_label }}
                </span>
              </div>
            </RouterLink>

            <div v-if="currentPage < lastPage" class="p-4 text-center">
              <button
                @click="loadMore"
                :disabled="isLoadingMore"
                class="px-6 py-2 text-sm text-primary-600 border border-primary-300 rounded-lg hover:bg-primary-50 disabled:opacity-50 transition"
              >
                {{ isLoadingMore ? 'در حال بارگذاری...' : 'سفارشات بیشتر' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
