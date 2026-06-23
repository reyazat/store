<script setup lang="ts">
import { computed, ref } from 'vue'
import { ChevronDown, Tag, X, Loader2 } from 'lucide-vue-next'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'
import { formatPrice } from '@shared/utils/format'

const cartStore = useCartStore()
const authStore = useAuthStore()

const expanded = ref(false)
const couponCode    = ref('')
const couponError   = ref('')
const couponSuccess = ref('')
const isApplying    = ref(false)

const hasItems = computed(() => cartStore.items.length > 0)
const hasCoupon = computed(() => !!cartStore.appliedCoupon)

const applyCoupon = async () => {
  const code = couponCode.value.trim()
  if (!code) return

  couponError.value   = ''
  couponSuccess.value = ''
  isApplying.value    = true

  try {
    const error = await cartStore.applyCoupon(code)
    if (error) {
      couponError.value = error
    } else {
      couponCode.value    = ''
      couponSuccess.value = `کوپن "${cartStore.appliedCoupon?.code}" با موفقیت اعمال شد.`
    }
  } finally {
    isApplying.value = false
  }
}

const removeCoupon = async () => {
  couponError.value   = ''
  couponSuccess.value = ''
  await cartStore.removeCoupon()
}
</script>

<template>
  <div class="bg-white rounded-xl shadow-sm p-5 sticky top-24">
    <button
      class="w-full flex items-center justify-between mb-1 lg:cursor-default"
      @click="expanded = !expanded"
    >
      <h2 class="text-base font-bold text-gray-800">خلاصه سفارش</h2>
      <ChevronDown
        :class="['w-4 h-4 text-gray-400 transition-transform lg:hidden', expanded ? 'rotate-180' : '']"
      />
    </button>

    <!-- Items list (always visible on lg, collapsible on mobile) -->
    <div :class="['overflow-hidden transition-all', expanded ? 'max-h-[500px]' : 'max-h-0 lg:max-h-[500px]']">
      <div v-if="hasItems" class="divide-y divide-gray-50 mt-3">
        <div
          v-for="item in cartStore.items"
          :key="item.id"
          class="flex items-center gap-3 py-2.5"
        >
          <img
            v-if="item.image"
            :src="item.image"
            :alt="item.name"
            class="w-12 h-12 object-cover rounded-lg border border-gray-100 flex-shrink-0"
          />
          <div v-else class="w-12 h-12 bg-gray-100 rounded-lg flex-shrink-0" />
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-800 truncate">{{ item.name }}</p>
            <p class="text-xs text-gray-400 mt-0.5">× {{ item.quantity }}</p>
          </div>
          <p class="text-sm font-semibold text-gray-700 whitespace-nowrap">
            {{ formatPrice(item.price * item.quantity) }}
          </p>
        </div>
      </div>
    </div>

    <!-- Coupon section (authenticated users only) -->
    <div v-if="authStore.isAuthenticated" class="mt-4 pt-3 border-t border-gray-100">
      <!-- Applied coupon badge -->
      <div v-if="hasCoupon" class="flex items-center justify-between bg-green-50 border border-green-200 rounded-lg px-3 py-2">
        <div class="flex items-center gap-2">
          <Tag class="w-4 h-4 text-green-600 flex-shrink-0" />
          <div>
            <p class="text-sm font-semibold text-green-700">{{ cartStore.appliedCoupon!.code }}</p>
            <p v-if="cartStore.appliedCoupon!.name" class="text-xs text-green-600">{{ cartStore.appliedCoupon!.name }}</p>
          </div>
        </div>
        <button
          type="button"
          class="p-1 hover:bg-green-100 rounded-md transition"
          title="حذف کوپن"
          @click="removeCoupon"
        >
          <X class="w-4 h-4 text-green-600" />
        </button>
      </div>

      <!-- Coupon input form -->
      <div v-else class="space-y-2">
        <label class="block text-sm font-medium text-gray-700">کد تخفیف</label>
        <div class="flex gap-2">
          <input
            v-model="couponCode"
            type="text"
            dir="ltr"
            placeholder="کد کوپن را وارد کنید"
            class="flex-1 h-9 px-3 text-sm border border-gray-200 rounded-lg focus:border-primary-500 focus:ring-1 focus:ring-primary-500 font-mono uppercase placeholder:normal-case placeholder:font-sans"
            @keydown.enter.prevent="applyCoupon"
          />
          <button
            type="button"
            :disabled="!couponCode.trim() || isApplying"
            class="px-3 h-9 bg-primary-600 hover:bg-primary-700 disabled:opacity-50 text-white text-sm font-medium rounded-lg transition flex items-center gap-1.5"
            @click="applyCoupon"
          >
            <Loader2 v-if="isApplying" class="w-3.5 h-3.5 animate-spin" />
            <span v-else>اعمال</span>
          </button>
        </div>
        <p v-if="couponError" class="text-xs text-red-600">{{ couponError }}</p>
        <p v-if="couponSuccess" class="text-xs text-green-600">{{ couponSuccess }}</p>
      </div>
    </div>

    <!-- Totals -->
    <div class="mt-4 pt-3 border-t border-gray-100 space-y-2 text-sm">
      <div class="flex justify-between text-gray-500">
        <span>جمع اقلام ({{ cartStore.itemsCount }} عدد)</span>
        <span>{{ formatPrice(cartStore.subtotal) }} تومان</span>
      </div>
      <div
        v-if="cartStore.couponDiscount > 0"
        class="flex justify-between text-green-600 font-medium"
      >
        <span>تخفیف کوپن</span>
        <span>- {{ formatPrice(cartStore.couponDiscount) }} تومان</span>
      </div>
      <div class="flex justify-between text-gray-500">
        <span>هزینه ارسال</span>
        <span class="text-xs">در مرحله بعد محاسبه می‌شود</span>
      </div>
      <div class="flex justify-between text-base font-bold text-gray-900 pt-2 border-t border-gray-100">
        <span>مبلغ قابل پرداخت</span>
        <span class="text-primary-600">{{ formatPrice(cartStore.total) }} تومان</span>
      </div>
    </div>
  </div>
</template>
