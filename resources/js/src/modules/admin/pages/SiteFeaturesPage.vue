<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { Plus, Edit2, Trash2, Eye, EyeOff, AlertCircle, Check, X, GripVertical } from 'lucide-vue-next'
import { siteFeatureService } from '../services/siteFeatureService'
import type { SiteFeature, SiteFeatureFormData } from '../types/siteFeature'

const features = ref<SiteFeature[]>([])
const isLoading = ref(true)
const isSaving = ref(false)
const showModal = ref(false)
const editingFeature = ref<SiteFeature | null>(null)
const errorMsg = ref<string | null>(null)
const successMsg = ref<string | null>(null)

const iconOptions = [
  { value: 'Truck', label: '🚚 ارسال', emoji: '🚚' },
  { value: 'Shield', label: '🛡️ ضمانت', emoji: '🛡️' },
  { value: 'RotateCcw', label: '🔄 بازگشت', emoji: '🔄' },
  { value: 'Headphones', label: '🎧 پشتیبانی', emoji: '🎧' },
  { value: 'CreditCard', label: '💳 پرداخت', emoji: '💳' },
  { value: 'Package', label: '📦 بسته‌بندی', emoji: '📦' },
  { value: 'Star', label: '⭐ کیفیت', emoji: '⭐' },
  { value: 'Clock', label: '⏰ سرعت', emoji: '⏰' },
]

function getIconEmoji(iconName: string | null | undefined): string {
  if (!iconName) return '—'
  const found = iconOptions.find(o => o.value === iconName)
  return found ? found.emoji : iconName
}

const defaultForm = (): SiteFeatureFormData => ({
  title: '',
  description: '',
  icon: 'Truck',
  sort_order: 0,
  is_active: true,
})

const form = reactive<SiteFeatureFormData>(defaultForm())

onMounted(loadFeatures)

async function loadFeatures() {
  isLoading.value = true
  try {
    features.value = await siteFeatureService.getAll()
  } catch {
    showError('خطا در دریافت ویژگی‌ها')
  } finally {
    isLoading.value = false
  }
}

function openCreate() {
  editingFeature.value = null
  Object.assign(form, defaultForm())
  showModal.value = true
}

function openEdit(feature: SiteFeature) {
  editingFeature.value = feature
  Object.assign(form, {
    title: feature.title,
    description: feature.description ?? '',
    icon: feature.icon ?? 'Truck',
    sort_order: feature.sort_order,
    is_active: feature.is_active,
  })
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  editingFeature.value = null
}

async function handleSubmit() {
  if (!form.title.trim()) {
    showError('عنوان ویژگی الزامی است')
    return
  }
  isSaving.value = true
  errorMsg.value = null
  try {
    const payload = {
      title: form.title.trim(),
      description: form.description?.trim() || null,
      icon: form.icon || null,
      sort_order: Number(form.sort_order) || 0,
      is_active: Boolean(form.is_active),
    }
    if (editingFeature.value) {
      const updated = await siteFeatureService.update(editingFeature.value.id, payload)
      const idx = features.value.findIndex(f => f.id === editingFeature.value!.id)
      if (idx !== -1) features.value[idx] = updated
      showSuccess('ویژگی ویرایش شد')
    } else {
      const created = await siteFeatureService.create(payload)
      features.value.push(created)
      showSuccess('ویژگی ایجاد شد')
    }
    closeModal()
  } catch (err: any) {
    const msg = err?.response?.data?.message || err?.response?.data?.error || 'خطا در ذخیره ویژگی'
    showError(msg)
  } finally {
    isSaving.value = false
  }
}

async function toggleActive(feature: SiteFeature) {
  const previous = feature.is_active
  feature.is_active = !feature.is_active
  try {
    await siteFeatureService.update(feature.id, {
      title: feature.title,
      description: feature.description ?? null,
      icon: feature.icon ?? null,
      sort_order: Number(feature.sort_order) || 0,
      is_active: feature.is_active,
    })
    showSuccess('وضعیت ویژگی تغییر کرد')
  } catch (err: any) {
    feature.is_active = previous
    const msg = err?.response?.data?.message || 'خطا در تغییر وضعیت'
    showError(msg)
  }
}

async function handleDelete(feature: SiteFeature) {
  if (!confirm(`آیا از حذف "${feature.title}" اطمینان دارید؟`)) return
  try {
    await siteFeatureService.delete(feature.id)
    features.value = features.value.filter(f => f.id !== feature.id)
    showSuccess('ویژگی حذف شد')
  } catch (err: any) {
    const msg = err?.response?.data?.message || 'خطا در حذف ویژگی'
    showError(msg)
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
      <h1 class="text-2xl font-bold text-secondary-800">ویژگی‌های سایت</h1>
      <button
        @click="openCreate"
        class="flex items-center gap-2 px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition"
      >
        <Plus class="w-5 h-5" />
        افزودن ویژگی
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
    <div v-else-if="features.length === 0" class="text-center py-12 bg-white rounded-xl">
      <p class="text-secondary-500 mb-4">هیچ ویژگی تعریف نشده است</p>
      <button
        @click="openCreate"
        class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition"
      >
        افزودن اولین ویژگی
      </button>
    </div>

    <!-- Features List -->
    <div v-else class="bg-white rounded-xl shadow-sm overflow-hidden">
      <table class="w-full">
        <thead class="bg-secondary-50">
          <tr>
            <th class="px-4 py-3 text-right text-sm font-medium text-secondary-600">ترتیب</th>
            <th class="px-4 py-3 text-right text-sm font-medium text-secondary-600">آیکون</th>
            <th class="px-4 py-3 text-right text-sm font-medium text-secondary-600">عنوان</th>
            <th class="px-4 py-3 text-right text-sm font-medium text-secondary-600">توضیحات</th>
            <th class="px-4 py-3 text-right text-sm font-medium text-secondary-600">وضعیت</th>
            <th class="px-4 py-3 text-right text-sm font-medium text-secondary-600">عملیات</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-secondary-100">
          <tr v-for="feature in features" :key="feature.id" class="hover:bg-secondary-50">
            <td class="px-4 py-3 text-sm text-secondary-600">{{ feature.sort_order }}</td>
            <td class="px-4 py-3 text-xl" :title="feature.icon ?? ''">{{ getIconEmoji(feature.icon) }}</td>
            <td class="px-4 py-3 font-medium text-secondary-800">{{ feature.title }}</td>
            <td class="px-4 py-3 text-sm text-secondary-500">{{ feature.description || '-' }}</td>
            <td class="px-4 py-3">
              <button
                @click="toggleActive(feature)"
                :class="[
                  'p-2 rounded-lg transition',
                  feature.is_active ? 'text-emerald-600 hover:bg-emerald-50' : 'text-secondary-400 hover:bg-secondary-100'
                ]"
              >
                <Eye v-if="feature.is_active" class="w-5 h-5" />
                <EyeOff v-else class="w-5 h-5" />
              </button>
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <button
                  @click="openEdit(feature)"
                  class="p-2 hover:bg-primary-50 text-primary-600 rounded-lg transition"
                >
                  <Edit2 class="w-4 h-4" />
                </button>
                <button
                  @click="handleDelete(feature)"
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
            {{ editingFeature ? 'ویرایش ویژگی' : 'افزودن ویژگی' }}
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
              placeholder="مثلاً: ارسال سریع"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-secondary-700 mb-1">توضیحات</label>
            <input
              v-model="form.description"
              type="text"
              class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              placeholder="مثلاً: تحویل ۲۴ ساعته"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-secondary-700 mb-1">آیکون</label>
            <select
              v-model="form.icon"
              class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            >
              <option v-for="icon in iconOptions" :key="icon.value" :value="icon.value">
                {{ icon.label }}
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

          <div class="flex items-center gap-2">
            <input
              v-model="form.is_active"
              type="checkbox"
              id="is_active"
              class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
            />
            <label for="is_active" class="text-sm text-secondary-700">فعال</label>
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
              {{ isSaving ? 'در حال ذخیره...' : (editingFeature ? 'ذخیره تغییرات' : 'ایجاد ویژگی') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
