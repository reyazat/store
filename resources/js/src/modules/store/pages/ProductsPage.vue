<script setup lang="ts">
/**
 * Store Products Management Page
 *
 * Lists products belonging to this store and provides CRUD via the shared ProductFormDrawer.
 * ProductFormDrawer is imported from the admin module; it should be moved to shared/ in a
 * future refactoring pass.
 */
import { ref, computed } from 'vue'
import { Plus, Search, Check, AlertCircle, Package } from 'lucide-vue-next'
import { formatPrice } from '@shared/utils/format'
import { storeProductService } from '../services/storeProductService'
import type { AdminProduct } from '@/modules/admin/services/productService'
import type { AdminProductDetail } from '@/modules/admin/types/product'

// TODO: move ProductFormDrawer + useProductForm + productService to shared/ when refactoring module boundaries
import { useProductForm } from '@/modules/admin/composables/useProductForm'
import ProductFormDrawer from '@/modules/admin/components/ProductFormDrawer.vue'
import { productService } from '@/modules/admin/services/productService'

// ── State ───────────────────────────────────────────────────────────────────
const products      = ref<AdminProduct[]>([])
const isLoading     = ref(false)
const errorMsg      = ref<string | null>(null)
const successMsg    = ref<string | null>(null)
const deleteError   = ref<string | null>(null)
const deletingSlug  = ref<string | null>(null)

const meta = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0 })

const filters = ref({
  search:   '',
  status:   null as number | null,
  per_page: 20,
})

let searchTimer: ReturnType<typeof setTimeout> | null = null

// ── Load products ────────────────────────────────────────────────────────────
const load = async (page = 1) => {
  isLoading.value = true
  errorMsg.value  = null
  try {
    const result = await storeProductService.getAll({
      search:   filters.value.search || undefined,
      status:   filters.value.status,
      per_page: filters.value.per_page,
      page,
    })
    products.value = result.data
    meta.value     = result.meta
  } catch {
    errorMsg.value = 'خطا در بارگذاری محصولات'
  } finally {
    isLoading.value = false
  }
}

const reload = () => load(1)

const debouncedReload = () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(reload, 350)
}

// ── Form / Drawer ────────────────────────────────────────────────────────────
const onSaveSuccess = (product: AdminProductDetail) => {
  successMsg.value = `محصول "${product.name}" با موفقیت ذخیره شد`
  setTimeout(() => (successMsg.value = null), 3500)
  reload()
}

const formState = useProductForm(onSaveSuccess)

// ── Delete ───────────────────────────────────────────────────────────────────
const handleDelete = async (product: AdminProduct) => {
  if (!confirm(`آیا از حذف محصول "${product.name}" مطمئن هستید؟`)) return
  deletingSlug.value = product.slug
  deleteError.value  = null
  try {
    await productService.destroy(product.slug)
    reload()
  } catch {
    deleteError.value = 'خطا در حذف محصول'
    setTimeout(() => (deleteError.value = null), 3000)
  } finally {
    deletingSlug.value = null
  }
}

const handleCreateCategory = async (name: string) => {
  const slug = name.toLowerCase().trim().replace(/[\s_]+/g, '-').replace(/[^\w\-]+/g, '')
  try {
    const created = await productService.createCategory({ name, slug, parent_id: null })
    formState.categories.value.push(created)
    formState.form.category_ids.push(created.id)
  } catch {
    formState.serverError.value = 'خطا در ایجاد دسته‌بندی'
  }
}

const handleCreateManufacturer = async (name: string) => {
  const slug = name.toLowerCase().trim().replace(/[\s_]+/g, '-').replace(/[^\w\-]+/g, '')
  try {
    const created = await productService.createManufacturer({ name, slug })
    formState.manufacturers.value.push(created)
    formState.form.manufacturer_id = created.id
  } catch {
    formState.serverError.value = 'خطا در ایجاد برند'
  }
}

// ── Pagination ───────────────────────────────────────────────────────────────
const goToPage = (page: number) => load(page)

const statusLabel = (s: number) => s === 1 ? 'فعال' : 'غیرفعال'
const statusClass = (s: number) => s === 1
  ? 'bg-green-100 text-green-700'
  : 'bg-secondary-100 text-secondary-500'

// init
load()
</script>

<template>
  <div dir="rtl">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-secondary-800">محصولات فروشگاه</h2>
        <p v-if="!isLoading" class="text-sm text-secondary-500 mt-1">{{ meta.total }} محصول</p>
      </div>
      <button
        class="flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl transition shadow-sm"
        @click="formState.openCreate"
      >
        <Plus class="w-5 h-5" />
        افزودن محصول
      </button>
    </div>

    <!-- Alerts -->
    <div v-if="successMsg" class="flex items-center gap-2 mb-4 px-4 py-3 bg-green-50 border border-green-200 rounded-xl text-green-700 text-sm">
      <Check class="w-5 h-5 flex-shrink-0" />{{ successMsg }}
    </div>
    <div v-if="deleteError" class="flex items-center gap-2 mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
      <AlertCircle class="w-5 h-5 flex-shrink-0" />{{ deleteError }}
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
        class="h-10 px-3 rounded-lg border border-secondary-200 text-sm text-secondary-700"
        @change="reload"
      >
        <option :value="20">۲۰ در صفحه</option>
        <option :value="50">۵۰ در صفحه</option>
      </select>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div v-if="isLoading" class="flex items-center justify-center py-20">
        <div class="w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
      </div>

      <div v-else-if="errorMsg" class="p-6 text-center">
        <p class="text-red-500 mb-3">{{ errorMsg }}</p>
        <button class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm" @click="reload">تلاش مجدد</button>
      </div>

      <div v-else-if="!products.length" class="flex flex-col items-center justify-center py-20 text-secondary-400">
        <Package class="w-12 h-12 mb-3" />
        <p>هیچ محصولی یافت نشد</p>
        <button class="mt-4 px-4 py-2 bg-primary-600 text-white rounded-lg text-sm" @click="formState.openCreate">افزودن اولین محصول</button>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-secondary-50 border-b border-secondary-100 text-right">
            <tr>
              <th class="px-4 py-3 font-medium text-secondary-500">تصویر</th>
              <th class="px-4 py-3 font-medium text-secondary-500">نام محصول</th>
              <th class="px-4 py-3 font-medium text-secondary-500">SKU</th>
              <th class="px-4 py-3 font-medium text-secondary-500">قیمت</th>
              <th class="px-4 py-3 font-medium text-secondary-500 text-center">موجودی</th>
              <th class="px-4 py-3 font-medium text-secondary-500 text-center">وضعیت</th>
              <th class="px-4 py-3 font-medium text-secondary-500">عملیات</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-secondary-100">
            <tr v-for="product in products" :key="product.slug" class="hover:bg-secondary-50 transition">
              <td class="px-4 py-3">
                <img
                  v-if="product.main_image"
                  :src="product.main_image"
                  :alt="product.name"
                  class="w-12 h-12 object-cover rounded-lg border border-secondary-100"
                />
                <div v-else class="w-12 h-12 bg-secondary-100 rounded-lg flex items-center justify-center">
                  <Package class="w-5 h-5 text-secondary-400" />
                </div>
              </td>
              <td class="px-4 py-3">
                <div class="font-medium text-secondary-800 max-w-xs truncate">{{ product.name }}</div>
                <div class="text-xs text-secondary-400 mt-0.5">{{ product.slug }}</div>
              </td>
              <td class="px-4 py-3 text-secondary-500 text-xs font-mono">{{ product.base_sku }}</td>
              <td class="px-4 py-3 text-secondary-700">
                {{ product.price ? formatPrice(product.price) + ' تومان' : '—' }}
              </td>
              <td class="px-4 py-3 text-center">
                <span :class="[
                  'text-xs font-semibold px-2 py-0.5 rounded',
                  product.stock !== null && product.stock > 0 ? 'text-green-700' : 'text-red-500'
                ]">
                  {{ product.stock ?? 0 }}
                </span>
              </td>
              <td class="px-4 py-3 text-center">
                <span :class="['text-xs font-medium px-2 py-1 rounded-full', statusClass(product.status)]">
                  {{ statusLabel(product.status) }}
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <button
                    @click="formState.openEdit(product.slug)"
                    class="px-3 py-1.5 bg-primary-50 hover:bg-primary-100 text-primary-700 rounded-lg text-xs font-medium transition"
                  >
                    ویرایش
                  </button>
                  <button
                    @click="handleDelete(product)"
                    :disabled="deletingSlug === product.slug"
                    class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg text-xs font-medium transition disabled:opacity-50"
                  >
                    {{ deletingSlug === product.slug ? '...' : 'حذف' }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-secondary-100 bg-secondary-50">
        <span class="text-sm text-secondary-500">
          صفحه {{ meta.current_page }} از {{ meta.last_page }}
        </span>
        <div class="flex gap-1">
          <button
            v-for="page in meta.last_page"
            :key="page"
            @click="goToPage(page)"
            :class="[
              'w-8 h-8 text-sm rounded-lg transition',
              page === meta.current_page
                ? 'bg-primary-600 text-white'
                : 'hover:bg-secondary-200 text-secondary-600'
            ]"
          >{{ page }}</button>
        </div>
      </div>
    </div>

    <!-- Product Form Drawer (shared with admin panel) -->
    <!-- TODO: move ProductFormDrawer to shared/ when module boundaries are refactored -->
    <ProductFormDrawer
      v-bind="formState"
      @save-success="onSaveSuccess"
      @create-category="handleCreateCategory"
      @create-manufacturer="handleCreateManufacturer"
    />
  </div>
</template>
