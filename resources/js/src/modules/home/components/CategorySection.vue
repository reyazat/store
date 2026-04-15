<script setup lang="ts">
import { RouterLink } from 'vue-router'
import type { Category } from '@shared/types'

interface Props {
  categories: Category[]
  loading?: boolean
}

defineProps<Props>()

// Category icons mapping
const categoryIcons: Record<string, string> = {
  'electronics': 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
  'fashion': 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
  'home-kitchen': 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
  'default': 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'
}

const getIconPath = (slug: string) => {
  return categoryIcons[slug] || categoryIcons.default
}
</script>

<template>
  <div v-if="loading" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
    <div v-for="i in 4" :key="i" class="animate-pulse">
      <div class="bg-gray-200 rounded-2xl p-6 h-32"></div>
    </div>
  </div>
  
  <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
    <RouterLink 
      v-for="category in categories.slice(0, 4)" 
      :key="category.id"
      :to="`/categories/${category.slug}`"
      class="group relative bg-gradient-to-br from-gray-50 to-gray-100 hover:from-indigo-50 hover:to-purple-50 rounded-2xl p-6 transition-all duration-300 hover:shadow-lg border border-gray-200 hover:border-indigo-200"
    >
      <div class="flex flex-col items-center text-center">
        <!-- Icon Circle -->
        <div class="w-16 h-16 mb-3 bg-white rounded-full flex items-center justify-center shadow-sm group-hover:shadow-md transition-shadow">
          <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getIconPath(category.slug)"></path>
          </svg>
        </div>
        
        <h3 class="font-bold text-gray-900 group-hover:text-indigo-600 transition-colors mb-1">
          {{ category.name }}
        </h3>
        
        <p v-if="category.products_count" class="text-xs text-gray-500">
          {{ category.products_count }} محصول
        </p>
      </div>
      
      <!-- Hover Arrow -->
      <div class="absolute top-4 left-4 opacity-0 group-hover:opacity-100 transition-opacity">
        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
      </div>
    </RouterLink>
  </div>
</template>
