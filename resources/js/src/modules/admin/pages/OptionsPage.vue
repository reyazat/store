<script setup lang="ts">
import { ref } from 'vue'
import { Plus, Edit, Trash2, Search, AlertTriangle, X, Loader2, Check, ChevronDown, ChevronUp, GripVertical } from 'lucide-vue-next'
import { useAdminList } from '../composables/useAdminList'
import { optionService, type AdminOption, type AdminOptionDetail, type AdminOptionFormData } from '../services/optionService'
import AdminPagination from '../components/AdminPagination.vue'

const { items: options, isLoading, errorMsg, filters, meta, debouncedReload, reload, goToPage } =
  useAdminList<AdminOption, { search: string; per_page: number }>({
    defaultFilters: { search: '', per_page: 20 },
    fetchFn: (f) => optionService.getAll(f),
  })

const isDrawerOpen = ref(false)
const isEditing = ref(false)
const editingId = ref<number | null>(null)
const isSaving = ref(false)
const saveError = ref<string | null>(null)
const formErrors = ref<Record<string, string>>({})

const form = ref<AdminOptionFormData>({
  name: '',
  slug: '',
  type: 'radio',
  sorted: 0,
})

const values = ref<Array<{ id?: number; value: string; sorted: number; isEditing?: boolean; tempValue?: string; tempSorted?: number }>>([])
const isLoadingValues = ref(false)
const valuesError = ref<string | null>(null)

const deleteTarget = ref<AdminOption | null>(null)
const isDeleting = ref(false)
const deleteError = ref<string | null>(null)

const expandedOptionId = ref<number | null>(null)
const expandedOptionDetails = ref<Map<number, AdminOptionDetail>>(new Map())

const typeOptions = [
  { value: 'radio', label: 'تک انتخابی (Radio)' },
  { value: 'checkbox', label: 'چند انتخابی (Checkbox)' },
  { value: 'select', label: 'لیست کشویی (Select)' },
]

function openCreate(): void {
  isEditing.value = false
  editingId.value = null
  form.value = { name: '', slug: '', type: 'radio', sorted: 0 }
  values.value = []
  saveError.value = null
  formErrors.value = {}
  isDrawerOpen.value = true
}

async function openEdit(option: AdminOption): Promise<void> {
  isEditing.value = true
  editingId.value = option.id
  saveError.value = null
  formErrors.value = {}
  isDrawerOpen.value = true

  try {
    const detail = await optionService.getById(option.id)
    form.value = {
      name: detail.name,
      slug: detail.slug,
      type: detail.type as AdminOptionFormData['type'],
      sorted: detail.sorted,
    }
    values.value = (detail.values || []).map(v => ({
      id: v.id,
      value: v.value,
      sorted: v.sorted,
      isEditing: false,
    })).sort((a, b) => a.sorted - b.sorted)
  } catch {
    saveError.value = 'خطا در بارگذاری اطلاعات ویژگی'
  }
}

function closeDrawer(): void {
  isDrawerOpen.value = false
  isEditing.value = false
  editingId.value = null
  saveError.value = null
  formErrors.value = {}
}

function slugify(text: string): string {
  return text
    .toLowerCase()
    .trim()
    .replace(/[\s_]+/g, '-')
    .replace(/[^\w\-]+/g, '')
    .replace(/\-\-+/g, '-')
    .replace(/^-+/, '')
    .replace(/-+$/, '')
}

function autoSlug(): void {
  if (!isEditing.value && form.value.name) {
    form.value.slug = slugify(form.value.name)
  }
}

function addValue(): void {
  const maxSorted = values.value.length > 0
    ? Math.max(...values.value.map(v => v.sorted))
    : -1
  values.value.push({
    value: '',
    sorted: maxSorted + 1,
    isEditing: true,
    tempValue: '',
    tempSorted: maxSorted + 1,
  })
}

function removeValue(index: number): void {
  values.value.splice(index, 1)
}

function startEditValue(index: number): void {
  const v = values.value[index]
  v.isEditing = true
  v.tempValue = v.value
  v.tempSorted = v.sorted
}

function cancelEditValue(index: number): void {
  const v = values.value[index]
  v.isEditing = false
  v.tempValue = undefined
  v.tempSorted = undefined
}

function saveEditValue(index: number): void {
  const v = values.value[index]
  if (v.tempValue?.trim()) {
    v.value = v.tempValue.trim()
    v.sorted = Number(v.tempSorted) || 0
  }
  v.isEditing = false
  v.tempValue = undefined
  v.tempSorted = undefined
}

function moveValueUp(index: number): void {
  if (index > 0) {
    const temp = values.value[index - 1].sorted
    values.value[index - 1].sorted = values.value[index].sorted
    values.value[index].sorted = temp
    values.value.sort((a, b) => a.sorted - b.sorted)
  }
}

function moveValueDown(index: number): void {
  if (index < values.value.length - 1) {
    const temp = values.value[index + 1].sorted
    values.value[index + 1].sorted = values.value[index].sorted
    values.value[index].sorted = temp
    values.value.sort((a, b) => a.sorted - b.sorted)
  }
}

async function submit(): Promise<void> {
  if (!form.value.name.trim()) {
    formErrors.value = { name: 'نام ویژگی الزامی است' }
    return
  }
  if (!form.value.slug.trim()) {
    formErrors.value = { slug: 'شناسه ویژگی الزامی است' }
    return
  }

  isSaving.value = true
  saveError.value = null
  formErrors.value = {}

  try {
    const payload: AdminOptionFormData = {
      name: form.value.name.trim(),
      slug: form.value.slug.trim(),
      type: form.value.type,
      sorted: Number(form.value.sorted) || 0,
    }

    let optionId: number
    if (isEditing.value && editingId.value) {
      await optionService.update(editingId.value, payload)
      optionId = editingId.value
    } else {
      const created = await optionService.create(payload)
      optionId = created.id
    }

    // Save values if editing (creating values inline only works in edit mode)
    if (isEditing.value) {
      const existingValueIds = values.value.filter(v => v.id).map(v => v.id as number)
      
      // Delete removed values
      const originalDetail = await optionService.getById(optionId)
      const originalIds = originalDetail.values.map(v => v.id)
      const toDelete = originalIds.filter(id => !existingValueIds.includes(id))
      
      const failedDeletes: number[] = []
      for (const deleteId of toDelete) {
        try {
          await optionService.destroyValue(deleteId)
        } catch (err: any) {
          // Value is in use by product variants - collect and report at the end
          failedDeletes.push(deleteId)
          const msg = err.response?.data?.message || `مقدار #${deleteId} قابل حذف نیست`
          saveError.value = msg
        }
      }
      if (failedDeletes.length > 0) {
        // Abort save so user sees the error
        isSaving.value = false
        return
      }

      // Update or create values
      for (const val of values.value) {
        if (val.id) {
          // Update existing
          await optionService.updateValue(val.id, { value: val.value, sorted: val.sorted })
        } else {
          // Create new
          if (val.value.trim()) {
            await optionService.createValue({
              option_id: optionId,
              value: val.value.trim(),
              sorted: val.sorted,
            })
          }
        }
      }
    }

    closeDrawer()
    reload()
  } catch (err: any) {
    if (err.response?.data?.errors) {
      const backendErrors = err.response.data.errors
      formErrors.value = Object.fromEntries(
        Object.entries(backendErrors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : String(v)])
      )
    } else {
      saveError.value = err.response?.data?.message || 'خطا در ذخیره ویژگی'
    }
  } finally {
    isSaving.value = false
  }
}

async function confirmDelete(): Promise<void> {
  if (!deleteTarget.value) return
  
  // Check if option has values
  try {
    const detail = await optionService.getById(deleteTarget.value.id)
    if (detail.values && detail.values.length > 0) {
      deleteError.value = `این ویژگی دارای ${detail.values.length} مقدار است. ابتدا مقادیر را حذف کنید.`
      return
    }
  } catch {
    // Continue with delete attempt
  }

  isDeleting.value = true
  deleteError.value = null
  try {
    await optionService.destroy(deleteTarget.value.id)
    deleteTarget.value = null
    reload()
  } catch (err: any) {
    deleteError.value = err.response?.data?.message || 'خطا در حذف ویژگی'
  } finally {
    isDeleting.value = false
  }
}

function toggleExpand(option: AdminOption): void {
  if (expandedOptionId.value === option.id) {
    expandedOptionId.value = null
  } else {
    expandedOptionId.value = option.id
    loadOptionValues(option.id)
  }
}

async function loadOptionValues(optionId: number): Promise<void> {
  isLoadingValues.value = true
  valuesError.value = null
  try {
    const detail = await optionService.getById(optionId)
    // Store in map for display in expanded row
    expandedOptionDetails.value.set(optionId, detail)
  } catch {
    valuesError.value = 'خطا در بارگذاری مقادیر'
  } finally {
    isLoadingValues.value = false
  }
}

</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-secondary-800">مدیریت ویژگی‌های محصول</h1>
        <p v-if="!isLoading" class="text-sm text-secondary-500 mt-1">
          {{ meta.total }} ویژگی یافت شد
        </p>
      </div>
      <button
        class="flex items-center gap-2 px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg text-sm font-medium transition"
        @click="openCreate"
      >
        <Plus class="w-4 h-4" />
        ویژگی جدید
      </button>
    </div>

    <!-- Error -->
    <div v-if="errorMsg" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
      {{ errorMsg }}
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-secondary-200 p-4 mb-6">
      <div class="flex flex-wrap items-center gap-4">
        <div class="flex-1 min-w-[200px] relative">
          <Search class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-secondary-400" />
          <input
            v-model="filters.search"
            type="text"
            placeholder="جستجوی ویژگی..."
            class="w-full h-10 pr-10 pl-4 rounded-lg border border-secondary-200 focus:border-primary-400 text-sm"
            @input="debouncedReload"
          />
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-secondary-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-secondary-50 border-b border-secondary-200">
            <tr>
              <th class="px-4 py-3 text-right text-sm font-semibold text-secondary-700">نام ویژگی</th>
              <th class="px-4 py-3 text-right text-sm font-semibold text-secondary-700">شناسه (Slug)</th>
              <th class="px-4 py-3 text-center text-sm font-semibold text-secondary-700">نوع</th>
              <th class="px-4 py-3 text-center text-sm font-semibold text-secondary-700">تعداد مقادیر</th>
              <th class="px-4 py-3 text-center text-sm font-semibold text-secondary-700">ترتیب</th>
              <th class="px-4 py-3 text-center text-sm font-semibold text-secondary-700">عملیات</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-secondary-100">
            <template v-if="isLoading">
              <tr v-for="i in 5" :key="i">
                <td v-for="j in 6" :key="j" class="px-4 py-4">
                  <div class="h-4 bg-secondary-100 rounded animate-pulse" />
                </td>
              </tr>
            </template>
            <template v-else-if="options.length === 0">
              <tr>
                <td colspan="6" class="px-4 py-12 text-center text-secondary-400">
                  <div class="w-16 h-16 bg-secondary-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <Search class="w-8 h-8 text-secondary-300" />
                  </div>
                  <p class="text-sm">هیچ ویژگی یافت نشد</p>
                </td>
              </tr>
            </template>
            <template v-else>
              <template v-for="option in options" :key="option.id">
                <tr class="hover:bg-secondary-50 transition">
                  <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                      <button
                        type="button"
                        class="p-1 hover:bg-secondary-200 rounded transition"
                        @click="toggleExpand(option)"
                      >
                        <ChevronDown v-if="expandedOptionId !== option.id" class="w-4 h-4 text-secondary-400" />
                        <ChevronUp v-else class="w-4 h-4 text-secondary-400" />
                      </button>
                      <span class="font-medium text-secondary-800">{{ option.name }}</span>
                    </div>
                  </td>
                  <td class="px-4 py-3 text-sm font-mono text-secondary-600" dir="ltr">{{ option.slug }}</td>
                  <td class="px-4 py-3 text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-secondary-100 text-secondary-700">
                      {{ option.type === 'radio' ? 'تک انتخابی' : option.type === 'checkbox' ? 'چند انتخابی' : 'لیست کشویی' }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-center text-sm text-secondary-600">
                    <!-- Show count from expanded data or from cached details -->
                    <span v-if="expandedOptionDetails.has(option.id)">{{ expandedOptionDetails.get(option.id)?.values?.length || 0 }}</span>
                    <span v-else class="text-secondary-400">-</span>
                  </td>
                  <td class="px-4 py-3 text-center text-sm text-secondary-600">{{ option.sorted }}</td>
                  <td class="px-4 py-3">
                    <div class="flex items-center justify-center gap-1">
                      <button
                        class="p-2 hover:bg-secondary-100 rounded-lg text-secondary-500 hover:text-primary-600 transition"
                        title="ویرایش"
                        @click="openEdit(option)"
                      >
                        <Edit class="w-4 h-4" />
                      </button>
                      <button
                        class="p-2 hover:bg-red-50 rounded-lg text-secondary-500 hover:text-red-600 transition"
                        title="حذف"
                        @click="deleteTarget = option"
                      >
                        <Trash2 class="w-4 h-4" />
                      </button>
                    </div>
                  </td>
                </tr>
                <!-- Expanded values row -->
                <tr v-if="expandedOptionId === option.id" class="bg-secondary-50/50">
                  <td colspan="6" class="px-4 py-4">
                    <div v-if="isLoadingValues" class="flex items-center justify-center py-4">
                      <Loader2 class="w-5 h-5 text-secondary-400 animate-spin" />
                    </div>
                    <div v-else-if="valuesError" class="text-red-500 text-sm">{{ valuesError }}</div>
                    <div v-else>
                      <h4 class="text-sm font-medium text-secondary-700 mb-2">مقادیر ویژگی:</h4>
                      <div v-if="expandedOptionDetails.get(option.id)?.values?.length" class="flex flex-wrap gap-2">
                        <span
                          v-for="val in expandedOptionDetails.get(option.id)?.values" :key="val.id"
                          class="inline-flex items-center px-3 py-1 bg-white border border-secondary-200 rounded-lg text-sm text-secondary-700"
                        >
                          {{ val.value }}
                        </span>
                      </div>
                      <p v-else class="text-sm text-secondary-400">هیچ مقداری تعریف نشده</p>
                    </div>
                  </td>
                </tr>
              </template>
            </template>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <AdminPagination
        v-if="meta.last_page > 1"
        :meta="meta"
        @page="goToPage"
      />
    </div>

    <!-- Form Drawer -->
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="translate-x-full opacity-0"
      enter-to-class="translate-x-0 opacity-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="translate-x-0 opacity-100"
      leave-to-class="translate-x-full opacity-0"
    >
      <div
        v-if="isDrawerOpen"
        class="fixed inset-y-0 left-0 z-50 w-full max-w-2xl bg-white shadow-2xl overflow-auto"
      >
        <div class="sticky top-0 bg-white border-b border-secondary-200 px-6 py-4 flex items-center justify-between">
          <h2 class="text-lg font-bold text-secondary-800">
            {{ isEditing ? 'ویرایش ویژگی' : 'ویژگی جدید' }}
          </h2>
          <button
            class="p-2 hover:bg-secondary-100 rounded-lg text-secondary-500 transition"
            @click="closeDrawer"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="p-6 space-y-6">
          <!-- Error -->
          <div v-if="saveError" class="p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
            {{ saveError }}
          </div>

          <form class="space-y-5" @submit.prevent="submit">
            <!-- Name -->
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1.5">
                نام ویژگی <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.name"
                type="text"
                placeholder="مثال: رنگ، سایز، استوریج"
                class="w-full h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-400 focus:ring-2 focus:ring-primary-100 text-sm"
                :class="{ 'border-red-400': formErrors.name }"
                @blur="autoSlug"
              />
              <p v-if="formErrors.name" class="mt-1 text-xs text-red-500">{{ formErrors.name }}</p>
            </div>

            <!-- Slug -->
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1.5">
                شناسه (Slug) <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.slug"
                type="text"
                dir="ltr"
                placeholder="color-size-storage"
                class="w-full h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-400 focus:ring-2 focus:ring-primary-100 text-sm font-mono"
                :class="{ 'border-red-400': formErrors.slug }"
                :disabled="isEditing"
              />
              <p v-if="formErrors.slug" class="mt-1 text-xs text-red-500">{{ formErrors.slug }}</p>
              <p v-if="isEditing" class="mt-1 text-xs text-secondary-400">شناسه قابل تغییر نیست</p>
            </div>

            <!-- Type -->
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1.5">نوع نمایش</label>
              <select
                v-model="form.type"
                class="w-full h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-400 focus:ring-2 focus:ring-primary-100 text-sm"
              >
                <option v-for="opt in typeOptions" :key="opt.value" :value="opt.value">
                  {{ opt.label }}
                </option>
              </select>
            </div>

            <!-- Sorted -->
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1.5">ترتیب نمایش</label>
              <input
                v-model.number="form.sorted"
                type="number"
                min="0"
                class="w-full h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-400 focus:ring-2 focus:ring-primary-100 text-sm"
              />
            </div>

            <!-- Values Section (Only for Edit Mode) -->
            <div v-if="isEditing" class="border-t border-secondary-200 pt-6">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-secondary-800">مقادیر ویژگی</h3>
                <button
                  type="button"
                  class="flex items-center gap-1.5 px-3 py-1.5 text-xs bg-primary-50 text-primary-700 rounded-lg hover:bg-primary-100 transition"
                  @click="addValue"
                >
                  <Plus class="w-3.5 h-3.5" />
                  مقدار جدید
                </button>
              </div>

              <div v-if="values.length === 0" class="text-center py-6 bg-secondary-50 rounded-xl">
                <p class="text-sm text-secondary-400">هیچ مقداری تعریف نشده</p>
              </div>

              <div v-else class="space-y-2">
                <div
                  v-for="(val, idx) in values"
                  :key="idx"
                  class="flex items-center gap-2 p-3 bg-secondary-50 rounded-xl"
                >
                  <!-- Drag handle for visual only -->
                  <GripVertical class="w-4 h-4 text-secondary-300 flex-shrink-0" />

                  <!-- Value input (inline edit mode) -->
                  <template v-if="val.isEditing">
                    <input
                      v-model="val.tempValue"
                      type="text"
                      placeholder="مقدار"
                      class="flex-1 h-9 px-3 rounded-lg border border-secondary-200 focus:border-primary-400 text-sm"
                    />
                    <input
                      v-model.number="val.tempSorted"
                      type="number"
                      min="0"
                      placeholder="ترتیب"
                      class="w-20 h-9 px-2 rounded-lg border border-secondary-200 focus:border-primary-400 text-sm"
                    />
                    <button
                      type="button"
                      class="p-1.5 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition"
                      @click="saveEditValue(idx)"
                    >
                      <Check class="w-4 h-4" />
                    </button>
                    <button
                      type="button"
                      class="p-1.5 bg-secondary-200 text-secondary-500 rounded-lg hover:bg-secondary-300 transition"
                      @click="cancelEditValue(idx)"
                    >
                      <X class="w-4 h-4" />
                    </button>
                  </template>

                  <!-- Display mode -->
                  <template v-else>
                    <span class="flex-1 text-sm text-secondary-700">{{ val.value }}</span>
                    <span class="text-xs text-secondary-400 w-12">{{ val.sorted }}</span>
                    <button
                      type="button"
                      class="p-1.5 hover:bg-secondary-200 rounded-lg text-secondary-500 transition"
                      @click="moveValueUp(idx)"
                      :disabled="idx === 0"
                    >
                      <ChevronDown class="w-4 h-4 rotate-180" />
                    </button>
                    <button
                      type="button"
                      class="p-1.5 hover:bg-secondary-200 rounded-lg text-secondary-500 transition"
                      @click="moveValueDown(idx)"
                      :disabled="idx === values.length - 1"
                    >
                      <ChevronDown class="w-4 h-4" />
                    </button>
                    <button
                      type="button"
                      class="p-1.5 hover:bg-secondary-200 rounded-lg text-secondary-500 hover:text-primary-600 transition"
                      @click="startEditValue(idx)"
                    >
                      <Edit class="w-4 h-4" />
                    </button>
                    <button
                      type="button"
                      class="p-1.5 hover:bg-red-100 rounded-lg text-secondary-500 hover:text-red-600 transition"
                      @click="removeValue(idx)"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </template>
                </div>
              </div>
            </div>

            <p v-else class="text-sm text-secondary-400 bg-secondary-50 p-3 rounded-lg">
              پس از ایجاد ویژگی، می‌توانید مقادیر آن را اضافه کنید.
            </p>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-secondary-200">
              <button
                type="button"
                class="px-4 py-2 bg-white border border-secondary-200 hover:bg-secondary-50 text-secondary-700 rounded-lg text-sm transition"
                @click="closeDrawer"
              >
                انصراف
              </button>
              <button
                type="submit"
                :disabled="isSaving"
                class="flex items-center gap-2 px-4 py-2 bg-primary-500 hover:bg-primary-600 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg text-sm font-medium transition"
              >
                <Loader2 v-if="isSaving" class="w-4 h-4 animate-spin" />
                <Check v-else class="w-4 h-4" />
                {{ isEditing ? 'ذخیره تغییرات' : 'ایجاد ویژگی' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>

    <!-- Overlay -->
    <Transition
      enter-active-class="transition-opacity duration-300"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isDrawerOpen"
        class="fixed inset-0 bg-black/50 z-40"
        @click="closeDrawer"
      />
    </Transition>

    <!-- Delete Confirm Modal -->
    <div
      v-if="deleteTarget"
      class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
            <AlertTriangle class="w-6 h-6 text-red-600" />
          </div>
          <div>
            <h3 class="text-lg font-bold text-secondary-800">حذف ویژگی</h3>
            <p class="text-sm text-secondary-500">آیا از حذف این ویژگی اطمینان دارید؟</p>
          </div>
        </div>

        <div v-if="deleteError" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
          {{ deleteError }}
        </div>

        <div class="bg-secondary-50 rounded-xl p-4 mb-6">
          <p class="text-sm font-medium text-secondary-800">{{ deleteTarget.name }}</p>
          <p class="text-xs text-secondary-500 font-mono mt-1" dir="ltr">{{ deleteTarget.slug }}</p>
        </div>

        <div class="flex items-center justify-end gap-3">
          <button
            class="px-4 py-2 bg-white border border-secondary-200 hover:bg-secondary-50 text-secondary-700 rounded-lg text-sm transition"
            @click="deleteTarget = null; deleteError = null"
          >
            انصراف
          </button>
          <button
            :disabled="isDeleting"
            class="flex items-center gap-2 px-4 py-2 bg-red-500 hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg text-sm font-medium transition"
            @click="confirmDelete"
          >
            <Loader2 v-if="isDeleting" class="w-4 h-4 animate-spin" />
            <Trash2 v-else class="w-4 h-4" />
            حذف
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
