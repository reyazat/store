<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { Star, CheckCircle, XCircle, MessageSquare, Filter, Loader2 } from 'lucide-vue-next'
import api from '@shared/utils/api'
import { formatDate } from '@shared/utils/format'

// ─── Types ────────────────────────────────────────────────────────────────────

interface ReviewUser {
  id: number
  name: string
  email: string
}

interface ReviewProduct {
  id: number
  name: string
  slug: string
}

interface Review {
  id: number
  status: 'pending' | 'approved' | 'rejected'
  rating: number
  title: string | null
  body: string | null
  created_at: string
  user: ReviewUser | null
  product: ReviewProduct | null
}

interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

// ─── State ────────────────────────────────────────────────────────────────────

const reviews    = ref<Review[]>([])
const isLoading  = ref(true)
const errorMsg   = ref('')
const meta       = ref<PaginationMeta | null>(null)

const statusFilter = ref<'' | 'pending' | 'approved' | 'rejected'>('')
const ratingFilter = ref<number | ''>('')
const currentPage  = ref(1)

const actionLoading = ref<Record<number, boolean>>({})

// ─── Filter options ───────────────────────────────────────────────────────────

const statusOptions: { value: '' | 'pending' | 'approved' | 'rejected'; label: string }[] = [
  { value: '',         label: 'همه' },
  { value: 'pending',  label: 'در انتظار' },
  { value: 'approved', label: 'تأیید شده' },
  { value: 'rejected', label: 'رد شده' },
]

// ─── Helpers ──────────────────────────────────────────────────────────────────

const statusLabel = (s: Review['status']): string =>
  ({ pending: 'در انتظار', approved: 'تأیید شده', rejected: 'رد شده' })[s] ?? s

const statusClass = (s: Review['status']): string =>
  ({
    pending:  'bg-amber-100 text-amber-700',
    approved: 'bg-green-100 text-green-700',
    rejected: 'bg-red-100 text-red-700',
  })[s] ?? 'bg-secondary-100 text-secondary-600'

const excerpt = (text: string | null, max = 100): string => {
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
    const params: Record<string, string | number> = {
      page:     currentPage.value,
      per_page: 10,
    }
    if (statusFilter.value)   params.status = statusFilter.value
    if (ratingFilter.value !== '') params.rating = ratingFilter.value as number

    const { data } = await api.get('/store/reviews', { params })
    reviews.value = data.data
    meta.value    = data.meta
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

// ─── Actions ──────────────────────────────────────────────────────────────────

const approveReview = async (review: Review) => {
  actionLoading.value[review.id] = true
  try {
    await api.post(`/store/reviews/${review.id}/approve`)
    review.status = 'approved'
  } catch {
    // silently ignore — status stays pending; a more advanced UX would show a toast
  } finally {
    actionLoading.value[review.id] = false
  }
}

const rejectReview = async (review: Review) => {
  actionLoading.value[review.id] = true
  try {
    await api.post(`/store/reviews/${review.id}/reject`)
    review.status = 'rejected'
  } catch {
    // silently ignore
  } finally {
    actionLoading.value[review.id] = false
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
              </div>

              <!-- Title -->
              <p v-if="review.title" class="font-medium text-secondary-800 mb-1 truncate">
                {{ review.title }}
              </p>

              <!-- Body excerpt -->
              <p v-if="review.body" class="text-sm text-secondary-600 mb-3 leading-relaxed">
                {{ excerpt(review.body) }}
              </p>

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

            <!-- Action buttons (pending only) -->
            <div v-if="review.status === 'pending'" class="flex items-center gap-2 shrink-0 mt-1">
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
