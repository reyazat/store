<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { ChevronLeft, ChevronRight, Truck, Shield, RotateCcw, Headphones, Percent, Star, Clock, TrendingUp, Grid3X3 } from 'lucide-vue-next'
import { ProductCard } from '@shared/components'
import CategorySection from '../components/CategorySection.vue'
import DiscountProductsSection from '../components/DiscountProductsSection.vue'
import { homeService } from '../services'
import type { Product, Category } from '@shared/types'

const featuredProducts = ref<Product[]>([])
const newProducts = ref<Product[]>([])
const discountedProducts = ref<Product[]>([])
const categories = ref<Category[]>([])
const isLoading = ref(true)

// Slider state
const currentSlide = ref(0)
const slides = ref<Array<{
  id: number
  title: string
  subtitle?: string
  image?: string
  link?: string
  button_text?: string
  button_link?: string
}>>([])

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
    const [productsRes, discountedRes, categoriesRes, slidersData] = await Promise.all([
      homeService.getFeaturedProducts(),
      homeService.getDiscountedProducts(8),
      homeService.getCategories(),
      homeService.getSliders(),
    ])

    featuredProducts.value = productsRes.data || []
    newProducts.value = productsRes.data || []
    discountedProducts.value = discountedRes.data || []
    categories.value = categoriesRes.data || []
    slides.value = slidersData
  } catch (error) {
    console.error('Failed to load home data:', error)
  } finally {
    isLoading.value = false
    startAutoSlide()
  }
})

const services = [
  { icon: Truck, title: 'ارسال سریع', desc: 'ارسال به سراسر کشور' },
  { icon: Shield, title: 'ضمانت اصالت', desc: '۱۰۰٪ اصل و اورجینال' },
  { icon: RotateCcw, title: '۷ روز بازگشت', desc: 'بازگشت آسان کالا' },
  { icon: Headphones, title: 'پشتیبانی ۲۴/۷', desc: 'پاسخگویی همیشگی' },
]

const quickCategoriesRef = ref<HTMLElement | null>(null)

const scrollCategories = (dir: 'right' | 'left') => {
  if (!quickCategoriesRef.value) return
  quickCategoriesRef.value.scrollBy({ left: dir === 'left' ? -240 : 240, behavior: 'smooth' })
}
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
      <div class="relative container mx-auto px-4">

        <!-- Scroll Right Arrow -->
        <button
          @click="scrollCategories('right')"
          class="absolute right-0 top-1/2 -translate-y-1/2 z-10 w-9 h-9 bg-white border border-secondary-200 rounded-full shadow flex items-center justify-center hover:bg-secondary-50 transition"
          aria-label="قبلی"
        >
          <ChevronRight class="w-5 h-5 text-secondary-600" />
        </button>

        <!-- Scrollable row -->
        <div
          ref="quickCategoriesRef"
          class="flex items-start justify-start gap-4 overflow-x-auto px-10 scrollbar-hide scroll-smooth"
        >
          <!-- Loading skeletons -->
          <template v-if="isLoading">
            <div v-for="i in 8" :key="i" class="flex-shrink-0 flex flex-col items-center gap-2 w-24 animate-pulse">
              <div class="w-20 h-20 rounded-full bg-secondary-100"></div>
              <div class="h-3 w-14 bg-secondary-100 rounded"></div>
            </div>
          </template>

          <!-- API categories -->
          <template v-else-if="categories.length">
            <RouterLink
              v-for="cat in categories"
              :key="cat.id"
              :to="`/categories/${cat.slug}`"
              class="flex-shrink-0 flex flex-col items-center gap-2 w-24 group"
            >
              <div class="w-20 h-20 rounded-full border-2 border-primary-200 group-hover:border-primary-500 bg-white overflow-hidden flex items-center justify-center transition shadow-sm">
                <img
                  v-if="cat.image"
                  :src="cat.image"
                  :alt="cat.name"
                  class="w-full h-full object-cover"
                />
                <span v-else class="text-3xl select-none">🗂️</span>
              </div>
              <span class="text-xs font-medium text-center text-secondary-700 group-hover:text-primary-600 leading-tight line-clamp-2 transition">
                {{ cat.name }}
              </span>
            </RouterLink>
          </template>
        </div>

        <!-- Scroll Left Arrow -->
        <button
          @click="scrollCategories('left')"
          class="absolute left-0 top-1/2 -translate-y-1/2 z-10 w-9 h-9 bg-white border border-secondary-200 rounded-full shadow flex items-center justify-center hover:bg-secondary-50 transition"
          aria-label="بعدی"
        >
          <ChevronLeft class="w-5 h-5 text-secondary-600" />
        </button>

      </div>
    </section>
    
    <!-- Services Bar -->
    <section class="py-6 bg-white border-t">
      <div class="container mx-auto px-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div 
            v-for="service in services" 
            :key="service.title"
            class="flex items-center gap-3 p-4 rounded-xl bg-secondary-50"
          >
            <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center">
              <component :is="service.icon" class="w-6 h-6 text-primary-600" />
            </div>
            <div>
              <h4 class="font-bold text-secondary-800">{{ service.title }}</h4>
              <p class="text-sm text-secondary-500">{{ service.desc }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>
    
    <!-- Categories Section -->
    <section class="py-8">
      <div class="container mx-auto px-4">
        <div class="bg-white rounded-2xl shadow-sm p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl md:text-2xl font-bold text-secondary-800 flex items-center gap-2">
              <Grid3X3 class="w-6 h-6 text-primary-500" />
              دسته‌بندی محصولات
            </h2>
            <RouterLink to="/categories" class="text-primary-600 hover:text-primary-700 font-medium flex items-center gap-1 text-sm">
              مشاهده همه
              <ChevronLeft class="w-4 h-4" />
            </RouterLink>
          </div>
          <CategorySection :categories="categories" :loading="isLoading" />
        </div>
      </div>
    </section>
    
    <!-- Discount Products Section -->
    <section class="py-4">
      <div class="container mx-auto px-4">
        <DiscountProductsSection
          :products="discountedProducts"
          :loading="isLoading"
        />
      </div>
    </section>
    
    <!-- Featured Products -->
    <section class="py-8">
      <div class="container mx-auto px-4">
        <div class="bg-white rounded-2xl shadow-sm p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl md:text-2xl font-bold text-secondary-800 flex items-center gap-2">
              <Star class="w-6 h-6 text-amber-500" />
              محصولات پرفروش
            </h2>
            <RouterLink to="/products" class="text-primary-600 hover:text-primary-700 font-medium flex items-center gap-1 text-sm">
              مشاهده همه
              <ChevronLeft class="w-4 h-4" />
            </RouterLink>
          </div>
          
          <div v-if="isLoading" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <div v-for="i in 5" :key="i" class="animate-pulse">
              <div class="aspect-square bg-secondary-100 rounded-xl mb-3"></div>
              <div class="h-4 bg-secondary-100 rounded mb-2"></div>
              <div class="h-4 bg-secondary-100 rounded w-2/3"></div>
            </div>
          </div>
          
          <div v-else class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <ProductCard 
              v-for="product in featuredProducts.slice(0, 5)" 
              :key="product.id" 
              :product="product" 
            />
          </div>
        </div>
      </div>
    </section>
    
    <!-- Promotional Banners -->
    <section class="py-4">
      <div class="container mx-auto px-4">
        <div class="grid md:grid-cols-3 gap-4">
          <!-- Banner 1 -->
          <div class="bg-gradient-to-br from-primary-500 to-primary-700 rounded-2xl p-6 text-white relative overflow-hidden group cursor-pointer">
            <div class="relative z-10">
              <Percent class="w-10 h-10 mb-3 opacity-80" />
              <h3 class="text-xl font-bold mb-1">تخفیف‌های روزانه</h3>
              <p class="text-primary-100 text-sm mb-4">هر روز تخفیف‌های جدید</p>
              <span class="text-sm font-medium flex items-center gap-1 group-hover:gap-2 transition-all">
                مشاهده <ChevronLeft class="w-4 h-4" />
              </span>
            </div>
            <div class="absolute -bottom-6 -left-6 w-24 h-24 bg-white/10 rounded-full group-hover:scale-150 transition-transform"></div>
          </div>
          
          <!-- Banner 2 -->
          <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl p-6 text-white relative overflow-hidden group cursor-pointer">
            <div class="relative z-10">
              <Truck class="w-10 h-10 mb-3 opacity-80" />
              <h3 class="text-xl font-bold mb-1">ارسال رایگان</h3>
              <p class="text-emerald-100 text-sm mb-4">خرید بالای ۵۰۰ هزار تومان</p>
              <span class="text-sm font-medium flex items-center gap-1 group-hover:gap-2 transition-all">
                شروع خرید <ChevronLeft class="w-4 h-4" />
              </span>
            </div>
            <div class="absolute -bottom-6 -left-6 w-24 h-24 bg-white/10 rounded-full group-hover:scale-150 transition-transform"></div>
          </div>
          
          <!-- Banner 3 -->
          <div class="bg-gradient-to-br from-violet-500 to-purple-700 rounded-2xl p-6 text-white relative overflow-hidden group cursor-pointer">
            <div class="relative z-10">
              <Shield class="w-10 h-10 mb-3 opacity-80" />
              <h3 class="text-xl font-bold mb-1">ضمانت اصالت</h3>
              <p class="text-violet-100 text-sm mb-4">۱۰۰٪ اصل و اورجینال</p>
              <span class="text-sm font-medium flex items-center gap-1 group-hover:gap-2 transition-all">
                بیشتر بدانید <ChevronLeft class="w-4 h-4" />
              </span>
            </div>
            <div class="absolute -bottom-6 -left-6 w-24 h-24 bg-white/10 rounded-full group-hover:scale-150 transition-transform"></div>
          </div>
        </div>
      </div>
    </section>
    
    <!-- New Products -->
    <section class="py-8">
      <div class="container mx-auto px-4">
        <div class="bg-white rounded-2xl shadow-sm p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl md:text-2xl font-bold text-secondary-800 flex items-center gap-2">
              <Clock class="w-6 h-6 text-primary-500" />
              جدیدترین محصولات
            </h2>
            <RouterLink to="/products?sort=newest" class="text-primary-600 hover:text-primary-700 font-medium flex items-center gap-1 text-sm">
              مشاهده همه
              <ChevronLeft class="w-4 h-4" />
            </RouterLink>
          </div>
          
          <div v-if="isLoading" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <div v-for="i in 5" :key="i" class="animate-pulse">
              <div class="aspect-square bg-secondary-100 rounded-xl mb-3"></div>
              <div class="h-4 bg-secondary-100 rounded mb-2"></div>
              <div class="h-4 bg-secondary-100 rounded w-2/3"></div>
            </div>
          </div>
          
          <div v-else class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <ProductCard 
              v-for="product in newProducts.slice(0, 5)" 
              :key="product.id" 
              :product="product" 
            />
          </div>
        </div>
      </div>
    </section>
    
    <!-- Trending Section -->
    <section class="py-8">
      <div class="container mx-auto px-4">
        <div class="bg-white rounded-2xl shadow-sm p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl md:text-2xl font-bold text-secondary-800 flex items-center gap-2">
              <TrendingUp class="w-6 h-6 text-emerald-500" />
              پیشنهاد ویژه اتکا
            </h2>
            <RouterLink to="/products" class="text-primary-600 hover:text-primary-700 font-medium flex items-center gap-1 text-sm">
              مشاهده همه
              <ChevronLeft class="w-4 h-4" />
            </RouterLink>
          </div>
          
          <div v-if="isLoading" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <div v-for="i in 5" :key="i" class="animate-pulse">
              <div class="aspect-square bg-secondary-100 rounded-xl mb-3"></div>
              <div class="h-4 bg-secondary-100 rounded mb-2"></div>
              <div class="h-4 bg-secondary-100 rounded w-2/3"></div>
            </div>
          </div>
          
          <div v-else class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <ProductCard 
              v-for="product in featuredProducts.slice(0, 5)" 
              :key="product.id" 
              :product="product" 
            />
          </div>
        </div>
      </div>
    </section>
    
    <!-- Newsletter -->
    <section class="py-12 bg-gradient-to-l from-primary-600 to-primary-800">
      <div class="container mx-auto px-4">
        <div class="max-w-2xl mx-auto text-center text-white">
          <h2 class="text-2xl md:text-3xl font-bold mb-4">از تخفیف‌ها باخبر شوید!</h2>
          <p class="text-primary-100 mb-6">با عضویت در خبرنامه، از جدیدترین تخفیف‌ها و محصولات مطلع شوید.</p>
          <form class="flex flex-col sm:flex-row gap-3 max-w-md mx-auto">
            <input 
              type="email" 
              placeholder="ایمیل خود را وارد کنید"
              class="flex-1 px-5 py-3 rounded-full text-secondary-800 focus:outline-none focus:ring-4 focus:ring-primary-300"
            />
            <button 
              type="submit"
              class="px-8 py-3 bg-white text-primary-600 rounded-full font-bold hover:bg-primary-50 transition"
            >
              عضویت
            </button>
          </form>
        </div>
      </div>
    </section>
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

.scrollbar-hide::-webkit-scrollbar {
  display: none;
}

.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
