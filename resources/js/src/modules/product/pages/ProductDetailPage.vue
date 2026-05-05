<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { Heart, ShoppingCart, Minus, Plus, Package, Truck, CheckCircle } from 'lucide-vue-next'
import { Breadcrumb } from '@shared/components'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'
import { useWishlistStore } from '@/stores/wishlist'
import { productService } from '../services'
import { formatPrice } from '@shared/utils/format'
import { useProductVariants } from '../composables/useProductVariants'
import VariantSelector from '../components/VariantSelector.vue'
import type { Product } from '../types'

const route = useRoute()
const cartStore = useCartStore()
const authStore = useAuthStore()
const wishlistStore = useWishlistStore()

const isInWishlist = computed(() => product.value ? wishlistStore.has(product.value.id) : false)

const product = ref<Product | null>(null)
const isLoading = ref(true)
const quantity = ref(1)
const selectedImage = ref(0)

// Composable runs once at setup with the reactive product ref —
// all returned values are themselves reactive refs/computeds
const {
  selectedOptions,
  selectedVariant,
  availableOptions,
  selectOption,
  currentPrice,
  currentStock,
  isInStock,
  isVariantSelected,
} = useProductVariants(product)

const hasVariants = computed(() =>
  !!(product.value?.variants && product.value.variants.length > 0)
)

const breadcrumbItems = computed(() => {
  if (!product.value) return []
  return [
    { title: 'محصولات', url: '/products' },
    { title: product.value.name },
  ]
})

const fetchProduct = async () => {
  isLoading.value = true
  try {
    const response = await productService.getProduct(route.params.slug as string)
    product.value = response.data
  } catch (error) {
    console.error('Failed to load product:', error)
  } finally {
    isLoading.value = false
  }
}

const addToCart = () => {
  if (!product.value) return

  if (hasVariants.value && !isVariantSelected.value) {
    alert('لطفاً تمام گزینه‌ها را انتخاب کنید')
    return
  }

  if (hasVariants.value && !isInStock.value) {
    alert('این محصول موجود نیست')
    return
  }

  const listing = hasVariants.value && selectedVariant.value?.listing_variant
    ? (product.value.listings?.find(l => l.id === selectedVariant.value!.listing_variant!.listing_id) ?? product.value.listings?.[0])
    : product.value.listings?.[0]

  if (!listing) {
    alert('این محصول در حال حاضر قابل خرید نیست')
    return
  }

  cartStore.addItem({
    id: listing.id,
    productId: product.value.id,
    listingId: listing.id,
    variantId: selectedVariant.value?.id,
    name: product.value.name,
    price: currentPrice.value,
    image: product.value.main_image,
    quantity: quantity.value,
  })
}

const toggleWishlist = async () => {
  if (!authStore.isAuthenticated || !product.value) return
  try {
    await wishlistStore.toggle(product.value.id)
  } catch (error) {
    console.error('Failed to toggle wishlist:', error)
  }
}

watch(selectedImage, (newIndex) => {
  if (product.value?.images && newIndex >= product.value.images.length) {
    selectedImage.value = 0
  }
})

onMounted(fetchProduct)
</script>

<template>
  <div class="container mx-auto px-4 py-8">
    <!-- Loading -->
    <div v-if="isLoading" class="animate-pulse">
      <div class="h-4 bg-gray-200 rounded w-1/4 mb-8"></div>
      <div class="grid md:grid-cols-2 gap-8">
        <div class="aspect-square bg-gray-200 rounded-lg"></div>
        <div class="space-y-4">
          <div class="h-8 bg-gray-200 rounded w-3/4"></div>
          <div class="h-4 bg-gray-200 rounded w-1/2"></div>
          <div class="h-6 bg-gray-200 rounded w-1/4"></div>
        </div>
      </div>
    </div>
    
    <!-- Product Detail -->
    <div v-else-if="product">
      <Breadcrumb :items="breadcrumbItems" />
      
      <div class="grid md:grid-cols-2 gap-8 mt-8">
        <!-- Images -->
        <div>
          <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden mb-4 relative">
            <img 
              :src="product.images?.[selectedImage]?.file_name || product.main_image || '/images/placeholder.jpg'" 
              :alt="product.name"
              class="w-full h-full object-cover"
            />
            <div v-if="!isInStock && hasVariants" class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
              <span class="text-white text-xl font-bold">ناموجود</span>
            </div>
          </div>
          <div v-if="product.images && product.images.length > 1" class="flex gap-2 overflow-x-auto">
            <button 
              v-for="(image, index) in product.images" 
              :key="index"
              @click="selectedImage = index"
              :class="[
                'w-20 h-20 rounded border-2 overflow-hidden flex-shrink-0',
                selectedImage === index ? 'border-primary-600' : 'border-gray-200'
              ]"
            >
              <img :src="image.file_name" :alt="`${product.name} ${index + 1}`" class="w-full h-full object-cover" />
            </button>
          </div>
        </div>
        
        <!-- Info -->
        <div>
          <h1 class="text-2xl font-bold mb-2">{{ product.name }}</h1>
          
          <p v-if="product.manufacturer" class="text-gray-500 mb-4">
            برند: {{ product.manufacturer.name }}
          </p>
          
          <!-- Stock Status -->
          <div v-if="hasVariants" class="flex items-center gap-2 mb-4">
            <CheckCircle v-if="isInStock" class="w-5 h-5 text-green-500" />
            <Package v-else class="w-5 h-5 text-red-500" />
            <span :class="isInStock ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium">
              {{ isInStock ? `موجود (${currentStock} عدد)` : 'ناموجود' }}
            </span>
          </div>
          
          <!-- Variant Selector -->
          <div v-if="hasVariants && availableOptions.length > 0" class="mb-6 p-4 bg-gray-50 rounded-lg">
            <VariantSelector
              :options="availableOptions"
              :selected-options="selectedOptions"
              @select="selectOption"
            />
          </div>
          
          <!-- Price -->
          <div class="flex items-baseline gap-4 mb-6">
            <span class="text-3xl font-bold text-primary-600">
              {{ formatPrice(currentPrice) }} تومان
            </span>
            <span v-if="selectedVariant?.sku" class="text-sm text-gray-500">
              کد: {{ selectedVariant.sku }}
            </span>
          </div>
          
          <!-- Quantity -->
          <div class="flex items-center gap-4 mb-6">
            <span class="text-gray-700">تعداد:</span>
            <div class="flex items-center border rounded-lg">
              <button 
                @click="quantity = Math.max(1, quantity - 1)"
                class="p-2 hover:bg-gray-100 disabled:opacity-50"
                :disabled="!isInStock && hasVariants"
              >
                <Minus class="w-5 h-5" />
              </button>
              <span class="w-12 text-center">{{ quantity }}</span>
              <button 
                @click="quantity = Math.min(currentStock || 999, quantity + 1)"
                class="p-2 hover:bg-gray-100 disabled:opacity-50"
                :disabled="!isInStock && hasVariants"
              >
                <Plus class="w-5 h-5" />
              </button>
            </div>
            <span v-if="hasVariants && currentStock > 0" class="text-sm text-gray-500">
              حداکثر {{ currentStock }} عدد
            </span>
          </div>
          
          <!-- Actions -->
          <div class="flex gap-4 mb-8">
            <button 
              @click="addToCart"
              :disabled="(hasVariants && !isInStock) || (hasVariants && !isVariantSelected)"
              :class="[
                'flex-1 flex items-center justify-center gap-2 py-3 rounded-lg transition',
                (hasVariants && !isInStock) || (hasVariants && !isVariantSelected)
                  ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                  : 'bg-primary-600 text-white hover:bg-primary-700'
              ]"
            >
              <ShoppingCart class="w-5 h-5" />
              {{ hasVariants && !isVariantSelected ? 'لطفاً گزینه‌ها را انتخاب کنید' : 'افزودن به سبد خرید' }}
            </button>
            <button
              @click="toggleWishlist"
              :class="[
                'p-3 border rounded-lg transition',
                isInWishlist
                  ? 'bg-red-50 border-red-200 text-red-500'
                  : 'hover:bg-gray-100 hover:text-red-500 hover:border-red-200',
              ]"
              :title="isInWishlist ? 'حذف از علاقه‌مندی‌ها' : 'افزودن به علاقه‌مندی‌ها'"
            >
              <Heart :class="['w-5 h-5', isInWishlist ? 'fill-current' : '']" />
            </button>
          </div>
          
          <!-- Features -->
          <div class="mb-6 space-y-2">
            <div class="flex items-center gap-2 text-sm text-gray-600">
              <Truck class="w-4 h-4" />
              <span>ارسال رایگان برای خریدهای بالای 500,000 تومان</span>
            </div>
            <div class="flex items-center gap-2 text-sm text-gray-600">
              <CheckCircle class="w-4 h-4" />
              <span>گارانتی اصالت و سلامت فیزیکی کالا</span>
            </div>
          </div>
          
          <!-- Description -->
          <div v-if="product.description" class="prose prose-sm max-w-none border-t pt-6">
            <h3 class="font-bold mb-2">توضیحات</h3>
            <p class="text-gray-600">{{ product.description }}</p>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Not Found -->
    <div v-else class="text-center py-16">
      <p class="text-gray-500 text-lg">محصول یافت نشد</p>
    </div>
  </div>
</template>
