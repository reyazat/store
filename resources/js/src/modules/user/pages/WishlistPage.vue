<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Heart } from 'lucide-vue-next'
import { RouterLink } from 'vue-router'
import { Breadcrumb, ProductCard } from '@shared/components'
import { userService } from '../services'
import { useWishlistStore } from '@/stores/wishlist'
import UserSidebar from '../components/UserSidebar.vue'
import type { Product } from '@shared/types'

interface WishlistItem {
  id: number
  product: Product
}

const wishlistStore = useWishlistStore()
const wishlist      = ref<WishlistItem[]>([])
const isLoading     = ref(true)

const fetchWishlist = async () => {
  isLoading.value = true
  try {
    const response = await userService.getWishlist()
    wishlist.value = response.data || []
  } catch {
    /* silently fail */
  } finally {
    isLoading.value = false
  }
}

const handleRemove = async (productId: number) => {
  const item = wishlist.value.find(w => w.product.id === productId)
  if (!item) return
  try {
    await userService.removeFromWishlist(item.id)
    wishlist.value = wishlist.value.filter(w => w.product.id !== productId)
    wishlistStore.removeByProductId(productId)
  } catch { /* silently fail */ }
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
        <!-- Loading skeletons -->
        <div v-if="isLoading" class="grid grid-cols-2 md:grid-cols-3 gap-4">
          <div v-for="i in 6" :key="i" class="bg-white rounded-xl animate-pulse">
            <div class="aspect-square bg-secondary-100 rounded-t-xl"></div>
            <div class="p-3 space-y-2">
              <div class="h-4 bg-secondary-100 rounded w-full"></div>
              <div class="h-4 bg-secondary-100 rounded w-2/3"></div>
              <div class="h-8 bg-secondary-100 rounded mt-3"></div>
            </div>
          </div>
        </div>

        <!-- Empty state -->
        <div v-else-if="wishlist.length === 0" class="bg-white rounded-2xl shadow-sm p-12 text-center">
          <div class="w-20 h-20 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
            <Heart class="w-9 h-9 text-red-300" />
          </div>
          <p class="text-secondary-600 font-medium mb-2">لیست علاقه‌مندی‌ها خالی است</p>
          <p class="text-secondary-400 text-sm mb-6">محصولات مورد علاقه‌تان را ذخیره کنید تا بعداً راحت‌تر پیدا کنید.</p>
          <RouterLink
            to="/products"
            class="inline-block px-6 py-2.5 bg-primary-500 hover:bg-primary-600 text-white rounded-xl font-medium transition text-sm"
          >
            مشاهده محصولات
          </RouterLink>
        </div>

        <!-- Grid -->
        <div v-else>
          <p class="text-sm text-secondary-500 mb-4">{{ wishlist.length }} محصول</p>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <ProductCard
              v-for="item in wishlist"
              :key="item.id"
              :product="item.product"
              :show-remove-from-wishlist="true"
              @remove-from-wishlist="handleRemove"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
