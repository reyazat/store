<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ProductCard, Breadcrumb } from '@shared/components'
import ProductFilters, { type FiltersState } from '../components/ProductFilters.vue'
import { productService, type ProductSort } from '../services'
import { brandsService, type Brand } from '@modules/category/services/brandsService'
import { categoryService } from '@modules/category/services'
import type { Product, Category } from '@shared/types'
import { ChevronDown, Grid3X3, List } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()

const products = ref<Product[]>([])
const isLoading = ref(true)
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)
const brands = ref<Brand[]>([])
const categories = ref<Category[]>([])
const filtersLoading = ref(false)
const showMobileFilters = ref(false)

const filters = ref<FiltersState>({
  search: '',
  category_slug: null,
  manufacturer_slug: null,
  manufacturer_ids: [],
  min_price: null,
  max_price: null,
  has_discount: false,
  in_stock: false,
})

const sortOptions: { value: ProductSort; label: string }[] = [
  { value: 'newest', label: 'جدیدترین' },
  { value: 'oldest', label: 'قدیمی‌ترین' },
  { value: 'price_asc', label: 'ارزان‌ترین' },
  { value: 'price_desc', label: 'گران‌ترین' },
  { value: 'popular', label: 'محبوب‌ترین' },
  { value: 'discount', label: 'بیشترین تخفیف' },
]

const currentSort = computed<ProductSort>(() => (route.query.sort as ProductSort) || 'newest')
const viewMode = ref<'grid' | 'list'>('grid')

const syncFiltersFromQuery = () => {
  const q = route.query
  filters.value = {
    search: (q.q as string) || '',
    category_slug: (q.category_slug as string) || null,
    manufacturer_slug: (q.manufacturer_slug as string) || null,
    manufacturer_ids: q.manufacturer_ids
      ? String(q.manufacturer_ids).split(',').map(Number).filter(Boolean)
      : [],
    min_price: q.min_price ? Number(q.min_price) : null,
    max_price: q.max_price ? Number(q.max_price) : null,
    has_discount: q.has_discount === '1' || q.has_discount === 'true',
    in_stock: q.in_stock === '1' || q.in_stock === 'true',
  }
  currentPage.value = q.page ? Number(q.page) : 1
}

const buildQueryFromFilters = (): Record<string, string | undefined> => {
  const q: Record<string, string | undefined> = {}
  if (filters.value.search) q.q = filters.value.search
  if (filters.value.category_slug) q.category_slug = filters.value.category_slug
  if (filters.value.manufacturer_slug) q.manufacturer_slug = filters.value.manufacturer_slug
  if (filters.value.manufacturer_ids.length)
    q.manufacturer_ids = filters.value.manufacturer_ids.join(',')
  if (filters.value.min_price != null) q.min_price = String(filters.value.min_price)
  if (filters.value.max_price != null) q.max_price = String(filters.value.max_price)
  if (filters.value.has_discount) q.has_discount = '1'
  if (filters.value.in_stock) q.in_stock = '1'
  if (currentSort.value !== 'newest') q.sort = currentSort.value
  if (currentPage.value > 1) q.page = String(currentPage.value)
  return q
}

const fetchProducts = async () => {
  isLoading.value = true
  try {
    const response = await productService.getProducts({
      page: currentPage.value,
      per_page: 24,
      search: filters.value.search || undefined,
      category_slug: filters.value.category_slug || undefined,
      manufacturer_slug: filters.value.manufacturer_slug || undefined,
      manufacturer_ids: filters.value.manufacturer_ids.length ? filters.value.manufacturer_ids : undefined,
      min_price: filters.value.min_price ?? undefined,
      max_price: filters.value.max_price ?? undefined,
      has_discount: filters.value.has_discount || undefined,
      in_stock: filters.value.in_stock || undefined,
      sort: currentSort.value,
    })
    products.value = response.data || []
    currentPage.value = response.meta?.current_page || 1
    lastPage.value = response.meta?.last_page || 1
    total.value = response.meta?.total || 0
  } catch (error) {
    console.error('Failed to load products:', error)
  } finally {
    isLoading.value = false
  }
}

const fetchFilterData = async () => {
  filtersLoading.value = true
  try {
    const [brandsRes, categoriesRes] = await Promise.all([
      brandsService.getBrands({ per_page: 100 }),
      categoryService.getCategories(),
    ])
    brands.value = brandsRes.data || []
    categories.value = categoriesRes.data || []
  } catch (error) {
    console.error('Failed to load filter data:', error)
  } finally {
    filtersLoading.value = false
  }
}

const applyFilters = () => {
  currentPage.value = 1
  router.push({ query: buildQueryFromFilters() })
  showMobileFilters.value = false
}

const resetFilters = () => {
  filters.value = {
    search: '',
    category_slug: null,
    manufacturer_slug: null,
    manufacturer_ids: [],
    min_price: null,
    max_price: null,
    has_discount: false,
    in_stock: false,
  }
  currentPage.value = 1
  router.push({ query: {} })
  showMobileFilters.value = false
}

const changeSort = (sort: ProductSort) => {
  router.push({ query: { ...route.query, sort, page: '1' } })
}

const changePage = (page: number) => {
  currentPage.value = page
  router.push({ query: { ...route.query, page: String(page) } })
}

const pageNumbers = computed<(number | -1)[]>(() => {
  const total = lastPage.value
  const current = currentPage.value
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)

  const pages: (number | -1)[] = [1]

  if (current > 3) pages.push(-1)

  const start = Math.max(2, current - 1)
  const end = Math.min(total - 1, current + 1)
  for (let i = start; i <= end; i++) pages.push(i)

  if (current < total - 2) pages.push(-1)
  pages.push(total)

  return pages
})

const hasActiveFilters = computed(() =>
  filters.value.category_slug !== null ||
  filters.value.manufacturer_slug !== null ||
  filters.value.manufacturer_ids.length > 0 ||
  filters.value.min_price !== null ||
  filters.value.max_price !== null ||
  filters.value.has_discount ||
  filters.value.in_stock ||
  filters.value.search.length > 0
)

const resolveManufacturerSlug = async () => {
  const slug = filters.value.manufacturer_slug
  if (!slug || !brands.value.length) return
  const brand = brands.value.find((b) => b.slug === slug)
  if (brand && !filters.value.manufacturer_ids.includes(brand.id)) {
    filters.value.manufacturer_ids = [...filters.value.manufacturer_ids, brand.id]
  }
  filters.value.manufacturer_slug = null
}

onMounted(async () => {
  syncFiltersFromQuery()
  await fetchFilterData()
  await resolveManufacturerSlug()
  fetchProducts()
})

watch(() => route.query, async () => {
  syncFiltersFromQuery()
  await resolveManufacturerSlug()
  fetchProducts()
}, { deep: true })
</script>

<template>
  <div class="container mx-auto px-4 py-8">
    <Breadcrumb :items="[{ title: 'محصولات' }]" />

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
      <h1 class="text-2xl font-bold">
        {{ filters.search ? `نتایج جستجو: ${filters.search}` : 'همه محصولات' }}
      </h1>

      <div class="flex items-center gap-3">
        <!-- Mobile filter toggle -->
        <button
          type="button"
          class="md:hidden px-4 py-2 bg-white border rounded-lg flex items-center gap-2"
          @click="showMobileFilters = true"
        >
          <Grid3X3 class="w-4 h-4" />
          فیلترها
          <span v-if="hasActiveFilters" class="w-2 h-2 bg-primary-500 rounded-full"></span>
        </button>

        <!-- Sort dropdown -->
        <div class="relative group">
          <button
            type="button"
            class="px-4 py-2 bg-white border rounded-lg flex items-center gap-2 hover:bg-secondary-50"
          >
            <span class="text-sm text-secondary-500">مرتب‌سازی:</span>
            <span class="text-sm font-medium">{{ sortOptions.find((s) => s.value === currentSort)?.label }}</span>
            <ChevronDown class="w-4 h-4 text-secondary-400" />
          </button>
          <div
            class="absolute left-0 top-full mt-1 w-48 bg-white border rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition z-20"
          >
            <button
              v-for="opt in sortOptions"
              :key="opt.value"
              type="button"
              :class="[
                'w-full text-right px-4 py-2 text-sm hover:bg-secondary-50 first:rounded-t-lg last:rounded-b-lg',
                currentSort === opt.value ? 'text-primary-600 font-medium bg-primary-50/50' : 'text-secondary-700',
              ]"
              @click="changeSort(opt.value)"
            >
              {{ opt.label }}
            </button>
          </div>
        </div>

        <!-- View Mode Toggle -->
        <div class="flex items-center bg-secondary-100 rounded-lg p-1">
          <button
            type="button"
            @click="viewMode = 'grid'"
            :class="['p-2 rounded transition', viewMode === 'grid' ? 'bg-white shadow text-primary-600' : 'text-secondary-500 hover:text-secondary-700']"
            aria-label="نمایش شبکه‌ای"
          >
            <Grid3X3 class="w-4 h-4" />
          </button>
          <button
            type="button"
            @click="viewMode = 'list'"
            :class="['p-2 rounded transition', viewMode === 'list' ? 'bg-white shadow text-primary-600' : 'text-secondary-500 hover:text-secondary-700']"
            aria-label="نمایش لیستی"
          >
            <List class="w-4 h-4" />
          </button>
        </div>

        <span class="text-sm text-secondary-500">{{ total }} محصول</span>
      </div>
    </div>

    <div class="flex gap-6">
      <!-- Sidebar Filters (desktop) -->
      <div class="hidden md:block w-64 flex-shrink-0">
        <ProductFilters
          v-model="filters"
          :brands="brands"
          :categories="categories"
          :loading="filtersLoading"
          @apply="applyFilters"
          @reset="resetFilters"
        />
      </div>

      <!-- Mobile Filters Drawer -->
      <div
        v-if="showMobileFilters"
        class="fixed inset-0 z-50 md:hidden"
        @click.self="showMobileFilters = false"
      >
        <div class="absolute inset-y-0 right-0 w-80 bg-white shadow-xl p-4 overflow-y-auto">
          <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold text-lg">فیلترها</h2>
            <button type="button" class="p-2 hover:bg-secondary-100 rounded" @click="showMobileFilters = false">
              ✕
            </button>
          </div>
          <ProductFilters
            v-model="filters"
            :brands="brands"
            :categories="categories"
            :loading="filtersLoading"
            @apply="applyFilters"
            @reset="resetFilters"
          />
        </div>
      </div>

      <!-- Products -->
      <div class="flex-1 min-w-0">
        <!-- Active filters chips -->
        <div v-if="hasActiveFilters" class="flex flex-wrap gap-2 mb-4">
          <span
            v-if="filters.search"
            class="inline-flex items-center gap-1 px-3 py-1 bg-primary-50 text-primary-700 rounded-full text-sm"
          >
            جستجو: {{ filters.search }}
            <button type="button" class="hover:text-primary-900" @click="filters.search = ''; applyFilters()">✕</button>
          </span>
          <span
            v-if="filters.category_slug"
            class="inline-flex items-center gap-1 px-3 py-1 bg-primary-50 text-primary-700 rounded-full text-sm"
          >
            {{ categories.find((c) => c.slug === filters.category_slug)?.name || filters.category_slug }}
            <button type="button" class="hover:text-primary-900" @click="filters.category_slug = null; applyFilters()">✕</button>
          </span>
          <span
            v-if="filters.manufacturer_slug"
            class="inline-flex items-center gap-1 px-3 py-1 bg-primary-50 text-primary-700 rounded-full text-sm"
          >
            {{ brands.find((b) => b.slug === filters.manufacturer_slug)?.name || filters.manufacturer_slug }}
            <button type="button" class="hover:text-primary-900" @click="filters.manufacturer_slug = null; applyFilters()">✕</button>
          </span>
          <span
            v-for="mid in filters.manufacturer_ids"
            :key="mid"
            class="inline-flex items-center gap-1 px-3 py-1 bg-primary-50 text-primary-700 rounded-full text-sm"
          >
            {{ brands.find((b) => b.id === mid)?.name || mid }}
            <button
              type="button"
              class="hover:text-primary-900"
              @click="filters.manufacturer_ids = filters.manufacturer_ids.filter((id) => id !== mid); applyFilters()"
            >
              ✕
            </button>
          </span>
          <span
            v-if="filters.min_price != null || filters.max_price != null"
            class="inline-flex items-center gap-1 px-3 py-1 bg-primary-50 text-primary-700 rounded-full text-sm"
          >
            {{ filters.min_price ?? 0 }} - {{ filters.max_price ?? '∞' }} تومان
            <button
              type="button"
              class="hover:text-primary-900"
              @click="filters.min_price = null; filters.max_price = null; applyFilters()"
            >
              ✕
            </button>
          </span>
          <span
            v-if="filters.has_discount"
            class="inline-flex items-center gap-1 px-3 py-1 bg-primary-50 text-primary-700 rounded-full text-sm"
          >
            فقط تخفیف‌دار
            <button type="button" class="hover:text-primary-900" @click="filters.has_discount = false; applyFilters()">✕</button>
          </span>
          <span
            v-if="filters.in_stock"
            class="inline-flex items-center gap-1 px-3 py-1 bg-primary-50 text-primary-700 rounded-full text-sm"
          >
            فقط موجود
            <button type="button" class="hover:text-primary-900" @click="filters.in_stock = false; applyFilters()">✕</button>
          </span>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          <div v-for="i in 12" :key="i" class="bg-white rounded-xl p-4 animate-pulse">
            <div class="aspect-square bg-secondary-100 rounded-lg mb-3"></div>
            <div class="h-4 bg-secondary-100 rounded mb-2"></div>
            <div class="h-4 bg-secondary-100 rounded w-2/3"></div>
          </div>
        </div>

        <!-- Products -->
        <div
          v-else-if="products.length > 0"
          :class="[
            'gap-4',
            viewMode === 'grid'
              ? 'grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4'
              : 'flex flex-col',
          ]"
        >
          <ProductCard v-for="product in products" :key="product.id" :product="product" :view-mode="viewMode" />
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-16 bg-white rounded-xl">
          <p class="text-secondary-500 text-lg mb-4">محصولی یافت نشد</p>
          <button
            v-if="hasActiveFilters"
            type="button"
            class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition"
            @click="resetFilters"
          >
            حذف فیلترها
          </button>
        </div>

        <!-- Pagination -->
        <div v-if="lastPage > 1" class="flex justify-center gap-2 mt-8">
          <button
            :disabled="currentPage === 1"
            type="button"
            class="px-3 py-2 rounded-lg border bg-white disabled:opacity-50"
            @click="changePage(currentPage - 1)"
          >
            قبلی
          </button>
          <button
            v-for="(page, idx) in pageNumbers"
            :key="idx"
            type="button"
            :disabled="page === -1"
            :class="[
              'px-3 py-2 rounded-lg border',
              page === currentPage ? 'bg-primary-600 text-white border-primary-600' : 'bg-white hover:bg-secondary-50',
              page === -1 ? 'cursor-default opacity-50' : '',
            ]"
            @click="page !== -1 && changePage(page)"
          >
            {{ page === -1 ? '...' : page }}
          </button>
          <button
            :disabled="currentPage === lastPage"
            type="button"
            class="px-3 py-2 rounded-lg border bg-white disabled:opacity-50"
            @click="changePage(currentPage + 1)"
          >
            بعدی
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
