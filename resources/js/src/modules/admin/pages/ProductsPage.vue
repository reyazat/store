<script setup lang="ts">
import { ref } from 'vue'
import { Plus, Edit, Trash2, Search, Check, AlertCircle, Package } from 'lucide-vue-next'
import { formatPrice } from '@shared/utils/format'
import { useAdminList } from '../composables/useAdminList'
import { useProductForm } from '../composables/useProductForm'
import { productService, type AdminProduct } from '../services/productService'
import AdminPagination from '../components/AdminPagination.vue'
import ProductFormDrawer from '../components/ProductFormDrawer.vue'
import type { AdminProductDetail } from '../types/product'

const { items: products, isLoading, errorMsg, filters, meta, debouncedReload, reload, goToPage } =
  useAdminList<AdminProduct, { search: string; status: number | null; per_page: number }>({
    defaultFilters: { search: '', status: null, per_page: 20 },
    fetchFn: (f) => productService.getAll(f),
  })

const successMsg   = ref<string | null>(null)
const deleteError  = ref<string | null>(null)
const deletingSlug = ref<string | null>(null)

function onSaveSuccess(product: AdminProductDetail) {
  successMsg.value = formState.isEditing.value
    ? `محصول "${product.name}" ویرایش شد`
    : `محصول "${product.name}" ایجاد شد`
  setTimeout(() => (successMsg.value = null), 3500)
  reload()
}

const formState = useProductForm(onSaveSuccess)

async function handleDelete(product: AdminProduct) {
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

async function handleCreateCategory(name: string) {
  const slug = name.toLowerCase().trim().replace(/[\s_]+/g, '-').replace(/[^\w\-]+/g, '')
  try {
    const created = await productService.createCategory({ name, slug, parent_id: null })
    formState.categories.value.push(created)
    formState.form.category_ids.push(created.id)
  } catch {
    formState.serverError.value = 'خطا در ایجاد دسته‌بندی'
  }
}

async function handleCreateManufacturer(name: string) {
  const slug = name.toLowerCase().trim().replace(/[\s_]+/g, '-').replace(/[^\w\-]+/g, '')
  try {
    const created = await productService.createManufacturer({ name, slug })
    formState.manufacturers.value.push(created)
    formState.form.manufacturer_id = created.id
  } catch {
    formState.serverError.value = 'خطا در ایجاد برند'
  }
}

</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-secondary-800">مدیریت محصولات</h1>
        <p v-if="!isLoading" class="text-sm text-secondary-500 mt-1">{{ meta.total }} محصول یافت شد</p>
      </div>
      <button
        class="flex items-center gap-2 px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-xl transition shadow-sm"
        @click="formState.openCreate"
      >
        <Plus class="w-5 h-5" />
        افزودن محصول
      </button>
    </div>

    <!-- Alerts -->
    <Transition name="fade">
      <div v-if="successMsg" class="flex items-center gap-2 mb-4 px-4 py-3 bg-green-50 border border-green-200 rounded-xl text-green-700 text-sm">
        <Check class="w-5 h-5 flex-shrink-0" />
        {{ successMsg }}
      </div>
    </Transition>
    <Transition name="fade">
      <div v-if="deleteError" class="flex items-center gap-2 mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
        <AlertCircle class="w-5 h-5 flex-shrink-0" />
        {{ deleteError }}
      </div>
    </Transition>

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
        @change="reload"
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
              <td colspan="6" class="px-6 py-16 text-center">
                <Package class="w-12 h-12 text-secondary-200 mx-auto mb-3" />
                <p class="text-secondary-400 text-sm">محصولی یافت نشد</p>
                <button
                  class="mt-4 px-4 py-2 bg-primary-500 text-white rounded-lg text-sm hover:bg-primary-600 transition"
                  @click="formState.openCreate"
                >
                  اولین محصول را ایجاد کنید
                </button>
              </td>
            </tr>
            <tr
              v-else
              v-for="product in products"
              :key="product.id"
              class="hover:bg-secondary-50 transition"
            >
              <td class="px-6 py-4">
                <div class="w-12 h-12 bg-secondary-100 rounded-xl overflow-hidden flex-shrink-0">
                  <img v-if="product.main_image" :src="product.main_image" :alt="product.name" class="w-full h-full object-cover" />
                  <Package v-else class="w-6 h-6 text-secondary-300 m-auto mt-3" />
                </div>
              </td>
              <td class="px-6 py-4">
                <p class="text-sm font-medium text-secondary-800">{{ product.name }}</p>
                <p class="text-xs text-secondary-400 font-mono">{{ product.slug }}</p>
                <p v-if="product.discount_percent" class="text-xs text-amber-600 mt-0.5">تخفیف {{ product.discount_percent }}%</p>
              </td>
              <td class="px-6 py-4 text-sm text-secondary-800 whitespace-nowrap">
                {{ formatPrice(product.price) }} <span class="text-xs text-secondary-400">تومان</span>
              </td>
              <td class="px-6 py-4 text-center text-sm text-secondary-600">{{ product.stock ?? 0 }}</td>
              <td class="px-6 py-4 text-center">
                <span
                  :class="product.status === 1 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'"
                  class="px-2.5 py-0.5 text-xs rounded-full font-medium"
                >
                  {{ product.status === 1 ? 'فعال' : 'غیرفعال' }}
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center justify-center gap-1">
                  <button
                    class="p-2 hover:bg-primary-50 rounded-lg transition"
                    title="ویرایش"
                    @click="formState.openEdit(product.slug)"
                  >
                    <Edit class="w-4 h-4 text-primary-600" />
                  </button>
                  <button
                    class="p-2 hover:bg-red-50 rounded-lg transition"
                    title="حذف"
                    :disabled="deletingSlug === product.slug"
                    @click="handleDelete(product)"
                  >
                    <Trash2 class="w-4 h-4 text-red-500" />
                  </button>
                </div>
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

    <!-- Product Form Drawer -->
    <ProductFormDrawer
      :is-open="formState.isOpen.value"
      :is-editing="formState.isEditing.value"
      :is-saving="formState.isSaving.value"
      :form="formState.form"
      :errors="formState.errors.value"
      :server-error="formState.serverError.value"
      :categories="formState.categories.value"
      :manufacturers="formState.manufacturers.value"
      :tax-classes="formState.taxClasses.value"
      :available-options="formState.availableOptions.value"
      :main-image-preview="formState.mainImagePreview.value"
      :related-products="formState.relatedProducts.value"
      :related-search="formState.relatedSearch.value"
      :related-results="formState.relatedResults.value"
      @close="formState.close"
      @submit="formState.submit"
      @auto-slug="formState.autoSlug"
      @main-image-change="formState.onMainImageChange"
      @remove-main-image="formState.removeMainImage"
      @add-gallery-images="formState.addGalleryImages"
      @remove-gallery-image="formState.removeGalleryImage"
      @toggle-category="formState.toggleCategory"
      @add-related="formState.addRelated"
      @remove-related="formState.removeRelated"
      @search-related="formState.searchRelated"
      @update:related-search="v => (formState.relatedSearch.value = v)"
      @create-category="handleCreateCategory"
      @create-manufacturer="handleCreateManufacturer"
    />
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
