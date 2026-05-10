<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { productService } from '../services'
import type { ProductListItem } from '../services'
import { formatPrice } from '@shared/utils/format'

const props = defineProps<{
  productSlug: string
}>()

const items = ref<ProductListItem[]>([])
const isLoading = ref(false)
const scrollContainer = ref<HTMLElement | null>(null)

const load = async (slug: string) => {
  if (!slug) return
  isLoading.value = true
  try {
    items.value = await productService.getSimilarProducts(slug)
  } finally {
    isLoading.value = false
  }
}

const scroll = (direction: 'left' | 'right') => {
  if (!scrollContainer.value) return
  const amount = scrollContainer.value.clientWidth * 0.7
  scrollContainer.value.scrollBy({
    left: direction === 'left' ? amount : -amount,
    behavior: 'smooth',
  })
}

onMounted(() => load(props.productSlug))
watch(() => props.productSlug, (slug) => load(slug))
</script>

<template>
  <section v-if="isLoading || items.length > 0" class="mt-12">
    <h2 class="text-xl font-bold text-secondary-800 mb-5">محصولات مشابه</h2>

    <!-- Loading skeleton -->
    <div v-if="isLoading" class="flex gap-4 overflow-hidden">
      <div
        v-for="i in 5"
        :key="i"
        class="flex-shrink-0 w-48 animate-pulse"
      >
        <div class="aspect-square bg-secondary-200 rounded-xl mb-3"></div>
        <div class="h-3 bg-secondary-200 rounded w-3/4 mb-2"></div>
        <div class="h-4 bg-secondary-200 rounded w-1/2"></div>
      </div>
    </div>

    <!-- Products carousel -->
    <div v-else class="relative group">
      <!-- Scroll Left (RTL: next) -->
      <button
        type="button"
        @click="scroll('left')"
        class="absolute left-0 top-1/2 -translate-y-1/2 z-10 -translate-x-3 w-9 h-9 bg-white shadow-md rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition hover:bg-secondary-50"
        aria-label="بعدی"
      >
        <ChevronLeft class="w-5 h-5 text-secondary-600" />
      </button>

      <!-- Scroll Right (RTL: prev) -->
      <button
        type="button"
        @click="scroll('right')"
        class="absolute right-0 top-1/2 -translate-y-1/2 z-10 translate-x-3 w-9 h-9 bg-white shadow-md rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition hover:bg-secondary-50"
        aria-label="قبلی"
      >
        <ChevronRight class="w-5 h-5 text-secondary-600" />
      </button>

      <div
        ref="scrollContainer"
        class="flex gap-4 overflow-x-auto scroll-smooth pb-2"
        style="scrollbar-width: none; -ms-overflow-style: none;"
      >
        <RouterLink
          v-for="item in items"
          :key="item.id"
          :to="{ name: 'product-detail', params: { slug: item.slug } }"
          class="flex-shrink-0 w-44 sm:w-48 group/card bg-white border border-secondary-100 rounded-xl overflow-hidden hover:shadow-md transition"
        >
          <!-- Image -->
          <div class="aspect-square bg-secondary-50 overflow-hidden relative">
            <img
              :src="item.main_image || '/images/placeholder.jpg'"
              :alt="item.name"
              class="w-full h-full object-cover group-hover/card:scale-105 transition-transform duration-300"
              loading="lazy"
            />
            <span
              v-if="item.discount_percent"
              class="absolute top-2 right-2 bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded"
            >
              {{ item.discount_percent }}%
            </span>
          </div>

          <!-- Info -->
          <div class="p-3">
            <p class="text-xs text-secondary-400 mb-1 truncate">{{ item.manufacturer || '' }}</p>
            <h3 class="text-sm font-medium text-secondary-800 line-clamp-2 mb-2 leading-5">
              {{ item.name }}
            </h3>
            <div class="space-y-0.5">
              <p
                v-if="item.old_price"
                class="text-xs text-secondary-400 line-through"
              >
                {{ formatPrice(item.old_price) }}
              </p>
              <p class="text-sm font-bold text-primary-600">
                {{ formatPrice(item.price) }}
                <span class="text-xs font-normal">تومان</span>
              </p>
            </div>
          </div>
        </RouterLink>
      </div>
    </div>
  </section>
</template>
