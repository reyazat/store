<script setup lang="ts">
import { computed, ref } from 'vue'
import { ChevronDown } from 'lucide-vue-next'
import { useCartStore } from '@/stores/cart'
import { formatPrice } from '@shared/utils/format'

const cartStore = useCartStore()
const expanded = ref(false)

const hasItems = computed(() => cartStore.items.length > 0)
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

    <!-- Totals -->
    <div class="mt-4 pt-3 border-t border-gray-100 space-y-2 text-sm">
      <div class="flex justify-between text-gray-500">
        <span>جمع اقلام ({{ cartStore.itemsCount }} عدد)</span>
        <span>{{ formatPrice(cartStore.total) }} تومان</span>
      </div>
      <div class="flex justify-between text-green-600">
        <span>هزینه ارسال</span>
        <span>رایگان</span>
      </div>
      <div class="flex justify-between text-base font-bold text-gray-900 pt-2 border-t border-gray-100">
        <span>مبلغ قابل پرداخت</span>
        <span class="text-primary-600">{{ formatPrice(cartStore.total) }} تومان</span>
      </div>
    </div>
  </div>
</template>
