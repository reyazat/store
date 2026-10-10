<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { Plus, Edit, Trash2, Search, X, Save, AlertCircle, Check, Ticket, ChevronRight, ChevronLeft } from 'lucide-vue-next'
import api from '@shared/utils/api'
import { formatPrice, formatDate } from '@shared/utils/format'

// ─── Types ────────────────────────────────────────────────────────────────────

interface Coupon {
  id: number
  code: string
  name: string | null
  description: string | null
  type: 'percentage' | 'fixed'
  value: number
  min_purchase: number | null
  max_discount: number | null
  usage_limit: number | null
  usage_per_user: number | null
  used_count: number
  status: boolean
  is_valid: boolean
  starts_at: string | null
  expires_at: string | null
}

interface CouponFormData {
  code: string
  name: string | null
  description: string | null
  type: 'percentage' | 'fixed'
  value: number
  min_purchase: number | null
  max_discount: number | null
  usage_limit: number | null
  usage_per_user: number | null
  status: boolean
  starts_at: string | null
  expires_at: string | null
}

interface PaginationMeta {
  current_page: number
  last_page: number
  total: number
  per_page: number
}

// ─── State ────────────────────────────────────────────────────────────────────

const coupons   = ref<Coupon[]>([])
const isLoading = ref(true)
const errorMsg  = ref('')
const meta      = ref<PaginationMeta>({ current_page: 1, last_page: 1, total: 0, per_page: 20 })

const filters = reactive<{ search: string; status: string; per_page: number }>({
  search:   '',
  status:   '',
  per_page: 20,
})

const showModal  = ref(false)
const isSaving   = ref(false)
const isDeleting = ref<number | null>(null)
const editingId  = ref<number | null>(null)
const toastMsg   = ref<{ text: string; type: 'success' | 'error' } | null>(null)

const showDeleteConfirm = ref(false)
const deletingCoupon    = ref<Coupon | null>(null)

// ─── Toast ────────────────────────────────────────────────────────────────────

let toastTimer: ReturnType<typeof setTimeout> | null = null

const showToast = (text: string, type: 'success' | 'error') => {
  if (toastTimer) clearTimeout(toastTimer)
  toastMsg.value = { text, type }
  toastTimer = setTimeout(() => (toastMsg.value = null), 3500)
}

// ─── Form ─────────────────────────────────────────────────────────────────────

const defaultForm = (): CouponFormData => ({
  code:           '',
  name:           null,
  description:    null,
  type:           'percentage',
  value:          0,
  min_purchase:   null,
  max_discount:   null,
  usage_limit:    null,
  usage_per_user: null,
  status:         true,
  starts_at:      null,
  expires_at:     null,
})

const form = reactive<CouponFormData>(defaultForm())

// ─── Data loading ─────────────────────────────────────────────────────────────

let searchTimer: ReturnType<typeof setTimeout> | null = null

const loadCoupons = async (page = 1) => {
  isLoading.value = true
  errorMsg.value  = ''
  try {
    const { data } = await api.get('/store/coupons', {
      params: {
        search:   filters.search   || undefined,
        status:   filters.status   || undefined,
        per_page: filters.per_page,
        page,
      },
    })
    coupons.value = data.data
    meta.value    = data.meta
  } catch {
    errorMsg.value = 'خطا در بارگذاری کوپن‌ها'
  } finally {
    isLoading.value = false
  }
}

const debouncedLoad = () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => loadCoupons(1), 400)
}

const goToPage = (page: number) => loadCoupons(page)

// ─── Modal ────────────────────────────────────────────────────────────────────

const openCreate = () => {
  editingId.value = null
  Object.assign(form, defaultForm())
  showModal.value = true
}

const openEdit = (coupon: Coupon) => {
  editingId.value = coupon.id
  Object.assign(form, {
    code:           coupon.code,
    name:           coupon.name,
    description:    coupon.description,
    type:           coupon.type,
    value:          coupon.value,
    min_purchase:   coupon.min_purchase,
    max_discount:   coupon.max_discount,
    usage_limit:    coupon.usage_limit,
    usage_per_user: coupon.usage_per_user,
    status:         coupon.status,
    starts_at:      coupon.starts_at,
    expires_at:     coupon.expires_at,
  })
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editingId.value = null
}

const handleSubmit = async () => {
  if (!form.code.trim()) { showToast('کد کوپن الزامی است', 'error'); return }
  if (!form.name?.trim()) { showToast('نام کوپن الزامی است', 'error'); return }
  if (!form.type)        { showToast('نوع کوپن الزامی است', 'error'); return }
  if (form.value <= 0)   { showToast('مقدار تخفیف باید بیشتر از صفر باشد', 'error'); return }

  isSaving.value = true
  try {
    if (editingId.value) {
      const { data } = await api.put(`/store/coupons/${editingId.value}`, form)
      const idx = coupons.value.findIndex(c => c.id === editingId.value)
      if (idx !== -1) coupons.value[idx] = data.data
      showToast('کوپن با موفقیت ویرایش شد', 'success')
    } else {
      const { data } = await api.post('/store/coupons', form)
      coupons.value.unshift(data.data)
      meta.value.total++
      showToast('کوپن با موفقیت ایجاد شد', 'success')
    }
    closeModal()
  } catch (e: any) {
    const validationErrors = e.response?.data?.errors as Record<string, string[]> | undefined
    const validationMessage = validationErrors ? Object.values(validationErrors).flat()[0] : null
    showToast(validationMessage || e.response?.data?.message || 'خطا در ذخیره کوپن', 'error')
  } finally {
    isSaving.value = false
  }
}

// ─── Delete ───────────────────────────────────────────────────────────────────

const openDelete = (coupon: Coupon) => {
  deletingCoupon.value    = coupon
  showDeleteConfirm.value = true
}

const closeDelete = () => {
  showDeleteConfirm.value = false
  deletingCoupon.value    = null
}

const confirmDelete = async () => {
  if (!deletingCoupon.value) return
  const target = deletingCoupon.value
  isDeleting.value = target.id
  try {
    await api.delete(`/store/coupons/${target.id}`)
    coupons.value = coupons.value.filter(c => c.id !== target.id)
    meta.value.total--
    showToast('کوپن با موفقیت حذف شد', 'success')
    closeDelete()
  } catch {
    showToast('خطا در حذف کوپن', 'error')
  } finally {
    isDeleting.value = null
  }
}

// ─── Display helpers ──────────────────────────────────────────────────────────

const couponDisplayValue = (coupon: Coupon): string =>
  coupon.type === 'percentage'
    ? `${coupon.value}٪`
    : `${formatPrice(coupon.value)} تومان`

onMounted(() => loadCoupons())
</script>

<template>
  <div dir="rtl">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-secondary-800">کوپن‌ها</h2>
        <p v-if="!isLoading" class="text-sm text-secondary-500 mt-1">{{ meta.total }} کوپن یافت شد</p>
      </div>
      <button
        class="flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium transition"
        @click="openCreate"
      >
        <Plus class="w-4 h-4" />
        ایجاد کوپن
      </button>
    </div>

    <!-- Toast -->
    <transition name="fade">
      <div
        v-if="toastMsg"
        :class="toastMsg.type === 'success'
          ? 'bg-green-50 border-green-200 text-green-700'
          : 'bg-red-50 border-red-200 text-red-700'"
        class="flex items-center gap-2 mb-4 px-4 py-3 border rounded-lg text-sm"
      >
        <Check v-if="toastMsg.type === 'success'" class="w-4 h-4 flex-shrink-0" />
        <AlertCircle v-else class="w-4 h-4 flex-shrink-0" />
        {{ toastMsg.text }}
      </div>
    </transition>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-4 mb-4 flex flex-wrap items-center gap-3">
      <div class="relative flex-1 min-w-48">
        <Search class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-secondary-400" />
        <input
          v-model="filters.search"
          type="text"
          placeholder="جستجو در کد یا نام کوپن..."
          class="w-full pr-9 pl-4 h-10 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm"
          @input="debouncedLoad"
        />
      </div>
      <select
        v-model="filters.status"
        class="h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 text-sm text-secondary-700"
        @change="loadCoupons(1)"
      >
        <option value="">همه وضعیت‌ها</option>
        <option value="active">فعال</option>
        <option value="inactive">غیرفعال</option>
      </select>
      <select
        v-model="filters.per_page"
        class="h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 text-sm text-secondary-700"
        @change="loadCoupons(1)"
      >
        <option :value="20">۲۰ در صفحه</option>
        <option :value="50">۵۰ در صفحه</option>
        <option :value="100">۱۰۰ در صفحه</option>
      </select>
    </div>

    <!-- Table card -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">

      <!-- Error state -->
      <div v-if="errorMsg" class="p-6 text-center">
        <p class="text-red-500 text-sm mb-3">{{ errorMsg }}</p>
        <button
          class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 transition"
          @click="loadCoupons(1)"
        >
          تلاش مجدد
        </button>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-secondary-50 border-b border-secondary-100">
            <tr>
              <th class="px-5 py-3 text-right text-xs font-medium text-secondary-500">کد کوپن</th>
              <th class="px-5 py-3 text-right text-xs font-medium text-secondary-500">نام</th>
              <th class="px-5 py-3 text-center text-xs font-medium text-secondary-500">نوع</th>
              <th class="px-5 py-3 text-center text-xs font-medium text-secondary-500">مقدار</th>
              <th class="px-5 py-3 text-center text-xs font-medium text-secondary-500">استفاده</th>
              <th class="px-5 py-3 text-center text-xs font-medium text-secondary-500">وضعیت</th>
              <th class="px-5 py-3 text-right text-xs font-medium text-secondary-500">تاریخ انقضا</th>
              <th class="px-5 py-3 text-center text-xs font-medium text-secondary-500">عملیات</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-secondary-100">

            <!-- Skeleton rows -->
            <template v-if="isLoading">
              <tr v-for="i in 6" :key="i">
                <td v-for="j in 8" :key="j" class="px-5 py-4">
                  <div
                    class="h-4 bg-secondary-200 rounded animate-pulse"
                    :class="j === 1 ? 'w-24' : 'w-16'"
                  />
                </td>
              </tr>
            </template>

            <!-- Empty state -->
            <tr v-else-if="coupons.length === 0">
              <td colspan="8" class="px-5 py-16 text-center">
                <Ticket class="w-10 h-10 text-secondary-300 mx-auto mb-2" />
                <p class="text-secondary-400 text-sm">هیچ کوپنی یافت نشد</p>
              </td>
            </tr>

            <!-- Data rows -->
            <tr
              v-else
              v-for="coupon in coupons"
              :key="coupon.id"
              class="hover:bg-secondary-50 transition"
            >
              <td class="px-5 py-4">
                <span class="font-mono font-semibold text-secondary-800 tracking-wide">{{ coupon.code }}</span>
              </td>
              <td class="px-5 py-4 text-secondary-600">{{ coupon.name || '—' }}</td>
              <td class="px-5 py-4 text-center">
                <span
                  :class="coupon.type === 'percentage'
                    ? 'bg-blue-100 text-blue-700'
                    : 'bg-orange-100 text-orange-700'"
                  class="text-xs font-medium px-2.5 py-0.5 rounded-full whitespace-nowrap"
                >
                  {{ coupon.type === 'percentage' ? 'درصد' : 'مبلغ ثابت' }}
                </span>
              </td>
              <td class="px-5 py-4 text-center font-medium text-secondary-800">
                {{ couponDisplayValue(coupon) }}
              </td>
              <td class="px-5 py-4 text-center text-secondary-600">
                {{ coupon.used_count }}
                <span v-if="coupon.usage_limit" class="text-secondary-400"> / {{ coupon.usage_limit }}</span>
                <span v-else class="text-secondary-400"> / ∞</span>
              </td>
              <td class="px-5 py-4 text-center">
                <span
                  :class="coupon.status
                    ? 'bg-green-100 text-green-700'
                    : 'bg-secondary-100 text-secondary-500'"
                  class="text-xs font-medium px-2.5 py-0.5 rounded-full"
                >
                  {{ coupon.status ? 'فعال' : 'غیرفعال' }}
                </span>
              </td>
              <td class="px-5 py-4 text-secondary-500">{{ formatDate(coupon.expires_at) }}</td>
              <td class="px-5 py-4">
                <div class="flex items-center justify-center gap-1">
                  <button
                    class="p-2 hover:bg-secondary-100 rounded-lg transition"
                    title="ویرایش"
                    @click="openEdit(coupon)"
                  >
                    <Edit class="w-4 h-4 text-secondary-600" />
                  </button>
                  <button
                    class="p-2 hover:bg-red-50 rounded-lg transition disabled:opacity-50"
                    title="حذف"
                    :disabled="isDeleting === coupon.id"
                    @click="openDelete(coupon)"
                  >
                    <Trash2 class="w-4 h-4 text-red-500" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div
        v-if="!isLoading && !errorMsg && meta.last_page > 1"
        class="flex items-center justify-between px-5 py-4 border-t border-secondary-100"
      >
        <p class="text-sm text-secondary-500">
          صفحه {{ meta.current_page }} از {{ meta.last_page }}
          <span class="text-secondary-400 mr-1">({{ meta.total }} کوپن)</span>
        </p>
        <div class="flex items-center gap-1">
          <button
            :disabled="meta.current_page === 1"
            class="p-2 rounded-lg hover:bg-secondary-100 disabled:opacity-40 disabled:cursor-not-allowed transition"
            @click="goToPage(meta.current_page - 1)"
          >
            <ChevronRight class="w-4 h-4" />
          </button>
          <template v-for="p in meta.last_page" :key="p">
            <button
              v-if="p === 1 || p === meta.last_page || Math.abs(p - meta.current_page) <= 1"
              :class="p === meta.current_page
                ? 'bg-primary-600 text-white'
                : 'hover:bg-secondary-100 text-secondary-700'"
              class="w-9 h-9 rounded-lg text-sm font-medium transition"
              @click="goToPage(p)"
            >
              {{ p }}
            </button>
            <span
              v-else-if="Math.abs(p - meta.current_page) === 2"
              class="w-9 h-9 flex items-center justify-center text-secondary-400 text-sm"
            >
              ...
            </span>
          </template>
          <button
            :disabled="meta.current_page === meta.last_page"
            class="p-2 rounded-lg hover:bg-secondary-100 disabled:opacity-40 disabled:cursor-not-allowed transition"
            @click="goToPage(meta.current_page + 1)"
          >
            <ChevronLeft class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Create / Edit Modal -->
    <Teleport to="body">
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        dir="rtl"
        @click.self="closeModal"
      >
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">

          <!-- Modal header -->
          <div class="flex items-center justify-between p-5 border-b border-secondary-100 sticky top-0 bg-white z-10">
            <h3 class="text-lg font-bold text-secondary-800">
              {{ editingId ? 'ویرایش کوپن' : 'ایجاد کوپن' }}
            </h3>
            <button
              type="button"
              class="p-1 hover:bg-secondary-100 rounded-lg transition"
              @click="closeModal"
            >
              <X class="w-5 h-5 text-secondary-500" />
            </button>
          </div>

          <!-- Modal form -->
          <form class="p-5 space-y-4" @submit.prevent="handleSubmit">

            <!-- کد کوپن + نام -->
            <div class="grid grid-cols-2 gap-4">
              <div class="col-span-2 sm:col-span-1">
                <label class="block text-sm font-medium text-secondary-700 mb-1">
                  کد کوپن <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.code"
                  type="text"
                  dir="ltr"
                  placeholder="مثال: SAVE20"
                  class="w-full h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 font-mono uppercase text-sm"
                  required
                />
              </div>
              <div class="col-span-2 sm:col-span-1">
                <label class="block text-sm font-medium text-secondary-700 mb-1">نام کوپن</label>
                <input
                  v-model="form.name"
                  type="text"
                  placeholder="اختیاری"
                  class="w-full h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm"
                />
              </div>
            </div>

            <!-- نوع + مقدار -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-secondary-700 mb-1">
                  نوع <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.type"
                  class="w-full h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm"
                  required
                >
                  <option value="percentage">درصد</option>
                  <option value="fixed">مبلغ ثابت (تومان)</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-secondary-700 mb-1">
                  مقدار <span class="text-red-500">*</span>
                </label>
                <input
                  v-model.number="form.value"
                  type="number"
                  min="0.01"
                  step="any"
                  class="w-full h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm"
                  required
                />
              </div>
            </div>

            <!-- حداقل سفارش + سقف تخفیف -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-secondary-700 mb-1">حداقل مبلغ سفارش</label>
                <input
                  v-model.number="form.min_purchase"
                  type="number"
                  min="0"
                  placeholder="اختیاری"
                  class="w-full h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-secondary-700 mb-1">سقف تخفیف (تومان)</label>
                <input
                  v-model.number="form.max_discount"
                  type="number"
                  min="0"
                  placeholder="اختیاری"
                  class="w-full h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm"
                />
              </div>
            </div>

            <!-- تاریخ شروع + انقضا -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-secondary-700 mb-1">تاریخ شروع</label>
                <input
                  v-model="form.starts_at"
                  type="datetime-local"
                  dir="ltr"
                  class="w-full h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-secondary-700 mb-1">تاریخ انقضا</label>
                <input
                  v-model="form.expires_at"
                  type="datetime-local"
                  dir="ltr"
                  class="w-full h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm"
                />
              </div>
            </div>

            <!-- محدودیت استفاده + وضعیت -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-secondary-700 mb-1">محدودیت استفاده</label>
                <input
                  v-model.number="form.usage_limit"
                  type="number"
                  min="1"
                  placeholder="بدون محدودیت"
                  class="w-full h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm"
                />
              </div>
              <div class="flex items-end pb-1">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                  <input
                    v-model="form.status"
                    type="checkbox"
                    class="w-4 h-4 rounded border-secondary-300 text-primary-600"
                  />
                  <span class="text-sm text-secondary-700">فعال</span>
                </label>
              </div>
            </div>

            <!-- دکمه‌های فرم -->
            <div class="flex justify-end gap-3 pt-2 border-t border-secondary-100">
              <button
                type="button"
                class="px-4 py-2 text-sm text-secondary-600 hover:bg-secondary-100 rounded-lg transition"
                @click="closeModal"
              >
                انصراف
              </button>
              <button
                type="submit"
                :disabled="isSaving"
                class="flex items-center gap-2 px-5 py-2 bg-primary-600 hover:bg-primary-700 disabled:opacity-60 text-white rounded-lg text-sm font-medium transition"
              >
                <Save class="w-4 h-4" />
                {{ isSaving ? 'در حال ذخیره...' : 'ذخیره' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Delete Confirm Modal -->
    <Teleport to="body">
      <div
        v-if="showDeleteConfirm"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        dir="rtl"
      >
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm">
          <div class="flex items-center justify-between p-5 border-b border-secondary-100">
            <h3 class="font-bold text-secondary-800">تأیید حذف کوپن</h3>
            <button type="button" class="p-1 hover:bg-secondary-100 rounded-lg transition" @click="closeDelete">
              <X class="w-5 h-5 text-secondary-400" />
            </button>
          </div>
          <div class="p-5 space-y-4">
            <p class="text-sm text-secondary-600">
              آیا از حذف کوپن
              <span class="font-mono font-semibold text-secondary-800 mx-1">{{ deletingCoupon?.code }}</span>
              مطمئن هستید؟ این عمل قابل بازگشت نیست.
            </p>
            <div class="flex gap-3">
              <button
                type="button"
                :disabled="isDeleting !== null"
                class="flex-1 bg-red-500 hover:bg-red-600 disabled:opacity-50 text-white py-2 rounded-lg text-sm font-medium transition"
                @click="confirmDelete"
              >
                {{ isDeleting !== null ? 'در حال حذف...' : 'بله، حذف شود' }}
              </button>
              <button
                type="button"
                :disabled="isDeleting !== null"
                class="flex-1 border border-secondary-200 hover:bg-secondary-50 disabled:opacity-50 py-2 rounded-lg text-sm transition"
                @click="closeDelete"
              >
                انصراف
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from,
.fade-leave-to     { opacity: 0; }
</style>
