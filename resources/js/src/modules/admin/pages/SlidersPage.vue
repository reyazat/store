<script setup lang="ts">
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { Plus, Edit2, Trash2, Eye, EyeOff, Image as ImageIcon, X, Check, AlertCircle } from 'lucide-vue-next'
import { sliderService } from '../services/sliderService'
import type { Slider, SliderFormData } from '../types/slider'

const sliders = ref<Slider[]>([])
const isLoading = ref(true)
const isSaving = ref(false)
const showModal = ref(false)
const editingSlider = ref<Slider | null>(null)
const imagePreview = ref<string | null>(null)
const errorMsg = ref<string | null>(null)
const successMsg = ref<string | null>(null)

const defaultForm = (): SliderFormData => ({
  title: '',
  subtitle: '',
  link: '',
  button_text: '',
  button_link: '',
  sort_order: 0,
  is_active: true,
  image: null,
})

const form = reactive<SliderFormData>(defaultForm())

onMounted(loadSliders)

async function loadSliders() {
  isLoading.value = true
  try {
    sliders.value = await sliderService.getAll()
  } catch {
    showError('خطا در دریافت اسلایدرها')
  } finally {
    isLoading.value = false
  }
}

function openCreate() {
  editingSlider.value = null
  Object.assign(form, defaultForm())
  imagePreview.value = null
  showModal.value = true
}

function openEdit(slider: Slider) {
  editingSlider.value = slider
  Object.assign(form, {
    title: slider.title,
    subtitle: slider.subtitle ?? '',
    link: slider.link ?? '',
    button_text: slider.button_text ?? '',
    button_link: slider.button_link ?? '',
    sort_order: slider.sort_order,
    is_active: slider.is_active,
    image: null,
  })
  imagePreview.value = slider.image ?? null
  showModal.value = true
}

function closeModal() {
  if (imagePreview.value && imagePreview.value.startsWith('blob:')) {
    URL.revokeObjectURL(imagePreview.value)
  }
  showModal.value = false
  editingSlider.value = null
  imagePreview.value = null
}

function onImageChange(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) return

  // Validation: File size (max 5MB)
  const maxSize = 5 * 1024 * 1024 // 5MB
  if (file.size > maxSize) {
    showError('حجم فایل نباید بیشتر از 5 مگابایت باشد')
    return
  }

  // Validation: File type
  const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif']
  if (!allowedTypes.includes(file.type)) {
    showError('فرمت فایل مجاز نیست. JPG, PNG, WebP مجاز هستند')
    return
  }

  form.image = file
  imagePreview.value = URL.createObjectURL(file)
}

function removeImage() {
  if (imagePreview.value && imagePreview.value.startsWith('blob:')) {
    URL.revokeObjectURL(imagePreview.value)
  }
  form.image = null
  imagePreview.value = null
}

async function handleSubmit() {
  if (!form.title.trim()) {
    showError('عنوان اسلایدر الزامی است')
    return
  }
  isSaving.value = true
  errorMsg.value = null
  try {
    if (editingSlider.value) {
      const updated = await sliderService.update(editingSlider.value.id, form)
      const idx = sliders.value.findIndex(s => s.id === editingSlider.value!.id)
      if (idx !== -1) sliders.value[idx] = updated
      showSuccess('اسلایدر ویرایش شد')
    } else {
      const created = await sliderService.create(form)
      sliders.value.push(created)
      showSuccess('اسلایدر ایجاد شد')
    }
    closeModal()
  } catch {
    showError('خطا در ذخیره اسلایدر')
  } finally {
    isSaving.value = false
  }
}

async function toggleActive(slider: Slider) {
  try {
    const updated = await sliderService.update(slider.id, {
      title: slider.title,
      subtitle: slider.subtitle ?? '',
      link: slider.link ?? '',
      button_text: slider.button_text ?? '',
      button_link: slider.button_link ?? '',
      sort_order: slider.sort_order,
      is_active: !slider.is_active,
      // ❌ Don't send 'image' field - let backend preserve existing image
    })
    const idx = sliders.value.findIndex(s => s.id === slider.id)
    if (idx !== -1) sliders.value[idx] = updated
  } catch {
    showError('خطا در تغییر وضعیت')
  }
}

async function handleDelete(slider: Slider) {
  if (!confirm(`آیا از حذف اسلایدر "${slider.title}" مطمئن هستید؟`)) return
  try {
    await sliderService.remove(slider.id)
    sliders.value = sliders.value.filter(s => s.id !== slider.id)
    showSuccess('اسلایدر حذف شد')
  } catch {
    showError('خطا در حذف اسلایدر')
  }
}

function showError(msg: string) {
  errorMsg.value = msg
  setTimeout(() => (errorMsg.value = null), 4000)
}

function showSuccess(msg: string) {
  successMsg.value = msg
  setTimeout(() => (successMsg.value = null), 3000)
}

onUnmounted(() => {
  if (imagePreview.value && imagePreview.value.startsWith('blob:')) {
    URL.revokeObjectURL(imagePreview.value)
  }
})
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-secondary-800">مدیریت اسلایدر</h1>
      <button
        @click="openCreate"
        class="flex items-center gap-2 px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition"
      >
        <Plus class="w-5 h-5" />
        اسلایدر جدید
      </button>
    </div>

    <!-- Alerts -->
    <transition name="fade">
      <div v-if="successMsg" class="flex items-center gap-2 mb-4 px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-green-700">
        <Check class="w-5 h-5 flex-shrink-0" />
        {{ successMsg }}
      </div>
    </transition>
    <transition name="fade">
      <div v-if="errorMsg" class="flex items-center gap-2 mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-red-700">
        <AlertCircle class="w-5 h-5 flex-shrink-0" />
        {{ errorMsg }}
      </div>
    </transition>

    <!-- List -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <!-- Loading -->
      <div v-if="isLoading" class="flex items-center justify-center py-16">
        <div class="animate-spin w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full"></div>
      </div>

      <!-- Empty -->
      <div v-else-if="sliders.length === 0" class="flex flex-col items-center justify-center py-16 text-secondary-400">
        <ImageIcon class="w-16 h-16 mb-4 opacity-40" />
        <p class="text-lg">هیچ اسلایدری ثبت نشده است</p>
        <button @click="openCreate" class="mt-4 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition">
          اولین اسلایدر را بسازید
        </button>
      </div>

      <!-- Table -->
      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-secondary-50 border-b border-secondary-200">
            <tr>
              <th class="px-4 py-3 text-right text-xs font-medium text-secondary-500">تصویر</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-secondary-500">عنوان</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-secondary-500">زیرعنوان</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-secondary-500">دکمه</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-secondary-500">ترتیب</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-secondary-500">وضعیت</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-secondary-500">عملیات</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-secondary-100">
            <tr v-for="slider in sliders" :key="slider.id" class="hover:bg-secondary-50 transition">
              <td class="px-4 py-3">
                <div class="w-20 h-12 rounded-lg overflow-hidden bg-secondary-100 flex items-center justify-center">
                  <img
                    v-if="slider.image"
                    :src="slider.image"
                    :alt="slider.title"
                    class="w-full h-full object-cover"
                  />
                  <ImageIcon v-else class="w-6 h-6 text-secondary-300" />
                </div>
              </td>
              <td class="px-4 py-3">
                <p class="font-medium text-secondary-800 text-sm">{{ slider.title }}</p>
              </td>
              <td class="px-4 py-3 text-sm text-secondary-500">
                {{ slider.subtitle || '—' }}
              </td>
              <td class="px-4 py-3 text-sm text-secondary-500">
                {{ slider.button_text || '—' }}
              </td>
              <td class="px-4 py-3 text-sm text-secondary-600 text-center">
                {{ slider.sort_order }}
              </td>
              <td class="px-4 py-3">
                <button @click="toggleActive(slider)" :title="slider.is_active ? 'غیرفعال کردن' : 'فعال کردن'">
                  <span :class="['inline-flex items-center gap-1 px-2 py-1 text-xs rounded-full font-medium', slider.is_active ? 'bg-green-100 text-green-700' : 'bg-secondary-100 text-secondary-500']">
                    <Eye v-if="slider.is_active" class="w-3 h-3" />
                    <EyeOff v-else class="w-3 h-3" />
                    {{ slider.is_active ? 'فعال' : 'غیرفعال' }}
                  </span>
                </button>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-1">
                  <button
                    @click="openEdit(slider)"
                    class="p-2 hover:bg-primary-50 rounded-lg transition"
                    title="ویرایش"
                  >
                    <Edit2 class="w-4 h-4 text-primary-600" />
                  </button>
                  <button
                    @click="handleDelete(slider)"
                    class="p-2 hover:bg-red-50 rounded-lg transition"
                    title="حذف"
                  >
                    <Trash2 class="w-4 h-4 text-red-500" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal -->
    <transition name="modal">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Overlay -->
        <div class="absolute inset-0 bg-black/50" @click="closeModal"></div>

        <!-- Dialog -->
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
          <!-- Modal Header -->
          <div class="sticky top-0 bg-white flex items-center justify-between px-6 py-4 border-b border-secondary-100 z-10">
            <h2 class="text-lg font-bold text-secondary-800">
              {{ editingSlider ? 'ویرایش اسلایدر' : 'اسلایدر جدید' }}
            </h2>
            <button @click="closeModal" class="p-2 hover:bg-secondary-100 rounded-lg transition">
              <X class="w-5 h-5 text-secondary-600" />
            </button>
          </div>

          <!-- Modal Body -->
          <form @submit.prevent="handleSubmit" class="p-6 space-y-5">

            <!-- Image Upload -->
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-2">تصویر اسلایدر</label>
              <div
                v-if="imagePreview"
                class="relative rounded-xl overflow-hidden bg-secondary-100 aspect-[16/5]"
              >
                <img :src="imagePreview" alt="پیش‌نمایش" class="w-full h-full object-cover" />
                <button
                  type="button"
                  @click="removeImage"
                  class="absolute top-2 left-2 w-8 h-8 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 transition"
                >
                  <X class="w-4 h-4" />
                </button>
              </div>
              <label
                v-else
                class="flex flex-col items-center justify-center gap-2 border-2 border-dashed border-secondary-300 rounded-xl aspect-[16/5] cursor-pointer hover:border-primary-400 hover:bg-primary-50 transition"
              >
                <ImageIcon class="w-10 h-10 text-secondary-300" />
                <span class="text-sm text-secondary-500">کلیک کنید یا فایل را اینجا رها کنید</span>
                <span class="text-xs text-secondary-400">JPG, PNG, WebP – حداکثر ۴ مگابایت</span>
                <input type="file" accept="image/*" class="hidden" @change="onImageChange" />
              </label>
            </div>

            <!-- Title -->
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">
                عنوان <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.title"
                type="text"
                placeholder="مثال: جشنواره تابستانه"
                class="w-full px-4 py-2.5 border border-secondary-200 rounded-lg focus:border-primary-400 focus:ring-2 focus:ring-primary-100 outline-none transition"
              />
            </div>

            <!-- Subtitle -->
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">زیرعنوان</label>
              <input
                v-model="form.subtitle"
                type="text"
                placeholder="مثال: تا ۷۰٪ تخفیف"
                class="w-full px-4 py-2.5 border border-secondary-200 rounded-lg focus:border-primary-400 focus:ring-2 focus:ring-primary-100 outline-none transition"
              />
            </div>

            <!-- Link -->
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">لینک اسلایدر</label>
              <input
                v-model="form.link"
                type="text"
                placeholder="مثال: /products"
                class="w-full px-4 py-2.5 border border-secondary-200 rounded-lg focus:border-primary-400 focus:ring-2 focus:ring-primary-100 outline-none transition"
                dir="ltr"
              />
            </div>

            <!-- Button Text + Button Link -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-secondary-700 mb-1">متن دکمه</label>
                <input
                  v-model="form.button_text"
                  type="text"
                  placeholder="مثال: مشاهده تخفیف‌ها"
                  class="w-full px-4 py-2.5 border border-secondary-200 rounded-lg focus:border-primary-400 focus:ring-2 focus:ring-primary-100 outline-none transition"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-secondary-700 mb-1">لینک دکمه</label>
                <input
                  v-model="form.button_link"
                  type="text"
                  placeholder="مثال: /products?discount=true"
                  class="w-full px-4 py-2.5 border border-secondary-200 rounded-lg focus:border-primary-400 focus:ring-2 focus:ring-primary-100 outline-none transition"
                  dir="ltr"
                />
              </div>
            </div>

            <!-- Sort Order + Active -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-secondary-700 mb-1">ترتیب نمایش</label>
                <input
                  v-model.number="form.sort_order"
                  type="number"
                  min="0"
                  class="w-full px-4 py-2.5 border border-secondary-200 rounded-lg focus:border-primary-400 focus:ring-2 focus:ring-primary-100 outline-none transition"
                />
              </div>
              <div class="flex items-end pb-1">
                <label class="flex items-center gap-3 cursor-pointer">
                  <div
                    @click="form.is_active = !form.is_active"
                    :class="['relative w-12 h-6 rounded-full transition', form.is_active ? 'bg-primary-500' : 'bg-secondary-300']"
                  >
                    <span :class="['absolute top-1 w-4 h-4 bg-white rounded-full shadow transition-all', form.is_active ? 'left-7' : 'left-1']"></span>
                  </div>
                  <span class="text-sm font-medium text-secondary-700">
                    {{ form.is_active ? 'فعال' : 'غیرفعال' }}
                  </span>
                </label>
              </div>
            </div>

            <!-- Error in modal -->
            <div v-if="errorMsg" class="flex items-center gap-2 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
              <AlertCircle class="w-4 h-4 flex-shrink-0" />
              {{ errorMsg }}
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 pt-2 border-t border-secondary-100">
              <button
                type="button"
                @click="closeModal"
                class="px-5 py-2.5 border border-secondary-200 rounded-lg text-secondary-700 hover:bg-secondary-50 transition"
              >
                انصراف
              </button>
              <button
                type="submit"
                :disabled="isSaving"
                class="flex items-center gap-2 px-5 py-2.5 bg-primary-500 hover:bg-primary-600 disabled:opacity-60 text-white rounded-lg transition"
              >
                <span v-if="isSaving" class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full"></span>
                {{ editingSlider ? 'ذخیره تغییرات' : 'ایجاد اسلایدر' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
