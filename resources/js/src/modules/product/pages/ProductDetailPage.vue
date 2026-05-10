<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import {
  Heart, ShoppingCart, Minus, Plus, Package,
  Truck, CheckCircle, Shield, RotateCcw, Headphones, Star,
  ChevronLeft, ZoomIn,
} from 'lucide-vue-next'
import { Breadcrumb } from '@shared/components'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'
import { useWishlistStore } from '@/stores/wishlist'
import { productService } from '../services'
import { formatPrice } from '@shared/utils/format'
import { useProductVariants } from '../composables/useProductVariants'
import VariantSelector from '../components/VariantSelector.vue'
import RelatedProducts from '../components/RelatedProducts.vue'
import ProductReviews from '../components/ProductReviews.vue'
import type { Product } from '../types'

const route = useRoute()
const cartStore = useCartStore()
const authStore = useAuthStore()
const wishlistStore = useWishlistStore()

const product = ref<Product | null>(null)
const isLoading = ref(true)
const quantity = ref(1)
const selectedImage = ref(0)
const activeTab = ref<'specs' | 'review' | 'comments'>('specs')
const addedToCart = ref(false)

const isInWishlist = computed(() => product.value ? wishlistStore.has(product.value.id) : false)

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

const originalPrice = computed(() => {
  const listing = product.value?.listings?.[0]
  return listing?.base_price ?? 0
})

const discountPercent = computed((): number | null => {
  const p = product.value
  if (!p) return null
  const d = (p as any).discount_percent
  return d && d > 0 ? d : null
})

const breadcrumbItems = computed(() => {
  if (!product.value) return []
  const items: { title: string; url?: string }[] = [
    { title: 'محصولات', url: '/products' },
  ]
  if (product.value.categories?.length) {
    const cat = product.value.categories[0]
    items.push({ title: cat.name, url: `/products?category_slug=${cat.slug}` })
  }
  items.push({ title: product.value.name })
  return items
})

const canAddToCart = computed(() => {
  if (hasVariants.value) return isInStock.value && isVariantSelected.value
  return !!(product.value?.listings?.length)
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
  if (!product.value || !canAddToCart.value) return

  const listing = hasVariants.value && selectedVariant.value?.listing_variant
    ? (product.value.listings?.find(l => l.id === selectedVariant.value!.listing_variant!.listing_id) ?? product.value.listings?.[0])
    : product.value.listings?.[0]

  if (!listing) return

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

  addedToCart.value = true
  setTimeout(() => { addedToCart.value = false }, 2000)
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

watch(() => route.params.slug, () => {
  selectedImage.value = 0
  activeTab.value = 'specs'
  fetchProduct()
})

onMounted(fetchProduct)
</script>

<template>
  <div dir="rtl" class="min-h-screen bg-secondary-50">
    <!-- Loading skeleton -->
    <div v-if="isLoading" class="container mx-auto px-4 py-8">
      <div class="animate-pulse space-y-4">
        <div class="h-4 bg-secondary-200 rounded w-1/3"></div>
        <div class="grid lg:grid-cols-3 gap-6 mt-6">
          <div class="lg:col-span-1 aspect-square bg-secondary-200 rounded-2xl"></div>
          <div class="lg:col-span-1 space-y-4">
            <div class="h-6 bg-secondary-200 rounded w-3/4"></div>
            <div class="h-4 bg-secondary-200 rounded w-1/2"></div>
            <div class="h-10 bg-secondary-200 rounded w-1/3 mt-6"></div>
          </div>
          <div class="lg:col-span-1 bg-secondary-200 rounded-2xl h-64"></div>
        </div>
      </div>
    </div>

    <!-- Product detail -->
    <div v-else-if="product" class="container mx-auto px-4 py-6">
      <Breadcrumb :items="breadcrumbItems" class="mb-4" />

      <!-- Main grid: image | info | purchase card -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- ── Image gallery ── -->
        <div class="lg:col-span-4 order-1">
          <div class="bg-white rounded-2xl p-4 shadow-sm">
            <!-- Main image -->
            <div class="relative aspect-square rounded-xl overflow-hidden bg-secondary-50 mb-3 group">
              <img
                :src="product.images?.[selectedImage]?.file_name || product.main_image || '/images/placeholder.jpg'"
                :alt="product.name"
                class="w-full h-full object-contain transition-transform duration-300 group-hover:scale-105"
              />
              <!-- Discount badge -->
              <span
                v-if="discountPercent"
                class="absolute top-3 right-3 bg-red-500 text-white text-sm font-bold px-2.5 py-1 rounded-lg"
              >
                {{ discountPercent }}%
              </span>
              <!-- Out of stock overlay -->
              <div
                v-if="hasVariants && !isInStock"
                class="absolute inset-0 bg-black/50 flex items-center justify-center rounded-xl"
              >
                <span class="text-white text-lg font-bold">ناموجود</span>
              </div>
              <!-- Zoom hint -->
              <div class="absolute bottom-3 left-3 opacity-0 group-hover:opacity-100 transition">
                <ZoomIn class="w-5 h-5 text-secondary-500" />
              </div>
            </div>
            <!-- Thumbnails -->
            <div v-if="product.images && product.images.length > 1" class="flex gap-2 overflow-x-auto pb-1">
              <button
                v-for="(image, index) in product.images"
                :key="index"
                @click="selectedImage = index"
                :class="[
                  'flex-shrink-0 w-16 h-16 rounded-lg border-2 overflow-hidden transition',
                  selectedImage === index
                    ? 'border-primary-500 shadow-sm'
                    : 'border-secondary-200 hover:border-secondary-400',
                ]"
              >
                <img :src="image.file_name" :alt="`${product.name} ${index + 1}`" class="w-full h-full object-contain" />
              </button>
            </div>
          </div>
        </div>

        <!-- ── Product info ── -->
        <div class="lg:col-span-5 order-2">
          <div class="bg-white rounded-2xl p-5 shadow-sm space-y-4">
            <!-- Category -->
            <div v-if="product.categories?.length" class="flex items-center gap-1 text-xs text-secondary-500">
              <span v-for="(cat, i) in product.categories" :key="cat.id">
                <RouterLink
                  :to="{ path: '/products', query: { category_slug: cat.slug } }"
                  class="hover:text-primary-600 transition"
                >{{ cat.name }}</RouterLink>
                <span v-if="i < product.categories.length - 1"> · </span>
              </span>
            </div>

            <!-- Brand -->
            <p v-if="product.manufacturer" class="text-sm font-semibold text-primary-600">
              {{ product.manufacturer.name }}
            </p>

            <!-- Title -->
            <h1 class="text-xl md:text-2xl font-bold text-secondary-900 leading-snug">
              {{ product.name }}
            </h1>

            <!-- Short description -->
            <p v-if="product.short_description" class="text-sm text-secondary-500 leading-relaxed">
              {{ product.short_description }}
            </p>

            <!-- Stock badge -->
            <div class="flex items-center gap-2">
              <span
                v-if="!hasVariants || isInStock"
                class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-full"
              >
                <CheckCircle class="w-3.5 h-3.5" />
                موجود در انبار
              </span>
              <span
                v-else
                class="inline-flex items-center gap-1 text-xs font-medium text-red-600 bg-red-50 border border-red-200 px-2.5 py-1 rounded-full"
              >
                <Package class="w-3.5 h-3.5" />
                ناموجود
              </span>
              <span v-if="hasVariants && isInStock && currentStock <= 5" class="text-xs text-amber-600">
                (فقط {{ currentStock }} عدد باقی‌مانده)
              </span>
            </div>

            <!-- Variants -->
            <div v-if="hasVariants && availableOptions.length > 0">
              <VariantSelector
                :options="availableOptions"
                :selected-options="selectedOptions"
                @select="selectOption"
              />
            </div>

            <!-- Price (desktop only — shown in card on mobile) -->
            <div class="hidden lg:block">
              <div v-if="discountPercent" class="flex items-center gap-2 mb-1">
                <span class="text-secondary-400 line-through text-sm">
                  {{ formatPrice(originalPrice) }} تومان
                </span>
                <span class="bg-red-100 text-red-600 text-xs font-bold px-2 py-0.5 rounded">
                  {{ discountPercent }}% تخفیف
                </span>
              </div>
              <p class="text-3xl font-extrabold text-primary-600">
                {{ formatPrice(currentPrice) }}
                <span class="text-base font-normal text-secondary-500">تومان</span>
              </p>
            </div>

            <!-- Delivery info chips -->
            <div class="flex flex-wrap gap-2 pt-1">
              <span class="inline-flex items-center gap-1 text-xs text-secondary-600 bg-secondary-50 border border-secondary-100 px-2.5 py-1 rounded-full">
                <Truck class="w-3.5 h-3.5 text-primary-500" />
                ارسال سریع
              </span>
              <span class="inline-flex items-center gap-1 text-xs text-secondary-600 bg-secondary-50 border border-secondary-100 px-2.5 py-1 rounded-full">
                <Shield class="w-3.5 h-3.5 text-primary-500" />
                ضمانت اصالت
              </span>
              <span class="inline-flex items-center gap-1 text-xs text-secondary-600 bg-secondary-50 border border-secondary-100 px-2.5 py-1 rounded-full">
                <RotateCcw class="w-3.5 h-3.5 text-primary-500" />
                ۷ روز بازگشت
              </span>
            </div>
          </div>
        </div>

        <!-- ── Purchase card ── -->
        <div class="lg:col-span-3 order-3">
          <div class="bg-white rounded-2xl p-5 shadow-sm sticky top-24 space-y-4">
            <!-- Price -->
            <div>
              <div v-if="discountPercent" class="flex items-center gap-2 mb-1">
                <span class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded">
                  {{ discountPercent }}%
                </span>
                <span class="text-secondary-400 line-through text-sm">
                  {{ formatPrice(originalPrice) }}
                </span>
              </div>
              <p class="text-2xl font-extrabold text-secondary-900">
                {{ formatPrice(currentPrice) }}
                <span class="text-sm font-normal text-secondary-500">تومان</span>
              </p>
            </div>

            <!-- Quantity -->
            <div class="flex items-center gap-3">
              <span class="text-sm text-secondary-600">تعداد:</span>
              <div class="flex items-center border border-secondary-200 rounded-xl overflow-hidden">
                <button
                  @click="quantity = Math.max(1, quantity - 1)"
                  class="w-9 h-9 flex items-center justify-center hover:bg-secondary-50 transition text-secondary-600 disabled:opacity-40"
                  :disabled="!canAddToCart"
                >
                  <Minus class="w-4 h-4" />
                </button>
                <span class="w-10 text-center text-sm font-semibold">{{ quantity }}</span>
                <button
                  @click="quantity = Math.min(currentStock || 99, quantity + 1)"
                  class="w-9 h-9 flex items-center justify-center hover:bg-secondary-50 transition text-secondary-600 disabled:opacity-40"
                  :disabled="!canAddToCart"
                >
                  <Plus class="w-4 h-4" />
                </button>
              </div>
            </div>

            <!-- Add to cart -->
            <button
              @click="addToCart"
              :disabled="!canAddToCart"
              :class="[
                'w-full flex items-center justify-center gap-2 py-3.5 rounded-xl font-bold text-base transition',
                addedToCart
                  ? 'bg-emerald-500 text-white'
                  : canAddToCart
                    ? 'bg-primary-500 hover:bg-primary-600 text-white'
                    : 'bg-secondary-200 text-secondary-400 cursor-not-allowed',
              ]"
            >
              <CheckCircle v-if="addedToCart" class="w-5 h-5" />
              <ShoppingCart v-else class="w-5 h-5" />
              {{ addedToCart ? 'به سبد اضافه شد' : (hasVariants && !isVariantSelected ? 'گزینه‌ها را انتخاب کنید' : 'افزودن به سبد خرید') }}
            </button>

            <!-- Wishlist -->
            <button
              @click="toggleWishlist"
              :class="[
                'w-full flex items-center justify-center gap-2 py-2.5 rounded-xl border text-sm font-medium transition',
                isInWishlist
                  ? 'border-red-300 bg-red-50 text-red-500'
                  : 'border-secondary-200 text-secondary-600 hover:border-red-300 hover:text-red-500',
              ]"
            >
              <Heart :class="['w-4 h-4', isInWishlist ? 'fill-current' : '']" />
              {{ isInWishlist ? 'در لیست علاقه‌مندی‌ها' : 'افزودن به علاقه‌مندی‌ها' }}
            </button>

            <!-- Trust items -->
            <div class="border-t border-secondary-100 pt-4 space-y-2.5">
              <div class="flex items-center gap-2 text-xs text-secondary-600">
                <Shield class="w-4 h-4 text-emerald-500 flex-shrink-0" />
                ضمانت اصالت و سلامت فیزیکی کالا
              </div>
              <div class="flex items-center gap-2 text-xs text-secondary-600">
                <RotateCcw class="w-4 h-4 text-emerald-500 flex-shrink-0" />
                ۷ روز ضمانت بازگشت کالا
              </div>
              <div class="flex items-center gap-2 text-xs text-secondary-600">
                <Headphones class="w-4 h-4 text-emerald-500 flex-shrink-0" />
                پشتیبانی ۲۴ ساعته پس از خرید
              </div>
              <div class="flex items-center gap-2 text-xs text-secondary-600">
                <Truck class="w-4 h-4 text-emerald-500 flex-shrink-0" />
                ارسال سریع به سراسر کشور
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Trust badges bar ── -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
        <div
          v-for="badge in [
            { icon: Truck, label: 'تحویل سریع و آسان' },
            { icon: Headphones, label: 'خدمات پس از خرید', sub: '24 ساعته' },
            { icon: Shield, label: 'ضمانت اصالت' },
            { icon: RotateCcw, label: 'ضمانت بازگشت کالا', sub: '۷ روزه' },
          ]"
          :key="badge.label"
          class="bg-white rounded-xl p-4 flex flex-col items-center text-center shadow-sm"
        >
          <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center mb-2">
            <component :is="badge.icon" class="w-5 h-5 text-emerald-600" />
          </div>
          <span v-if="badge.sub" class="text-lg font-bold text-secondary-800">{{ badge.sub }}</span>
          <span class="text-xs text-secondary-500">{{ badge.label }}</span>
        </div>
      </div>

      <!-- ── Tabs ── -->
      <div class="bg-white rounded-2xl shadow-sm mt-6 overflow-hidden">
        <!-- Tab nav -->
        <div class="flex border-b border-secondary-100 overflow-x-auto">
          <button
            v-for="tab in [
              { key: 'specs', label: 'مشخصات' },
              { key: 'review', label: 'بررسی محصول' },
              { key: 'comments', label: 'نظر کاربران' },
            ]"
            :key="tab.key"
            @click="activeTab = tab.key as typeof activeTab"
            :class="[
              'flex-shrink-0 px-6 py-4 text-sm font-medium border-b-2 transition',
              activeTab === tab.key
                ? 'border-primary-500 text-primary-600'
                : 'border-transparent text-secondary-500 hover:text-secondary-700',
            ]"
          >
            {{ tab.label }}
          </button>
        </div>

        <!-- Tab: Specs -->
        <div v-show="activeTab === 'specs'" class="p-6">
          <div v-if="product.base_sku || product.manufacturer || product.weight" class="divide-y divide-secondary-100">
            <div v-if="product.base_sku" class="flex items-center justify-between py-3">
              <span class="text-sm text-secondary-500">کد محصول</span>
              <span class="text-sm font-medium text-secondary-800">{{ product.base_sku }}</span>
            </div>
            <div v-if="product.manufacturer" class="flex items-center justify-between py-3">
              <span class="text-sm text-secondary-500">برند</span>
              <span class="text-sm font-medium text-primary-600">{{ product.manufacturer.name }}</span>
            </div>
            <div v-if="product.categories?.length" class="flex items-center justify-between py-3">
              <span class="text-sm text-secondary-500">دسته‌بندی</span>
              <span class="text-sm font-medium text-secondary-800">{{ product.categories.map(c => c.name).join(' / ') }}</span>
            </div>
            <div v-if="product.weight" class="flex items-center justify-between py-3">
              <span class="text-sm text-secondary-500">وزن</span>
              <span class="text-sm font-medium text-secondary-800">{{ product.weight }} کیلوگرم</span>
            </div>
            <div class="flex items-center justify-between py-3">
              <span class="text-sm text-secondary-500">گارانتی</span>
              <span class="text-sm font-medium text-secondary-800">گارانتی دارد</span>
            </div>
          </div>
          <p v-else class="text-sm text-secondary-400 text-center py-8">مشخصاتی ثبت نشده است</p>
        </div>

        <!-- Tab: Review -->
        <div v-show="activeTab === 'review'" class="p-6">
          <div v-if="product.description" class="prose prose-sm max-w-none text-secondary-700 leading-relaxed">
            <p class="whitespace-pre-line">{{ product.description }}</p>
          </div>
          <div v-else class="text-center py-12">
            <Star class="w-10 h-10 text-secondary-200 mx-auto mb-3" />
            <p class="text-sm text-secondary-400">بررسی‌ای برای این محصول ثبت نشده است</p>
          </div>
        </div>

        <!-- Tab: Comments -->
        <div v-show="activeTab === 'comments'" class="p-6">
          <ProductReviews :product-slug="product.slug" />
        </div>
      </div>

      <!-- ── Related products ── -->
      <RelatedProducts :product-slug="product.slug" />
    </div>

    <!-- Not found -->
    <div v-else-if="!isLoading" class="container mx-auto px-4 py-24 text-center">
      <Package class="w-16 h-16 text-secondary-300 mx-auto mb-4" />
      <p class="text-secondary-500 text-lg">محصول یافت نشد</p>
      <RouterLink to="/products" class="mt-4 inline-flex items-center gap-1 text-primary-600 hover:underline text-sm">
        <ChevronLeft class="w-4 h-4" />
        بازگشت به محصولات
      </RouterLink>
    </div>

    <!-- Mobile sticky bottom bar -->
    <div
      v-if="product && !isLoading"
      class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white border-t border-secondary-200 px-4 py-3 flex items-center gap-3"
    >
      <div class="flex-1">
        <div v-if="discountPercent" class="flex items-center gap-1.5">
          <span class="bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded">{{ discountPercent }}%</span>
          <span class="text-secondary-400 line-through text-xs">{{ formatPrice(originalPrice) }}</span>
        </div>
        <p class="text-base font-bold text-secondary-900">
          {{ formatPrice(currentPrice) }}
          <span class="text-xs font-normal text-secondary-500">تومان</span>
        </p>
      </div>
      <button
        @click="addToCart"
        :disabled="!canAddToCart"
        :class="[
          'flex items-center gap-2 px-5 py-3 rounded-xl font-bold text-sm transition',
          addedToCart
            ? 'bg-emerald-500 text-white'
            : canAddToCart
              ? 'bg-primary-500 hover:bg-primary-600 text-white'
              : 'bg-secondary-200 text-secondary-400 cursor-not-allowed',
        ]"
      >
        <CheckCircle v-if="addedToCart" class="w-4 h-4" />
        <ShoppingCart v-else class="w-4 h-4" />
        {{ addedToCart ? 'اضافه شد' : 'افزودن به سبد' }}
      </button>
    </div>
  </div>
</template>
