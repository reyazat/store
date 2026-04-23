<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { ProductCard } from '@shared/components'
import type { Product } from '@shared/types'

interface Props {
  products: Product[]
  loading?: boolean
}

defineProps<Props>()

const scrollContainerRef = ref<HTMLElement | null>(null)
const canScrollLeft = ref(false)
const canScrollRight = ref(true)

const scroll = (direction: 'left' | 'right') => {
  if (!scrollContainerRef.value) return
  const scrollAmount = 280
  scrollContainerRef.value.scrollBy({
    left: direction === 'left' ? -scrollAmount : scrollAmount,
    behavior: 'smooth'
  })
}

const checkScroll = () => {
  if (!scrollContainerRef.value) return
  const { scrollLeft, scrollWidth, clientWidth } = scrollContainerRef.value
  canScrollLeft.value = scrollLeft > 0
  canScrollRight.value = scrollLeft < scrollWidth - clientWidth - 10
}
</script>

<template>
  <section class="relative">
    <div class="bg-gradient-to-l from-violet-600 to-purple-700 rounded-2xl overflow-hidden">
      <!-- Header -->
      <div class="flex items-center justify-between px-6 py-4 border-b border-white/10">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
            <span class="text-white text-xl">%</span>
          </div>
          <h3 class="text-xl font-bold text-white">پر تخفیف‌ترین‌ها</h3>
        </div>
        <RouterLink
          to="/discounts"
          class="flex items-center gap-1 text-white/90 hover:text-white font-medium text-sm transition"
        >
          مشاهده همه
          <ChevronLeft class="w-4 h-4" />
        </RouterLink>
      </div>

      <!-- Products Carousel -->
      <div class="relative py-4 px-6">
        <!-- Scroll Left Button -->
        <button
          v-if="canScrollLeft"
          @click="scroll('left')"
          class="absolute right-2 top-1/2 -translate-y-1/2 z-10 w-10 h-10 bg-white rounded-full shadow-lg flex items-center justify-center text-violet-600 hover:bg-violet-50 transition"
        >
          <ChevronRight class="w-5 h-5" />
        </button>

        <!-- Scrollable Container -->
        <div
          ref="scrollContainerRef"
          @scroll="checkScroll"
          class="flex gap-4 overflow-x-auto scrollbar-hide scroll-smooth pb-2"
        >
          <!-- Loading State -->
          <template v-if="loading">
            <div
              v-for="i in 5"
              :key="i"
              class="flex-shrink-0 w-[220px] bg-white rounded-xl p-3 animate-pulse"
            >
              <div class="aspect-square bg-secondary-100 rounded-lg mb-3"></div>
              <div class="h-4 bg-secondary-100 rounded mb-2"></div>
              <div class="h-4 bg-secondary-100 rounded w-2/3 mb-3"></div>
              <div class="flex items-center justify-between">
                <div class="h-6 bg-secondary-100 rounded w-20"></div>
                <div class="h-8 bg-secondary-100 rounded w-8"></div>
              </div>
            </div>
          </template>

          <!-- Products -->
          <template v-else-if="products.length">
            <div
              v-for="product in products"
              :key="product.id"
              class="flex-shrink-0 w-[220px]"
            >
              <ProductCard :product="product" view-mode="grid" />
            </div>
          </template>

          <!-- Empty State -->
          <div v-else class="flex-shrink-0 w-full text-center py-8 text-white/70">
            محصولی با تخفیف موجود نیست
          </div>
        </div>

        <!-- Scroll Right Button -->
        <button
          v-if="canScrollRight"
          @click="scroll('right')"
          class="absolute left-2 top-1/2 -translate-y-1/2 z-10 w-10 h-10 bg-white rounded-full shadow-lg flex items-center justify-center text-violet-600 hover:bg-violet-50 transition"
        >
          <ChevronLeft class="w-5 h-5" />
        </button>
      </div>
    </div>
  </section>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}

.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
