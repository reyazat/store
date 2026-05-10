<script setup lang="ts">
import { RouterLink } from 'vue-router'
import type { Category } from '@shared/types'

interface Props {
  categories: Category[]
  loading?: boolean
}

defineProps<Props>()

const getCategoryImage = (category: Category): string => {
  if (category.image) {
    return category.image.startsWith('http')
      ? category.image
      : `/storage/${category.image}`
  }
  return ''
}

const handleImageError = (event: Event) => {
  const img = event.target as HTMLImageElement
  img.style.display = 'none'
}
</script>

<template>
  <!-- Loading State -->
  <div v-if="loading" class="flex items-start justify-center gap-3 overflow-x-auto pb-2">
    <div v-for="i in 10" :key="i" class="flex-shrink-0 flex flex-col items-center animate-pulse">
      <div class="w-24 h-24 bg-amber-100 rounded-2xl mb-3"></div>
      <div class="h-3 bg-gray-200 rounded w-16"></div>
    </div>
  </div>

  <!-- Categories Row -->
  <div v-else class="flex items-start justify-center gap-3 overflow-x-auto pb-2 scrollbar-hide">
    <RouterLink
      v-for="category in categories"
      :key="category.id"
      :to="{ path: '/products', query: { category_slug: category.slug } }"
      class="group flex-shrink-0 flex flex-col items-center text-center"
    >
      <!-- Rounded Square Image Container - warm beige background -->
      <div class="w-24 h-24 bg-[#f5f0e8] rounded-2xl overflow-hidden mb-2 group-hover:shadow-md transition-all duration-200 group-hover:scale-105">
        <img
          v-if="getCategoryImage(category)"
          :src="getCategoryImage(category)"
          :alt="category.name"
          class="w-full h-full object-contain"
          @error="handleImageError"
        />
        <div v-else class="w-full h-full flex items-center justify-center text-amber-300">
          <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
          </svg>
        </div>
      </div>

      <!-- Category Name -->
      <h3 class="text-xs text-gray-700 group-hover:text-gray-900 transition-colors leading-tight max-w-[96px] line-clamp-2">
        {{ category.name }}
      </h3>
    </RouterLink>
  </div>
</template>
