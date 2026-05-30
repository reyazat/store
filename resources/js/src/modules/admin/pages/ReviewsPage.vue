<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { Star, CheckCircle, XCircle, Trash2, ExternalLink, ChevronLeft, ChevronRight, AlertCircle } from 'lucide-vue-next'
import { reviewService } from '@/modules/product/services/reviewService'
import type { ProductReview, AdminReviewMeta } from '@/modules/product/services/reviewService'
import { formatDate } from '@shared/utils/format'

const reviews = ref<ProductReview[]>([])
const meta = ref<AdminReviewMeta>({ current_page: 1, last_page: 1, per_page: 20, total: 0 })
const isLoading = ref(false)
const actionLoading = ref<number | null>(null)
const actionError = ref<string | null>(null)

const statusLabel: Record<string, { text: string; class: string }> = {
  pending:  { text: 'در انتظار',  class: 'bg-amber-100 text-amber-700' },
  approved: { text: 'تأیید شده', class: 'bg-emerald-100 text-emerald-700' },
  rejected: { text: 'رد شده',    class: 'bg-red-100 text-red-600' },
}

const load = async (page = 1) => {
  isLoading.value = true
  try {
    const result = await reviewService.adminGetAll(page, meta.value.per_page)
    reviews.value = result.data
    meta.value = result.meta
  } finally {
    isLoading.value = false
  }
}

const approve = async (review: ProductReview) => {
  actionLoading.value = review.id
  actionError.value = null
  try {
    const updated = await reviewService.approve(review.id)
    const idx = reviews.value.findIndex(r => r.id === review.id)
    if (idx !== -1) reviews.value[idx] = updated
  } catch {
    actionError.value = 'خطا در تأیید نظر'
    setTimeout(() => (actionError.value = null), 3000)
  } finally {
    actionLoading.value = null
  }
}

const reject = async (review: ProductReview) => {
  actionLoading.value = review.id
  actionError.value = null
  try {
    const updated = await reviewService.reject(review.id)
    const idx = reviews.value.findIndex(r => r.id === review.id)
    if (idx !== -1) reviews.value[idx] = updated
  } catch {
    actionError.value = 'خطا در رد نظر'
    setTimeout(() => (actionError.value = null), 3000)
  } finally {
    actionLoading.value = null
  }
}

const destroy = async (review: ProductReview) => {
  if (!confirm(`آیا از حذف نظر "${review.user?.name ?? 'کاربر'}" مطمئن هستید؟`)) return
  actionLoading.value = review.id
  actionError.value = null
  try {
    await reviewService.destroy(review.id)
    reviews.value = reviews.value.filter(r => r.id !== review.id)
    meta.value.total -= 1
  } catch {
    actionError.value = 'خطا در حذف نظر'
    setTimeout(() => (actionError.value = null), 3000)
  } finally {
    actionLoading.value = null
  }
}

onMounted(() => load())
</script>

<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-secondary-900">مدیریت نظرات</h1>
        <p class="text-sm text-secondary-500 mt-1">{{ meta.total }} نظر ثبت شده</p>
      </div>
    </div>

    <!-- Action Error -->
    <Transition name="fade">
      <div v-if="actionError" class="flex items-center gap-2 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
        <AlertCircle class="w-4 h-4 flex-shrink-0" />
        {{ actionError }}
      </div>
    </Transition>

    <!-- Loading -->
    <div v-if="isLoading" class="space-y-3">
      <div v-for="i in 5" :key="i" class="animate-pulse bg-white rounded-xl p-4 h-24"></div>
    </div>

    <!-- Empty -->
    <div v-else-if="!reviews.length" class="bg-white rounded-xl p-12 text-center">
      <Star class="w-12 h-12 text-secondary-200 mx-auto mb-3" />
      <p class="text-secondary-400">هنوز نظری ثبت نشده است</p>
    </div>

    <!-- Table -->
    <div v-else class="bg-white rounded-2xl shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-secondary-100 bg-secondary-50 text-right">
              <th class="px-4 py-3 text-xs font-semibold text-secondary-500">کاربر</th>
              <th class="px-4 py-3 text-xs font-semibold text-secondary-500">محصول</th>
              <th class="px-4 py-3 text-xs font-semibold text-secondary-500">امتیاز</th>
              <th class="px-4 py-3 text-xs font-semibold text-secondary-500">نظر</th>
              <th class="px-4 py-3 text-xs font-semibold text-secondary-500">وضعیت</th>
              <th class="px-4 py-3 text-xs font-semibold text-secondary-500">تاریخ</th>
              <th class="px-4 py-3 text-xs font-semibold text-secondary-500">عملیات</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-secondary-50">
            <tr
              v-for="review in reviews"
              :key="review.id"
              class="hover:bg-secondary-50 transition"
            >
              <!-- User -->
              <td class="px-4 py-3">
                <p class="text-sm font-medium text-secondary-800 whitespace-nowrap">
                  {{ review.user?.name ?? 'کاربر حذف‌شده' }}
                </p>
              </td>

              <!-- Product -->
              <td class="px-4 py-3">
                <RouterLink
                  v-if="review.product"
                  :to="{ name: 'product-detail', params: { slug: review.product.slug } }"
                  target="_blank"
                  class="flex items-center gap-1 text-sm text-primary-600 hover:underline whitespace-nowrap"
                >
                  {{ review.product.name }}
                  <ExternalLink class="w-3 h-3" />
                </RouterLink>
                <span v-else class="text-sm text-secondary-400">—</span>
              </td>

              <!-- Rating -->
              <td class="px-4 py-3">
                <div class="flex gap-0.5">
                  <Star
                    v-for="s in 5"
                    :key="s"
                    :class="['w-3.5 h-3.5', s <= review.rating ? 'text-amber-400 fill-current' : 'text-secondary-200']"
                  />
                </div>
              </td>

              <!-- Body -->
              <td class="px-4 py-3 max-w-xs">
                <p v-if="review.title" class="text-xs font-semibold text-secondary-700 mb-0.5 truncate">{{ review.title }}</p>
                <p class="text-sm text-secondary-600 truncate">{{ review.body }}</p>
              </td>

              <!-- Status -->
              <td class="px-4 py-3">
                <span
                  :class="['inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap', statusLabel[review.status]?.class]"
                >
                  {{ statusLabel[review.status]?.text }}
                </span>
              </td>

              <!-- Date -->
              <td class="px-4 py-3">
                <p class="text-xs text-secondary-400 whitespace-nowrap">{{ formatDate(review.created_at) }}</p>
              </td>

              <!-- Actions -->
              <td class="px-4 py-3">
                <div class="flex items-center gap-1">
                  <button
                    v-if="review.status !== 'approved'"
                    @click="approve(review)"
                    :disabled="actionLoading === review.id"
                    class="p-1.5 rounded-lg text-emerald-600 hover:bg-emerald-50 transition disabled:opacity-40"
                    title="تأیید"
                  >
                    <CheckCircle class="w-4 h-4" />
                  </button>
                  <button
                    v-if="review.status !== 'rejected'"
                    @click="reject(review)"
                    :disabled="actionLoading === review.id"
                    class="p-1.5 rounded-lg text-amber-600 hover:bg-amber-50 transition disabled:opacity-40"
                    title="رد"
                  >
                    <XCircle class="w-4 h-4" />
                  </button>
                  <button
                    @click="destroy(review)"
                    :disabled="actionLoading === review.id"
                    class="p-1.5 rounded-lg text-red-500 hover:bg-red-50 transition disabled:opacity-40"
                    title="حذف"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-secondary-100">
        <p class="text-xs text-secondary-500">
          صفحه {{ meta.current_page }} از {{ meta.last_page }} — مجموع {{ meta.total }} نظر
        </p>
        <div class="flex items-center gap-1">
          <button
            @click="load(meta.current_page - 1)"
            :disabled="meta.current_page === 1"
            class="p-1.5 rounded-lg hover:bg-secondary-100 transition disabled:opacity-40"
          >
            <ChevronRight class="w-4 h-4" />
          </button>
          <button
            @click="load(meta.current_page + 1)"
            :disabled="meta.current_page === meta.last_page"
            class="p-1.5 rounded-lg hover:bg-secondary-100 transition disabled:opacity-40"
          >
            <ChevronLeft class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
