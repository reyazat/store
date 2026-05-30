<script setup lang="ts">
import { ref } from 'vue'
import { Plus, Edit, Trash2, Search, AlertTriangle, GripVertical, ArrowUpDown, Check, X as XIcon, Loader2 } from 'lucide-vue-next'
import { useAdminList } from '../composables/useAdminList'
import { useCategoryForm } from '../composables/useCategoryForm'
import { useCategorySort } from '../composables/useCategorySort'
import { categoryService, type AdminCategory } from '../services/categoryService'
import AdminPagination from '../components/AdminPagination.vue'
import CategoryFormDrawer from '../components/CategoryFormDrawer.vue'

const { items: categories, isLoading, errorMsg, filters, meta, debouncedReload, reload, goToPage } =
  useAdminList<AdminCategory, { search: string; status: number | null; per_page: number }>({
    defaultFilters: { search: '', status: null, per_page: 20 },
    fetchFn: (f) => categoryService.getAll(f),
  })

const formState  = useCategoryForm()
const sortState  = useCategorySort(() => categories.value, reload)

const deleteTarget = ref<AdminCategory | null>(null)
const isDeleting   = ref(false)
const deleteError  = ref<string | null>(null)

function onSaved(): void {
  reload()
}

async function confirmDelete(): Promise<void> {
  if (!deleteTarget.value) return
  isDeleting.value = true
  deleteError.value = null
  try {
    await categoryService.destroy(deleteTarget.value.id)
    deleteTarget.value = null
    reload()
  } catch {
    deleteError.value = 'خطا در حذف دسته‌بندی'
  } finally {
    isDeleting.value = false
  }
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-secondary-800">مدیریت دسته‌بندی‌ها</h1>
        <p v-if="!isLoading" class="text-sm text-secondary-500 mt-1">
          {{ meta.total }} دسته‌بندی یافت شد
        </p>
      </div>
      <div class="flex items-center gap-2">
        <!-- Sort mode controls -->
        <template v-if="sortState.sortMode.value">
          <button
            class="flex items-center gap-2 px-4 py-2 bg-white border border-secondary-200 hover:bg-secondary-50 text-secondary-700 rounded-lg text-sm transition"
            @click="sortState.exitSortMode"
          >
            <XIcon class="w-4 h-4" />
            انصراف
          </button>
          <button
            :disabled="!sortState.isDirty.value || sortState.sortStatus.value === 'saving'"
            class="flex items-center gap-2 px-4 py-2 bg-green-500 hover:bg-green-600 disabled:bg-green-300 disabled:cursor-not-allowed text-white rounded-lg text-sm font-medium transition"
            @click="sortState.saveOrder"
          >
            <Loader2 v-if="sortState.sortStatus.value === 'saving'" class="w-4 h-4 animate-spin" />
            <Check v-else class="w-4 h-4" />
            ذخیره ترتیب
          </button>
        </template>

        <template v-else>
          <button
            :disabled="isLoading || categories.length < 2"
            class="flex items-center gap-2 px-4 py-2 bg-white border border-secondary-200 hover:bg-secondary-50 disabled:opacity-50 disabled:cursor-not-allowed text-secondary-700 rounded-lg text-sm transition"
            title="تنظیم ترتیب نمایش با drag & drop"
            @click="sortState.enterSortMode"
          >
            <ArrowUpDown class="w-4 h-4" />
            مرتب‌سازی
          </button>
          <button
            class="flex items-center gap-2 px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition"
            @click="formState.openCreate"
          >
            <Plus class="w-5 h-5" />
            افزودن دسته‌بندی
          </button>
        </template>
      </div>
    </div>

    <!-- Sort mode banner -->
    <Transition name="slide-down">
      <div
        v-if="sortState.sortMode.value"
        class="mb-4 flex items-center gap-3 px-4 py-3 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-800"
      >
        <GripVertical class="w-4 h-4 flex-shrink-0 text-amber-500" />
        <span>ردیف‌ها را بکشید و رها کنید تا ترتیب نمایش دسته‌بندی‌ها تغییر کند. پس از تنظیم، روی «ذخیره ترتیب» کلیک کنید.</span>
        <span
          v-if="sortState.sortStatus.value === 'error'"
          class="mr-auto text-red-600 font-medium"
        >
          خطا در ذخیره — دوباره تلاش کنید
        </span>
      </div>
    </Transition>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-4 mb-4 flex flex-wrap items-center gap-3">
      <!-- Search -->
      <div class="relative flex-1 min-w-48">
        <Search class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-secondary-400" />
        <input
          v-model="filters.search"
          type="text"
          placeholder="جستجو در نام یا slug..."
          class="w-full pr-9 pl-4 h-10 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm"
          @input="debouncedReload"
        />
      </div>

      <!-- Status filter -->
      <select
        v-model="filters.status"
        class="h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm text-secondary-700"
        @change="reload"
      >
        <option :value="null">همه وضعیت‌ها</option>
        <option :value="1">فعال</option>
        <option :value="0">غیرفعال</option>
      </select>

      <!-- Per page -->
      <select
        v-model="filters.per_page"
        class="h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm text-secondary-700"
        @change="reload"
      >
        <option :value="20">۲۰ در صفحه</option>
        <option :value="50">۵۰ در صفحه</option>
        <option :value="100">۱۰۰ در صفحه</option>
      </select>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <!-- Error -->
      <div v-if="errorMsg" class="p-6 text-center">
        <p class="text-red-500 mb-3">{{ errorMsg }}</p>
        <button
          class="px-4 py-2 bg-primary-500 text-white rounded-lg text-sm hover:bg-primary-600 transition"
          @click="reload"
        >
          تلاش مجدد
        </button>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-secondary-50 border-b border-secondary-100">
            <tr>
              <th v-if="sortState.sortMode.value" class="w-10 px-3 py-3" aria-label="جابجایی" />
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 uppercase">نام</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 uppercase">Slug</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-secondary-500 uppercase">محصولات</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-secondary-500 uppercase">وضعیت</th>
              <th v-if="!sortState.sortMode.value" class="px-6 py-3 text-center text-xs font-medium text-secondary-500 uppercase">عملیات</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-secondary-100">
            <!-- Loading skeleton -->
            <template v-if="isLoading">
              <tr v-for="i in filters.per_page > 20 ? 20 : filters.per_page" :key="i">
                <td class="px-6 py-4">
                  <div class="h-4 bg-secondary-200 rounded animate-pulse w-32"></div>
                </td>
                <td class="px-6 py-4">
                  <div class="h-4 bg-secondary-200 rounded animate-pulse w-24"></div>
                </td>
                <td class="px-6 py-4 text-center">
                  <div class="h-4 bg-secondary-200 rounded animate-pulse w-8 mx-auto"></div>
                </td>
                <td class="px-6 py-4 text-center">
                  <div class="h-5 bg-secondary-200 rounded-full animate-pulse w-14 mx-auto"></div>
                </td>
                <td class="px-6 py-4"></td>
              </tr>
            </template>

            <!-- Empty state -->
            <tr v-else-if="categories.length === 0">
              <td colspan="6" class="px-6 py-12 text-center text-secondary-400 text-sm">
                دسته‌بندی‌ای یافت نشد
              </td>
            </tr>

            <!-- Normal rows -->
            <template v-else-if="!sortState.sortMode.value">
              <tr
                v-for="category in categories"
                :key="category.id"
                class="hover:bg-secondary-50 transition"
              >
                <td class="px-6 py-4">
                  <div class="flex items-center gap-2">
                    <span v-if="category.icon" class="text-lg">{{ category.icon }}</span>
                    <div>
                      <p class="text-sm font-medium text-secondary-800">{{ category.name }}</p>
                      <p v-if="category.parent_id" class="text-xs text-secondary-400">زیردسته</p>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-sm text-secondary-500 font-mono">{{ category.slug }}</td>
                <td class="px-6 py-4 text-center">
                  <span class="inline-flex items-center justify-center min-w-8 px-2 py-0.5 rounded-full text-xs font-medium bg-secondary-100 text-secondary-600">
                    {{ category.products_count }}
                  </span>
                </td>
                <td class="px-6 py-4 text-center">
                  <span
                    :class="category.status === 1 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  >
                    {{ category.status === 1 ? 'فعال' : 'غیرفعال' }}
                  </span>
                </td>
                <td class="px-6 py-4 text-center">
                  <div class="flex items-center justify-center gap-1">
                    <button
                      class="p-2 hover:bg-secondary-100 rounded-lg transition"
                      title="ویرایش"
                      @click="formState.openEdit(category.slug)"
                    >
                      <Edit class="w-4 h-4 text-secondary-600" />
                    </button>
                    <button
                      class="p-2 hover:bg-red-100 rounded-lg transition"
                      title="حذف"
                      @click="deleteTarget = category"
                    >
                      <Trash2 class="w-4 h-4 text-red-500" />
                    </button>
                  </div>
                </td>
              </tr>
            </template>

            <!-- Drag-and-drop sort rows -->
            <template v-else>
              <tr
                v-for="category in sortState.dragList.value"
                :key="category.id"
                draggable="true"
                :class="[
                  'transition-colors cursor-grab active:cursor-grabbing select-none',
                  sortState.dragOverId.value === category.id
                    ? 'bg-primary-50 ring-1 ring-inset ring-primary-300'
                    : sortState.dragSrcId.value === category.id
                      ? 'bg-secondary-100 opacity-60'
                      : 'hover:bg-secondary-50',
                ]"
                @dragstart="sortState.onDragStart(category.id)"
                @dragover.prevent="sortState.onDragOver(category.id)"
                @dragend="sortState.onDragEnd"
              >
                <td class="w-10 px-3 py-4 text-center">
                  <GripVertical class="w-4 h-4 text-secondary-400 mx-auto" />
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-2">
                    <span v-if="category.icon" class="text-lg">{{ category.icon }}</span>
                    <div>
                      <p class="text-sm font-medium text-secondary-800">{{ category.name }}</p>
                      <p v-if="category.parent_id" class="text-xs text-secondary-400">زیردسته</p>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-sm text-secondary-400 font-mono">{{ category.slug }}</td>
                <td class="px-6 py-4 text-center">
                  <span class="inline-flex items-center justify-center min-w-8 px-2 py-0.5 rounded-full text-xs font-medium bg-secondary-100 text-secondary-600">
                    {{ category.products_count }}
                  </span>
                </td>
                <td class="px-6 py-4 text-center">
                  <span
                    :class="category.status === 1 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  >
                    {{ category.status === 1 ? 'فعال' : 'غیرفعال' }}
                  </span>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <AdminPagination
        v-if="!isLoading && !errorMsg && meta.last_page > 1"
        :meta="meta"
        @page="goToPage"
      />
    </div>
  </div>

  <!-- Category Form Drawer -->
  <CategoryFormDrawer :state="formState" @saved="onSaved" />

  <!-- Delete confirmation modal -->
  <Transition name="fade">
    <div
      v-if="deleteTarget"
      class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
      @click.self="deleteTarget = null"
    >
      <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6">
        <div class="flex items-start gap-4 mb-5">
          <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
            <AlertTriangle class="w-5 h-5 text-red-500" />
          </div>
          <div>
            <h3 class="font-semibold text-secondary-800 mb-1">حذف دسته‌بندی</h3>
            <p class="text-sm text-secondary-500">
              آیا از حذف
              <span class="font-medium text-secondary-700">«{{ deleteTarget.name }}»</span>
              اطمینان دارید؟ این عملیات قابل بازگشت نیست.
            </p>
          </div>
        </div>
        <p v-if="deleteError" class="mb-3 text-sm text-red-600">{{ deleteError }}</p>
        <div class="flex gap-3">
          <button
            class="flex-1 py-2 rounded-lg border border-secondary-200 text-secondary-700 text-sm hover:bg-secondary-50 transition"
            @click="deleteTarget = null; deleteError = null"
          >
            انصراف
          </button>
          <button
            :disabled="isDeleting"
            class="flex-1 py-2 rounded-lg bg-red-500 hover:bg-red-600 disabled:bg-red-300 disabled:cursor-not-allowed text-white text-sm font-medium transition"
            @click="confirmDelete"
          >
            {{ isDeleting ? 'در حال حذف...' : 'بله، حذف شود' }}
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from,
.fade-leave-to { opacity: 0; }

.slide-down-enter-active,
.slide-down-leave-active { transition: all 0.2s ease; }
.slide-down-enter-from,
.slide-down-leave-to { opacity: 0; transform: translateY(-8px); }
</style>
