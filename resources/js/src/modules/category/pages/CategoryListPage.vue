<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { Breadcrumb } from '@shared/components'
import { categoryService } from '../services'
import type { Category } from '@shared/types'

const categories = ref<Category[]>([])
const isLoading = ref(true)

onMounted(async () => {
  try {
    const response = await categoryService.getCategories()
    categories.value = response.data
  } catch (error) {
    console.error('Failed to load categories:', error)
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="container mx-auto px-4 py-8">
    <Breadcrumb :items="[{ title: 'دسته‌بندی‌ها' }]" />
    
    <h1 class="text-2xl font-bold mb-8">دسته‌بندی‌ها</h1>
    
    <div v-if="isLoading" class="grid grid-cols-2 md:grid-cols-4 gap-6">
      <div v-for="i in 8" :key="i" class="animate-pulse">
        <div class="aspect-square bg-gray-200 rounded-lg mb-2"></div>
        <div class="h-4 bg-gray-200 rounded"></div>
      </div>
    </div>
    
    <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-6">
      <RouterLink 
        v-for="category in categories" 
        :key="category.id"
        :to="`/categories/${category.slug}`"
        class="group"
      >
        <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden mb-2 group-hover:ring-2 ring-primary-500">
          <img 
            v-if="category.image"
            :src="category.image" 
            :alt="category.name"
            class="w-full h-full object-cover group-hover:scale-105 transition"
          />
        </div>
        <h3 class="font-medium text-center group-hover:text-primary-600">{{ category.name }}</h3>
        <p v-if="category.products_count" class="text-sm text-gray-500 text-center">
          {{ category.products_count }} محصول
        </p>
      </RouterLink>
    </div>
  </div>
</template>
