<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import {
  X, Image as ImageIcon, Plus, Trash2, Search,
  AlertCircle, Check, Loader2,
  Tag, Layers, Percent, Globe, Link2, PackageSearch,
} from 'lucide-vue-next'
import type { AdminProductFormData, AdminProductCategory } from '../types/product'

interface Props {
  isOpen: boolean
  isEditing: boolean
  isSaving: boolean
  form: AdminProductFormData
  errors: Record<string, string>
  serverError: string | null
  categories: AdminProductCategory[]
  manufacturers: Array<{ id: number; name: string; slug: string }>
  taxClasses: Array<{ id: number; name: string; rate: number }>
  mainImagePreview: string | null
  relatedProducts: Array<{ id: number; name: string; slug: string; main_image: string | null }>
  relatedSearch: string
  relatedResults: Array<{ id: number; name: string; slug: string; main_image: string | null }>
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'submit'): void
  (e: 'auto-slug'): void
  (e: 'main-image-change', event: Event): void
  (e: 'remove-main-image'): void
  (e: 'add-gallery-images', event: Event): void
  (e: 'remove-gallery-image', index: number): void
  (e: 'toggle-category', id: number): void
  (e: 'add-related', product: { id: number; name: string; slug: string; main_image: string | null }): void
  (e: 'remove-related', id: number): void
  (e: 'search-related'): void
  (e: 'update:relatedSearch', value: string): void
  (e: 'create-category', name: string): void
  (e: 'create-manufacturer', name: string): void
}>()

type Tab = 'basic' | 'images' | 'categories' | 'discount' | 'seo' | 'related'

const activeTab = ref<Tab>('basic')

const tabs: Array<{ key: Tab; label: string; icon: typeof Tag; badge?: () => number }> = [
  { key: 'basic',      label: 'اطلاعات پایه',   icon: Tag },
  { key: 'images',     label: 'تصاویر',          icon: ImageIcon },
  { key: 'categories', label: 'دسته‌بندی',       icon: Layers },
  { key: 'discount',   label: 'تخفیف',           icon: Percent },
  { key: 'seo',        label: 'سئو',              icon: Globe },
  { key: 'related',    label: 'محصولات مرتبط',   icon: Link2, badge: () => props.relatedProducts.length },
]

const tabErrors = computed<Record<Tab, boolean>>(() => {
  const e = props.errors
  return {
    basic:      !!(e.name || e.slug || e.base_sku || e.tax_class_id || e.weight),
    images:     !!e.main_image,
    categories: !!e.category_ids,
    discount:   !!(e.discount_percent || e.discount_start_at || e.discount_end_at),
    seo:        false,
    related:    false,
  }
})

const hasDiscount = computed(() => props.form.discount_percent !== '' && Number(props.form.discount_percent) > 0)

const categorySearch = ref('')
const newCategoryName = ref('')
const showNewCategory = ref(false)
const categoryCreating = ref(false)

const filteredCategories = computed(() => {
  if (!categorySearch.value) return props.categories
  return props.categories.filter(c =>
    c.name.toLowerCase().includes(categorySearch.value.toLowerCase())
  )
})

const parentCategories = computed(() => props.categories.filter(c => c.parent_id === null))

async function submitNewCategory() {
  if (!newCategoryName.value.trim()) return
  categoryCreating.value = true
  try {
    emit('create-category', newCategoryName.value)
    newCategoryName.value = ''
    showNewCategory.value = false
  } finally {
    categoryCreating.value = false
  }
}

const newMfName = ref('')
const showNewMf  = ref(false)
const mfCreating = ref(false)

async function submitNewManufacturer() {
  if (!newMfName.value.trim()) return
  mfCreating.value = true
  try {
    emit('create-manufacturer', newMfName.value)
    newMfName.value = ''
    showNewMf.value = false
  } finally {
    mfCreating.value = false
  }
}

function onRelatedSearchInput(e: Event) {
  emit('update:relatedSearch', (e.target as HTMLInputElement).value)
  emit('search-related')
}

function clearRelatedSearch() {
  emit('update:relatedSearch', '')
  emit('search-related')
}

watch(() => props.isOpen, (val) => {
  if (val) activeTab.value = 'basic'
})
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="isOpen" class="fixed inset-0 z-50 flex">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/40" @click="emit('close')" />

        <!-- Drawer -->
        <Transition
          enter-active-class="transition-transform duration-300 ease-out"
          enter-from-class="translate-x-full"
          enter-to-class="translate-x-0"
          leave-active-class="transition-transform duration-200 ease-in"
          leave-from-class="translate-x-0"
          leave-to-class="translate-x-full"
        >
          <div
            v-if="isOpen"
            class="absolute left-0 top-0 bottom-0 w-full max-w-4xl bg-white shadow-2xl flex flex-col"
            dir="rtl"
          >
            <!-- ── Header ── -->
            <div class="flex-none flex items-center justify-between px-6 py-4 border-b border-secondary-100 bg-white z-10">
              <div>
                <h2 class="text-xl font-bold text-secondary-900">
                  {{ isEditing ? 'ویرایش محصول' : 'افزودن محصول جدید' }}
                </h2>
                <p class="text-sm text-secondary-400 mt-0.5">
                  {{ isEditing ? 'اطلاعات محصول را ویرایش کنید' : 'اطلاعات محصول جدید را وارد کنید' }}
                </p>
              </div>
              <button
                type="button"
                class="p-2 hover:bg-secondary-100 rounded-xl transition"
                @click="emit('close')"
              >
                <X class="w-5 h-5 text-secondary-600" />
              </button>
            </div>

            <!-- ── Tabs ── -->
            <div class="flex-none flex items-center gap-1 px-4 pt-3 pb-0 border-b border-secondary-100 bg-white overflow-x-auto">
              <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                :class="[
                  'relative flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium rounded-t-lg transition whitespace-nowrap',
                  activeTab === tab.key
                    ? 'text-primary-600 bg-primary-50 border-b-2 border-primary-500 -mb-px'
                    : 'text-secondary-500 hover:text-secondary-700 hover:bg-secondary-50'
                ]"
                @click="activeTab = tab.key"
              >
                <component :is="tab.icon" class="w-4 h-4" />
                {{ tab.label }}
                <span
                  v-if="tabErrors[tab.key]"
                  class="absolute -top-1 -right-1 w-2 h-2 bg-red-500 rounded-full"
                />
                <span
                  v-if="tab.badge && tab.badge() > 0"
                  class="mr-1 min-w-4 h-4 px-1 bg-primary-500 text-white text-xs rounded-full flex items-center justify-center leading-none"
                >
                  {{ tab.badge() }}
                </span>
              </button>
            </div>

            <!-- ── Body (scrollable) ── -->
            <div class="flex-1 overflow-y-auto">
              <form @submit.prevent="emit('submit')" class="p-6 space-y-6">

                <!-- Server Error -->
                <div
                  v-if="serverError"
                  class="flex items-center gap-2 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm"
                >
                  <AlertCircle class="w-4 h-4 flex-shrink-0" />
                  {{ serverError }}
                </div>

                <!-- ════ TAB: Basic Info ════ -->
                <div v-show="activeTab === 'basic'" class="space-y-5">

                  <!-- Name + Slug -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-secondary-700 mb-1">
                        نام محصول <span class="text-red-500">*</span>
                      </label>
                      <input
                        v-model="form.name"
                        type="text"
                        placeholder="مثال: گوشی موبایل سامسونگ"
                        class="field"
                        :class="{ 'border-red-400': errors.name }"
                        @blur="emit('auto-slug')"
                      />
                      <p v-if="errors.name" class="field-error">{{ errors.name }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-secondary-700 mb-1">
                        Slug (URL) <span class="text-red-500">*</span>
                      </label>
                      <input
                        v-model="form.slug"
                        type="text"
                        placeholder="samsung-galaxy-s24"
                        class="field font-mono text-sm"
                        dir="ltr"
                        :class="{ 'border-red-400': errors.slug }"
                      />
                      <p v-if="errors.slug" class="field-error">{{ errors.slug }}</p>
                    </div>
                  </div>

                  <!-- SKU + Tax + Manufacturer -->
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-secondary-700 mb-1">
                        SKU پایه <span class="text-red-500">*</span>
                      </label>
                      <input
                        v-model="form.base_sku"
                        type="text"
                        placeholder="SKU-001"
                        class="field font-mono text-sm"
                        dir="ltr"
                        :class="{ 'border-red-400': errors.base_sku }"
                      />
                      <p v-if="errors.base_sku" class="field-error">{{ errors.base_sku }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-secondary-700 mb-1">
                        کلاس مالیاتی <span class="text-red-500">*</span>
                      </label>
                      <select
                        v-model="form.tax_class_id"
                        class="field"
                        :class="{ 'border-red-400': errors.tax_class_id }"
                      >
                        <option :value="null">انتخاب کنید</option>
                        <option v-for="tc in taxClasses" :key="tc.id" :value="tc.id">
                          {{ tc.name }} ({{ tc.rate }}%)
                        </option>
                      </select>
                      <p v-if="errors.tax_class_id" class="field-error">{{ errors.tax_class_id }}</p>
                    </div>
                    <div>
                      <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-secondary-700">برند/سازنده</label>
                        <button
                          type="button"
                          class="text-xs text-primary-600 hover:underline flex items-center gap-0.5"
                          @click="showNewMf = !showNewMf"
                        >
                          <Plus class="w-3 h-3" /> جدید
                        </button>
                      </div>
                      <Transition name="slide-down">
                        <div v-if="showNewMf" class="mb-2 flex gap-2">
                          <input
                            v-model="newMfName"
                            type="text"
                            placeholder="نام برند"
                            class="field flex-1 text-sm py-1.5"
                          />
                          <button
                            type="button"
                            :disabled="mfCreating"
                            class="px-3 py-1.5 bg-primary-500 text-white text-sm rounded-lg hover:bg-primary-600 disabled:opacity-60 transition"
                            @click="submitNewManufacturer"
                          >
                            <Loader2 v-if="mfCreating" class="w-3 h-3 animate-spin" />
                            <Check v-else class="w-3 h-3" />
                          </button>
                        </div>
                      </Transition>
                      <select v-model="form.manufacturer_id" class="field">
                        <option :value="null">بدون برند</option>
                        <option v-for="m in manufacturers" :key="m.id" :value="m.id">{{ m.name }}</option>
                      </select>
                    </div>
                  </div>

                  <!-- Short Description -->
                  <div>
                    <label class="block text-sm font-medium text-secondary-700 mb-1">توضیح کوتاه</label>
                    <textarea
                      v-model="form.short_description"
                      rows="2"
                      placeholder="یک یا دو جمله درباره محصول..."
                      class="field resize-none"
                    />
                  </div>

                  <!-- Description -->
                  <div>
                    <label class="block text-sm font-medium text-secondary-700 mb-1">توضیحات کامل</label>
                    <textarea
                      v-model="form.description"
                      rows="5"
                      placeholder="توضیحات جامع محصول..."
                      class="field resize-y"
                    />
                  </div>

                  <!-- Dimensions row -->
                  <div>
                    <p class="text-sm font-medium text-secondary-700 mb-2">ابعاد و وزن (سانتیمتر / کیلوگرم)</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                      <div>
                        <label class="block text-xs text-secondary-500 mb-1">وزن (kg)</label>
                        <input v-model="form.weight" type="number" step="0.001" min="0" placeholder="0.000" class="field text-sm" dir="ltr" />
                      </div>
                      <div>
                        <label class="block text-xs text-secondary-500 mb-1">طول (cm)</label>
                        <input v-model="form.length" type="number" step="0.001" min="0" placeholder="0.000" class="field text-sm" dir="ltr" />
                      </div>
                      <div>
                        <label class="block text-xs text-secondary-500 mb-1">عرض (cm)</label>
                        <input v-model="form.width"  type="number" step="0.001" min="0" placeholder="0.000" class="field text-sm" dir="ltr" />
                      </div>
                      <div>
                        <label class="block text-xs text-secondary-500 mb-1">ارتفاع (cm)</label>
                        <input v-model="form.height" type="number" step="0.001" min="0" placeholder="0.000" class="field text-sm" dir="ltr" />
                      </div>
                    </div>
                  </div>

                  <!-- Status + Sorted + Date Available -->
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-secondary-700 mb-1">وضعیت</label>
                      <div class="flex items-center gap-3 h-10">
                        <button
                          type="button"
                          :class="['relative w-12 h-6 rounded-full transition flex-shrink-0', form.status === 1 ? 'bg-primary-500' : 'bg-secondary-300']"
                          @click="form.status = form.status === 1 ? 0 : 1"
                        >
                          <span :class="['absolute top-1 w-4 h-4 bg-white rounded-full shadow transition-all', form.status === 1 ? 'left-7' : 'left-1']" />
                        </button>
                        <span class="text-sm text-secondary-700">{{ form.status === 1 ? 'فعال' : 'غیرفعال' }}</span>
                      </div>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-secondary-700 mb-1">ترتیب نمایش</label>
                      <input v-model.number="form.sorted" type="number" min="0" class="field" dir="ltr" />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-secondary-700 mb-1">تاریخ در دسترس بودن</label>
                      <input v-model="form.date_available" type="date" class="field" dir="ltr" />
                    </div>
                  </div>
                </div>

                <!-- ════ TAB: Images ════ -->
                <div v-show="activeTab === 'images'" class="space-y-6">

                  <!-- Main Image -->
                  <div>
                    <label class="block text-sm font-medium text-secondary-700 mb-2">
                      تصویر اصلی <span class="text-red-500">*</span>
                    </label>
                    <div
                      v-if="mainImagePreview"
                      class="relative w-full aspect-video max-h-64 rounded-2xl overflow-hidden bg-secondary-100"
                    >
                      <img :src="mainImagePreview" alt="تصویر اصلی" class="w-full h-full object-contain" />
                      <button
                        type="button"
                        class="absolute top-3 left-3 w-8 h-8 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center shadow transition"
                        @click="emit('remove-main-image')"
                      >
                        <X class="w-4 h-4" />
                      </button>
                    </div>
                    <label
                      v-else
                      :class="['flex flex-col items-center justify-center gap-3 border-2 border-dashed rounded-2xl aspect-video max-h-64 cursor-pointer transition', errors.main_image ? 'border-red-400 bg-red-50' : 'border-secondary-300 hover:border-primary-400 hover:bg-primary-50']"
                    >
                      <ImageIcon class="w-12 h-12 text-secondary-300" />
                      <span class="text-sm text-secondary-500 font-medium">کلیک کنید یا فایل را اینجا رها کنید</span>
                      <span class="text-xs text-secondary-400">JPG، PNG، WebP — حداکثر ۵ مگابایت</span>
                      <input type="file" accept="image/*" class="hidden" @change="emit('main-image-change', $event)" />
                    </label>
                    <p v-if="errors.main_image" class="field-error mt-1">{{ errors.main_image }}</p>
                  </div>

                  <!-- Gallery -->
                  <div>
                    <div class="flex items-center justify-between mb-2">
                      <label class="block text-sm font-medium text-secondary-700">گالری تصاویر</label>
                      <label class="flex items-center gap-1.5 px-3 py-1.5 border border-secondary-200 rounded-lg text-sm text-secondary-600 hover:bg-secondary-50 cursor-pointer transition">
                        <Plus class="w-4 h-4" />
                        افزودن تصویر
                        <input type="file" accept="image/*" multiple class="hidden" @change="emit('add-gallery-images', $event)" />
                      </label>
                    </div>

                    <div
                      v-if="form.images.filter(i => !i._toDelete).length === 0"
                      class="border-2 border-dashed border-secondary-200 rounded-2xl p-8 text-center"
                    >
                      <ImageIcon class="w-10 h-10 text-secondary-200 mx-auto mb-2" />
                      <p class="text-sm text-secondary-400">هنوز تصویری در گالری نیست</p>
                    </div>

                    <div v-else class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
                      <div
                        v-for="(img, idx) in form.images"
                        :key="idx"
                        v-show="!img._toDelete"
                        class="relative aspect-square rounded-xl overflow-hidden bg-secondary-100 group"
                      >
                        <img
                          :src="img._preview ?? img.file_name"
                          :alt="`گالری ${idx + 1}`"
                          class="w-full h-full object-cover"
                        />
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition" />
                        <button
                          type="button"
                          class="absolute top-1.5 left-1.5 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition"
                          @click="emit('remove-gallery-image', idx)"
                        >
                          <X class="w-3 h-3" />
                        </button>
                        <span class="absolute bottom-1 right-1.5 text-white text-xs bg-black/40 rounded px-1">{{ idx + 1 }}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- ════ TAB: Categories ════ -->
                <div v-show="activeTab === 'categories'" class="space-y-4">

                  <!-- Quick create category -->
                  <div class="flex items-center justify-between">
                    <p class="text-sm text-secondary-500">
                      {{ form.category_ids.length }} دسته انتخاب شده
                    </p>
                    <button
                      type="button"
                      class="flex items-center gap-1.5 text-sm text-primary-600 hover:underline"
                      @click="showNewCategory = !showNewCategory"
                    >
                      <Plus class="w-4 h-4" />
                      دسته‌بندی جدید
                    </button>
                  </div>

                  <Transition name="slide-down">
                    <div v-if="showNewCategory" class="bg-primary-50 border border-primary-200 rounded-xl p-4">
                      <p class="text-sm font-medium text-primary-700 mb-3">ایجاد دسته‌بندی جدید</p>
                      <div class="flex gap-2">
                        <input
                          v-model="newCategoryName"
                          type="text"
                          placeholder="نام دسته‌بندی"
                          class="field flex-1"
                          @keydown.enter.prevent="submitNewCategory"
                        />
                        <button
                          type="button"
                          :disabled="categoryCreating || !newCategoryName.trim()"
                          class="flex items-center gap-1.5 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 disabled:opacity-60 transition text-sm"
                          @click="submitNewCategory"
                        >
                          <Loader2 v-if="categoryCreating" class="w-4 h-4 animate-spin" />
                          <Check v-else class="w-4 h-4" />
                          ایجاد
                        </button>
                        <button
                          type="button"
                          class="px-3 py-2 border border-secondary-200 rounded-lg text-secondary-600 hover:bg-secondary-50 transition text-sm"
                          @click="showNewCategory = false; newCategoryName = ''"
                        >
                          <X class="w-4 h-4" />
                        </button>
                      </div>
                    </div>
                  </Transition>

                  <!-- Search -->
                  <div class="relative">
                    <Search class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-secondary-400" />
                    <input
                      v-model="categorySearch"
                      type="text"
                      placeholder="جستجو در دسته‌بندی‌ها..."
                      class="field pr-9"
                    />
                  </div>

                  <!-- Category tree -->
                  <div class="border border-secondary-200 rounded-xl overflow-hidden max-h-80 overflow-y-auto">
                    <template v-if="filteredCategories.length === 0">
                      <p class="text-center text-sm text-secondary-400 py-8">دسته‌بندی‌ای یافت نشد</p>
                    </template>
                    <template v-else>
                      <template v-for="parent in parentCategories" :key="parent.id">
                        <!-- Parent -->
                        <div
                          :class="['flex items-center gap-3 px-4 py-2.5 cursor-pointer hover:bg-secondary-50 transition border-b border-secondary-100',
                            form.category_ids.includes(parent.id) ? 'bg-primary-50' : '']"
                          @click="emit('toggle-category', parent.id)"
                        >
                          <div
                            :class="['w-5 h-5 rounded border-2 flex items-center justify-center flex-shrink-0 transition',
                              form.category_ids.includes(parent.id) ? 'bg-primary-500 border-primary-500' : 'border-secondary-300']"
                          >
                            <Check v-if="form.category_ids.includes(parent.id)" class="w-3 h-3 text-white" />
                          </div>
                          <span class="text-sm font-medium text-secondary-800">{{ parent.name }}</span>
                        </div>
                        <!-- Children -->
                        <template v-for="child in filteredCategories.filter(c => c.parent_id === parent.id)" :key="child.id">
                          <div
                            :class="['flex items-center gap-3 px-4 py-2 cursor-pointer hover:bg-secondary-50 transition border-b border-secondary-100 pr-10',
                              form.category_ids.includes(child.id) ? 'bg-primary-50' : '']"
                            @click="emit('toggle-category', child.id)"
                          >
                            <div
                              :class="['w-4 h-4 rounded border-2 flex items-center justify-center flex-shrink-0 transition',
                                form.category_ids.includes(child.id) ? 'bg-primary-500 border-primary-500' : 'border-secondary-300']"
                            >
                              <Check v-if="form.category_ids.includes(child.id)" class="w-2.5 h-2.5 text-white" />
                            </div>
                            <span class="text-sm text-secondary-600">{{ child.name }}</span>
                          </div>
                        </template>
                      </template>
                      <!-- Orphaned categories (no parent found in list) -->
                      <template v-for="cat in filteredCategories.filter(c => c.parent_id !== null && !parentCategories.find(p => p.id === c.parent_id))" :key="cat.id">
                        <div
                          :class="['flex items-center gap-3 px-4 py-2.5 cursor-pointer hover:bg-secondary-50 transition border-b border-secondary-100',
                            form.category_ids.includes(cat.id) ? 'bg-primary-50' : '']"
                          @click="emit('toggle-category', cat.id)"
                        >
                          <div
                            :class="['w-5 h-5 rounded border-2 flex items-center justify-center flex-shrink-0 transition',
                              form.category_ids.includes(cat.id) ? 'bg-primary-500 border-primary-500' : 'border-secondary-300']"
                          >
                            <Check v-if="form.category_ids.includes(cat.id)" class="w-3 h-3 text-white" />
                          </div>
                          <span class="text-sm text-secondary-700">{{ cat.name }}</span>
                        </div>
                      </template>
                    </template>
                  </div>

                  <!-- Selected chips -->
                  <div v-if="form.category_ids.length > 0" class="flex flex-wrap gap-2">
                    <span
                      v-for="id in form.category_ids"
                      :key="id"
                      class="inline-flex items-center gap-1.5 px-3 py-1 bg-primary-100 text-primary-700 rounded-full text-sm"
                    >
                      {{ categories.find(c => c.id === id)?.name ?? id }}
                      <button type="button" class="hover:text-primary-900" @click="emit('toggle-category', id)">
                        <X class="w-3.5 h-3.5" />
                      </button>
                    </span>
                  </div>
                </div>

                <!-- ════ TAB: Discount ════ -->
                <div v-show="activeTab === 'discount'" class="space-y-5">

                  <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-700">
                    تخفیف به صورت درصدی روی قیمت پایه لیستینگ اعمال می‌شود. اگر تاریخ شروع و پایان ندهید، همیشه فعال خواهد بود.
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-secondary-700 mb-1">درصد تخفیف (%)</label>
                      <div class="relative">
                        <input
                          v-model="form.discount_percent"
                          type="number"
                          min="0"
                          max="100"
                          placeholder="0"
                          class="field pl-8"
                          dir="ltr"
                          :class="{ 'border-red-400': errors.discount_percent }"
                        />
                        <Percent class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-secondary-400" />
                      </div>
                      <p v-if="errors.discount_percent" class="field-error">{{ errors.discount_percent }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-secondary-700 mb-1">شروع تخفیف</label>
                      <input
                        v-model="form.discount_start_at"
                        type="date"
                        class="field"
                        dir="ltr"
                        :disabled="!hasDiscount"
                      />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-secondary-700 mb-1">پایان تخفیف</label>
                      <input
                        v-model="form.discount_end_at"
                        type="date"
                        class="field"
                        dir="ltr"
                        :disabled="!hasDiscount"
                        :min="form.discount_start_at || undefined"
                      />
                      <p v-if="errors.discount_end_at" class="field-error">{{ errors.discount_end_at }}</p>
                    </div>
                  </div>

                  <!-- Preview -->
                  <div
                    v-if="hasDiscount"
                    class="flex items-center gap-3 p-4 bg-green-50 border border-green-200 rounded-xl text-sm"
                  >
                    <Percent class="w-5 h-5 text-green-600 flex-shrink-0" />
                    <span class="text-green-700">
                      تخفیف <strong>{{ form.discount_percent }}%</strong>
                      <template v-if="form.discount_start_at || form.discount_end_at">
                        از {{ form.discount_start_at || 'اکنون' }} تا {{ form.discount_end_at || 'نامحدود' }}
                      </template>
                      فعال است
                    </span>
                  </div>
                </div>

                <!-- ════ TAB: SEO ════ -->
                <div v-show="activeTab === 'seo'" class="space-y-5">

                  <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-700">
                    اگر خالی بگذارید، نام و توضیح محصول به صورت خودکار استفاده می‌شود.
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-secondary-700 mb-1">عنوان متا (Meta Title)</label>
                    <input
                      v-model="form.meta_title"
                      type="text"
                      placeholder="عنوانی که در موتور جستجو نمایش داده می‌شود"
                      class="field"
                    />
                    <p class="text-xs text-secondary-400 mt-1">{{ form.meta_title.length }} / 60 کاراکتر پیشنهادی</p>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-secondary-700 mb-1">توضیح متا (Meta Description)</label>
                    <textarea
                      v-model="form.meta_description"
                      rows="3"
                      placeholder="توضیحی که در نتایج جستجو نمایش داده می‌شود..."
                      class="field resize-none"
                    />
                    <p class="text-xs text-secondary-400 mt-1">{{ form.meta_description.length }} / 160 کاراکتر پیشنهادی</p>
                  </div>

                  <!-- Preview card -->
                  <div class="border border-secondary-200 rounded-xl p-4 bg-white">
                    <p class="text-xs text-secondary-400 mb-2 font-medium uppercase tracking-wide">پیش‌نمایش در گوگل</p>
                    <p class="text-blue-600 text-base font-medium truncate">
                      {{ form.meta_title || form.name || 'عنوان محصول' }}
                    </p>
                    <p class="text-green-700 text-xs mt-0.5 font-mono">
                      example.com › products › {{ form.slug || 'slug' }}
                    </p>
                    <p class="text-secondary-600 text-sm mt-1 line-clamp-2">
                      {{ form.meta_description || form.short_description || 'توضیح محصول در اینجا نمایش داده می‌شود...' }}
                    </p>
                  </div>
                </div>

                <!-- ════ TAB: Related Products ════ -->
                <div v-show="activeTab === 'related'" class="space-y-4">

                  <!-- Search box -->
                  <div>
                    <label class="block text-sm font-medium text-secondary-700 mb-2">
                      جستجو و افزودن محصول مرتبط
                    </label>
                    <div class="relative">
                      <Search class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-secondary-400 pointer-events-none" />
                      <input
                        type="text"
                        :value="relatedSearch"
                        placeholder="نام یا SKU محصول را تایپ کنید..."
                        class="field pr-9"
                        :class="relatedSearch ? 'pl-8' : ''"
                        @input="onRelatedSearchInput"
                      />
                      <button
                        v-if="relatedSearch"
                        type="button"
                        class="absolute left-2.5 top-1/2 -translate-y-1/2 p-0.5 hover:bg-secondary-200 rounded transition"
                        aria-label="پاک کردن جستجو"
                        @click="clearRelatedSearch"
                      >
                        <X class="w-3.5 h-3.5 text-secondary-400" />
                      </button>
                    </div>
                    <p class="text-xs text-secondary-400 mt-1">حداقل ۲ کاراکتر وارد کنید</p>
                  </div>

                  <!-- Search results -->
                  <div
                    v-if="relatedSearch.length >= 2"
                    class="border border-secondary-200 rounded-xl overflow-hidden"
                  >
                    <!-- No results -->
                    <div
                      v-if="relatedResults.length === 0"
                      class="flex items-center gap-3 px-4 py-4 text-secondary-400"
                    >
                      <PackageSearch class="w-5 h-5 flex-shrink-0" />
                      <span class="text-sm">محصولی با این نام یافت نشد</span>
                    </div>

                    <!-- Results list -->
                    <div v-else class="divide-y divide-secondary-100 max-h-60 overflow-y-auto">
                      <div
                        v-for="result in relatedResults"
                        :key="result.id"
                        class="flex items-center gap-3 px-4 py-3 transition select-none"
                        :class="form.related_product_ids.includes(result.id)
                          ? 'bg-green-50 cursor-default'
                          : 'hover:bg-secondary-50 cursor-pointer'"
                        @click="!form.related_product_ids.includes(result.id) && emit('add-related', result)"
                      >
                        <div class="w-10 h-10 rounded-lg overflow-hidden bg-secondary-100 flex-shrink-0">
                          <img v-if="result.main_image" :src="result.main_image" :alt="result.name" class="w-full h-full object-cover" />
                          <ImageIcon v-else class="w-5 h-5 text-secondary-300 m-auto mt-2.5" />
                        </div>
                        <div class="flex-1 min-w-0">
                          <p class="text-sm font-medium text-secondary-800 truncate">{{ result.name }}</p>
                          <p class="text-xs text-secondary-400 font-mono">{{ result.slug }}</p>
                        </div>
                        <span
                          v-if="form.related_product_ids.includes(result.id)"
                          class="flex items-center gap-1 text-xs text-green-600 font-medium flex-shrink-0"
                        >
                          <Check class="w-3.5 h-3.5" />
                          افزوده شده
                        </span>
                        <span
                          v-else
                          class="flex items-center gap-1 text-xs text-primary-600 font-medium flex-shrink-0"
                        >
                          <Plus class="w-3.5 h-3.5" />
                          افزودن
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- Selected related products -->
                  <div v-if="relatedProducts.length > 0">
                    <div class="flex items-center justify-between mb-2">
                      <p class="text-sm font-medium text-secondary-700">
                        محصولات مرتبط
                        <span class="mr-1 text-xs font-normal text-secondary-400">({{ relatedProducts.length }} مورد)</span>
                      </p>
                    </div>
                    <div class="space-y-2">
                      <div
                        v-for="rel in relatedProducts"
                        :key="rel.id"
                        class="group flex items-center gap-3 p-3 bg-white border border-secondary-200 rounded-xl hover:border-secondary-300 transition"
                      >
                        <div class="w-10 h-10 rounded-lg overflow-hidden bg-secondary-100 flex-shrink-0">
                          <img v-if="rel.main_image" :src="rel.main_image" :alt="rel.name" class="w-full h-full object-cover" />
                          <ImageIcon v-else class="w-5 h-5 text-secondary-300 m-auto mt-2.5" />
                        </div>
                        <div class="flex-1 min-w-0">
                          <p class="text-sm font-medium text-secondary-800 truncate">{{ rel.name }}</p>
                          <p class="text-xs text-secondary-400 font-mono truncate">{{ rel.slug }}</p>
                        </div>
                        <button
                          type="button"
                          class="p-1.5 rounded-lg text-secondary-300 hover:text-red-500 hover:bg-red-50 transition opacity-0 group-hover:opacity-100"
                          title="حذف از محصولات مرتبط"
                          @click="emit('remove-related', rel.id)"
                        >
                          <Trash2 class="w-4 h-4" />
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Empty state (no search, no items) -->
                  <div
                    v-else-if="!relatedSearch"
                    class="flex flex-col items-center justify-center py-12 text-secondary-400"
                  >
                    <Link2 class="w-10 h-10 mb-3 opacity-30" />
                    <p class="text-sm font-medium">هنوز محصول مرتبطی انتخاب نشده</p>
                    <p class="text-xs mt-1 text-center">با جستجو در بالا، محصولات مرتبط را اضافه کنید</p>
                  </div>
                </div>

              </form>
            </div>

            <!-- ── Footer ── -->
            <div class="flex-none flex items-center justify-between px-6 py-4 border-t border-secondary-100 bg-white">
              <div class="flex items-center gap-2 text-sm text-secondary-500">
                <template v-if="Object.keys(errors).length > 0">
                  <AlertCircle class="w-4 h-4 text-red-500" />
                  <span class="text-red-600">{{ Object.keys(errors).length }} خطای اعتبارسنجی</span>
                </template>
              </div>
              <div class="flex items-center gap-3">
                <button
                  type="button"
                  class="px-5 py-2.5 border border-secondary-200 rounded-xl text-secondary-700 hover:bg-secondary-50 transition text-sm font-medium"
                  @click="emit('close')"
                >
                  انصراف
                </button>
                <button
                  type="button"
                  :disabled="isSaving"
                  class="flex items-center gap-2 px-6 py-2.5 bg-primary-500 hover:bg-primary-600 disabled:opacity-60 text-white rounded-xl transition text-sm font-medium"
                  @click="emit('submit')"
                >
                  <Loader2 v-if="isSaving" class="w-4 h-4 animate-spin" />
                  <Check v-else class="w-4 h-4" />
                  {{ isEditing ? 'ذخیره تغییرات' : 'ایجاد محصول' }}
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
@reference "../../../../../css/app.css";

.field {
  @apply w-full px-4 py-2.5 border border-secondary-200 rounded-xl text-sm text-secondary-800 bg-white
         focus:border-primary-400 focus:ring-2 focus:ring-primary-100 outline-none transition;
}

.field-error {
  @apply text-xs text-red-500 mt-1;
}

.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.2s ease;
  overflow: hidden;
}
.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  max-height: 0;
}
.slide-down-enter-to,
.slide-down-leave-from {
  opacity: 1;
  max-height: 200px;
}
</style>
