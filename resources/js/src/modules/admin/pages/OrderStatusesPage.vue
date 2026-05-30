<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { Plus, Pencil, Trash2, X, Save } from 'lucide-vue-next'
import { orderStatusService } from '../services/paymentMethodService'
import type { OrderStatus } from '../types/payment'

const statuses = ref<OrderStatus[]>([])
const isLoading = ref(true)
const isSaving = ref(false)
const successMsg = ref('')
const errorMsg = ref('')
const showModal = ref(false)
const editingId = ref<number | null>(null)

const EMPTY_FORM = (): Omit<OrderStatus, 'id'> => ({
  key: '',
  label: '',
  color: '#6b7280',
  is_cancellation: false,
  is_completion: false,
  sort_order: 0,
  notify_customer: false,
  notification_message: null,
})

const form = reactive<Omit<OrderStatus, 'id'>>(EMPTY_FORM())

const showMsg = (success: boolean, msg: string) => {
  if (success) { successMsg.value = msg; setTimeout(() => (successMsg.value = ''), 3000) }
  else { errorMsg.value = msg; setTimeout(() => (errorMsg.value = ''), 4000) }
}

onMounted(async () => {
  try {
    statuses.value = await orderStatusService.getAll()
  } catch {
    showMsg(false, 'خطا در بارگذاری')
  } finally {
    isLoading.value = false
  }
})

const openCreate = () => {
  editingId.value = null
  Object.assign(form, EMPTY_FORM())
  showModal.value = true
}

const openEdit = (status: OrderStatus) => {
  editingId.value = status.id
  Object.assign(form, { ...status })
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
}

const handleSubmit = async () => {
  if (!form.key.trim()) { showMsg(false, 'کلید وضعیت الزامی است'); return }
  if (!form.label.trim()) { showMsg(false, 'عنوان وضعیت الزامی است'); return }
  isSaving.value = true
  try {
    if (editingId.value) {
      const updated = await orderStatusService.update(editingId.value, { ...form })
      const idx = statuses.value.findIndex(s => s.id === editingId.value)
      if (idx !== -1) statuses.value[idx] = updated
      showMsg(true, 'وضعیت به‌روزرسانی شد')
    } else {
      const created = await orderStatusService.create({ ...form })
      statuses.value.push(created)
      showMsg(true, 'وضعیت جدید ایجاد شد')
    }
    closeModal()
  } catch (e: unknown) {
    const msg = (e as { response?: { data?: { message?: string } } })?.response?.data?.message
    showMsg(false, msg || 'خطا در ذخیره')
  } finally {
    isSaving.value = false
  }
}

const handleDelete = async (status: OrderStatus) => {
  if (!confirm(`آیا از حذف وضعیت "${status.label}" اطمینان دارید؟`)) return
  try {
    await orderStatusService.destroy(status.id)
    statuses.value = statuses.value.filter(s => s.id !== status.id)
    showMsg(true, 'وضعیت حذف شد')
  } catch {
    showMsg(false, 'خطا در حذف')
  }
}
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-secondary-800">وضعیت‌های سفارش</h1>
      <button @click="openCreate"
        class="flex items-center gap-2 px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg text-sm transition">
        <Plus class="w-4 h-4" /> وضعیت جدید
      </button>
    </div>

    <transition name="fade">
      <div v-if="successMsg" class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">✓ {{ successMsg }}</div>
    </transition>
    <transition name="fade">
      <div v-if="errorMsg" class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">✕ {{ errorMsg }}</div>
    </transition>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div v-if="isLoading" class="p-8">
        <div v-for="i in 5" :key="i" class="animate-pulse h-12 bg-secondary-100 rounded-lg mb-3"></div>
      </div>

      <table v-else class="w-full text-sm">
        <thead class="bg-secondary-50 border-b border-secondary-100">
          <tr>
            <th class="text-right py-3 px-4 font-medium text-secondary-600">وضعیت</th>
            <th class="text-right py-3 px-4 font-medium text-secondary-600">کلید</th>
            <th class="text-center py-3 px-4 font-medium text-secondary-600">رنگ</th>
            <th class="text-center py-3 px-4 font-medium text-secondary-600">لغو</th>
            <th class="text-center py-3 px-4 font-medium text-secondary-600">تکمیل</th>
            <th class="text-center py-3 px-4 font-medium text-secondary-600">اعلان</th>
            <th class="text-center py-3 px-4 font-medium text-secondary-600">ترتیب</th>
            <th class="text-center py-3 px-4 font-medium text-secondary-600">عملیات</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-secondary-50">
          <tr v-for="status in statuses" :key="status.id" class="hover:bg-secondary-25 transition">
            <td class="py-3 px-4">
              <span class="inline-flex items-center gap-2 font-medium text-secondary-800">
                <span class="inline-block w-3 h-3 rounded-full flex-shrink-0" :style="{ backgroundColor: status.color }"></span>
                {{ status.label }}
              </span>
            </td>
            <td class="py-3 px-4"><code class="text-xs bg-secondary-100 px-2 py-0.5 rounded">{{ status.key }}</code></td>
            <td class="py-3 px-4 text-center">
              <span class="inline-block w-6 h-6 rounded border border-secondary-200" :style="{ backgroundColor: status.color }"></span>
            </td>
            <td class="py-3 px-4 text-center">
              <span :class="['text-xs px-2 py-0.5 rounded-full', status.is_cancellation ? 'bg-red-100 text-red-700' : 'bg-secondary-100 text-secondary-400']">
                {{ status.is_cancellation ? 'بله' : 'خیر' }}
              </span>
            </td>
            <td class="py-3 px-4 text-center">
              <span :class="['text-xs px-2 py-0.5 rounded-full', status.is_completion ? 'bg-green-100 text-green-700' : 'bg-secondary-100 text-secondary-400']">
                {{ status.is_completion ? 'بله' : 'خیر' }}
              </span>
            </td>
            <td class="py-3 px-4 text-center">
              <span :class="['text-xs px-2 py-0.5 rounded-full', status.notify_customer ? 'bg-blue-100 text-blue-700' : 'bg-secondary-100 text-secondary-400']">
                {{ status.notify_customer ? 'فعال' : 'غیرفعال' }}
              </span>
            </td>
            <td class="py-3 px-4 text-center text-secondary-500">{{ status.sort_order }}</td>
            <td class="py-3 px-4">
              <div class="flex items-center justify-center gap-2">
                <button @click="openEdit(status)" class="p-1.5 text-secondary-500 hover:text-primary-600 hover:bg-primary-50 rounded transition">
                  <Pencil class="w-4 h-4" />
                </button>
                <button @click="handleDelete(status)" class="p-1.5 text-secondary-500 hover:text-red-600 hover:bg-red-50 rounded transition">
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <transition name="fade">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="closeModal">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg">
          <div class="flex items-center justify-between px-6 py-4 border-b border-secondary-100">
            <h2 class="font-semibold text-secondary-800">{{ editingId ? 'ویرایش وضعیت' : 'وضعیت جدید' }}</h2>
            <button @click="closeModal" class="p-1 hover:bg-secondary-100 rounded"><X class="w-5 h-5 text-secondary-500" /></button>
          </div>

          <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-secondary-700 mb-1">کلید (key) <span class="text-red-500">*</span></label>
                <input v-model="form.key" :disabled="!!editingId" type="text" dir="ltr" placeholder="pending_confirmation"
                  class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 disabled:bg-secondary-50 text-sm" required />
                <p class="mt-0.5 text-xs text-secondary-400">فقط حروف کوچک و زیرخط</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-secondary-700 mb-1">عنوان فارسی <span class="text-red-500">*</span></label>
                <input v-model="form.label" type="text" placeholder="در انتظار تایید"
                  class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 text-sm" required />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-secondary-700 mb-1">رنگ</label>
                <div class="flex items-center gap-3">
                  <input v-model="form.color" type="color" class="w-10 h-10 rounded border border-secondary-200 cursor-pointer p-0.5" />
                  <input v-model="form.color" type="text" dir="ltr" class="flex-1 h-10 px-3 rounded-lg border border-secondary-200 text-sm font-mono" />
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-secondary-700 mb-1">ترتیب نمایش</label>
                <input v-model.number="form.sort_order" type="number" min="0"
                  class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 text-sm" />
              </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
              <label class="flex items-center gap-2 cursor-pointer">
                <input v-model="form.is_cancellation" type="checkbox" class="w-4 h-4 rounded border-secondary-300 text-primary-600" />
                <span class="text-sm text-secondary-700">وضعیت لغو</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input v-model="form.is_completion" type="checkbox" class="w-4 h-4 rounded border-secondary-300 text-primary-600" />
                <span class="text-sm text-secondary-700">وضعیت تکمیل</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input v-model="form.notify_customer" type="checkbox" class="w-4 h-4 rounded border-secondary-300 text-primary-600" />
                <span class="text-sm text-secondary-700">اعلان به مشتری</span>
              </label>
            </div>

            <div v-if="form.notify_customer">
              <label class="block text-sm font-medium text-secondary-700 mb-1">متن پیام اعلان</label>
              <textarea v-model="form.notification_message" rows="2"
                class="w-full px-4 py-2 rounded-lg border border-secondary-200 focus:border-primary-500 text-sm resize-none"></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-2">
              <button type="button" @click="closeModal"
                class="px-4 py-2 border border-secondary-200 text-secondary-600 rounded-lg text-sm hover:bg-secondary-50 transition">
                انصراف
              </button>
              <button type="submit" :disabled="isSaving"
                class="flex items-center gap-2 px-4 py-2 bg-primary-500 hover:bg-primary-600 disabled:opacity-60 text-white rounded-lg text-sm transition">
                <Save class="w-4 h-4" />
                {{ isSaving ? 'در حال ذخیره...' : 'ذخیره' }}
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
</style>
