<script setup lang="ts">
import { Plus, Edit, Search } from 'lucide-vue-next'
import { useAdminList } from '../composables/useAdminList'
import { productService, type AdminProduct } from '../services/productService'
import AdminPagination from '../components/AdminPagination.vue'

const { items: products, isLoading, errorMsg, filters, meta, debouncedReload, reload, goToPage } =
  useAdminList<AdminProduct, { search: string; status: number | null; per_page: number }>({
    defaultFilters: { search: '', status: null, per_page: 20 },
    fetchFn: (f) => productService.getAll(f),
  })

const formatPrice = (price: number) => new Intl.NumberFormat('fa-IR').format(price)
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-secondary-800">مدیریت محصولات</h1>
        <p v-if="!isLoading" class="text-sm text-secondary-500 mt-1">{{ meta.total }} محصول یافت شد</p>
      </div>
      <button class="flex items-center gap-2 px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition">
        <Plus class="w-5 h-5" />
        افزودن محصول
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-4 mb-4 flex flex-wrap items-center gap-3">
      <div class="relative flex-1 min-w-48">
        <Search class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-secondary-400" />
        <input
          v-model="filters.search"
          type="text"
          placeholder="جستجو در نام، slug یا SKU..."
          class="w-full pr-9 pl-4 h-10 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm"
          @input="debouncedReload"
        />
      </div>
      <select
        v-model="filters.status"
        class="h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 text-sm text-secondary-700"
        @change="reload"
      >
        <option :value="null">همه وضعیت‌ها</option>
        <option :value="1">فعال</option>
        <option :value="0">غیرفعال</option>
      </select>
      <select
        v-model="filters.per_page"
        class="h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 text-sm text-secondary-700"
      >
        <option :value="20">۲۰ در صفحه</option>
        <option :value="50">۵۰ در صفحه</option>
        <option :value="100">۱۰۰ در صفحه</option>
      </select>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div v-if="errorMsg" class="p-6 text-center">
        <p class="text-red-500 mb-3">{{ errorMsg }}</p>
        <button class="px-4 py-2 bg-primary-500 text-white rounded-lg text-sm hover:bg-primary-600 transition" @click="reload">تلاش مجدد</button>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-secondary-50 border-b border-secondary-100">
            <tr>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">تصویر</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">نام محصول</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">قیمت</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-secondary-500">موجودی</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-secondary-500">وضعیت</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-secondary-500">عملیات</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-secondary-100">
            <template v-if="isLoading">
              <tr v-for="i in 8" :key="i">
                <td class="px-6 py-4"><div class="w-10 h-10 bg-secondary-200 rounded-lg animate-pulse"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-secondary-200 rounded animate-pulse w-40"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-secondary-200 rounded animate-pulse w-24"></div></td>
                <td class="px-6 py-4 text-center"><div class="h-4 bg-secondary-200 rounded animate-pulse w-8 mx-auto"></div></td>
                <td class="px-6 py-4 text-center"><div class="h-5 bg-secondary-200 rounded-full animate-pulse w-14 mx-auto"></div></td>
                <td class="px-6 py-4"></td>
              </tr>
            </template>
            <tr v-else-if="products.length === 0">
              <td colspan="6" class="px-6 py-12 text-center text-secondary-400 text-sm">محصولی یافت نشد</td>
            </tr>
            <tr v-else v-for="product in products" :key="product.id" class="hover:bg-secondary-50 transition">
              <td class="px-6 py-4">
                <div class="w-10 h-10 bg-secondary-100 rounded-lg overflow-hidden flex-shrink-0">
                  <img v-if="product.main_image" :src="product.main_image" :alt="product.name" class="w-full h-full object-cover" />
                </div>
              </td>
              <td class="px-6 py-4">
                <p class="text-sm font-medium text-secondary-800">{{ product.name }}</p>
                <p class="text-xs text-secondary-400 font-mono">{{ product.slug }}</p>
              </td>
              <td class="px-6 py-4 text-sm text-secondary-800">{{ formatPrice(product.price) }} تومان</td>
              <td class="px-6 py-4 text-center text-sm text-secondary-600">{{ product.stock ?? 0 }}</td>
              <td class="px-6 py-4 text-center">
                <span :class="product.status === 1 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'" class="px-2.5 py-0.5 text-xs rounded-full font-medium">
                  {{ product.status === 1 ? 'فعال' : 'غیرفعال' }}
                </span>
              </td>
              <td class="px-6 py-4 text-center">
                <button class="p-2 hover:bg-secondary-100 rounded-lg transition" title="ویرایش">
                  <Edit class="w-4 h-4 text-secondary-600" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <AdminPagination
        v-if="!isLoading && !errorMsg && meta.last_page > 1"
        :meta="meta"
        @page="goToPage"
      />
    </div>
  </div>
</template>
