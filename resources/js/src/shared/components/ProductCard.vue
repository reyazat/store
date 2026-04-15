<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { Heart, ShoppingCart } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { userService } from '@modules/user/services'
import ProductQuickModal from '@shared/components/ProductQuickModal.vue'
import type { Product } from '@shared/types'

interface Props {
  product: Product
  viewMode?: 'grid' | 'list'
}

const props = withDefaults(defineProps<Props>(), {
  viewMode: 'grid'
})
const authStore = useAuthStore()

const showQuickModal = ref(false)

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('fa-IR').format(price)
}

const addToWishlist = async () => {
  if (!authStore.isAuthenticated) {
    alert('لطفاً ابتدا وارد شوید')
    return
  }
  try {
    await userService.addToWishlist(props.product.id)
    alert('محصول به لیست علاقه‌مندی‌ها اضافه شد')
  } catch (error) {
    console.error('Failed to add to wishlist:', error)
    alert('خطا در افزودن به لیست علاقه‌مندی‌ها')
  }
}
</script>

<template>
  <!-- Grid View -->
  <div
    v-if="viewMode === 'grid'"
    class="bg-white rounded-xl border border-secondary-100 hover:border-primary-200 hover:shadow-lg transition-all duration-300 group overflow-hidden"
  >
    <RouterLink :to="`/products/${product.slug}`" class="block relative">
      <!-- Image Container -->
      <div class="relative aspect-square bg-secondary-50 overflow-hidden">
        <img
          :src="product.main_image || '/images/placeholder.jpg'"
          :alt="product.name"
          class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
        />

        <!-- Discount Badge -->
        <span
          v-if="product.discount_percent"
          class="absolute top-2 right-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-lg"
        >
          {{ product.discount_percent }}٪
        </span>

        <!-- Quick Actions -->
        <div class="absolute top-2 left-2 flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
          <button
            @click.prevent="addToWishlist"
            class="w-8 h-8 bg-white shadow-md rounded-lg flex items-center justify-center text-secondary-400 hover:text-red-500 hover:bg-red-50 transition"
            title="افزودن به علاقه‌مندی‌ها"
          >
            <Heart class="w-4 h-4" />
          </button>
        </div>

        <!-- Out of Stock Overlay -->
        <div v-if="product.stock === 0" class="absolute inset-0 bg-secondary-900/60 flex items-center justify-center">
          <span class="bg-white text-secondary-700 px-4 py-2 rounded-lg font-medium text-sm">ناموجود</span>
        </div>
      </div>
    </RouterLink>

    <div class="p-3">
      <!-- Product Name -->
      <RouterLink :to="`/products/${product.slug}`">
        <h3 class="font-medium text-secondary-800 hover:text-primary-600 line-clamp-2 text-sm leading-6 min-h-[48px]">
          {{ product.name }}
        </h3>
      </RouterLink>

      <!-- Brand -->
      <p v-if="product.manufacturer" class="text-xs text-secondary-400 mt-1">
        {{ product.manufacturer }}
      </p>

      <!-- Price Section -->
      <div class="mt-3 pt-3 border-t border-secondary-100">
        <div class="flex items-end justify-between">
          <div>
            <p v-if="product.old_price" class="text-xs text-secondary-400 line-through">
              {{ formatPrice(product.old_price) }}
            </p>
            <p class="text-primary-600 font-bold">
              {{ formatPrice(product.price) }}
              <span class="text-xs font-normal text-secondary-500">تومان</span>
            </p>
          </div>

          <button
            @click.prevent="showQuickModal = true"
            :disabled="product.stock === 0"
            class="flex items-center gap-1 px-3 py-2 bg-primary-500 hover:bg-primary-600 disabled:bg-secondary-300 text-white text-xs font-medium rounded-lg transition"
            title="افزودن به سبد خرید"
          >
            <ShoppingCart class="w-4 h-4" />
            <span class="hidden sm:inline">خرید</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- List View -->
  <div
    v-else
    class="bg-white rounded-xl border border-secondary-100 hover:border-primary-200 hover:shadow-lg transition-all duration-300 group overflow-hidden"
  >
    <div class="flex gap-4 p-4">
      <!-- Image -->
      <RouterLink :to="`/products/${product.slug}`" class="block relative flex-shrink-0">
        <div class="w-32 h-32 md:w-40 md:h-40 bg-secondary-50 rounded-lg overflow-hidden">
          <img
            :src="product.main_image || '/images/placeholder.jpg'"
            :alt="product.name"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
        </div>

        <!-- Discount Badge -->
        <span
          v-if="product.discount_percent"
          class="absolute top-2 right-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-lg"
        >
          {{ product.discount_percent }}٪
        </span>
      </RouterLink>

      <!-- Content -->
      <div class="flex-1 flex flex-col justify-between">
        <div>
          <!-- Product Name -->
          <RouterLink :to="`/products/${product.slug}`">
            <h3 class="font-medium text-secondary-800 hover:text-primary-600 text-base mb-2">
              {{ product.name }}
            </h3>
          </RouterLink>

          <!-- Brand -->
          <p v-if="product.manufacturer" class="text-sm text-secondary-500 mb-2">
            {{ product.manufacturer }}
          </p>

          <!-- Stock Status -->
          <p v-if="product.stock === 0" class="text-sm text-red-500">
            ناموجود
          </p>
          <p v-else class="text-sm text-emerald-600">
            موجود
          </p>
        </div>

        <!-- Price & Actions -->
        <div class="flex items-end justify-between mt-4">
          <div>
            <p v-if="product.old_price" class="text-sm text-secondary-400 line-through">
              {{ formatPrice(product.old_price) }}
            </p>
            <p class="text-primary-600 font-bold text-lg">
              {{ formatPrice(product.price) }}
              <span class="text-sm font-normal text-secondary-500">تومان</span>
            </p>
          </div>

          <div class="flex items-center gap-2">
            <button
              @click.prevent="addToWishlist"
              class="w-10 h-10 bg-secondary-100 hover:bg-red-50 hover:text-red-500 rounded-lg flex items-center justify-center text-secondary-400 transition"
              title="افزودن به علاقه‌مندی‌ها"
            >
              <Heart class="w-5 h-5" />
            </button>
            <button
              @click.prevent="showQuickModal = true"
              :disabled="product.stock === 0"
              class="flex items-center gap-2 px-4 py-2 bg-primary-500 hover:bg-primary-600 disabled:bg-secondary-300 text-white text-sm font-medium rounded-lg transition"
              title="افزودن به سبد خرید"
            >
              <ShoppingCart class="w-4 h-4" />
              <span>افزودن به سبد</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <ProductQuickModal
    v-if="showQuickModal"
    :product-slug="product.slug"
    :product-name="product.name"
    :fallback-listing-id="product.listing_id"
    :fallback-price="product.price"
    :fallback-product-id="product.id"
    @close="showQuickModal = false"
  />
</template>
