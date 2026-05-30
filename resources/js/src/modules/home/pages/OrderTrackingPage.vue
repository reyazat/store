<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  Search,
  Package,
  CreditCard,
  Truck,
  CheckCircle2,
  Clock,
  AlertCircle,
  Copy,
  Check,
  PackageSearch,
} from 'lucide-vue-next'
import { orderTrackingService, type OrderTrackingResult, type TrackingTimelineItem } from '@shared/services/orderTracking'
import { formatPrice, formatDate } from '@shared/utils/format'

const route = useRoute()
const router = useRouter()

const trackingCode = ref('')
const isLoading = ref(false)
const errorMessage = ref('')
const orderData = ref<OrderTrackingResult | null>(null)
const copied = ref(false)

// Initialize from query params if present
if (route.query.code) {
  trackingCode.value = route.query.code as string
}

const hasSearched = computed(() => orderData.value !== null || errorMessage.value !== '')

const search = async () => {
  if (!trackingCode.value.trim()) {
    errorMessage.value = 'لطفاً کد رهگیری را وارد کنید'
    orderData.value = null
    return
  }

  isLoading.value = true
  errorMessage.value = ''
  orderData.value = null

  try {
    const result = await orderTrackingService.track(trackingCode.value.trim())
    if (result.success && result.data) {
      orderData.value = result.data
      // Update URL with tracking code for shareability
      router.replace({ query: { code: trackingCode.value.trim() } })
    } else {
      errorMessage.value = result.message || 'سفارشی با این کد رهگیری یافت نشد'
    }
  } catch (e: any) {
    errorMessage.value = e.response?.data?.message || 'خطا در جستجو. لطفاً دوباره تلاش کنید.'
  } finally {
    isLoading.value = false
  }
}

const copyTrackingCode = async () => {
  if (!orderData.value) return
  try {
    await navigator.clipboard.writeText(orderData.value.order_number)
    copied.value = true
    setTimeout(() => (copied.value = false), 2000)
  } catch {
    /* ignore */
  }
}

const getStepIcon = (step: TrackingTimelineItem) => {
  if (step.key === 'created') return Package
  if (step.key === 'paid') return CreditCard
  if (step.key === 'processing') return Clock
  if (step.key === 'shipped') return Truck
  if (step.key === 'delivered') return CheckCircle2
  return Clock
}

const getStepStatusColor = (step: TrackingTimelineItem) => {
  if (step.completed) return 'bg-green-500 border-green-500'
  if (step.current) return 'bg-primary-500 border-primary-500 animate-pulse'
  return 'bg-gray-200 border-gray-200'
}

</script>

<template>
  <div class="min-h-screen bg-gradient-to-b from-gray-50 to-white">
    <div class="container mx-auto px-4 py-12 max-w-3xl">
      <!-- Header -->
      <div class="text-center mb-10">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-primary-100 rounded-2xl mb-4">
          <PackageSearch class="w-8 h-8 text-primary-600" />
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">پیگیری سفارش</h1>
        <p class="text-gray-500">کد رهگیری سفارش خود را وارد کنید تا وضعیت آن را مشاهده کنید</p>
      </div>

      <!-- Search Box -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
        <div class="relative">
          <input
            v-model="trackingCode"
            type="text"
            placeholder="مثال: ORD-ABC123XYZ"
            class="w-full pr-12 pl-4 py-4 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
            @keyup.enter="search"
          />
          <div class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400">
            <Search class="w-5 h-5" />
          </div>
        </div>

        <button
          @click="search"
          :disabled="isLoading || !trackingCode.trim()"
          class="w-full mt-4 bg-primary-600 hover:bg-primary-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-semibold py-3.5 rounded-xl transition-all flex items-center justify-center gap-2"
        >
          <span v-if="isLoading" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
          <span v-else>جستجوی سفارش</span>
        </button>

        <!-- Error Message -->
        <div
          v-if="errorMessage"
          class="mt-4 p-4 bg-red-50 border border-red-100 rounded-xl flex items-start gap-3"
        >
          <AlertCircle class="w-5 h-5 text-red-500 shrink-0 mt-0.5" />
          <div class="flex-1">
            <p class="text-red-700 text-sm font-medium">{{ errorMessage }}</p>
          </div>
        </div>
      </div>

      <!-- Order Result -->
      <div v-if="orderData" class="space-y-6">
        <!-- Order Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <!-- Header -->
          <div class="bg-gradient-to-r from-primary-50 to-primary-100/50 px-6 py-5 border-b border-primary-100">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
              <div>
                <p class="text-sm text-gray-500 mb-1">کد رهگیری</p>
                <div class="flex items-center gap-3">
                  <span class="text-xl font-bold text-gray-900 font-mono">{{ orderData.order_number }}</span>
                  <button
                    @click="copyTrackingCode"
                    class="p-1.5 text-gray-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-all"
                    :title="copied ? 'کپی شد!' : 'کپی کد'"
                  >
                    <Check v-if="copied" class="w-4 h-4 text-green-500" />
                    <Copy v-else class="w-4 h-4" />
                  </button>
                </div>
              </div>
              <div class="text-left">
                <p class="text-sm text-gray-500 mb-1">تاریخ ثبت</p>
                <p class="text-sm font-medium text-gray-900">{{ orderData.created_at ? formatDate(orderData.created_at) : '-' }}</p>
              </div>
            </div>
          </div>

          <!-- Status Badge -->
          <div class="px-6 py-4 border-b border-gray-100">
            <div class="flex flex-wrap items-center gap-3">
              <span
                class="px-4 py-1.5 rounded-full text-sm font-medium"
                :style="{ backgroundColor: orderData.status_color + '20', color: orderData.status_color }"
              >
                {{ orderData.status_label }}
              </span>
              <span
                :class="[
                  'px-3 py-1 rounded-full text-xs font-medium',
                  orderData.payment_status === 'paid'
                    ? 'bg-green-100 text-green-700'
                    : 'bg-amber-100 text-amber-700',
                ]"
              >
                {{ orderData.payment_status === 'paid' ? 'پرداخت شده' : 'در انتظار پرداخت' }}
              </span>
            </div>
          </div>

          <!-- Timeline -->
          <div class="px-6 py-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-6">وضعیت سفارش</h3>

            <div class="relative">
              <!-- Timeline Line -->
              <div class="absolute right-6 top-0 bottom-0 w-0.5 bg-gray-200"></div>

              <!-- Timeline Steps -->
              <div class="space-y-6">
                <div
                  v-for="step in orderData.timeline"
                  :key="step.key"
                  class="relative flex items-start gap-4"
                >
                  <!-- Step Icon -->
                  <div
                    :class="[
                      'relative z-10 w-12 h-12 rounded-full border-2 flex items-center justify-center shrink-0 transition-all',
                      getStepStatusColor(step),
                      step.current ? 'ring-4 ring-primary-100' : '',
                    ]"
                  >
                    <component
                      :is="getStepIcon(step)"
                      :class="[
                        'w-5 h-5',
                        step.completed || step.current ? 'text-white' : 'text-gray-400',
                      ]"
                    />
                  </div>

                  <!-- Step Content -->
                  <div class="flex-1 pt-2">
                    <div class="flex items-center justify-between">
                      <span
                        :class="[
                          'font-semibold text-sm',
                          step.completed || step.current ? 'text-gray-900' : 'text-gray-400',
                        ]"
                      >
                        {{ step.label }}
                      </span>
                      <span v-if="step.date" class="text-xs text-gray-500">
                        {{ step.date }}
                      </span>
                    </div>
                    <p v-if="step.current" class="text-xs text-primary-600 mt-1">
                      {{ step.key === 'created' ? 'در انتظار تکمیل پرداخت' : 'در حال انجام' }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Order Summary -->
          <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-4">
                <div class="text-center">
                  <p class="text-xs text-gray-500 mb-1">تعداد کالا</p>
                  <p class="text-lg font-bold text-gray-900">{{ orderData.items_count }}</p>
                </div>
                <div class="w-px h-10 bg-gray-300"></div>
                <div>
                  <p class="text-xs text-gray-500 mb-1">مبلغ کل</p>
                  <p class="text-lg font-bold text-primary-600">{{ formatPrice(orderData.total) }} تومان</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Help Section -->
        <div class="bg-blue-50 border border-blue-100 rounded-2xl p-5">
          <div class="flex items-start gap-3">
            <div class="p-2 bg-blue-100 rounded-lg shrink-0">
              <AlertCircle class="w-5 h-5 text-blue-600" />
            </div>
            <div>
              <h4 class="font-semibold text-blue-900 text-sm mb-1">نیاز به کمک دارید؟</h4>
              <p class="text-sm text-blue-700 leading-relaxed">
                در صورت هرگونه سوال یا مشکل در مورد سفارش خود، با پشتیبانی تماس بگیرید.
                کد رهگیری را هنگام تماس در دسترس داشته باشید.
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="hasSearched && !orderData && !errorMessage" class="text-center py-12">
        <PackageSearch class="w-16 h-16 text-gray-300 mx-auto mb-4" />
        <p class="text-gray-500">نتیجه‌ای یافت نشد</p>
      </div>
    </div>
  </div>
</template>
