<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Plus, Edit, Trash2 } from 'lucide-vue-next'
import api from '@shared/utils/api'

const coupons = ref<any[]>([])
const isLoading = ref(true)

onMounted(async () => {
  try {
    const response = await api.get('/admin/coupons')
    coupons.value = response.data.data || response.data || []
  } catch (error) {
    console.error('Failed to load coupons:', error)
  } finally {
    isLoading.value = false
  }
})

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('fa-IR').format(price)
}
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-secondary-800">مدیریت کوپن‌ها</h1>
      <button class="flex items-center gap-2 px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition">
        <Plus class="w-5 h-5" />
        افزودن کوپن
      </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-secondary-50">
            <tr>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">کد</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">نوع</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">مقدار</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">تعداد استفاده</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">وضعیت</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">عملیات</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-secondary-100">
            <tr v-if="isLoading">
              <td colspan="6" class="px-6 py-8 text-center">
                <div class="animate-spin w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full mx-auto"></div>
              </td>
            </tr>
            <tr v-else-if="coupons.length === 0">
              <td colspan="6" class="px-6 py-8 text-center text-secondary-500">
                کوپنی یافت نشد
              </td>
            </tr>
            <tr v-for="coupon in coupons" :key="coupon.id" class="hover:bg-secondary-50">
              <td class="px-6 py-4 text-sm font-medium text-secondary-800">{{ coupon.code }}</td>
              <td class="px-6 py-4 text-sm text-secondary-600">
                {{ coupon.type === 'percentage' ? 'درصدی' : 'مبلغی' }}
              </td>
              <td class="px-6 py-4 text-sm text-secondary-800">
                {{ coupon.type === 'percentage' ? coupon.value + '%' : formatPrice(coupon.value) + ' تومان' }}
              </td>
              <td class="px-6 py-4 text-sm text-secondary-600">{{ coupon.used_count || 0 }}</td>
              <td class="px-6 py-4">
                <span :class="['px-2 py-1 text-xs rounded-full', coupon.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700']">
                  {{ coupon.is_active ? 'فعال' : 'غیرفعال' }}
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <button class="p-2 hover:bg-secondary-100 rounded-lg">
                    <Edit class="w-4 h-4 text-secondary-600" />
                  </button>
                  <button class="p-2 hover:bg-red-100 rounded-lg">
                    <Trash2 class="w-4 h-4 text-red-600" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
