<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { Breadcrumb } from '@shared/components'
import { brandsService, type Brand } from '../services/brandsService'

const brands = ref<Brand[]>([])
const isLoading = ref(true)
const errorMessage = ref<string | null>(null)

const fetchBrands = async () => {
  isLoading.value = true
  errorMessage.value = null
  try {
    const response = await brandsService.getBrands()
    brands.value = response.data ?? []
  } catch (error) {
    console.error('Failed to load brands:', error)
    errorMessage.value = 'خطا در بارگذاری برندها'
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchBrands)
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

    <div v-else-if="errorMessage" class="text-center py-16 text-red-500">
      <p>{{ errorMessage }}</p>
      <button
        type="button"
        class="mt-4 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition"
        @click="fetchBrands"
      >
        تلاش مجدد
      </button>
    </div>

    <div v-else-if="brands.length === 0" class="text-center py-16 text-gray-500">
      <p>برندی یافت نشد</p>
    </div>

    <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-6">
      <RouterLink
        v-for="brand in brands"
        :key="brand.id"
        :to="{ path: '/products', query: { manufacturer_slug: brand.slug } }"
        class="group"
        :aria-label="brand.name"
      >
        <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden mb-2 group-hover:ring-2 ring-primary-500 flex items-center justify-center">
          <img
            v-if="brand.image"
            :src="brand.image"
            :alt="brand.name"
            class="w-full h-full object-contain group-hover:scale-105 transition p-4"
          />
          <span v-else class="text-2xl font-bold text-gray-400">{{ brand.name.charAt(0) }}</span>
        </div>
        <h3 class="font-medium text-center group-hover:text-primary-600">{{ brand.name }}</h3>
      </RouterLink>
    </div>
  </div>
</template>
