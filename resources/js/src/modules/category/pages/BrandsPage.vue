<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { Breadcrumb } from '@shared/components'

const brands = ref<any[]>([])
const isLoading = ref(true)

onMounted(async () => {
  // TODO: Implement brands API call
  isLoading.value = false
})
</script>

<template>
  <div class="container mx-auto px-4 py-8">
    <Breadcrumb :items="[{ title: 'برندها' }]" />
    
    <h1 class="text-2xl font-bold mb-8">برندها</h1>
    
    <div v-if="isLoading" class="grid grid-cols-2 md:grid-cols-4 gap-6">
      <div v-for="i in 8" :key="i" class="animate-pulse">
        <div class="aspect-square bg-gray-200 rounded-lg mb-2"></div>
        <div class="h-4 bg-gray-200 rounded"></div>
      </div>
    </div>
    
    <div v-else-if="brands.length === 0" class="text-center py-16 text-gray-500">
      <p>برندی یافت نشد</p>
    </div>
    
    <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-6">
      <RouterLink 
        v-for="brand in brands" 
        :key="brand.id"
        :to="`/brands/${brand.slug}`"
        class="group"
      >
        <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden mb-2 group-hover:ring-2 ring-primary-500">
          <img 
            v-if="brand.logo"
            :src="brand.logo" 
            :alt="brand.name"
            class="w-full h-full object-cover group-hover:scale-105 transition"
          />
        </div>
        <h3 class="font-medium text-center group-hover:text-primary-600">{{ brand.name }}</h3>
      </RouterLink>
    </div>
  </div>
</template>
