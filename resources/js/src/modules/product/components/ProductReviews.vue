<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'
import { RouterLink } from 'vue-router'
import { Star, Send, User, AlertCircle, CheckCircle } from 'lucide-vue-next'
import { reviewService } from '../services/reviewService'
import type { ProductReview, ReviewFormData } from '../services/reviewService'
import { useAuthStore } from '@/stores/auth'

const props = defineProps<{
  productSlug: string
}>()

const authStore = useAuthStore()

const reviews = ref<ProductReview[]>([])
const isLoading = ref(false)
const submitLoading = ref(false)
const submitSuccess = ref(false)
const submitError = ref<string | null>(null)

const form = ref<ReviewFormData>({ rating: 0, title: '', body: '' })
const hoveredStar = ref(0)

const averageRating = computed(() => {
  if (!reviews.value.length) return 0
  const sum = reviews.value.reduce((acc, r) => acc + r.rating, 0)
  return (sum / reviews.value.length).toFixed(1)
})

const ratingCounts = computed(() => {
  const counts: Record<number, number> = { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 }
  reviews.value.forEach(r => { counts[r.rating] = (counts[r.rating] || 0) + 1 })
  return counts
})

const load = async (slug: string) => {
  isLoading.value = true
  try {
    reviews.value = await reviewService.getProductReviews(slug)
  } finally {
    isLoading.value = false
  }
}

const setRating = (value: number) => {
  form.value.rating = value
}

const submitReview = async () => {
  if (!form.value.rating || !form.value.body.trim()) return

  submitLoading.value = true
  submitError.value = null
  try {
    await reviewService.submitReview(props.productSlug, form.value)
    submitSuccess.value = true
    form.value = { rating: 0, title: '', body: '' }
    hoveredStar.value = 0
  } catch (err: any) {
    const msg = err?.response?.data?.message
      || err?.response?.data?.errors?.product?.[0]
      || 'خطایی رخ داد. لطفاً دوباره تلاش کنید'
    submitError.value = msg
  } finally {
    submitLoading.value = false
  }
}

const formatDate = (dateStr: string) => {
  return new Date(dateStr).toLocaleDateString('fa-IR', {
    year: 'numeric', month: 'long', day: 'numeric',
  })
}

onMounted(() => load(props.productSlug))
watch(() => props.productSlug, (slug) => {
  reviews.value = []
  submitSuccess.value = false
  submitError.value = null
  form.value = { rating: 0, title: '', body: '' }
  load(slug)
})
</script>

<template>
  <div class="space-y-8">
    <!-- Loading -->
    <div v-if="isLoading" class="space-y-4 animate-pulse">
      <div v-for="i in 3" :key="i" class="bg-secondary-50 rounded-xl p-4 space-y-2">
        <div class="h-4 bg-secondary-200 rounded w-1/4"></div>
        <div class="h-3 bg-secondary-200 rounded w-full"></div>
        <div class="h-3 bg-secondary-200 rounded w-3/4"></div>
      </div>
    </div>

    <!-- Summary + list -->
    <div v-else-if="reviews.length > 0">
      <!-- Rating summary -->
      <div class="flex flex-col sm:flex-row gap-6 bg-secondary-50 rounded-2xl p-5 mb-6">
        <div class="flex flex-col items-center justify-center min-w-[100px]">
          <span class="text-5xl font-extrabold text-secondary-900">{{ averageRating }}</span>
          <div class="flex gap-0.5 mt-1">
            <Star
              v-for="s in 5"
              :key="s"
              :class="['w-4 h-4', s <= Math.round(Number(averageRating)) ? 'text-amber-400 fill-current' : 'text-secondary-300']"
            />
          </div>
          <span class="text-xs text-secondary-500 mt-1">از {{ reviews.length }} نظر</span>
        </div>
        <div class="flex-1 space-y-1.5">
          <div v-for="star in [5, 4, 3, 2, 1]" :key="star" class="flex items-center gap-2">
            <span class="text-xs text-secondary-500 w-4">{{ star }}</span>
            <Star class="w-3.5 h-3.5 text-amber-400 fill-current flex-shrink-0" />
            <div class="flex-1 h-2 bg-secondary-200 rounded-full overflow-hidden">
              <div
                class="h-full bg-amber-400 rounded-full transition-all"
                :style="{ width: reviews.length ? `${(ratingCounts[star] / reviews.length) * 100}%` : '0%' }"
              ></div>
            </div>
            <span class="text-xs text-secondary-400 w-5 text-left">{{ ratingCounts[star] }}</span>
          </div>
        </div>
      </div>

      <!-- Review cards -->
      <div class="space-y-4">
        <div
          v-for="review in reviews"
          :key="review.id"
          class="bg-white border border-secondary-100 rounded-2xl p-5"
        >
          <div class="flex items-start justify-between gap-3 mb-3">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center flex-shrink-0">
                <User class="w-5 h-5 text-primary-600" />
              </div>
              <div>
                <p class="text-sm font-semibold text-secondary-800">{{ review.user?.name ?? 'کاربر' }}</p>
                <p class="text-xs text-secondary-400">{{ formatDate(review.created_at) }}</p>
              </div>
            </div>
            <div class="flex gap-0.5">
              <Star
                v-for="s in 5"
                :key="s"
                :class="['w-4 h-4', s <= review.rating ? 'text-amber-400 fill-current' : 'text-secondary-200']"
              />
            </div>
          </div>
          <h4 v-if="review.title" class="text-sm font-semibold text-secondary-800 mb-1">{{ review.title }}</h4>
          <p class="text-sm text-secondary-600 leading-relaxed">{{ review.body }}</p>
        </div>
      </div>
    </div>

    <!-- Empty -->
    <div v-else class="text-center py-8">
      <Star class="w-10 h-10 text-secondary-200 mx-auto mb-2" />
      <p class="text-sm text-secondary-400">هنوز نظری ثبت نشده است. اولین نظر را شما بنویسید!</p>
    </div>

    <!-- Submit form -->
    <div class="border-t border-secondary-100 pt-6">
      <h3 class="text-base font-bold text-secondary-800 mb-4">ثبت نظر</h3>

      <!-- Not logged in -->
      <div v-if="!authStore.isAuthenticated" class="bg-secondary-50 rounded-xl p-4 flex items-center gap-3">
        <AlertCircle class="w-5 h-5 text-amber-500 flex-shrink-0" />
        <p class="text-sm text-secondary-600">
          برای ثبت نظر باید
          <RouterLink to="/login" class="text-primary-600 font-medium hover:underline">وارد شوید</RouterLink>
        </p>
      </div>

      <!-- Success -->
      <div v-else-if="submitSuccess" class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex items-center gap-3">
        <CheckCircle class="w-5 h-5 text-emerald-600 flex-shrink-0" />
        <p class="text-sm text-emerald-700">نظر شما ثبت شد و پس از تأیید مدیر نمایش داده خواهد شد.</p>
      </div>

      <!-- Form -->
      <form v-else @submit.prevent="submitReview" class="space-y-4">
        <!-- Star rating picker -->
        <div>
          <label class="block text-sm font-medium text-secondary-700 mb-2">امتیاز شما <span class="text-red-500">*</span></label>
          <div class="flex gap-1">
            <button
              v-for="s in 5"
              :key="s"
              type="button"
              @click="setRating(s)"
              @mouseenter="hoveredStar = s"
              @mouseleave="hoveredStar = 0"
              class="p-0.5 transition"
            >
              <Star
                :class="[
                  'w-8 h-8 transition',
                  s <= (hoveredStar || form.rating) ? 'text-amber-400 fill-current' : 'text-secondary-300',
                ]"
              />
            </button>
          </div>
        </div>

        <!-- Title -->
        <div>
          <label for="review-title" class="block text-sm font-medium text-secondary-700 mb-1">عنوان نظر (اختیاری)</label>
          <input
            id="review-title"
            v-model="form.title"
            type="text"
            maxlength="150"
            placeholder="مثلاً: محصول عالی"
            class="w-full border border-secondary-200 rounded-xl px-4 py-2.5 text-sm text-secondary-800 focus:outline-none focus:ring-2 focus:ring-primary-300"
          />
        </div>

        <!-- Body -->
        <div>
          <label for="review-body" class="block text-sm font-medium text-secondary-700 mb-1">متن نظر <span class="text-red-500">*</span></label>
          <textarea
            id="review-body"
            v-model="form.body"
            rows="4"
            minlength="10"
            maxlength="2000"
            placeholder="تجربه خود را با دیگران به اشتراک بگذارید..."
            class="w-full border border-secondary-200 rounded-xl px-4 py-2.5 text-sm text-secondary-800 focus:outline-none focus:ring-2 focus:ring-primary-300 resize-none"
          ></textarea>
          <p class="text-xs text-secondary-400 mt-1">{{ form.body.length }} / 2000</p>
        </div>

        <!-- Error -->
        <div v-if="submitError" class="flex items-center gap-2 text-red-600 text-sm bg-red-50 rounded-xl px-4 py-3">
          <AlertCircle class="w-4 h-4 flex-shrink-0" />
          {{ submitError }}
        </div>

        <button
          type="submit"
          :disabled="submitLoading || !form.rating || !form.body.trim()"
          class="flex items-center gap-2 px-6 py-2.5 bg-primary-500 text-white text-sm font-medium rounded-xl hover:bg-primary-600 transition disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <Send class="w-4 h-4" />
          {{ submitLoading ? 'در حال ارسال...' : 'ثبت نظر' }}
        </button>
      </form>
    </div>
  </div>
</template>
