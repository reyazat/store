<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { Menu, X, ShoppingCart, Heart, User, Search, ChevronDown, Phone, Headphones, Grid3X3, Truck, Shield, RotateCcw } from 'lucide-vue-next'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const cartStore = useCartStore()
const authStore = useAuthStore()
const isMenuOpen = ref(false)
const isSearchOpen = ref(false)
const isCategoryMenuOpen = ref(false)
const searchQuery = ref('')

const toggleMenu = () => {
  isMenuOpen.value = !isMenuOpen.value
}

const handleSearch = () => {
  if (searchQuery.value.trim()) {
    router.push({ path: '/products', query: { search: searchQuery.value } })
    isSearchOpen.value = false
  }
}

const categories = [
  { name: 'الکترونیک', slug: 'electronics', icon: '📱' },
  { name: 'پوشاک و مد', slug: 'fashion', icon: '👕' },
  { name: 'خانه و آشپزخانه', slug: 'home-kitchen', icon: '🏠' },
  { name: 'زیبایی و سلامت', slug: 'beauty-health', icon: '💄' },
  { name: 'ورزش و سفر', slug: 'sports-travel', icon: '⚽' },
  { name: 'کتاب و لوازم تحریر', slug: 'books-stationery', icon: '📚' },
]
</script>

<template>
  <header class="sticky top-0 z-50">
    <!-- Top Bar -->
    <div class="bg-secondary-700 text-white text-sm py-2">
      <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
          <div class="flex items-center gap-6">
            <a href="tel:02112345678" class="flex items-center gap-1 hover:text-primary-300 transition">
              <Phone class="w-4 h-4" />
              <span>۰۲۱-۱۲۳۴۵۶۷۸</span>
            </a>
            <span class="hidden md:flex items-center gap-1">
              <Headphones class="w-4 h-4" />
              پشتیبانی ۲۴ ساعته
            </span>
          </div>
          <div class="flex items-center gap-4">
            <RouterLink to="/user/orders" class="hover:text-primary-300 transition hidden sm:block">
              پیگیری سفارش
            </RouterLink>
            <template v-if="authStore.isAuthenticated">
              <RouterLink to="/user/dashboard" class="hover:text-primary-300 transition">
                حساب کاربری
              </RouterLink>
            </template>
            <template v-else>
              <RouterLink to="/login" class="hover:text-primary-300 transition">
                ورود / ثبت‌نام
              </RouterLink>
            </template>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Main Header -->
    <div class="bg-white shadow-md">
      <div class="container mx-auto px-4">
        <div class="flex items-center gap-4 lg:gap-8 h-20">
          <!-- Logo -->
          <RouterLink to="/" class="flex-shrink-0">
            <img
              src="/images/Logo/tejarat-logo-500.png"
              alt="لوگو تجارت"
              class="h-12 w-auto object-contain"
            />
          </RouterLink>
          
          <!-- Search Bar -->
          <div class="flex-1 max-w-2xl hidden md:block">
            <form @submit.prevent="handleSearch" class="relative">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="جستجو در بین هزاران محصول..."
                class="w-full h-12 pl-12 pr-5 rounded-xl border-2 border-secondary-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-100 transition-all text-secondary-700 placeholder-secondary-400"
              />
              <button 
                type="submit"
                class="absolute left-1 top-1 h-10 px-4 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition flex items-center gap-2"
              >
                <Search class="w-5 h-5" />
                <span class="hidden lg:inline">جستجو</span>
              </button>
            </form>
          </div>
          
          <!-- Actions -->
          <div class="flex items-center gap-2 lg:gap-4 mr-auto">
            <!-- Search Toggle (Mobile) -->
            <button 
              @click="isSearchOpen = !isSearchOpen"
              class="md:hidden p-2.5 hover:bg-secondary-100 rounded-xl transition"
            >
              <Search class="w-6 h-6 text-secondary-600" />
            </button>
            
            <!-- User Account -->
            <RouterLink 
              v-if="authStore.isAuthenticated"
              to="/user/dashboard" 
              class="hidden sm:flex items-center gap-2 p-2.5 hover:bg-secondary-100 rounded-xl transition"
            >
              <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                <span class="text-primary-600 font-bold text-sm">
                  {{ authStore.user?.name?.charAt(0) || 'U' }}
                </span>
              </div>
              <div class="hidden lg:block text-right">
                <span class="block text-xs text-secondary-500">حساب کاربری</span>
                <span class="block text-sm font-medium text-secondary-700">{{ authStore.user?.name || 'کاربر' }}</span>
              </div>
            </RouterLink>
            <RouterLink 
              v-else
              to="/login" 
              class="hidden sm:flex items-center gap-2 p-2.5 hover:bg-secondary-100 rounded-xl transition"
            >
              <User class="w-6 h-6 text-secondary-600" />
              <div class="hidden lg:block text-right">
                <span class="block text-xs text-secondary-500">حساب کاربری</span>
                <span class="block text-sm font-medium text-secondary-700">ورود / ثبت‌نام</span>
              </div>
            </RouterLink>
            
            <!-- Wishlist -->
            <RouterLink 
              to="/user/wishlist" 
              class="p-2.5 hover:bg-secondary-100 rounded-xl transition relative"
            >
              <Heart class="w-6 h-6 text-secondary-600" />
            </RouterLink>
            
            <!-- Cart -->
            <button 
              @click="cartStore.toggleDrawer"
              class="flex items-center gap-2 p-2.5 bg-primary-50 hover:bg-primary-100 rounded-xl transition relative"
            >
              <ShoppingCart class="w-6 h-6 text-primary-600" />
              <div class="hidden lg:block text-right">
                <span class="block text-xs text-secondary-500">سبد خرید</span>
                <span class="block text-sm font-bold text-primary-600">
                  {{ cartStore.itemsCount }} کالا
                </span>
              </div>
              <span 
                v-if="cartStore.itemsCount > 0"
                class="lg:hidden absolute -top-1 -right-1 bg-red-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center font-bold"
              >
                {{ cartStore.itemsCount }}
              </span>
            </button>
            
            <!-- Mobile Menu Toggle -->
            <button 
              @click="toggleMenu"
              class="md:hidden p-2.5 hover:bg-secondary-100 rounded-xl transition"
            >
              <Menu v-if="!isMenuOpen" class="w-6 h-6 text-secondary-600" />
              <X v-else class="w-6 h-6 text-secondary-600" />
            </button>
          </div>
        </div>
        
        <!-- Mobile Search -->
        <div v-if="isSearchOpen" class="md:hidden pb-4">
          <form @submit.prevent="handleSearch" class="relative">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="جستجو..."
              class="w-full h-12 pl-12 pr-5 rounded-xl border-2 border-secondary-200 focus:border-primary-500"
            />
            <button type="submit" class="absolute left-1 top-1 h-10 px-4 bg-primary-500 text-white rounded-lg">
              <Search class="w-5 h-5" />
            </button>
          </form>
        </div>
      </div>
    </div>
    
    <!-- Navigation Bar -->
    <nav class="bg-white border-t border-secondary-100 hidden md:block">
      <div class="container mx-auto px-4">
        <div class="flex items-center gap-1 h-12">
          <!-- Categories Dropdown -->
          <div class="relative" @mouseenter="isCategoryMenuOpen = true" @mouseleave="isCategoryMenuOpen = false">
            <button class="flex items-center gap-2 px-4 h-12 bg-primary-500 hover:bg-primary-600 text-white rounded-t-lg transition">
              <Grid3X3 class="w-5 h-5" />
              <span class="font-medium">دسته‌بندی کالاها</span>
              <ChevronDown class="w-4 h-4" :class="{ 'rotate-180': isCategoryMenuOpen }" />
            </button>
            
            <!-- Dropdown Menu -->
            <div 
              v-show="isCategoryMenuOpen"
              class="absolute top-full right-0 w-64 bg-white shadow-xl rounded-b-lg border border-secondary-100 py-2 z-50"
            >
              <RouterLink 
                v-for="cat in categories" 
                :key="cat.slug"
                :to="`/categories/${cat.slug}`"
                class="flex items-center gap-3 px-4 py-3 hover:bg-secondary-50 transition"
                @click="isCategoryMenuOpen = false"
              >
                <span class="text-xl">{{ cat.icon }}</span>
                <span class="text-secondary-700">{{ cat.name }}</span>
              </RouterLink>
              <hr class="my-2" />
              <RouterLink 
                to="/categories"
                class="flex items-center gap-3 px-4 py-3 text-primary-600 hover:bg-primary-50 transition font-medium"
                @click="isCategoryMenuOpen = false"
              >
                مشاهده همه دسته‌بندی‌ها
              </RouterLink>
            </div>
          </div>
          
          <!-- Nav Links -->
          <RouterLink to="/" class="px-4 h-12 flex items-center text-secondary-700 hover:text-primary-600 hover:bg-secondary-50 transition font-medium">
            صفحه اصلی
          </RouterLink>
          <RouterLink to="/products" class="px-4 h-12 flex items-center text-secondary-700 hover:text-primary-600 hover:bg-secondary-50 transition font-medium">
            محصولات
          </RouterLink>
          <RouterLink to="/discounts" class="px-4 h-12 flex items-center text-red-500 hover:text-red-600 hover:bg-red-50 transition font-medium">
            🔥 تخفیف‌ها
          </RouterLink>
          <RouterLink to="/brands" class="px-4 h-12 flex items-center text-secondary-700 hover:text-primary-600 hover:bg-secondary-50 transition font-medium">
            برندها
          </RouterLink>
          
          <!-- Spacer -->
          <div class="flex-1"></div>
          
          <!-- Quick Info -->
          <div class="hidden lg:flex items-center gap-6 text-sm text-secondary-600">
            <span class="flex items-center gap-1">
              <Truck class="w-4 h-4 text-primary-500" />
              ارسال سریع
            </span>
            <span class="flex items-center gap-1">
              <Shield class="w-4 h-4 text-primary-500" />
              ضمانت اصالت
            </span>
            <span class="flex items-center gap-1">
              <RotateCcw class="w-4 h-4 text-primary-500" />
              ۷ روز بازگشت
            </span>
          </div>
        </div>
      </div>
    </nav>
    
    <!-- Mobile Menu -->
    <div 
      v-if="isMenuOpen"
      class="md:hidden bg-white border-t shadow-lg"
    >
      <nav class="container mx-auto px-4 py-4">
        <ul class="space-y-1">
          <li>
            <RouterLink to="/" class="flex items-center gap-3 px-4 py-3 rounded-lg text-secondary-700 hover:bg-secondary-50" @click="isMenuOpen = false">
              🏠 صفحه اصلی
            </RouterLink>
          </li>
          <li>
            <RouterLink to="/categories" class="flex items-center gap-3 px-4 py-3 rounded-lg text-secondary-700 hover:bg-secondary-50" @click="isMenuOpen = false">
              📦 دسته‌بندی‌ها
            </RouterLink>
          </li>
          <li>
            <RouterLink to="/products" class="flex items-center gap-3 px-4 py-3 rounded-lg text-secondary-700 hover:bg-secondary-50" @click="isMenuOpen = false">
              🛍️ محصولات
            </RouterLink>
          </li>
          <li>
            <RouterLink to="/discounts" class="flex items-center gap-3 px-4 py-3 rounded-lg text-red-500 hover:bg-red-50" @click="isMenuOpen = false">
              🔥 تخفیف‌ها
            </RouterLink>
          </li>
          <li>
            <RouterLink to="/brands" class="flex items-center gap-3 px-4 py-3 rounded-lg text-secondary-700 hover:bg-secondary-50" @click="isMenuOpen = false">
              ⭐ برندها
            </RouterLink>
          </li>
          <hr class="my-2" />
          <li>
            <RouterLink to="/user/dashboard" class="flex items-center gap-3 px-4 py-3 rounded-lg text-secondary-700 hover:bg-secondary-50" @click="isMenuOpen = false">
              👤 حساب کاربری
            </RouterLink>
          </li>
          <li>
            <RouterLink to="/user/orders" class="flex items-center gap-3 px-4 py-3 rounded-lg text-secondary-700 hover:bg-secondary-50" @click="isMenuOpen = false">
              📋 سفارشات من
            </RouterLink>
          </li>
        </ul>
      </nav>
    </div>
  </header>
</template>
