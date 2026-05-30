<script setup lang="ts">
import { ref, computed } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { Heart, ShoppingCart, Trash2 } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useWishlistStore } from '@/stores/wishlist'
import { formatPrice } from '@shared/utils/format'
import ProductQuickModal from '@shared/components/ProductQuickModal.vue'
import type { Product } from '@shared/types'

interface Props {
  product: Product
  viewMode?: 'grid' | 'list'
  showRemoveFromWishlist?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  viewMode: 'grid',
  showRemoveFromWishlist: false,
})

const emit = defineEmits<{
  removeFromWishlist: [productId: number]
}>()

const router         = useRouter()
const authStore      = useAuthStore()
const wishlistStore  = useWishlistStore()

const showQuickModal = ref(false)
const isWishlistLoading = ref(false)

const isInWishlist = computed(() => wishlistStore.has(props.product.id))

const handleWishlist = async () => {
  if (!authStore.isAuthenticated) {
    router.push('/login')
    return
  }
  if (props.showRemoveFromWishlist) {
    emit('removeFromWishlist', props.product.id)
    return
  }
  isWishlistLoading.value = true
  try {
    await wishlistStore.toggle(props.product.id)
  } catch {
    /* silently fail */
  } finally {
    isWishlistLoading.value = false
  }
}
</script>

<template>
  <!-- ── GRID VIEW ── -->
  <div
    v-if="viewMode === 'grid'"
    class="relative bg-white rounded-xl border border-secondary-100 hover:border-primary-200 hover:shadow-lg transition-all duration-300 group overflow-hidden flex flex-col"
  >
    <RouterLink :to="`/products/${product.slug}`" class="block relative">
      <div class="relative aspect-square bg-secondary-50 overflow-hidden">
        <img
          :src="product.main_image || '/images/placeholder.jpg'"
          :alt="product.name"
          class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          loading="lazy"
        />

        <!-- Discount Badge -->
        <span
          v-if="product.discount_percent"
          class="absolute top-2 right-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-lg shadow-sm"
        >
          {{ product.discount_percent }}٪
        </span>

        <!-- Out of Stock Overlay -->
        <div v-if="product.stock === 0" class="absolute inset-0 bg-secondary-900/60 flex items-center justify-center">
          <span class="bg-white text-secondary-700 px-4 py-2 rounded-lg font-medium text-sm">ناموجود</span>
        </div>
      </div>
    </RouterLink>

    <!-- Wishlist / Remove button -->
    <button
      @click.prevent="handleWishlist"
      :disabled="isWishlistLoading"
      :aria-label="showRemoveFromWishlist ? 'حذف از علاقه‌مندی‌ها' : isInWishlist ? 'حذف از علاقه‌مندی‌ها' : 'افزودن به علاقه‌مندی‌ها'"
      :class="[
        'absolute top-2 left-2 w-8 h-8 rounded-lg flex items-center justify-center shadow-md transition z-10',
        showRemoveFromWishlist
          ? 'bg-white hover:bg-red-50 text-red-400 hover:text-red-600'
          : isInWishlist
            ? 'bg-red-500 text-white hover:bg-red-600'
            : 'bg-white text-secondary-300 hover:text-red-500 hover:bg-red-50 opacity-0 group-hover:opacity-100',
      ]"
    >
      <Trash2 v-if="showRemoveFromWishlist" class="w-4 h-4" />
      <Heart v-else :class="['w-4 h-4', isInWishlist ? 'fill-current' : '']" />
    </button>

    <div class="p-3 flex flex-col flex-1">
      <RouterLink :to="`/products/${product.slug}`">
        <h3 class="font-medium text-secondary-800 hover:text-primary-600 line-clamp-2 text-sm leading-6 min-h-[48px]">
          {{ product.name }}
        </h3>
      </RouterLink>

      <p v-if="product.manufacturer" class="text-xs text-secondary-400 mt-1">
        {{ product.manufacturer }}
      </p>

      <!-- Price + Add to cart -->
      <div class="mt-auto pt-3 border-t border-secondary-100">
        <div class="flex items-end justify-between gap-2">
          <div class="min-w-0">
            <p v-if="product.old_price" class="text-xs text-secondary-400 line-through leading-none mb-0.5">
              {{ formatPrice(product.old_price) }}
            </p>
            <p class="text-primary-600 font-bold text-sm leading-none">
              {{ formatPrice(product.price) }}
              <span class="text-xs font-normal text-secondary-500">تومان</span>
            </p>
          </div>

          <button
            @click.prevent="showQuickModal = true"
            :disabled="product.stock === 0"
            aria-label="افزودن به سبد خرید"
            class="flex-shrink-0 flex items-center gap-1 px-3 py-2 bg-primary-500 hover:bg-primary-600 disabled:bg-secondary-200 disabled:cursor-not-allowed text-white text-xs font-medium rounded-lg transition"
          >
            <ShoppingCart class="w-4 h-4" />
            <span class="hidden sm:inline">خرید</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ── LIST VIEW ── -->
  <div
    v-else
    class="relative bg-white rounded-xl border border-secondary-100 hover:border-primary-200 hover:shadow-md transition-all duration-300 group overflow-hidden"
  >
    <div class="flex gap-4 p-4">
      <!-- Image -->
      <RouterLink :to="`/products/${product.slug}`" class="block relative flex-shrink-0">
        <div class="w-32 h-32 md:w-40 md:h-40 bg-secondary-50 rounded-xl overflow-hidden">
          <img
            :src="product.main_image || '/images/placeholder.jpg'"
            :alt="product.name"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            loading="lazy"
          />
        </div>
        <span
          v-if="product.discount_percent"
          class="absolute top-2 right-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-lg"
        >
          {{ product.discount_percent }}٪
        </span>
      </RouterLink>

      <!-- Content -->
      <div class="flex-1 flex flex-col justify-between min-w-0">
        <div>
          <RouterLink :to="`/products/${product.slug}`">
            <h3 class="font-semibold text-secondary-800 hover:text-primary-600 text-base mb-1 line-clamp-2">
              {{ product.name }}
            </h3>
          </RouterLink>

          <p v-if="product.manufacturer" class="text-sm text-secondary-400 mb-2">
            {{ product.manufacturer }}
          </p>

          <span
            :class="[
              'inline-flex items-center text-xs font-medium px-2 py-0.5 rounded-full',
              product.stock === 0 ? 'bg-red-50 text-red-500' : 'bg-emerald-50 text-emerald-600',
            ]"
          >
            {{ product.stock === 0 ? 'ناموجود' : 'موجود در انبار' }}
          </span>
        </div>

        <div class="flex items-end justify-between mt-4 gap-3">
          <div>
            <p v-if="product.old_price" class="text-sm text-secondary-400 line-through leading-none mb-1">
              {{ formatPrice(product.old_price) }} تومان
            </p>
            <p class="text-primary-600 font-bold text-lg leading-none">
              {{ formatPrice(product.price) }}
              <span class="text-sm font-normal text-secondary-500">تومان</span>
            </p>
          </div>

          <div class="flex items-center gap-2 flex-shrink-0">
            <button
              @click.prevent="handleWishlist"
              :disabled="isWishlistLoading"
              :aria-label="isInWishlist ? 'حذف از علاقه‌مندی‌ها' : 'افزودن به علاقه‌مندی‌ها'"
              :class="[
                'w-10 h-10 rounded-xl flex items-center justify-center transition',
                showRemoveFromWishlist || isInWishlist
                  ? 'bg-red-50 text-red-500 hover:bg-red-100'
                  : 'bg-secondary-100 text-secondary-400 hover:bg-red-50 hover:text-red-500',
              ]"
            >
              <Trash2 v-if="showRemoveFromWishlist" class="w-5 h-5" />
              <Heart v-else :class="['w-5 h-5', isInWishlist ? 'fill-current text-red-500' : '']" />
            </button>

            <button
              @click.prevent="showQuickModal = true"
              :disabled="product.stock === 0"
              aria-label="افزودن به سبد خرید"
              class="flex items-center gap-2 px-4 py-2.5 bg-primary-500 hover:bg-primary-600 disabled:bg-secondary-200 disabled:cursor-not-allowed text-white text-sm font-medium rounded-xl transition"
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

