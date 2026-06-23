<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import {
  Star, CheckCircle, XCircle, MessageSquare, Filter,
  Loader2, CornerDownRight, Send, ChevronDown, ChevronUp,
} from 'lucide-vue-next'
import { reviewService } from '@/modules/product/services/reviewService'
import type { ProductReview } from '@/modules/product/services/reviewService'
import { formatDate } from '@shared/utils/format'

// ─── Types ────────────────────────────────────────────────────────────────────

interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

// ─── State ────────────────────────────────────────────────────────────────────

const reviews    = ref<ProductReview[]>([])
const isLoading  = ref(true)
const errorMsg   = ref('')
const meta       = ref<PaginationMeta | null>(null)

const statusFilter = ref<'' | 'pending' | 'approved' | 'rejected'>('')
const ratingFilter = ref<number | ''>('')
const currentPage  = ref(1)

const actionLoading = ref<Record<number, boolean>>({})

// Reply state per review id
const replyOpen   = ref<Record<number, boolean>>({})
const replyText   = ref<Record<number, string>>({})
const replyLoading = ref<Record<number, boolean>>({})
const replyError  = ref<Record<number, string>>({})

// ─── Filter options ───────────────────────────────────────────────────────────

const statusOptions: { value: '' | 'pending' | 'approved' | 'rejected'; label: string }[] = [
  { value: '',         label: 'همه' },
  { value: 'pending',  label: 'در انتظار' },
  { value: 'approved', label: 'تأیید شده' },
  { value: 'rejected', label: 'رد شده' },
]

// ─── Helpers ──────────────────────────────────────────────────────────────────

const statusLabel = (s: ProductReview['status']): string =>
  ({ pending: 'در انتظار', approved: 'تأیید شده', rejected: 'رد شده' })[s] ?? s

const statusClass = (s: ProductReview['status']): string =>
  ({
    pending:  'bg-amber-100 text-amber-700',
    approved: 'bg-green-100 text-green-700',
    rejected: 'bg-red-100 text-red-700',
  })[s] ?? 'bg-secondary-100 text-secondary-600'

const excerpt = (text: string | null, max = 120): string => {
  if (!text) return ''
  return text.length > max ? text.slice(0, max) + '…' : text
}

// ─── Pagination range ─────────────────────────────────────────────────────────

const pageRange = computed<(number | string)[]>(() => {
  if (!meta.value) return []
  const last = meta.value.last_page
  const cur  = currentPage.value
  if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1)
  const pages: (number | string)[] = [1]
  if (cur > 3) pages.push('...')
  for (let i = Math.max(2, cur - 1); i <= Math.min(last - 1, cur + 1); i++) {
    pages.push(i)
  }
  if (cur < last - 2) pages.push('...')
  pages.push(last)
  return pages
})

// ─── Data Loading ─────────────────────────────────────────────────────────────

const loadReviews = async () => {
  isLoading.value = true
  errorMsg.value  = ''
  try {
    const result = await reviewService.storeGetAll(currentPage.value, 10, {
      status: statusFilter.value || undefined,
      rating: ratingFilter.value !== '' ? (ratingFilter.value as number) : undefined,
    })
    reviews.value = result.data
    meta.value    = result.meta
  } catch {
    errorMsg.value = 'خطا در بارگذاری نظرات'
  } finally {
    isLoading.value = false
  }
}

watch([statusFilter, ratingFilter], () => {
  currentPage.value = 1
  loadReviews()
})

// ─── Approve / Reject ─────────────────────────────────────────────────────────

const approveReview = async (review: ProductReview) => {
  actionLoading.value[review.id] = true
  try {
    const updated = await reviewService.storeApprove(review.id)
    const idx = reviews.value.findIndex(r => r.id === review.id)
    if (idx !== -1) reviews.value[idx] = updated
  } catch {
    // status stays unchanged; user sees no change — acceptable for now
  } finally {
    actionLoading.value[review.id] = false
  }
}

const rejectReview = async (review: ProductReview) => {
  actionLoading.value[review.id] = true
  try {
    const updated = await reviewService.storeReject(review.id)
    const idx = reviews.value.findIndex(r => r.id === review.id)
    if (idx !== -1) reviews.value[idx] = updated
  } catch {
    // status stays unchanged
  } finally {
    actionLoading.value[review.id] = false
  }
}

// ─── Reply ────────────────────────────────────────────────────────────────────

const toggleReply = (review: ProductReview) => {
  replyOpen.value[review.id] = !replyOpen.value[review.id]
  // Pre-fill with existing reply when opening
  if (replyOpen.value[review.id] && review.reply) {
    replyText.value[review.id] = review.reply
  }
  replyError.value[review.id] = ''
}

const submitReply = async (review: ProductReview) => {
  const body = (replyText.value[review.id] ?? '').trim()
  if (!body) return

  replyLoading.value[review.id] = true
  replyError.value[review.id]   = ''
  try {
    const updated = await reviewService.storeReply(review.id, body)
    const idx = reviews.value.findIndex(r => r.id === review.id)
    if (idx !== -1) reviews.value[idx] = updated
    replyOpen.value[review.id] = false
  } catch (err: any) {
    replyError.value[review.id] = err?.response?.data?.message ?? 'خطا در ثبت پاسخ'
  } finally {
    replyLoading.value[review.id] = false
  }
}

const goToPage = (page: number) => {
  currentPage.value = page
  loadReviews()
}

onMounted(loadReviews)
</script>

<template>
  <div dir="rtl">

    <!-- Page header -->
    <div class="flex items-center gap-3 mb-6">
      <MessageSquare class="w-6 h-6 text-primary-500" />
      <h2 class="text-2xl font-bold text-secondary-800">نظرات محصولات</h2>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-4 mb-4 flex flex-wrap items-center gap-4">

      <!-- Status tabs -->
      <div class="flex items-center gap-1 bg-secondary-100 rounded-lg p-1">
        <button
          v-for="opt in statusOptions"
          :key="opt.value"
          type="button"
          @click="statusFilter = opt.value"
          :class="[
            'px-3 py-1.5 rounded-md text-sm font-medium transition',
            statusFilter === opt.value
              ? 'bg-white text-secondary-800 shadow-sm'
              : 'text-secondary-500 hover:text-secondary-700',
          ]"
        >
          {{ opt.label }}
        </button>
      </div>

      <!-- Rating filter -->
      <div class="flex items-center gap-2">
        <Filter class="w-4 h-4 text-secondary-400" aria-hidden="true" />
        <label for="rating-filter" class="sr-only">فیلتر امتیاز</label>
        <select
          id="rating-filter"
          v-model="ratingFilter"
          class="border border-secondary-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300"
        >
          <option value="">همه امتیازها</option>
          <option v-for="n in [5, 4, 3, 2, 1]" :key="n" :value="n">{{ n }} ستاره</option>
        </select>
      </div>

    </div>

    <!-- Reviews panel -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">

      <!-- Loading state -->
      <div v-if="isLoading" class="flex items-center justify-center py-20">
        <Loader2 class="w-8 h-8 animate-spin text-primary-500" />
      </div>

      <!-- Error state -->
      <div v-else-if="errorMsg" class="text-red-500 text-sm text-center py-10">
        {{ errorMsg }}
      </div>

      <!-- Empty state -->
      <div v-else-if="!reviews.length" class="flex flex-col items-center gap-3 py-16 text-secondary-400">
        <MessageSquare class="w-10 h-10" />
        <p class="text-sm">هیچ نظری یافت نشد</p>
      </div>

      <!-- Review list -->
      <ul v-else role="list" class="divide-y divide-secondary-100">
        <li
          v-for="review in reviews"
          :key="review.id"
          class="p-5 hover:bg-secondary-50 transition"
        >
          <div class="flex items-start justify-between gap-4">

            <!-- Main content -->
            <div class="flex-1 min-w-0">

              <!-- Stars + status badge -->
              <div class="flex items-center gap-3 mb-2">
                <span class="flex gap-0.5" :aria-label="`امتیاز ${review.rating} از ۵`">
                  <Star
                    v-for="n in 5"
                    :key="n"
                    class="w-4 h-4"
                    :class="n <= review.rating ? 'text-amber-400' : 'text-secondary-200'"
                    :fill="n <= review.rating ? 'currentColor' : 'none'"
                  />
                </span>
                <span :class="['text-xs font-medium px-2 py-0.5 rounded-full', statusClass(review.status)]">
                  {{ statusLabel(review.status) }}
                </span>
                <!-- replied badge -->
                <span
                  v-if="review.reply"
                  class="text-xs font-medium px-2 py-0.5 rounded-full bg-blue-50 text-blue-600"
                >
                  پاسخ داده شده
                </span>
              </div>

              <!-- Title -->
              <p v-if="review.title" class="font-medium text-secondary-800 mb-1 truncate">
                {{ review.title }}
              </p>

              <!-- Body excerpt -->
              <p v-if="review.body" class="text-sm text-secondary-600 mb-3 leading-relaxed">
                {{ excerpt(review.body) }}
              </p>

              <!-- Existing reply preview -->
              <div
                v-if="review.reply && !replyOpen[review.id]"
                class="flex items-start gap-2 bg-blue-50 border border-blue-100 rounded-lg px-3 py-2 mb-3"
              >
                <CornerDownRight class="w-4 h-4 text-blue-400 shrink-0 mt-0.5" aria-hidden="true" />
                <p class="text-sm text-blue-700 leading-relaxed">{{ excerpt(review.reply, 150) }}</p>
              </div>

              <!-- Meta: product · user · date -->
              <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-secondary-400">
                <span v-if="review.product">
                  محصول:&nbsp;<span class="text-secondary-600 font-medium">{{ review.product.name }}</span>
                </span>
                <span v-if="review.user">
                  کاربر:&nbsp;<span class="text-secondary-600 font-medium">{{ review.user.name }}</span>
                </span>
                <time :datetime="review.created_at">{{ formatDate(review.created_at) }}</time>
              </div>
            </div>

            <!-- Action buttons -->
            <div class="flex items-center gap-2 shrink-0 mt-1 flex-wrap justify-end">

              <!-- Approve / Reject (pending only) -->
              <template v-if="review.status === 'pending'">
                <button
                  type="button"
                  @click="approveReview(review)"
                  :disabled="!!actionLoading[review.id]"
                  class="flex items-center gap-1.5 bg-green-50 hover:bg-green-100 text-green-700 disabled:opacity-50 px-3 py-1.5 rounded-lg text-sm font-medium transition"
                >
                  <Loader2 v-if="actionLoading[review.id]" class="w-4 h-4 animate-spin" />
                  <CheckCircle v-else class="w-4 h-4" />
                  تأیید
                </button>
                <button
                  type="button"
                  @click="rejectReview(review)"
                  :disabled="!!actionLoading[review.id]"
                  class="flex items-center gap-1.5 bg-red-50 hover:bg-red-100 text-red-700 disabled:opacity-50 px-3 py-1.5 rounded-lg text-sm font-medium transition"
                >
                  <Loader2 v-if="actionLoading[review.id]" class="w-4 h-4 animate-spin" />
                  <XCircle v-else class="w-4 h-4" />
                  رد
                </button>
              </template>

              <!-- Reply toggle button (always shown for approved reviews) -->
              <button
                type="button"
                @click="toggleReply(review)"
                :aria-expanded="!!replyOpen[review.id]"
                class="flex items-center gap-1.5 bg-secondary-50 hover:bg-secondary-100 text-secondary-600 px-3 py-1.5 rounded-lg text-sm font-medium transition"
              >
                <CornerDownRight class="w-4 h-4" />
                {{ review.reply ? 'ویرایش پاسخ' : 'پاسخ' }}
                <ChevronDown v-if="!replyOpen[review.id]" class="w-3.5 h-3.5" />
                <ChevronUp  v-else                        class="w-3.5 h-3.5" />
              </button>

            </div>

          </div>

          <!-- Inline reply form (toggleable) -->
          <div
            v-if="replyOpen[review.id]"
            class="mt-4 border-t border-secondary-100 pt-4"
          >
            <label :for="`reply-${review.id}`" class="block text-sm font-medium text-secondary-700 mb-2">
              پاسخ فروشگاه به نظر {{ review.user?.name ?? 'خریدار' }}
            </label>
            <textarea
              :id="`reply-${review.id}`"
              v-model="replyText[review.id]"
              rows="3"
              maxlength="1000"
              placeholder="پاسخ خود را اینجا بنویسید…"
              class="w-full border border-secondary-200 rounded-xl px-4 py-3 text-sm text-secondary-800 resize-none focus:outline-none focus:ring-2 focus:ring-primary-300 transition"
            />
            <div class="flex items-center justify-between mt-2 gap-3">
              <p v-if="replyError[review.id]" role="alert" class="text-xs text-red-600">
                {{ replyError[review.id] }}
              </p>
              <div v-else class="text-xs text-secondary-400">
                {{ (replyText[review.id] ?? '').length }} / 1000
              </div>
              <div class="flex items-center gap-2 shrink-0">
                <button
                  type="button"
                  @click="replyOpen[review.id] = false"
                  class="px-3 py-1.5 rounded-lg text-sm text-secondary-500 hover:text-secondary-700 transition"
                >
                  انصراف
                </button>
                <button
                  type="button"
                  @click="submitReply(review)"
                  :disabled="replyLoading[review.id] || !(replyText[review.id] ?? '').trim()"
                  class="flex items-center gap-1.5 bg-primary-600 hover:bg-primary-700 text-white disabled:opacity-50 px-4 py-1.5 rounded-lg text-sm font-medium transition"
                >
                  <Loader2 v-if="replyLoading[review.id]" class="w-4 h-4 animate-spin" />
                  <Send v-else class="w-4 h-4" />
                  ثبت پاسخ
                </button>
              </div>
            </div>
          </div>

        </li>
      </ul>

      <!-- Pagination -->
      <nav
        v-if="meta && meta.last_page > 1"
        aria-label="صفحه‌بندی"
        class="flex items-center justify-center gap-1.5 p-4 border-t border-secondary-100"
      >
        <button
          type="button"
          @click="goToPage(currentPage - 1)"
          :disabled="currentPage <= 1"
          class="px-3 py-1.5 rounded-lg text-sm border border-secondary-200 disabled:opacity-40 hover:bg-secondary-50 transition"
        >
          قبلی
        </button>

        <template v-for="page in pageRange" :key="String(page)">
          <span v-if="page === '...'" class="w-9 text-center text-secondary-400 text-sm select-none">
            …
          </span>
          <button
            v-else
            type="button"
            @click="goToPage(page as number)"
            :aria-current="page === currentPage ? 'page' : undefined"
            :class="[
              'w-9 h-9 rounded-lg text-sm font-medium transition',
              page === currentPage
                ? 'bg-primary-600 text-white'
                : 'border border-secondary-200 hover:bg-secondary-50 text-secondary-700',
            ]"
          >
            {{ page }}
          </button>
        </template>

        <button
          type="button"
          @click="goToPage(currentPage + 1)"
          :disabled="currentPage >= (meta?.last_page ?? 1)"
          class="px-3 py-1.5 rounded-lg text-sm border border-secondary-200 disabled:opacity-40 hover:bg-secondary-50 transition"
        >
          بعدی
        </button>
      </nav>

    </div>
  </div>
</template>
