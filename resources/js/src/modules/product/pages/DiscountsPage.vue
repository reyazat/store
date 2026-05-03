<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Percent, ChevronLeft, Filter, Grid3X3, List } from 'lucide-vue-next'
import { ProductCard } from '@shared/components'
import { productService } from '../services'
import type { Product } from '@shared/types'

const route = useRoute()
const router = useRouter()

const products = ref<Product[]>([])
const isLoading = ref(true)
const totalCount = ref(0)
const currentPage = ref(1)
const perPage = ref(24)
const viewMode = ref<'grid' | 'list'>('grid')

const sortOptions = [
  { value: 'discount_desc', label: 'بیشترین تخفیف' },
  { value: 'price_asc', label: 'قیمت: کم به زیاد' },
  { value: 'price_desc', label: 'قیمت: زیاد به کم' },
  { value: 'newest', label: 'جدیدترین' },
]

const currentSort = ref(route.query.sort as string || 'discount_desc')

const fetchProducts = async () => {
  isLoading.value = true
  try {
    const sortMapping: Record<string, { sort_by_discount?: string; sort_by?: string; sort_order?: string }> = {
      'discount_desc': { sort_by_discount: 'desc' },
      'price_asc': { sort_by: 'price', sort_order: 'asc' },
      'price_desc': { sort_by: 'price', sort_order: 'desc' },
      'newest': { sort_by: 'created_at', sort_order: 'desc' },
    }

    const sortParams = sortMapping[currentSort.value] || sortMapping['discount_desc']

    const response = await productService.getProducts({
      has_discount: true,
      page: currentPage.value,
      per_page: perPage.value,
      ...sortParams,
    })

    products.value = response.data || []
    totalCount.value = response.meta?.total || 0
  } catch (error) {
    console.error('Failed to fetch discounted products:', error)
  } finally {
    isLoading.value = false
  }
}

const handleSortChange = (value: string) => {
  currentSort.value = value
  currentPage.value = 1
  router.push({ query: { ...route.query, sort: value } })
  fetchProducts()
}

const handlePageChange = (page: number) => {
  currentPage.value = page
  fetchProducts()
}

watch(() => route.query.sort, (newSort) => {
  if (newSort && newSort !== currentSort.value) {
    currentSort.value = newSort as string
    fetchProducts()
  }
})

onMounted(() => {
  fetchProducts()
})

const totalPages = computed(() => Math.ceil(totalCount.value / perPage.value))
</script>

<template>
  <div class="min-h-screen bg-secondary-50">
    <!-- Header Banner -->
    <div class="bg-gradient-to-l from-violet-600 to-purple-700 py-12">
      <div class="container mx-auto px-4">
        <div class="flex items-center gap-4 text-white">
          <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
            <Percent class="w-8 h-8" />
          </div>
          <div>
            <h1 class="text-3xl font-bold">تخفیف‌های ویژه</h1>
            <p class="text-violet-100 mt-1">
              بهترین تخفیف‌ها را از دست ندهید
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Breadcrumb -->
    <div class="bg-white border-b">
      <div class="container mx-auto px-4 py-3">
        <div class="flex items-center gap-2 text-sm text-secondary-500">
          <RouterLink to="/" class="hover:text-primary-600">خانه</RouterLink>
          <ChevronLeft class="w-4 h-4" />
          <span class="text-secondary-800">تخفیف‌ها</span>
        </div>
      </div>
    </div>

    <!-- Filters & Sort -->
    <div class="bg-white border-b sticky top-0 z-20">
      <div class="container mx-auto px-4 py-4">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
          <div class="flex items-center gap-2">
            <span class="text-sm text-secondary-500">
              {{ totalCount.toLocaleString('fa-IR') }} محصول
            </span>
          </div>

          <div class="flex items-center gap-4">
            <!-- Sort Dropdown -->
            <div class="relative">
              <select
                v-model="currentSort"
                @change="handleSortChange(($event.target as HTMLSelectElement).value)"
                class="appearance-none bg-secondary-50 border border-secondary-200 rounded-lg px-4 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 cursor-pointer"
              >
                <option
                  v-for="option in sortOptions"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </option>
              </select>
              <Filter class="w-4 h-4 text-secondary-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
            </div>

            <!-- View Mode -->
            <div class="flex items-center bg-secondary-100 rounded-lg p-1">
              <button
                @click="viewMode = 'grid'"
                :class="['p-2 rounded transition', viewMode === 'grid' ? 'bg-white shadow text-primary-600' : 'text-secondary-500 hover:text-secondary-700']"
              >
                <Grid3X3 class="w-4 h-4" />
              </button>
              <button
                @click="viewMode = 'list'"
                :class="['p-2 rounded transition', viewMode === 'list' ? 'bg-white shadow text-primary-600' : 'text-secondary-500 hover:text-secondary-700']"
              >
                <List class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Products Grid -->
    <div class="container mx-auto px-4 py-8">
      <!-- Loading State -->
      <div v-if="isLoading" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <div v-for="i in 12" :key="i" class="animate-pulse">
          <div class="aspect-square bg-secondary-100 rounded-xl mb-3"></div>
          <div class="h-4 bg-secondary-100 rounded mb-2"></div>
          <div class="h-4 bg-secondary-100 rounded w-2/3"></div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="products.length === 0" class="text-center py-16">
        <div class="w-24 h-24 bg-secondary-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <Percent class="w-12 h-12 text-secondary-400" />
        </div>
        <h2 class="text-xl font-bold text-secondary-800 mb-2">محصولی با تخفیف موجود نیست</h2>
        <p class="text-secondary-500 mb-6">در حال حاضر محصولی با تخفیف فعال وجود ندارد.</p>
        <RouterLink
          to="/products"
          class="inline-flex items-center gap-2 bg-primary-500 hover:bg-primary-600 text-white px-6 py-3 rounded-lg font-medium transition"
        >
          مشاهده همه محصولات
          <ChevronLeft class="w-4 h-4" />
        </RouterLink>
      </div>

      <!-- Products -->
      <div
        v-else
        :class="[
          'gap-4',
          viewMode === 'grid'
            ? 'grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4'
            : 'flex flex-col'
        ]"
      >
        <ProductCard
          v-for="product in products"
          :key="product.id"
          :product="product"
          :view-mode="viewMode"
        />
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1 && !isLoading" class="mt-8 flex justify-center">
        <div class="flex items-center gap-2">
          <button
            @click="handlePageChange(currentPage - 1)"
            :disabled="currentPage === 1"
            class="px-4 py-2 bg-white border border-secondary-200 rounded-lg hover:bg-secondary-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
          >
            قبلی
          </button>
          <div class="flex items-center gap-1">
            <button
              v-for="page in totalPages"
              :key="page"
              @click="handlePageChange(page)"
              :class="[
                'w-10 h-10 rounded-lg transition',
                currentPage === page
                  ? 'bg-primary-500 text-white'
                  : 'bg-white border border-secondary-200 hover:bg-secondary-50'
              ]"
            >
              {{ page.toLocaleString('fa-IR') }}
            </button>
          </div>
          <button
            @click="handlePageChange(currentPage + 1)"
            :disabled="currentPage === totalPages"
            class="px-4 py-2 bg-white border border-secondary-200 rounded-lg hover:bg-secondary-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
          >
            بعدی
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
