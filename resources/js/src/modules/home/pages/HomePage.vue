<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { RouterLink } from 'vue-router'
import { ChevronLeft, ChevronRight, Star, Clock, TrendingUp } from 'lucide-vue-next'
import CategorySection from '../components/CategorySection.vue'
import DiscountProductsSection from '../components/DiscountProductsSection.vue'
import ProductsSection from '../components/ProductsSection.vue'
import ServicesBar from '../components/ServicesBar.vue'
import PromoBannersSection from '../components/PromoBannersSection.vue'
import NewsletterSection from '../components/NewsletterSection.vue'
import { homeService, type SiteFeature, type PromoBanner } from '../services'
import type { Product, Category } from '@shared/types'

const featuredProducts = ref<Product[]>([])
const newProducts = ref<Product[]>([])
const discountedProducts = ref<Product[]>([])
const specialOfferProducts = ref<Product[]>([])
const categories = ref<Category[]>([])
const siteFeatures = ref<SiteFeature[]>([])
const promoBanners = ref<PromoBanner[]>([])
const isLoading = ref(true)

type Slide = {
  id: number
  title: string
  subtitle?: string
  image?: string
  link?: string
  button_text?: string
  button_link?: string
}

const currentSlide = ref(0)
const slides = ref<Slide[]>([])

let slideInterval: ReturnType<typeof setInterval> | null = null

function startAutoSlide() {
  if (slideInterval) clearInterval(slideInterval)
  if (slides.value.length > 1) {
    slideInterval = setInterval(() => {
      currentSlide.value = (currentSlide.value + 1) % slides.value.length
    }, 5000)
  }
}

onMounted(async () => {
  try {
    const [
      productsRes,
      discountedRes,
      topSubcategoriesRes,
      slidersData,
      features,
      banners,
      newestRes,
      specialOfferRes,
    ] = await Promise.all([
      homeService.getFeaturedProducts(),
      homeService.getDiscountedProducts(8),
      homeService.getTopSubcategories(),
      homeService.getSliders(),
      homeService.getSiteFeatures(),
      homeService.getPromoBanners('home'),
      homeService.getNewestProducts(10),
      homeService.getSpecialOfferProducts(8),
    ])

    featuredProducts.value = productsRes.data || []
    newProducts.value = newestRes.data || []
    discountedProducts.value = discountedRes.data || []
    categories.value = topSubcategoriesRes.data || []
    slides.value = slidersData
    siteFeatures.value = features
    promoBanners.value = banners
    specialOfferProducts.value = specialOfferRes.data || []
  } catch (error) {
    console.error('Failed to load home data:', error)
  } finally {
    isLoading.value = false
    startAutoSlide()
  }
})

const hasSiteFeatures = computed(() => siteFeatures.value.length > 0)
const hasPromoBanners = computed(() => promoBanners.value.length > 0)
</script>

<template>
  <div class="bg-secondary-50">
    <!-- Hero Slider -->
    <section class="relative overflow-hidden bg-secondary-800">
      <div class="relative h-[300px] md:h-[420px]">

        <!-- Loading skeleton -->
        <div v-if="isLoading" class="absolute inset-0 animate-pulse bg-gradient-to-l from-primary-500 to-primary-700"></div>

        <!-- Empty state (no slides configured) -->
        <div
          v-else-if="slides.length === 0"
          class="absolute inset-0 bg-gradient-to-l from-primary-600 to-primary-800 flex items-center justify-center"
        >
          <div class="text-center text-white">
            <h1 class="text-3xl md:text-5xl font-bold mb-3">به فروشگاه ما خوش آمدید</h1>
            <RouterLink to="/products" class="inline-flex items-center gap-2 bg-white text-primary-700 px-8 py-3 rounded-full font-bold hover:bg-primary-50 transition shadow-lg mt-4">
              مشاهده محصولات <ChevronLeft class="w-5 h-5" />
            </RouterLink>
          </div>
        </div>

        <!-- Slides -->
        <transition-group v-else name="fade">
          <div
            v-for="(slide, index) in slides"
            :key="slide.id"
            v-show="currentSlide === index"
            class="absolute inset-0"
          >
            <!-- Background image or gradient -->
            <img
              v-if="slide.image"
              :src="slide.image"
              :alt="slide.title"
              class="absolute inset-0 w-full h-full object-cover"
            />
            <div
              v-else
              class="absolute inset-0 bg-gradient-to-l from-primary-500 to-primary-700"
            ></div>
            <!-- Overlay for readability when image is present -->
            <div v-if="slide.image" class="absolute inset-0 bg-black/40"></div>

            <!-- Content -->
            <div class="relative z-10 container mx-auto px-4 h-full flex items-center">
              <div class="max-w-lg text-white">
                <h1 class="text-3xl md:text-5xl font-bold mb-3 drop-shadow">{{ slide.title }}</h1>
                <p v-if="slide.subtitle" class="text-xl md:text-2xl mb-6 opacity-90 drop-shadow">
                  {{ slide.subtitle }}
                </p>
                <component
                  :is="slide.button_link ? RouterLink : 'span'"
                  :to="slide.button_link || undefined"
                  v-if="slide.button_text"
                  class="inline-flex items-center gap-2 bg-white text-secondary-800 px-8 py-3 rounded-full font-bold hover:bg-secondary-100 transition shadow-lg"
                >
                  {{ slide.button_text }}
                  <ChevronLeft class="w-5 h-5" />
                </component>
              </div>
            </div>
            <!-- Decorative circles (shown when no image) -->
            <template v-if="!slide.image">
              <div class="absolute top-10 left-10 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
              <div class="absolute bottom-0 right-0 w-96 h-96 bg-white/5 rounded-full"></div>
            </template>
          </div>
        </transition-group>

        <!-- Dots -->
        <div v-if="slides.length > 1" class="absolute bottom-5 left-1/2 -translate-x-1/2 flex gap-2 z-20">
          <button
            v-for="(slide, index) in slides"
            :key="slide.id"
            @click="currentSlide = index"
            :class="['h-2.5 rounded-full transition-all duration-300', currentSlide === index ? 'bg-white w-7' : 'bg-white/50 w-2.5']"
          ></button>
        </div>

        <!-- Prev / Next arrows -->
        <template v-if="slides.length > 1">
          <button
            @click="currentSlide = (currentSlide - 1 + slides.length) % slides.length"
            class="absolute right-4 top-1/2 -translate-y-1/2 z-20 w-10 h-10 bg-white/20 hover:bg-white/40 backdrop-blur-sm text-white rounded-full flex items-center justify-center transition"
          >
            <ChevronRight class="w-6 h-6" />
          </button>
          <button
            @click="currentSlide = (currentSlide + 1) % slides.length"
            class="absolute left-4 top-1/2 -translate-y-1/2 z-20 w-10 h-10 bg-white/20 hover:bg-white/40 backdrop-blur-sm text-white rounded-full flex items-center justify-center transition"
          >
            <ChevronLeft class="w-6 h-6" />
          </button>
        </template>

      </div>
    </section>
    
    <!-- Quick Categories -->
    <section class="py-6 bg-white shadow-sm">
      <div class="container mx-auto px-4">
        <CategorySection :categories="categories" :loading="isLoading" />
      </div>
    </section>

    <!-- Services Bar -->
    <ServicesBar v-if="hasSiteFeatures || isLoading" :features="siteFeatures" :loading="isLoading" />

    <!-- Discount Products -->
    <section class="py-4">
      <div class="container mx-auto px-4">
        <DiscountProductsSection :products="discountedProducts" :loading="isLoading" />
      </div>
    </section>

    <!-- Featured Products -->
    <ProductsSection
      title="محصولات پرفروش"
      :icon="Star"
      icon-class="text-amber-500"
      :products="featuredProducts"
      view-all-link="/products"
      :loading="isLoading"
    />

    <!-- Promotional Banners -->
    <PromoBannersSection v-if="hasPromoBanners" :banners="promoBanners" />

    <!-- New Products -->
    <ProductsSection
      title="جدیدترین محصولات"
      :icon="Clock"
      icon-class="text-primary-500"
      :products="newProducts"
      view-all-link="/products?sort=newest"
      :loading="isLoading"
    />

    <!-- Special Offers -->
    <ProductsSection
      title="پیشنهاد ویژه اتکا"
      :icon="TrendingUp"
      icon-class="text-emerald-500"
      :products="specialOfferProducts"
      view-all-link="/products"
      :loading="isLoading"
    />

    <!-- Newsletter -->
    <NewsletterSection />
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
