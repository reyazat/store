<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { Plus, Edit2, Trash2, Eye, EyeOff, AlertCircle, Check, X } from 'lucide-vue-next'
import { faqService } from '../services/faqService'
import type { Faq, FaqFormData } from '../types/faq'

const faqs = ref<Faq[]>([])
const isLoading = ref(true)
const isSaving = ref(false)
const showModal = ref(false)
const editingFaq = ref<Faq | null>(null)
const errorMsg = ref<string | null>(null)
const successMsg = ref<string | null>(null)

const defaultForm = (): FaqFormData => ({
  question: '',
  answer: '',
  category: '',
  sort_order: 0,
  is_active: true,
})

const form = reactive<FaqFormData>(defaultForm())

onMounted(loadFaqs)

async function loadFaqs() {
  isLoading.value = true
  try {
    faqs.value = await faqService.getAll()
  } catch {
    showError('خطا در دریافت سوالات متداول')
  } finally {
    isLoading.value = false
  }
}

function openCreate() {
  editingFaq.value = null
  Object.assign(form, defaultForm())
  showModal.value = true
}

function openEdit(faq: Faq) {
  editingFaq.value = faq
  Object.assign(form, {
    question: faq.question,
    answer: faq.answer,
    category: faq.category ?? '',
    sort_order: faq.sort_order,
    is_active: faq.is_active,
  })
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  editingFaq.value = null
}

async function handleSubmit() {
  if (!form.question.trim()) {
    showError('سوال الزامی است')
    return
  }
  if (!form.answer.trim()) {
    showError('پاسخ الزامی است')
    return
  }
  isSaving.value = true
  errorMsg.value = null
  try {
    if (editingFaq.value) {
      const updated = await faqService.update(editingFaq.value.id, form)
      const idx = faqs.value.findIndex((f) => f.id === editingFaq.value!.id)
      if (idx !== -1) faqs.value[idx] = updated
      showSuccess('سوال ویرایش شد')
    } else {
      const created = await faqService.create(form)
      faqs.value.push(created)
      showSuccess('سوال ایجاد شد')
    }
    closeModal()
  } catch {
    showError('خطا در ذخیره سوال')
  } finally {
    isSaving.value = false
  }
}

async function toggleActive(faq: Faq) {
  try {
    await faqService.update(faq.id, {
      question: faq.question,
      answer: faq.answer,
      category: faq.category ?? '',
      sort_order: faq.sort_order,
      is_active: !faq.is_active,
    })
    faq.is_active = !faq.is_active
    showSuccess('وضعیت تغییر کرد')
  } catch {
    showError('خطا در تغییر وضعیت')
  }
}

async function handleDelete(faq: Faq) {
  if (!confirm(`آیا از حذف این سوال اطمینان دارید؟`)) return
  try {
    await faqService.delete(faq.id)
    faqs.value = faqs.value.filter((f) => f.id !== faq.id)
    showSuccess('سوال حذف شد')
  } catch {
    showError('خطا در حذف سوال')
  }
}

function showError(msg: string) {
  errorMsg.value = msg
  setTimeout(() => (errorMsg.value = null), 5000)
}

function showSuccess(msg: string) {
  successMsg.value = msg
  setTimeout(() => (successMsg.value = null), 3000)
}
</script>

<template>
  <div class="p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-secondary-800">سوالات متداول (FAQ)</h1>
      <button
        @click="openCreate"
        class="flex items-center gap-2 px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition"
      >
        <Plus class="w-5 h-5" />
        افزودن سوال
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

    <!-- Empty -->
    <div v-else-if="faqs.length === 0" class="text-center py-12 bg-white rounded-xl">
      <p class="text-secondary-500 mb-4">هیچ سوالی تعریف نشده است</p>
      <button
        @click="openCreate"
        class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition"
      >
        افزودن اولین سوال
      </button>
    </div>

    <!-- Table -->
    <div v-else class="bg-white rounded-xl shadow-sm overflow-hidden">
      <table class="w-full">
        <thead class="bg-secondary-50">
          <tr>
            <th class="px-4 py-3 text-right text-sm font-medium text-secondary-600">ترتیب</th>
            <th class="px-4 py-3 text-right text-sm font-medium text-secondary-600">دسته‌بندی</th>
            <th class="px-4 py-3 text-right text-sm font-medium text-secondary-600">سوال</th>
            <th class="px-4 py-3 text-right text-sm font-medium text-secondary-600 hidden md:table-cell">پاسخ (خلاصه)</th>
            <th class="px-4 py-3 text-right text-sm font-medium text-secondary-600">وضعیت</th>
            <th class="px-4 py-3 text-right text-sm font-medium text-secondary-600">عملیات</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-secondary-100">
          <tr v-for="faq in faqs" :key="faq.id" class="hover:bg-secondary-50">
            <td class="px-4 py-3 text-sm text-secondary-500">{{ faq.sort_order }}</td>
            <td class="px-4 py-3 text-sm text-secondary-500">
              <span v-if="faq.category" class="px-2 py-0.5 bg-primary-50 text-primary-700 rounded-full text-xs">
                {{ faq.category }}
              </span>
              <span v-else class="text-secondary-400">—</span>
            </td>
            <td class="px-4 py-3 font-medium text-secondary-800 max-w-xs">
              <span class="line-clamp-2">{{ faq.question }}</span>
            </td>
            <td class="px-4 py-3 text-sm text-secondary-500 hidden md:table-cell max-w-xs">
              <span class="line-clamp-2">{{ faq.answer }}</span>
            </td>
            <td class="px-4 py-3">
              <button
                @click="toggleActive(faq)"
                :class="[
                  'p-2 rounded-lg transition',
                  faq.is_active ? 'text-emerald-600 hover:bg-emerald-50' : 'text-secondary-400 hover:bg-secondary-100',
                ]"
              >
                <Eye v-if="faq.is_active" class="w-5 h-5" />
                <EyeOff v-else class="w-5 h-5" />
              </button>
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <button
                  @click="openEdit(faq)"
                  class="p-2 hover:bg-primary-50 text-primary-600 rounded-lg transition"
                >
                  <Edit2 class="w-4 h-4" />
                </button>
                <button
                  @click="handleDelete(faq)"
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
      <div class="bg-white rounded-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-secondary-100 flex items-center justify-between">
          <h2 class="text-xl font-bold text-secondary-800">
            {{ editingFaq ? 'ویرایش سوال' : 'افزودن سوال' }}
          </h2>
          <button @click="closeModal" class="p-2 hover:bg-secondary-100 rounded-lg transition">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-secondary-700 mb-1" for="faq-question">سوال</label>
            <input
              id="faq-question"
              v-model="form.question"
              type="text"
              class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              placeholder="سوال را بنویسید..."
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-secondary-700 mb-1" for="faq-answer">پاسخ</label>
            <textarea
              id="faq-answer"
              v-model="form.answer"
              rows="5"
              class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-y"
              placeholder="پاسخ را بنویسید..."
            ></textarea>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1" for="faq-category">دسته‌بندی (اختیاری)</label>
              <input
                id="faq-category"
                v-model="form.category"
                type="text"
                class="w-full px-4 py-2 border border-secondary-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                placeholder="مثلاً: سفارش، پرداخت، ارسال"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1" for="faq-sort">ترتیب نمایش</label>
              <input
                id="faq-sort"
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
              id="faq-active"
              class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
            />
            <label for="faq-active" class="text-sm text-secondary-700">فعال (نمایش در سایت)</label>
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
              {{ isSaving ? 'در حال ذخیره...' : editingFaq ? 'ذخیره تغییرات' : 'ایجاد سوال' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
