<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { X, ShoppingCart, Loader2 } from 'lucide-vue-next'
import { useCartStore } from '@/stores/cart'
import { useProductVariants } from '@modules/product/composables/useProductVariants'
import VariantSelector from '@modules/product/components/VariantSelector.vue'
import { productService } from '@modules/product/services'
import { formatPrice } from '@shared/utils/format'
import type { Product } from '@modules/product/types'

interface Props {
  productSlug: string
  productName: string
  fallbackListingId?: number
  fallbackPrice?: number
  fallbackProductId?: number
}

const props = defineProps<Props>()
const emit = defineEmits<{ close: [] }>()

const cartStore = useCartStore()
const product = ref<Product | null>(null)
const isLoading = ref(true)

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

const quantity = ref(1)

watch(currentStock, (stock) => {
  if (quantity.value > stock && stock > 0) quantity.value = stock
})

const addToCart = () => {
  if (hasVariants.value && !isVariantSelected.value) return
  if (hasVariants.value && !isInStock.value) return

  const listing = hasVariants.value && selectedVariant.value?.listing_variant
    ? (product.value?.listings?.find(l => l.id === selectedVariant.value!.listing_variant!.listing_id) ?? product.value?.listings?.[0])
    : product.value?.listings?.[0]

  const listingId = listing?.id ?? props.fallbackListingId
  const price = currentPrice.value || props.fallbackPrice || 0
  const productId = product.value?.id ?? props.fallbackProductId

  if (!listingId || !productId) return

  cartStore.addItem({
    id: listingId,
    productId,
    listingId,
    variantId: selectedVariant.value?.id,
    name: product.value?.name ?? props.productName,
    price,
    image: product.value?.main_image,
    quantity: quantity.value,
  })

  emit('close')
}

const fetchProduct = async () => {
  isLoading.value = true
  try {
    const response = await productService.getProduct(props.productSlug)
    product.value = response.data
  } finally {
    isLoading.value = false
  }
}

fetchProduct()
</script>

<template>
  <Teleport to="body">
    <div
      class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
      @click.self="emit('close')"
    >
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-black/50" @click="emit('close')" />

      <!-- Modal -->
      <div class="relative bg-white w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl shadow-2xl z-10 max-h-[90vh] overflow-y-auto">
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b sticky top-0 bg-white rounded-t-2xl sm:rounded-t-2xl z-10">
          <h3 class="font-bold text-gray-800 text-base line-clamp-1">{{ productName }}</h3>
          <button
            @click="emit('close')"
            class="p-1.5 hover:bg-gray-100 rounded-lg transition"
          >
            <X class="w-5 h-5 text-gray-500" />
          </button>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="flex items-center justify-center py-16">
          <Loader2 class="w-8 h-8 animate-spin text-primary-500" />
        </div>

        <div v-else-if="product" class="p-4 space-y-4">
          <!-- Product preview -->
          <div class="flex gap-3">
            <img
              :src="product.main_image || '/images/placeholder.jpg'"
              :alt="product.name"
              class="w-20 h-20 object-cover rounded-lg border border-gray-100 flex-shrink-0"
            />
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-gray-800 text-sm line-clamp-2">{{ product.name }}</p>
              <p v-if="product.manufacturer" class="text-xs text-gray-400 mt-1">{{ product.manufacturer.name }}</p>
              <p class="text-primary-600 font-bold mt-2">
                {{ formatPrice(currentPrice) }}
                <span class="text-xs font-normal text-gray-500">تومان</span>
              </p>
            </div>
          </div>

          <!-- Variant selector -->
          <div v-if="hasVariants && availableOptions.length > 0" class="bg-gray-50 rounded-xl p-3">
            <VariantSelector
              :options="availableOptions"
              :selected-options="selectedOptions"
              @select="selectOption"
            />
          </div>

          <!-- Stock status -->
          <div v-if="hasVariants" class="text-sm">
            <span v-if="isVariantSelected" :class="isInStock ? 'text-green-600' : 'text-red-500'">
              {{ isInStock ? `موجود (${currentStock} عدد)` : 'ناموجود' }}
            </span>
            <span v-else class="text-amber-600">لطفاً گزینه‌ها را انتخاب کنید</span>
          </div>

          <!-- Quantity -->
          <div v-if="!hasVariants || isInStock" class="flex items-center gap-3">
            <span class="text-sm text-gray-600">تعداد:</span>
            <div class="flex items-center border rounded-lg overflow-hidden">
              <button
                @click="quantity = Math.max(1, quantity - 1)"
                class="px-3 py-1.5 hover:bg-gray-100 transition text-lg leading-none"
              >−</button>
              <span class="w-10 text-center text-sm font-medium">{{ quantity }}</span>
              <button
                @click="quantity = Math.min(currentStock || 999, quantity + 1)"
                class="px-3 py-1.5 hover:bg-gray-100 transition text-lg leading-none"
              >+</button>
            </div>
          </div>

          <!-- Add to cart -->
          <button
            @click="addToCart"
            :disabled="(hasVariants && !isVariantSelected) || (hasVariants && !isInStock)"
            class="w-full flex items-center justify-center gap-2 py-3 rounded-xl font-medium transition"
            :class="(hasVariants && !isVariantSelected) || (hasVariants && !isInStock)
              ? 'bg-gray-200 text-gray-400 cursor-not-allowed'
              : 'bg-primary-600 hover:bg-primary-700 text-white'"
          >
            <ShoppingCart class="w-5 h-5" />
            {{ hasVariants && !isVariantSelected ? 'ابتدا گزینه‌ها را انتخاب کنید' : 'افزودن به سبد خرید' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
