<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { Plus, Edit2, Trash2, Eye, EyeOff, AlertCircle, Check, X } from 'lucide-vue-next'
import { promoBannerService } from '../services/promoBannerService'
import type { PromoBanner, PromoBannerFormData } from '../types/promoBanner'

const banners = ref<PromoBanner[]>([])
const isLoading = ref(true)
const isSaving = ref(false)
const showModal = ref(false)
const editingBanner = ref<PromoBanner | null>(null)
const errorMsg = ref<string | null>(null)
const successMsg = ref<string | null>(null)

const placementOptions = [
  { value: 'home', label: 'صفحه اصلی' },
  { value: 'product', label: 'صفحه محصول' },
  { value: 'category', label: 'صفحه دسته‌بندی' },
]

const gradientOptions = [
  { value: 'primary-500', label: 'آبی (primary-500)' },
  { value: 'primary-600', label: 'آبی تیره (primary-600)' },
  { value: 'violet-500', label: 'بنفش (violet-500)' },
  { value: 'violet-600', label: 'بنفش تیره (violet-600)' },
  { value: 'emerald-500', label: 'سبز (emerald-500)' },
  { value: 'emerald-600', label: 'سبز تیره (emerald-600)' },
  { value: 'amber-500', label: 'نارنجی (amber-500)' },
  { value: 'amber-600', label: 'نارنجی تیره (amber-600)' },
  { value: 'red-500', label: 'قرمز (red-500)' },
  { value: 'red-600', label: 'قرمز تیره (red-600)' },
  { value: 'secondary-600', label: 'خاکستری (secondary-600)' },
  { value: 'secondary-800', label: 'خاکستری تیره (secondary-800)' },
]

const defaultForm = (): PromoBannerFormData => ({
  title: '',
  subtitle: '',
  icon: 'Star',
  link: '',
  cta_text: 'مشاهده',
  gradient_from: 'primary-500',
  gradient_to: 'primary-700',
  placement: 'home',
  sort_order: 0,
  is_active: true,
})

const form = reactive<PromoBannerFormData>(defaultForm())

onMounted(loadBanners)

async function loadBanners() {
  isLoading.value = true
  try {
    banners.value = await promoBannerService.getAll()
  } catch {
    showError('خطا در دریافت بنرها')
  } finally {
    isLoading.value = false
  }
}

function openCreate() {
  editingBanner.value = null
  Object.assign(form, defaultForm())
  showModal.value = true
}

function openEdit(banner: PromoBanner) {
  editingBanner.value = banner
  Object.assign(form, {
    title: banner.title,
    subtitle: banner.subtitle ?? '',
    icon: banner.icon ?? 'Star',
    link: banner.link ?? '',
    cta_text: banner.cta_text ?? 'مشاهده',
    gradient_from: banner.gradient_from ?? 'primary-500',
    gradient_to: banner.gradient_to ?? 'primary-700',
    placement: banner.placement,
    sort_order: banner.sort_order,
    is_active: banner.is_active,
  })
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  editingBanner.value = null
}

async function handleSubmit() {
  if (!form.title.trim()) {
    showError('عنوان بنر الزامی است')
    return
  }
  isSaving.value = true
  errorMsg.value = null
  try {
    if (editingBanner.value) {
      const updated = await promoBannerService.update(editingBanner.value.id, form)
      const idx = banners.value.findIndex(b => b.id === editingBanner.value!.id)
      if (idx !== -1) banners.value[idx] = updated
      showSuccess('بنر ویرایش شد')
    } else {
      const created = await promoBannerService.create(form)
      banners.value.push(created)
      showSuccess('بنر ایجاد شد')
    }
    closeModal()
  } catch {
    showError('خطا در ذخیره بنر')
  } finally {
    isSaving.value = false
  }
}

async function toggleActive(banner: PromoBanner) {
  try {
    await promoBannerService.update(banner.id, {
      title: banner.title,
      subtitle: banner.subtitle ?? '',
      icon: banner.icon ?? '',
      link: banner.link ?? '',
      cta_text: banner.cta_text ?? '',
      gradient_from: banner.gradient_from ?? 'primary-500',
      gradient_to: banner.gradient_to ?? 'primary-700',
      placement: banner.placement,
      sort_order: banner.sort_order,
      is_active: !banner.is_active,
    })
    banner.is_active = !banner.is_active
    showSuccess('وضعیت بنر تغییر کرد')
  } catch {
    showError('خطا در تغییر وضعیت')
  }
}

async function handleDelete(banner: PromoBanner) {
  if (!confirm(`آیا از حذف "${banner.title}" اطمینان دارید؟`)) return
  try {
    await promoBannerService.delete(banner.id)
    banners.value = banners.value.filter(b => b.id !== banner.id)
    showSuccess('بنر حذف شد')
  } catch {
    showError('خطا در حذف بنر')
  }
}

function showError(msg: string) {
  errorMsg.value = msg
  setTimeout(() => errorMsg.value = null, 5000)
}

function showSuccess(msg: string) {
  successMsg.value = msg
  setTimeout(() => successMsg.value = null, 3000)
}
</script>

<template>
  <div class="p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-secondary-800">بنرهای تبلیغاتی</h1>
      <button
        @click="openCreate"
        class="flex items-center gap-2 px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition"
      >
        <Plus class="w-5 h-5" />
        افزودن بنر
      </button>
    </div>

    <!-- Messages -->
    <div v-if="errorMsg" class="mb-4 p-4 bg-red-50 text-red-600 rounded-lg flex items-center gap-2">
      <AlertCircle class="w-5 h-5" />
      {{ errorMsg }}
    </div>
    <div v-if="successMsg" class="mb-4 p-4 bg-emerald-50 text-emerald-600 rounded-lg flex items-center gap-2">
      <Check class="w-5 h-5" />
      {{ successMsg }}
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="text-center py-12">
      <div class="animate-spin w-10 h-10 border-4 border-primary-500 border-t-transparent rounded-full mx-auto"></div>
      <p class="mt-4 text-secondary-500">در حال بارگذاری...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="banners.length === 0" class="text-center py-12 bg-white rounded-xl">
      <p class="text-secondary-500 mb-4">هیچ بنر تعریف نشده است</p>
      <button
        @click="openCreate"
        class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition"
      >
        افزودن اولین بنر
      </button>
    </div>

    <!-- Banners List -->
    <div v-else class="bg-white rounded-xl shadow-sm overflow-hidden">
      <table class="w-full">
        <thead class="bg-secondary-50">
          <tr>
            <th class="px-4 py-3 text-right text-sm font-medium text-secondary-600">ترتیب</th>
            <th class="px-4 py-3 text-right text-sm font-medium text-secondary-600">عنوان</th>
            <th class="px-4 py-3 text-right text-sm font-medium text-secondary-600">زیرنویس</th>
            <th class="px-4 py-3 text-right text-sm font-medium text-secondary-600">مکان نمایش</th>
            <th class="px-4 py-3 text-right text-sm font-medium text-secondary-600">گرادیان</th>
            <th class="px-4 py-3 text-right text-sm font-medium text-secondary-600">وضعیت</th>
            <th class="px-4 py-3 text-right text-sm font-medium text-secondary-600">عملیات</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-secondary-100">
          <tr v-for="banner in banners" :key="banner.id" class="hover:bg-secondary-50">
            <td class="px-4 py-3 text-sm text-secondary-600">{{ banner.sort_order }}</td>
            <td class="px-4 py-3 font-medium text-secondary-800">{{ banner.title }}</td>
            <td class="px-4 py-3 text-sm text-secondary-500">{{ banner.subtitle || '-' }}</td>
            <td class="px-4 py-3 text-sm text-secondary-600">
              {{ placementOptions.find(p => p.value === banner.placement)?.label || banner.placement }}
            </td>
            <td class="px-4 py-3 text-sm text-secondary-600">
              <span class="text-xs">{{ banner.gradient_from }} → {{ banner.gradient_to }}</span>
            </td>
            <td class="px-4 py-3">
              <button
                @click="toggleActive(banner)"
                :class="[
                  'p-2 rounded-lg transition',
                  banner.is_active ? 'text-emerald-600 hover:bg-emerald-50' : 'text-secondary-400 hover:bg-secondary-100'
                ]"
              >
                <Eye v-if="banner.is_active" class="w-5 h-5" />
                <EyeOff v-else class="w-5 h-5" />
              </button>
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <button
                  @click="openEdit(banner)"
                  class="p-2 hover:bg-primary-50 text-primary-600 rounded-lg transition"
                >
                  <Edit2 class="w-4 h-4" />
                </button>
                <button
                  @click="handleDelete(banner)"
                  class="p-2 hover:bg-red-50 text-red-600 rounded-lg transition"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
      <div class="bg-white rounded-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-secondary-100 flex items-center justify-between">
          <h2 class="text-xl font-bold text-secondary-800">
            {{ editingBanner ? 'ویرایش بنر' : 'افزودن بنر' }}
          </h2>
          <button @click="closeModal" class="p-2 hover:bg-secondary-100 rounded-lg transition">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-secondary-700 mb-1">عنوان</label>
            <input
              v-model="form.title"
              type="text"
              class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              placeholder="مثلاً: تخفیف ویژه"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-secondary-700 mb-1">زیرنویس</label>
            <input
              v-model="form.subtitle"
              type="text"
              class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              placeholder="مثلاً: تا ۵۰٪ تخفیف"
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">گرادیان (از)</label>
              <select
                v-model="form.gradient_from"
                class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              >
                <option v-for="color in gradientOptions" :key="color.value" :value="color.value">
                  {{ color.label }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">گرادیان (به)</label>
              <select
                v-model="form.gradient_to"
                class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              >
                <option v-for="color in gradientOptions" :key="color.value" :value="color.value">
                  {{ color.label }}
                </option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">لینک</label>
              <input
                v-model="form.link"
                type="text"
                class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                placeholder="/products"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">متن دکمه</label>
              <input
                v-model="form.cta_text"
                type="text"
                class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                placeholder="مشاهده"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">مکان نمایش</label>
              <select
                v-model="form.placement"
                class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              >
                <option v-for="place in placementOptions" :key="place.value" :value="place.value">
                  {{ place.label }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">ترتیب نمایش</label>
              <input
                v-model.number="form.sort_order"
                type="number"
                min="0"
                class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              />
            </div>
          </div>

          <div class="flex items-center gap-2">
            <input
              v-model="form.is_active"
              type="checkbox"
              id="is_active_banner"
              class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
            />
            <label for="is_active_banner" class="text-sm text-secondary-700">فعال</label>
          </div>

          <div class="flex gap-3 pt-4">
            <button
              type="button"
              @click="closeModal"
              class="flex-1 px-4 py-2 border border-secondary-200 text-secondary-700 rounded-lg hover:bg-secondary-50 transition"
            >
              انصراف
            </button>
            <button
              type="submit"
              :disabled="isSaving"
              class="flex-1 px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition disabled:opacity-50"
            >
              {{ isSaving ? 'در حال ذخیره...' : (editingBanner ? 'ذخیره تغییرات' : 'ایجاد بنر') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
