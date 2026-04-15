<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Trash2 } from 'lucide-vue-next'
import { RouterLink } from 'vue-router'
import { Breadcrumb, ProductCard } from '@shared/components'
import { userService } from '../services'
import UserSidebar from '../components/UserSidebar.vue'

const wishlist = ref<any[]>([])
const isLoading = ref(true)

const fetchWishlist = async () => {
  isLoading.value = true
  try {
    const response = await userService.getWishlist()
    wishlist.value = response.data || []
  } catch (error) {
    console.error('Failed to load wishlist:', error)
  } finally {
    isLoading.value = false
  }
}

const removeFromWishlist = async (id: number) => {
  try {
    await userService.removeFromWishlist(id)
    wishlist.value = wishlist.value.filter(w => w.id !== id)
  } catch (error) {
    console.error('Failed to remove from wishlist:', error)
  }
}

onMounted(fetchWishlist)
</script>

<template>
  <div class="container mx-auto px-4 py-8">
    <Breadcrumb :items="[{ title: 'پنل کاربری', url: '/user/dashboard' }, { title: 'علاقه‌مندی‌ها' }]" />
    
    <h1 class="text-2xl font-bold mb-8">لیست علاقه‌مندی‌ها</h1>
    
    <div class="grid lg:grid-cols-4 gap-8">
      <UserSidebar />
      
      <div class="lg:col-span-3">
        <div v-if="isLoading" class="grid grid-cols-2 md:grid-cols-3 gap-4">
          <div v-for="i in 6" :key="i" class="bg-white rounded-lg p-4 animate-pulse">
            <div class="aspect-square bg-gray-200 rounded mb-4"></div>
            <div class="h-4 bg-gray-200 rounded mb-2"></div>
            <div class="h-4 bg-gray-200 rounded w-2/3"></div>
          </div>
        </div>
        
        <div v-else-if="wishlist.length === 0" class="bg-white rounded-lg shadow p-12 text-center text-gray-500">
          <p class="mb-4">لیست علاقه‌مندی‌ها خالی است</p>
          <RouterLink to="/products" class="text-primary-600 hover:underline">
            مشاهده محصولات
          </RouterLink>
        </div>
        
        <div v-else class="grid grid-cols-2 md:grid-cols-3 gap-4">
          <div v-for="item in wishlist" :key="item.id" class="relative">
            <ProductCard :product="item.product" />
            <button 
              @click="removeFromWishlist(item.id)"
              class="absolute top-2 left-2 p-2 bg-white rounded-full shadow hover:bg-red-50"
            >
              <Trash2 class="w-4 h-4 text-red-500" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
