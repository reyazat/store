<script setup lang="ts">
import { X, Upload, Trash2, ChevronDown, Loader2 } from 'lucide-vue-next'
import type { AdminCategoryDetail } from '../services/categoryService'
import type { useCategoryForm } from '../composables/useCategoryForm'

type FormState = ReturnType<typeof useCategoryForm>

interface Props {
  state: FormState
}

const props = defineProps<Props>()

const emit = defineEmits<{
  saved: [category: AdminCategoryDetail]
}>()

async function handleSubmit(): Promise<void> {
  const result = await props.state.submit()
  if (result) {
    emit('saved', result)
  }
}
</script>

<template>
  <!-- Overlay -->
  <Transition name="fade">
    <div
      v-if="state.isOpen.value"
      class="fixed inset-0 bg-black/40 z-40"
      @click="!state.isSubmitting.value && state.close()"
    />
  </Transition>

  <!-- Drawer -->
  <Transition name="slide-left">
    <aside
      v-if="state.isOpen.value"
      class="fixed top-0 right-0 h-full w-full max-w-xl bg-white shadow-2xl z-50 flex flex-col"
      role="dialog"
      :aria-label="state.isEditing.value ? 'ویرایش دسته‌بندی' : 'افزودن دسته‌بندی جدید'"
    >
      <!-- Header -->
      <div class="flex items-center justify-between px-6 py-4 border-b border-secondary-100 flex-shrink-0">
        <div>
          <h2 class="text-lg font-bold text-secondary-800">
            {{ state.isEditing.value ? 'ویرایش دسته‌بندی' : 'افزودن دسته‌بندی جدید' }}
          </h2>
          <p class="text-xs text-secondary-400 mt-0.5">
            {{ state.isEditing.value ? 'اطلاعات دسته‌بندی را ویرایش کنید' : 'اطلاعات دسته‌بندی جدید را وارد کنید' }}
          </p>
        </div>
        <button
          class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-secondary-100 transition"
          aria-label="بستن"
          @click="state.close"
        >
          <X class="w-5 h-5 text-secondary-500" />
        </button>
      </div>

      <!-- Body -->
      <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">

        <!-- Image upload -->
        <div>
          <label class="block text-sm font-medium text-secondary-700 mb-2">تصویر دسته‌بندی</label>
          <div
            v-if="state.imagePreview.value"
            class="relative w-full aspect-video rounded-xl overflow-hidden border border-secondary-200 group"
          >
            <img
              :src="state.imagePreview.value"
              alt="پیش‌نمایش تصویر"
              class="w-full h-full object-cover"
            />
            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-3">
              <label
                class="cursor-pointer flex items-center gap-1.5 px-3 py-1.5 bg-white/90 rounded-lg text-secondary-700 text-xs font-medium hover:bg-white transition"
                role="button"
                tabindex="0"
              >
                <Upload class="w-4 h-4" />
                تغییر تصویر
                <input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="state.onImageChange" />
              </label>
              <button
                type="button"
                class="flex items-center gap-1.5 px-3 py-1.5 bg-red-500/90 rounded-lg text-white text-xs font-medium hover:bg-red-600 transition"
                @click="state.clearImage"
              >
                <Trash2 class="w-4 h-4" />
                حذف
              </button>
            </div>
          </div>

          <label
            v-else
            class="flex flex-col items-center justify-center w-full h-36 border-2 border-dashed border-secondary-200 rounded-xl cursor-pointer hover:border-primary-400 hover:bg-primary-50 transition"
            role="button"
            tabindex="0"
          >
            <Upload class="w-8 h-8 text-secondary-300 mb-2" />
            <span class="text-sm text-secondary-500">برای آپلود کلیک کنید</span>
            <span class="text-xs text-secondary-400 mt-1">JPG, PNG, WEBP — حداکثر ۲ مگابایت</span>
            <input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="state.onImageChange" />
          </label>

          <p v-if="state.errors.image_file" class="mt-1.5 text-xs text-red-500">{{ state.errors.image_file }}</p>
        </div>

        <!-- Name + Slug -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="cat-name" class="block text-sm font-medium text-secondary-700 mb-1.5">
              نام دسته‌بندی <span class="text-red-500">*</span>
            </label>
            <input
              id="cat-name"
              v-model="state.form.name"
              type="text"
              placeholder="مثال: الکترونیک"
              :class="['w-full h-10 px-3 rounded-lg border text-sm focus:ring-1 focus:ring-primary-500 transition',
                state.errors.name ? 'border-red-400 focus:border-red-400' : 'border-secondary-200 focus:border-primary-500']"
            />
            <p v-if="state.errors.name" class="mt-1 text-xs text-red-500">{{ state.errors.name }}</p>
          </div>

          <div>
            <label for="cat-slug" class="block text-sm font-medium text-secondary-700 mb-1.5">Slug <span class="text-red-500">*</span></label>
            <input
              id="cat-slug"
              v-model="state.form.slug"
              type="text"
              dir="ltr"
              placeholder="electronics"
              :class="['w-full h-10 px-3 rounded-lg border text-sm font-mono focus:ring-1 focus:ring-primary-500 transition',
                state.errors.slug ? 'border-red-400 focus:border-red-400' : 'border-secondary-200 focus:border-primary-500']"
            />
            <p v-if="state.errors.slug" class="mt-1 text-xs text-red-500">{{ state.errors.slug }}</p>
          </div>
        </div>

        <!-- Parent + Icon -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="cat-parent" class="block text-sm font-medium text-secondary-700 mb-1.5">دسته والد</label>
            <div class="relative">
              <select
                id="cat-parent"
                v-model="state.form.parent_id"
                class="w-full h-10 px-3 pr-8 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm appearance-none text-secondary-700"
              >
                <option :value="null">— دسته اصلی —</option>
                <option v-for="p in state.parentOptions.value" :key="p.id" :value="p.id">
                  {{ p.name }}
                </option>
              </select>
              <ChevronDown class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-secondary-400 pointer-events-none" />
            </div>
            <p v-if="state.errors.parent_id" class="mt-1 text-xs text-red-500">{{ state.errors.parent_id }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-secondary-700 mb-1.5">آیکون (اختیاری)</label>
            <div class="flex items-center gap-2">
              <input
                v-model="state.form.icon"
                type="text"
                placeholder="📱"
                maxlength="10"
                class="w-20 h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-center text-lg"
              />
              <div class="flex flex-wrap gap-1">
                <button
                  v-for="ic in state.commonIcons"
                  :key="ic"
                  type="button"
                  :class="['w-8 h-8 rounded-md text-base flex items-center justify-center transition',
                    state.form.icon === ic ? 'bg-primary-100 ring-1 ring-primary-400' : 'hover:bg-secondary-100']"
                  :title="ic"
                  @click="state.form.icon = state.form.icon === ic ? '' : ic"
                >
                  {{ ic }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Description -->
        <div>
          <label for="cat-desc" class="block text-sm font-medium text-secondary-700 mb-1.5">توضیحات</label>
          <textarea
            id="cat-desc"
            v-model="state.form.description"
            rows="3"
            placeholder="توضیح مختصری درباره این دسته‌بندی..."
            class="w-full px-3 py-2 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm resize-none"
          />
        </div>

        <!-- Status + Sorted -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="cat-status" class="block text-sm font-medium text-secondary-700 mb-1.5">وضعیت</label>
            <div class="relative">
              <select
                id="cat-status"
                v-model="state.form.status"
                class="w-full h-10 px-3 pr-8 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm appearance-none text-secondary-700"
              >
                <option :value="1">فعال</option>
                <option :value="0">غیرفعال</option>
              </select>
              <ChevronDown class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-secondary-400 pointer-events-none" />
            </div>
          </div>

          <div>
            <label for="cat-sorted" class="block text-sm font-medium text-secondary-700 mb-1.5">ترتیب نمایش</label>
            <input
              id="cat-sorted"
              v-model.number="state.form.sorted"
              type="number"
              min="0"
              placeholder="0"
              class="w-full h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm"
            />
          </div>
        </div>

        <!-- SEO section -->
        <div class="border border-secondary-100 rounded-xl p-4 space-y-3">
          <h3 class="text-sm font-semibold text-secondary-700">اطلاعات SEO</h3>

          <div>
            <label for="cat-meta-title" class="block text-xs font-medium text-secondary-600 mb-1">عنوان متا</label>
            <input
              id="cat-meta-title"
              v-model="state.form.meta_title"
              type="text"
              placeholder="عنوان برای موتورهای جستجو"
              maxlength="255"
              class="w-full h-9 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm"
            />
          </div>

          <div>
            <label for="cat-meta-desc" class="block text-xs font-medium text-secondary-600 mb-1">توضیح متا</label>
            <textarea
              id="cat-meta-desc"
              v-model="state.form.meta_description"
              rows="2"
              placeholder="توضیح برای موتورهای جستجو..."
              class="w-full px-3 py-2 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm resize-none"
            />
          </div>
        </div>

      </div>

      <!-- Footer -->
      <div class="flex items-center justify-between gap-3 px-6 py-4 border-t border-secondary-100 flex-shrink-0">
        <button
          type="button"
          class="px-4 py-2 rounded-lg border border-secondary-200 text-secondary-700 text-sm hover:bg-secondary-50 transition"
          @click="state.close"
        >
          انصراف
        </button>
        <button
          type="button"
          :disabled="state.isSubmitting.value"
          class="flex items-center gap-2 px-5 py-2 bg-primary-500 hover:bg-primary-600 disabled:bg-primary-300 disabled:cursor-not-allowed text-white rounded-lg text-sm font-medium transition"
          @click="handleSubmit"
        >
          <Loader2 v-if="state.isSubmitting.value" class="w-4 h-4 animate-spin" />
          {{ state.isEditing.value ? 'ذخیره تغییرات' : 'ثبت دسته‌بندی' }}
        </button>
      </div>
    </aside>
  </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from,
.fade-leave-to { opacity: 0; }

.slide-left-enter-active,
.slide-left-leave-active { transition: transform 0.25s ease; }
.slide-left-enter-from,
.slide-left-leave-to { transform: translateX(100%); }
</style>
