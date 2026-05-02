<script setup lang="ts">
import { ref, reactive } from 'vue'
import { Plus, Edit, Trash2, Search, X, Save, AlertCircle, Check } from 'lucide-vue-next'
import { useAdminList } from '../composables/useAdminList'
import { couponService, type AdminCoupon, type CouponFormData } from '../services/couponService'
import AdminPagination from '../components/AdminPagination.vue'

const { items: coupons, isLoading, errorMsg, filters, meta, debouncedReload, reload, goToPage } =
  useAdminList<AdminCoupon, { search: string; status: string; per_page: number }>({
    defaultFilters: { search: '', status: '', per_page: 20 },
    fetchFn: (f) => couponService.getAll(f),
  })

const showModal   = ref(false)
const isSaving    = ref(false)
const isDeleting  = ref<number | null>(null)
const editingId   = ref<number | null>(null)
const toastMsg    = ref<{ text: string; type: 'success' | 'error' } | null>(null)

const defaultForm = (): CouponFormData => ({
  code:                '',
  name:                null,
  type:                'percentage',
  value:               0,
  min_order_amount:    null,
  max_discount_amount: null,
  usage_limit:         null,
  is_active:           true,
  starts_at:           null,
  expires_at:          null,
})

const form = reactive<CouponFormData>(defaultForm())

let toastTimer: ReturnType<typeof setTimeout> | null = null
const showToast = (text: string, type: 'success' | 'error') => {
  if (toastTimer) clearTimeout(toastTimer)
  toastMsg.value = { text, type }
  toastTimer = setTimeout(() => (toastMsg.value = null), 3500)
}

const openCreate = () => {
  editingId.value = null
  Object.assign(form, defaultForm())
  showModal.value = true
}

const openEdit = (coupon: AdminCoupon) => {
  editingId.value = coupon.id
  Object.assign(form, {
    code:                coupon.code,
    name:                coupon.name,
    type:                coupon.type,
    value:               coupon.value,
    min_order_amount:    coupon.min_order_amount,
    max_discount_amount: coupon.max_discount_amount,
    usage_limit:         coupon.usage_limit,
    is_active:           coupon.is_active,
    starts_at:           coupon.starts_at,
    expires_at:          coupon.expires_at,
  })
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editingId.value = null
}

const handleSubmit = async () => {
  if (!form.code.trim()) { showToast('کد کوپن الزامی است', 'error'); return }
  isSaving.value = true
  try {
    if (editingId.value) {
      const updated = await couponService.update(editingId.value, form)
      const idx = coupons.value.findIndex(c => c.id === editingId.value)
      if (idx !== -1) coupons.value[idx] = updated
      showToast('کوپن ویرایش شد', 'success')
    } else {
      const created = await couponService.create(form)
      coupons.value.unshift(created)
      meta.value.total++
      showToast('کوپن ایجاد شد', 'success')
    }
    closeModal()
  } catch {
    showToast('خطا در ذخیره کوپن', 'error')
  } finally {
    isSaving.value = false
  }
}

const handleDelete = async (coupon: AdminCoupon) => {
  if (!confirm(`آیا از حذف کوپن "${coupon.code}" مطمئن هستید؟`)) return
  isDeleting.value = coupon.id
  try {
    await couponService.destroy(coupon.id)
    coupons.value = coupons.value.filter(c => c.id !== coupon.id)
    meta.value.total--
    showToast('کوپن حذف شد', 'success')
  } catch {
    showToast('خطا در حذف کوپن', 'error')
  } finally {
    isDeleting.value = null
  }
}

const formatPrice = (price: number) => new Intl.NumberFormat('fa-IR').format(price)

const couponDisplayValue = (coupon: AdminCoupon) =>
  coupon.type === 'percentage'
    ? `${coupon.value}٪`
    : `${formatPrice(coupon.value)} تومان`
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-secondary-800">مدیریت کوپن‌ها</h1>
        <p v-if="!isLoading" class="text-sm text-secondary-500 mt-1">{{ meta.total }} کوپن یافت شد</p>
      </div>
      <button
        class="flex items-center gap-2 px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition"
        @click="openCreate"
      >
        <Plus class="w-5 h-5" />
        افزودن کوپن
      </button>
    </div>

    <!-- Toast -->
    <transition name="fade">
      <div
        v-if="toastMsg"
        :class="toastMsg.type === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'"
        class="flex items-center gap-2 mb-4 px-4 py-3 border rounded-lg"
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
          @input="debouncedReload"
        />
      </div>
      <select
        v-model="filters.status"
        class="h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 text-sm text-secondary-700"
        @change="reload"
      >
        <option value="">همه وضعیت‌ها</option>
        <option value="active">فعال</option>
        <option value="inactive">غیرفعال</option>
      </select>
      <select
        v-model="filters.per_page"
        class="h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 text-sm text-secondary-700"
      >
        <option :value="20">۲۰ در صفحه</option>
        <option :value="50">۵۰ در صفحه</option>
        <option :value="100">۱۰۰ در صفحه</option>
      </select>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div v-if="errorMsg" class="p-6 text-center">
        <p class="text-red-500 mb-3">{{ errorMsg }}</p>
        <button class="px-4 py-2 bg-primary-500 text-white rounded-lg text-sm hover:bg-primary-600 transition" @click="reload">تلاش مجدد</button>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-secondary-50 border-b border-secondary-100">
            <tr>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">کد</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">نام</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-secondary-500">نوع / مقدار</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-secondary-500">استفاده شده</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">انقضا</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-secondary-500">وضعیت</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-secondary-500">عملیات</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-secondary-100">
            <template v-if="isLoading">
              <tr v-for="i in 8" :key="i">
                <td v-for="j in 7" :key="j" class="px-6 py-4">
                  <div class="h-4 bg-secondary-200 rounded animate-pulse" :class="j === 1 ? 'w-20' : 'w-16'"></div>
                </td>
              </tr>
            </template>
            <tr v-else-if="coupons.length === 0">
              <td colspan="7" class="px-6 py-12 text-center text-secondary-400 text-sm">کوپنی یافت نشد</td>
            </tr>
            <tr v-else v-for="coupon in coupons" :key="coupon.id" class="hover:bg-secondary-50 transition">
              <td class="px-6 py-4">
                <span class="font-mono text-sm font-semibold text-secondary-800 tracking-wide">{{ coupon.code }}</span>
              </td>
              <td class="px-6 py-4 text-sm text-secondary-600">{{ coupon.name || '—' }}</td>
              <td class="px-6 py-4 text-center">
                <span class="text-sm font-medium text-secondary-800">{{ couponDisplayValue(coupon) }}</span>
                <span class="block text-xs text-secondary-400">{{ coupon.type === 'percentage' ? 'درصدی' : 'مبلغی' }}</span>
              </td>
              <td class="px-6 py-4 text-center text-sm text-secondary-600">
                {{ coupon.used_count }}
                <span v-if="coupon.usage_limit" class="text-secondary-400">/ {{ coupon.usage_limit }}</span>
              </td>
              <td class="px-6 py-4 text-sm text-secondary-500">{{ coupon.expires_at || '—' }}</td>
              <td class="px-6 py-4 text-center">
                <span
                  :class="coupon.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'"
                  class="px-2.5 py-0.5 text-xs rounded-full font-medium"
                >
                  {{ coupon.is_active ? 'فعال' : 'غیرفعال' }}
                </span>
              </td>
              <td class="px-6 py-4 text-center">
                <div class="flex items-center justify-center gap-1">
                  <button class="p-2 hover:bg-secondary-100 rounded-lg transition" title="ویرایش" @click="openEdit(coupon)">
                    <Edit class="w-4 h-4 text-secondary-600" />
                  </button>
                  <button
                    class="p-2 hover:bg-red-100 rounded-lg transition disabled:opacity-50"
                    title="حذف"
                    :disabled="isDeleting === coupon.id"
                    @click="handleDelete(coupon)"
                  >
                    <Trash2 class="w-4 h-4 text-red-500" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <AdminPagination
        v-if="!isLoading && !errorMsg && meta.last_page > 1"
        :meta="meta"
        @page="goToPage"
      />
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="closeModal"
      >
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto" dir="rtl">
          <div class="flex items-center justify-between p-5 border-b border-secondary-100 sticky top-0 bg-white z-10">
            <h2 class="text-lg font-bold text-secondary-800">{{ editingId ? 'ویرایش کوپن' : 'کوپن جدید' }}</h2>
            <button type="button" class="p-1 hover:bg-secondary-100 rounded-lg" @click="closeModal">
              <X class="w-5 h-5 text-secondary-500" />
            </button>
          </div>

          <form class="p-5 space-y-4" @submit.prevent="handleSubmit">
            <div class="grid grid-cols-2 gap-4">
              <div class="col-span-2 sm:col-span-1">
                <label class="block text-sm font-medium text-secondary-700 mb-1">کد کوپن <span class="text-red-500">*</span></label>
                <input
                  v-model="form.code"
                  type="text"
                  dir="ltr"
                  placeholder="مثال: SUMMER20"
                  class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 font-mono uppercase"
                  required
                />
              </div>
              <div class="col-span-2 sm:col-span-1">
                <label class="block text-sm font-medium text-secondary-700 mb-1">نام کوپن</label>
                <input
                  v-model="form.name"
                  type="text"
                  placeholder="مثال: تخفیف تابستانه"
                  class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-secondary-700 mb-1">نوع تخفیف</label>
                <select v-model="form.type" class="w-full h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                  <option value="percentage">درصدی</option>
                  <option value="fixed">مبلغی (تومان)</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-secondary-700 mb-1">مقدار تخفیف</label>
                <input
                  v-model.number="form.value"
                  type="number"
                  min="0"
                  class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-secondary-700 mb-1">حداقل مبلغ سفارش</label>
                <input
                  v-model.number="form.min_order_amount"
                  type="number"
                  min="0"
                  placeholder="اختیاری"
                  class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-secondary-700 mb-1">سقف تخفیف (تومان)</label>
                <input
                  v-model.number="form.max_discount_amount"
                  type="number"
                  min="0"
                  placeholder="اختیاری"
                  class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-secondary-700 mb-1">تاریخ شروع</label>
                <input
                  v-model="form.starts_at"
                  type="datetime-local"
                  class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm"
                  dir="ltr"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-secondary-700 mb-1">تاریخ انقضا</label>
                <input
                  v-model="form.expires_at"
                  type="datetime-local"
                  class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm"
                  dir="ltr"
                />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-secondary-700 mb-1">محدودیت استفاده</label>
                <input
                  v-model.number="form.usage_limit"
                  type="number"
                  min="1"
                  placeholder="بدون محدودیت"
                  class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                />
              </div>
              <div class="flex items-end pb-1">
                <label class="flex items-center gap-2 cursor-pointer">
                  <input v-model="form.is_active" type="checkbox" class="w-4 h-4 rounded border-secondary-300 text-primary-600" />
                  <span class="text-sm text-secondary-700">فعال</span>
                </label>
              </div>
            </div>

            <div class="flex justify-end gap-3 pt-2 border-t border-secondary-100">
              <button type="button" class="px-4 py-2 text-secondary-600 hover:bg-secondary-100 rounded-lg transition" @click="closeModal">انصراف</button>
              <button
                type="submit"
                :disabled="isSaving"
                class="flex items-center gap-2 px-5 py-2 bg-primary-500 hover:bg-primary-600 disabled:opacity-60 text-white rounded-lg transition"
              >
                <Save class="w-4 h-4" />
                {{ isSaving ? 'در حال ذخیره...' : 'ذخیره' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
