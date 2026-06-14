<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import {
  Plus, Edit, Trash2, Search, AlertTriangle, GripVertical,
  Check, X as XIcon, Loader2, ChevronDown, ChevronRight,
  FolderOpen, Folder, ImageIcon, MoveVertical, ListTree
} from 'lucide-vue-next'
import { useAdminList } from '../composables/useAdminList'
import { useCategoryForm } from '../composables/useCategoryForm'
import { useCategoryHierarchy } from '../composables/useCategoryHierarchy'
import { categoryService, type AdminCategory } from '../services/categoryService'
import AdminPagination from '../components/AdminPagination.vue'
import CategoryFormDrawer from '../components/CategoryFormDrawer.vue'

// List state
const { items: categories, isLoading, errorMsg, filters, meta, debouncedReload, reload, goToPage } =
  useAdminList<AdminCategory, { search: string; status: number | null; parent_id: number | null; per_page: number }>({
    defaultFilters: { search: '', status: null, parent_id: null, per_page: 100 }, // Increased for tree view
    fetchFn: (f) => categoryService.getAll(f),
  })

// Form state
const formState = useCategoryForm()

// Hierarchy and sort state
const hierarchyState = useCategoryHierarchy({
  categories,
  onReorder: reload,
})

// UI State
const expandedIds = ref<Set<number>>(new Set())
const deleteTarget = ref<AdminCategory | null>(null)
const isDeleting = ref(false)
const deleteError = ref<string | null>(null)

// View mode: 'tree' or 'flat'
const viewMode = ref<'tree' | 'flat'>('tree')

// Toggle expand/collapse for tree view
function toggleExpand(categoryId: number): void {
  if (expandedIds.value.has(categoryId)) {
    expandedIds.value.delete(categoryId)
  } else {
    expandedIds.value.add(categoryId)
  }
}

// Expand all on initial load
onMounted(() => {
  // Auto-expand all categories with children
  categories.value.forEach(c => {
    if (c.children_count > 0) {
      expandedIds.value.add(c.id)
    }
  })
})

// Computed: filtered tree for display
const displayCategories = computed(() => {
  if (viewMode.value === 'flat' || hierarchyState.sortMode.value !== 'none') {
    return hierarchyState.sortMode.value !== 'none'
      ? hierarchyState.dragList.value
      : hierarchyState.flattenedTree.value
  }

  // Tree view: show only visible nodes based on expansion
  const result: Array<AdminCategory & { level: number; isVisible: boolean }> = []

  const traverse = (nodes: typeof hierarchyState.categoryTree.value, level = 0, parentExpanded = true) => {
    nodes.forEach(node => {
      const isExpanded = expandedIds.value.has(node.id)
      const shouldShow = parentExpanded

      if (shouldShow) {
        result.push({ ...node, level, isVisible: true })
      }

      if (node.children.length > 0) {
        traverse(node.children, level + 1, shouldShow && isExpanded)
      }
    })
  }

  traverse(hierarchyState.categoryTree.value)
  return result
})

// Check if category has children
function hasChildren(categoryId: number): boolean {
  return categories.value.some(c => c.parent_id === categoryId)
}

// Delete handlers
async function confirmDelete(): Promise<void> {
  if (!deleteTarget.value) return

  // Check if category has children
  if (deleteTarget.value.children_count > 0) {
    deleteError.value = 'این دسته‌بندی دارای زیردسته است. ابتدا زیردسته‌ها را حذف یا منتقل کنید.'
    return
  }

  isDeleting.value = true
  deleteError.value = null
  try {
    await categoryService.destroy(deleteTarget.value.slug)
    deleteTarget.value = null
    reload()
  } catch (err: any) {
    deleteError.value = err.response?.data?.message || 'خطا در حذف دسته‌بندی'
  } finally {
    isDeleting.value = false
  }
}

// Get parent name
function getParentName(parentId: number | null): string {
  if (!parentId) return ''
  const parent = categories.value.find(c => c.id === parentId)
  return parent?.name || ''
}

// Get sort mode display text
const sortModeText = computed(() => {
  if (hierarchyState.sortMode.value === 'root') {
    return 'مرتب‌سازی دسته‌های اصلی'
  }
  if (hierarchyState.sortMode.value === 'children') {
    return `مرتب‌سازی زیردسته‌های: ${hierarchyState.sortTargetParentName.value}`
  }
  return ''
})
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
      <div>
        <div class="flex items-center gap-3">
          <h1 class="text-2xl font-bold text-secondary-800">مدیریت دسته‌بندی‌ها</h1>
          <!-- View mode toggle -->
          <div class="flex items-center bg-secondary-100 rounded-lg p-1">
            <button
              :class="['px-3 py-1 rounded-md text-xs font-medium transition',
                viewMode === 'tree'
                  ? 'bg-white text-secondary-800 shadow-sm'
                  : 'text-secondary-500 hover:text-secondary-700']"
              @click="viewMode = 'tree'"
            >
              <ListTree class="w-3.5 h-3.5 inline ml-1" />
              سلسله‌مراتبی
            </button>
            <button
              :class="['px-3 py-1 rounded-md text-xs font-medium transition',
                viewMode === 'flat'
                  ? 'bg-white text-secondary-800 shadow-sm'
                  : 'text-secondary-500 hover:text-secondary-700']"
              @click="viewMode = 'flat'"
            >
              لیست
            </button>
          </div>
        </div>
        <p v-if="!isLoading" class="text-sm text-secondary-500 mt-1">
          {{ meta.total }} دسته‌بندی (
          {{ categories.filter(c => c.parent_id === null).length }} اصلی،
          {{ categories.filter(c => c.parent_id !== null).length }} زیردسته
          )
        </p>
      </div>

      <div class="flex items-center gap-2">
        <!-- Sort controls (when in sort mode) -->
        <template v-if="hierarchyState.sortMode.value !== 'none'">
          <div class="flex items-center gap-2 px-3 py-2 bg-amber-50 border border-amber-200 rounded-lg">
            <MoveVertical class="w-4 h-4 text-amber-600" />
            <span class="text-sm text-amber-800 font-medium">{{ sortModeText }}</span>
          </div>

          <button
            class="flex items-center gap-2 px-4 py-2 bg-white border border-secondary-200 hover:bg-secondary-50 text-secondary-700 rounded-lg text-sm transition"
            @click="hierarchyState.exitSortMode()"
          >
            <XIcon class="w-4 h-4" />
            انصراف
          </button>

          <button
            :disabled="!hierarchyState.isDirty.value || hierarchyState.sortStatus.value === 'saving'"
            class="flex items-center gap-2 px-4 py-2 bg-green-500 hover:bg-green-600 disabled:bg-green-300 disabled:cursor-not-allowed text-white rounded-lg text-sm font-medium transition"
            @click="hierarchyState.saveOrder()"
          >
            <Loader2 v-if="hierarchyState.sortStatus.value === 'saving'" class="w-4 h-4 animate-spin" />
            <Check v-else class="w-4 h-4" />
            ذخیره ترتیب
          </button>
        </template>

        <template v-else>
          <!-- Sort root categories button -->
          <button
            :disabled="isLoading || categories.filter(c => c.parent_id === null).length < 2"
            class="flex items-center gap-2 px-4 py-2 bg-white border border-secondary-200 hover:bg-secondary-50 disabled:opacity-50 disabled:cursor-not-allowed text-secondary-700 rounded-lg text-sm transition"
            title="مرتب‌سازی دسته‌های اصلی"
            @click="hierarchyState.enterRootSortMode()"
          >
            <MoveVertical class="w-4 h-4" />
            مرتب‌سازی اصلی‌ها
          </button>

          <button
            class="flex items-center gap-2 px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition"
            @click="formState.openCreate()"
          >
            <Plus class="w-5 h-5" />
            افزودن دسته‌بندی
          </button>
        </template>
      </div>
    </div>

    <!-- Sort mode help banner -->
    <Transition name="slide-down">
      <div
        v-if="hierarchyState.sortMode.value !== 'none'"
        class="mb-4 flex items-center gap-3 px-4 py-3 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-800"
      >
        <GripVertical class="w-4 h-4 flex-shrink-0 text-amber-500" />
        <span>ردیف‌ها را با Drag & Drop جابجا کنید تا ترتیب نمایش را تنظیم کنید.</span>
        <span
          v-if="hierarchyState.sortStatus.value === 'error'"
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

      <!-- Parent filter (for flat view) -->
      <select
        v-if="viewMode === 'flat'"
        v-model="filters.parent_id"
        class="h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm text-secondary-700"
        @change="reload"
      >
        <option :value="null">همه دسته‌بندی‌ها</option>
        <option :value="0">فقط دسته‌های اصلی</option>
        <option
          v-for="c in categories.filter(cat => cat.parent_id === null)"
          :key="c.id"
          :value="c.id"
        >
          زیردسته‌های: {{ c.name }}
        </option>
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
              <th v-if="hierarchyState.sortMode.value !== 'none'" class="w-10 px-3 py-3" aria-label="جابجایی" />
              <th class="w-10 px-3 py-3" v-else aria-label="گسترش" />
              <th class="px-4 py-3 text-right text-xs font-medium text-secondary-500 uppercase">تصویر</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-secondary-500 uppercase">نام / Slug</th>
              <th class="px-4 py-3 text-center text-xs font-medium text-secondary-500 uppercase">سطح</th>
              <th class="px-4 py-3 text-center text-xs font-medium text-secondary-500 uppercase">محصولات</th>
              <th class="px-4 py-3 text-center text-xs font-medium text-secondary-500 uppercase">وضعیت</th>
              <th v-if="hierarchyState.sortMode.value === 'none'" class="px-4 py-3 text-center text-xs font-medium text-secondary-500 uppercase">عملیات</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-secondary-100">
            <!-- Loading skeleton -->
            <template v-if="isLoading">
              <tr v-for="i in 5" :key="i">
                <td v-for="j in 7" :key="j" class="px-4 py-4">
                  <div class="h-4 bg-secondary-200 rounded animate-pulse" />
                </td>
              </tr>
            </template>

            <!-- Empty state -->
            <tr v-else-if="categories.length === 0">
              <td colspan="7" class="px-6 py-12 text-center text-secondary-400 text-sm">
                <div class="w-16 h-16 bg-secondary-100 rounded-full flex items-center justify-center mx-auto mb-3">
                  <FolderOpen class="w-8 h-8 text-secondary-300" />
                </div>
                <p>هیچ دسته‌بندی یافت نشد</p>
              </td>
            </tr>

            <!-- Normal rows (tree view) -->
            <template v-else-if="hierarchyState.sortMode.value === 'none'">
              <tr
                v-for="category in displayCategories"
                :key="category.id"
                class="hover:bg-secondary-50 transition"
                :class="{
                  'bg-blue-50/30': category.parent_id === null,
                  'bg-white': category.parent_id !== null
                }"
              >
                <!-- Expand/Collapse -->
                <td class="w-10 px-3 py-3">
                  <button
                    v-if="hasChildren(category.id)"
                    class="p-1 hover:bg-secondary-200 rounded transition"
                    @click="toggleExpand(category.id)"
                  >
                    <ChevronDown v-if="expandedIds.has(category.id)" class="w-4 h-4 text-secondary-500" />
                    <ChevronRight v-else class="w-4 h-4 text-secondary-500" />
                  </button>
                  <span v-else-if="category.parent_id !== null" class="w-6 inline-block" />
                </td>

                <!-- Image -->
                <td class="px-4 py-3">
                  <div class="flex items-center justify-center">
                    <div
                      v-if="(category as any).image"
                      class="w-12 h-12 rounded-lg overflow-hidden border border-secondary-200"
                    >
                      <img
                        :src="(category as any).image"
                        :alt="category.name"
                        class="w-full h-full object-cover"
                      />
                    </div>
                    <div
                      v-else
                      class="w-12 h-12 rounded-lg bg-secondary-100 flex items-center justify-center"
                    >
                      <ImageIcon class="w-5 h-5 text-secondary-300" />
                    </div>
                  </div>
                </td>

                <!-- Name & Slug -->
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <!-- Indentation -->
                    <span
                      v-for="n in (category as any).level"
                      :key="n"
                      class="w-6 inline-block"
                    />

                    <!-- Icon -->
                    <span v-if="(category as any).icon" class="text-lg">{{ (category as any).icon }}</span>
                    <Folder v-else-if="category.parent_id === null" class="w-5 h-5 text-primary-500" />
                    <FolderOpen v-else class="w-4 h-4 text-secondary-400" />

                    <div>
                      <p class="text-sm font-medium text-secondary-800">{{ category.name }}</p>
                      <p class="text-xs text-secondary-400 font-mono" dir="ltr">{{ category.slug }}</p>
                      <div class="flex items-center gap-1 mt-0.5">
                        <span
                          v-if="category.parent_id === null"
                          class="text-xs px-1.5 py-0.5 bg-primary-100 text-primary-700 rounded"
                        >
                          دسته اصلی
                        </span>
                        <span
                          v-else
                          class="text-xs px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded"
                        >
                          زیردسته {{ getParentName(category.parent_id) ? 'از: ' + getParentName(category.parent_id) : '' }}
                        </span>
                        <span
                          v-if="category.children_count > 0"
                          class="text-xs px-1.5 py-0.5 bg-purple-100 text-purple-700 rounded"
                        >
                          {{ category.children_count }} زیردسته
                        </span>
                      </div>
                    </div>
                  </div>
                </td>

                <!-- Level -->
                <td class="px-4 py-3 text-center">
                  <span
                    :class="[
                      'inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-medium',
                      (category as any).level === 0
                        ? 'bg-primary-100 text-primary-700'
                        : 'bg-secondary-100 text-secondary-600'
                    ]"
                  >
                    {{ (category as any).level + 1 }}
                  </span>
                </td>

                <!-- Products -->
                <td class="px-4 py-3 text-center">
                  <div class="flex flex-col items-center gap-1">
                    <span class="inline-flex items-center justify-center min-w-8 px-2 py-0.5 rounded-full text-xs font-medium bg-secondary-100 text-secondary-600">
                      {{ category.products_count }}
                    </span>
                    <span v-if="category.direct_products_count > 0" class="text-xs text-secondary-400">
                      ({{ category.direct_products_count }} مستقیم)
                    </span>
                  </div>
                </td>

                <!-- Status -->
                <td class="px-4 py-3 text-center">
                  <span
                    :class="category.status === 1 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  >
                    {{ category.status === 1 ? 'فعال' : 'غیرفعال' }}
                  </span>
                </td>

                <!-- Actions -->
                <td class="px-4 py-3 text-center">
                  <div class="flex items-center justify-center gap-1">
                    <!-- Sort children button (only for parents) -->
                    <button
                      v-if="category.parent_id === null && category.children_count > 0"
                      class="p-2 hover:bg-amber-100 rounded-lg text-secondary-500 hover:text-amber-600 transition"
                      title="مرتب‌سازی زیردسته‌ها"
                      @click="hierarchyState.enterChildrenSortMode(category)"
                    >
                      <MoveVertical class="w-4 h-4" />
                    </button>

                    <button
                      class="p-2 hover:bg-secondary-100 rounded-lg transition"
                      title="ویرایش"
                      @click="formState.openEdit(category.slug)"
                    >
                      <Edit class="w-4 h-4 text-secondary-600" />
                    </button>

                    <button
                      class="p-2 hover:bg-red-100 rounded-lg transition"
                      :class="{ 'opacity-50 cursor-not-allowed': category.children_count > 0 }"
                      :title="category.children_count > 0 ? 'ابتدا زیردسته‌ها را حذف کنید' : 'حذف'"
                      :disabled="category.children_count > 0"
                      @click="deleteTarget = category"
                    >
                      <Trash2 class="w-4 h-4 text-red-500" />
                    </button>
                  </div>
                </td>
              </tr>
            </template>

            <!-- Sort mode rows (drag & drop) -->
            <template v-else>
              <tr
                v-for="category in hierarchyState.dragList.value"
                :key="category.id"
                draggable="true"
                :class="[
                  'transition-colors cursor-grab active:cursor-grabbing select-none',
                  hierarchyState.dragOverId.value === category.id
                    ? 'bg-primary-50 ring-1 ring-inset ring-primary-300'
                    : hierarchyState.dragSrcId.value === category.id
                      ? 'bg-secondary-100 opacity-60'
                      : 'hover:bg-secondary-50',
                ]"
                @dragstart="hierarchyState.onDragStart(category.id)"
                @dragover.prevent="hierarchyState.onDragOver(category.id)"
                @dragend="hierarchyState.onDragEnd()"
              >
                <!-- Drag Handle -->
                <td class="w-10 px-3 py-4 text-center">
                  <GripVertical class="w-4 h-4 text-secondary-400 mx-auto" />
                </td>

                <!-- Image -->
                <td class="px-4 py-3">
                  <div class="flex items-center justify-center">
                    <div
                      v-if="(category as any).image"
                      class="w-10 h-10 rounded-lg overflow-hidden border border-secondary-200 opacity-60"
                    >
                      <img
                        :src="(category as any).image"
                        :alt="category.name"
                        class="w-full h-full object-cover"
                      />
                    </div>
                    <div
                      v-else
                      class="w-10 h-10 rounded-lg bg-secondary-100 flex items-center justify-center"
                    >
                      <ImageIcon class="w-4 h-4 text-secondary-300" />
                    </div>
                  </div>
                </td>

                <!-- Name -->
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <span v-if="(category as any).icon" class="text-lg">{{ (category as any).icon }}</span>
                    <Folder v-else-if="category.parent_id === null" class="w-5 h-5 text-primary-500" />
                    <FolderOpen v-else class="w-4 h-4 text-secondary-400" />
                    <div>
                      <p class="text-sm font-medium text-secondary-800">{{ category.name }}</p>
                      <p class="text-xs text-secondary-400 font-mono" dir="ltr">{{ category.slug }}</p>
                    </div>
                  </div>
                </td>

                <!-- Level -->
                <td class="px-4 py-3 text-center">
                  <span
                    :class="[
                      'inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-medium',
                      category.parent_id === null
                        ? 'bg-primary-100 text-primary-700'
                        : 'bg-secondary-100 text-secondary-600'
                    ]"
                  >
                    {{ category.parent_id === null ? '1' : '2' }}
                  </span>
                </td>

                <!-- Products -->
                <td class="px-4 py-3 text-center">
                  <span class="inline-flex items-center justify-center min-w-8 px-2 py-0.5 rounded-full text-xs font-medium bg-secondary-100 text-secondary-600">
                    {{ category.products_count }}
                  </span>
                </td>

                <!-- Status -->
                <td class="px-4 py-3 text-center">
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

      <!-- Pagination (only for flat view or when not in sort mode) -->
      <AdminPagination
        v-if="!isLoading && !errorMsg && meta.last_page > 1 && hierarchyState.sortMode.value === 'none' && viewMode === 'flat'"
        :meta="meta"
        @page="goToPage"
      />
    </div>

    <!-- Category Form Drawer -->
    <CategoryFormDrawer :state="formState" @saved="reload" />

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
  </div>
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
