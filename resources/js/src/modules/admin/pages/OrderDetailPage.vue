<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowRight, Package, Truck, CheckCircle, XCircle } from 'lucide-vue-next'
import api from '@shared/utils/api'

const route = useRoute()
const router = useRouter()
const order = ref<any>(null)
const isLoading = ref(true)

onMounted(async () => {
  try {
    const response = await api.get(`/admin/orders/${route.params.id}`)
    order.value = response.data.data || response.data
  } catch (error) {
    console.error('Failed to load order:', error)
  } finally {
    isLoading.value = false
  }
})

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('fa-IR').format(price)
}

const updateStatus = async (status: string) => {
  try {
    await api.patch(`/admin/orders/${route.params.id}/status`, { status })
    order.value.status = status
  } catch (error) {
    console.error('Failed to update status:', error)
  }
}
</script>

<template>
  <div>
    <div class="flex items-center gap-4 mb-6">
      <button @click="router.back()" class="p-2 hover:bg-secondary-100 rounded-lg">
        <ArrowRight class="w-5 h-5" />
      </button>
      <h1 class="text-2xl font-bold text-secondary-800">جزئیات سفارش</h1>
    </div>

    <div v-if="isLoading" class="bg-white rounded-xl p-8 text-center">
      <div class="animate-spin w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full mx-auto"></div>
    </div>

    <div v-else-if="order" class="space-y-6">
      <!-- Order Info -->
      <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <p class="text-sm text-secondary-500 mb-1">شماره سفارش</p>
            <p class="font-bold text-secondary-800">{{ order.order_number }}</p>
          </div>
          <div>
            <p class="text-sm text-secondary-500 mb-1">تاریخ ثبت</p>
            <p class="font-medium text-secondary-800">{{ order.created_at }}</p>
          </div>
          <div>
            <p class="text-sm text-secondary-500 mb-1">وضعیت</p>
            <select 
              :value="order.status"
              @change="updateStatus(($event.target as HTMLSelectElement).value)"
              class="px-3 py-1 rounded-lg border border-secondary-200"
            >
              <option value="pending">در انتظار</option>
              <option value="processing">در حال پردازش</option>
              <option value="shipped">ارسال شده</option>
              <option value="delivered">تحویل شده</option>
              <option value="cancelled">لغو شده</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Customer Info -->
      <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold text-secondary-800 mb-4">اطلاعات مشتری</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <p class="text-sm text-secondary-500">نام</p>
            <p class="font-medium">{{ order.customer_name || order.user?.name }}</p>
          </div>
          <div>
            <p class="text-sm text-secondary-500">ایمیل</p>
            <p class="font-medium">{{ order.user?.email }}</p>
          </div>
        </div>
      </div>

      <!-- Order Items -->
      <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold text-secondary-800 mb-4">اقلام سفارش</h2>
        <div class="space-y-4">
          <div v-for="item in order.items" :key="item.id" class="flex items-center gap-4 p-4 bg-secondary-50 rounded-lg">
            <div class="w-16 h-16 bg-secondary-200 rounded-lg"></div>
            <div class="flex-1">
              <p class="font-medium text-secondary-800">{{ item.product_name }}</p>
              <p class="text-sm text-secondary-500">تعداد: {{ item.quantity }}</p>
            </div>
            <p class="font-bold text-secondary-800">{{ formatPrice(item.subtotal) }} تومان</p>
          </div>
        </div>
        <div class="mt-6 pt-4 border-t border-secondary-200">
          <div class="flex justify-between text-lg font-bold">
            <span>مجموع</span>
            <span>{{ formatPrice(order.total) }} تومان</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
