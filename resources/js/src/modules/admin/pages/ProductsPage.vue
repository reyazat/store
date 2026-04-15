<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Plus, Edit, Trash2, Search } from 'lucide-vue-next'
import api from '@shared/utils/api'

const products = ref<any[]>([])
const isLoading = ref(true)
const searchQuery = ref('')

onMounted(async () => {
  await loadProducts()
})

const loadProducts = async () => {
  isLoading.value = true
  try {
    const response = await api.get('/products', {
      params: { search: searchQuery.value }
    })
    products.value = response.data.data || response.data || []
  } catch (error) {
    console.error('Failed to load products:', error)
  } finally {
    isLoading.value = false
  }
}

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('fa-IR').format(price)
}
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-secondary-800">مدیریت محصولات</h1>
      <button class="flex items-center gap-2 px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition">
        <Plus class="w-5 h-5" />
        افزودن محصول
      </button>
    </div>

    <!-- Search -->
    <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
      <div class="relative max-w-md">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="جستجو در محصولات..."
          class="w-full h-10 pl-4 pr-10 rounded-lg border border-secondary-200 focus:border-primary-500"
          @keyup.enter="loadProducts"
        />
        <Search class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400" />
      </div>
    </div>

    <!-- Products Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-secondary-50">
            <tr>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">تصویر</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">نام محصول</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">قیمت</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">موجودی</th>
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
            <tr v-else-if="products.length === 0">
              <td colspan="6" class="px-6 py-8 text-center text-secondary-500">
                محصولی یافت نشد
              </td>
            </tr>
            <tr v-for="product in products" :key="product.id" class="hover:bg-secondary-50">
              <td class="px-6 py-4">
                <div class="w-12 h-12 bg-secondary-100 rounded-lg overflow-hidden">
                  <img v-if="product.main_image" :src="product.main_image" class="w-full h-full object-cover" />
                </div>
              </td>
              <td class="px-6 py-4 text-sm font-medium text-secondary-800">{{ product.name }}</td>
              <td class="px-6 py-4 text-sm text-secondary-800">{{ formatPrice(product.price) }} تومان</td>
              <td class="px-6 py-4 text-sm text-secondary-600">{{ product.stock || 0 }}</td>
              <td class="px-6 py-4">
                <span :class="['px-2 py-1 text-xs rounded-full', product.status === 1 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700']">
                  {{ product.status === 1 ? 'فعال' : 'غیرفعال' }}
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
