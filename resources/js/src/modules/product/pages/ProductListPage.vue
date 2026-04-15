<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ProductCard, Breadcrumb } from '@shared/components'
import { productService } from '../services'
import type { Product } from '@shared/types'

const route = useRoute()
const router = useRouter()

const products = ref<Product[]>([])
const isLoading = ref(true)
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)

const fetchProducts = async () => {
  isLoading.value = true
  try {
    const response = await productService.getProducts({
      page: currentPage.value,
      search: route.query.q as string,
    })
    products.value = response.data
    currentPage.value = response.meta?.current_page || 1
    lastPage.value = response.meta?.last_page || 1
    total.value = response.meta?.total || 0
  } catch (error) {
    console.error('Failed to load products:', error)
  } finally {
    isLoading.value = false
  }
}

const changePage = (page: number) => {
  currentPage.value = page
  router.push({ query: { ...route.query, page } })
}

onMounted(fetchProducts)

watch(() => route.query, fetchProducts)
</script>

<template>
  <div class="container mx-auto px-4 py-8">
    <Breadcrumb :items="[{ title: 'محصولات' }]" />
    
    <div class="flex items-center justify-between mb-8">
      <h1 class="text-2xl font-bold">
        {{ route.query.q ? `نتایج جستجو: ${route.query.q}` : 'همه محصولات' }}
      </h1>
      <span class="text-gray-500">{{ total }} محصول</span>
    </div>
    
    <!-- Loading -->
    <div v-if="isLoading" class="grid grid-cols-2 md:grid-cols-4 gap-6">
      <div v-for="i in 8" :key="i" class="bg-white rounded-lg p-4 animate-pulse">
        <div class="aspect-square bg-gray-200 rounded mb-4"></div>
        <div class="h-4 bg-gray-200 rounded mb-2"></div>
        <div class="h-4 bg-gray-200 rounded w-2/3"></div>
      </div>
    </div>
    
    <!-- Products Grid -->
    <div v-else-if="products.length > 0" class="grid grid-cols-2 md:grid-cols-4 gap-6">
      <ProductCard 
        v-for="product in products" 
        :key="product.id" 
        :product="product" 
      />
    </div>
    
    <!-- Empty State -->
    <div v-else class="text-center py-16">
      <p class="text-gray-500 text-lg">محصولی یافت نشد</p>
    </div>
    
    <!-- Pagination -->
    <div v-if="lastPage > 1" class="flex justify-center gap-2 mt-8">
      <button 
        v-for="page in lastPage" 
        :key="page"
        @click="changePage(page)"
        :class="[
          'px-4 py-2 rounded',
          page === currentPage 
            ? 'bg-primary-600 text-white' 
            : 'bg-white hover:bg-gray-100'
        ]"
      >
        {{ page }}
      </button>
    </div>
  </div>
</template>
