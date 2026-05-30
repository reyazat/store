<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { Menu, X, ShoppingCart, Heart, User, Search, ChevronDown, Phone, Headphones, Grid3X3, Truck, Shield, RotateCcw } from 'lucide-vue-next'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'
import { useWishlistStore } from '@/stores/wishlist'
import { useSiteSettings } from '@shared/composables/useSiteSettings'
import { useNavLinks } from '@shared/composables/useNavLinks'
import SearchAutocomplete from '@shared/components/SearchAutocomplete.vue'
import { categoryService, type HierarchicalCategory } from '@modules/category/services'

const cartStore = useCartStore()
const authStore = useAuthStore()
const wishlistStore = useWishlistStore()
const isMenuOpen = ref(false)
const isSearchOpen = ref(false)
const isCategoryMenuOpen = ref(false)
const categories = ref<HierarchicalCategory[]>([])
const isLoadingCategories = ref(true)
const categoryError = ref<string | null>(null)
const expandedCategories = ref<Set<number>>(new Set())

const { settings } = useSiteSettings()
const { links: headerLinks } = useNavLinks('header')

const toggleMenu = () => {
  isMenuOpen.value = !isMenuOpen.value
}

const toggleMobileCategory = (categoryId: number) => {
  if (expandedCategories.value.has(categoryId)) {
    expandedCategories.value.delete(categoryId)
  } else {
    expandedCategories.value.add(categoryId)
  }
}

const getCategoryIcon = (category: HierarchicalCategory): string => {
  if (category.icon) return category.icon
  const iconMap: Record<string, string> = {
    'electronics': '📱',
    'fashion': '👕',
    'home-kitchen': '🏠',
    'beauty-health': '💄',
    'sports-travel': '⚽',
    'books-stationery': '📚',
    'toys-kids': '🧸',
    'tools-automotive': '🔧',
  }
  return iconMap[category.slug] || '📦'
}

const loadCategories = async () => {
  isLoadingCategories.value = true
  categoryError.value = null
  try {
    const response = await categoryService.getHierarchicalCategories()
    categories.value = response.data || []
  } catch (error: any) {
    console.error('Failed to load header categories:', error)
    categoryError.value = error.response?.data?.message || 'خطا در بارگذاری دسته‌بندی‌ها'
  } finally {
    isLoadingCategories.value = false
  }
}

onMounted(loadCategories)
</script>

<template>
  <header class="sticky top-0 z-50">
    <!-- Top Bar -->
    <div class="bg-secondary-700 text-white text-sm py-2">
      <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
          <div class="flex items-center gap-6">
            <a
              :href="settings.phone_link || 'tel:02112345678'"
              class="flex items-center gap-1 hover:text-primary-300 transition"
            >
              <Phone class="w-4 h-4" />
              <span>{{ settings.phone || '۰۲۱-۱۲۳۴۵۶۷۸' }}</span>
            </a>
            <span v-if="settings.support_hours" class="hidden md:flex items-center gap-1">
              <Headphones class="w-4 h-4" />
              {{ settings.support_hours }}
            </span>
          </div>
          <div class="flex items-center gap-4">
            <RouterLink to="/order-tracking" class="hover:text-primary-300 transition hidden sm:block">
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
              :src="settings.logo || '/images/Logo/tejarat-logo-500.png'"
              :alt="settings.logo_alt || settings.site_name || 'لوگو فروشگاه'"
              class="h-12 w-auto object-contain"
            />
          </RouterLink>
          
          <!-- Search Bar (Desktop) -->
          <div class="flex-1 max-w-2xl hidden md:block">
            <SearchAutocomplete />
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
            <component
              :is="authStore.isAuthenticated ? RouterLink : 'a'"
              v-bind="authStore.isAuthenticated
                ? { to: '/user/wishlist' }
                : { href: '/login?redirect=/user/wishlist' }"
              class="p-2.5 hover:bg-secondary-100 rounded-xl transition relative"
              aria-label="لیست علاقه‌مندی‌ها"
            >
              <Heart class="w-6 h-6 text-secondary-600" />
              <span
                v-if="authStore.isAuthenticated && wishlistStore.count > 0"
                class="absolute -top-1 -right-1 bg-red-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center font-bold"
              >
                {{ wishlistStore.count }}
              </span>
            </component>
            
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
          <SearchAutocomplete />
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
            
            <!-- Dropdown Menu - Mega Menu -->
            <div
              v-show="isCategoryMenuOpen"
              class="absolute top-full right-0 w-screen max-w-4xl bg-white shadow-2xl rounded-b-lg border border-secondary-100 z-50"
              @mouseenter="isCategoryMenuOpen = true"
              @mouseleave="isCategoryMenuOpen = false"
            >
              <!-- Loading State -->
              <div v-if="isLoadingCategories" class="p-8">
                <div class="animate-pulse">
                  <div class="grid grid-cols-3 gap-8">
                    <div v-for="i in 3" :key="i" class="space-y-4">
                      <div class="h-6 bg-secondary-200 rounded w-32"></div>
                      <div class="space-y-2">
                        <div v-for="j in 4" :key="j" class="h-4 bg-secondary-200 rounded w-24"></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Error State -->
              <div v-else-if="categoryError" class="p-8 text-center">
                <p class="text-sm text-red-500 mb-4">{{ categoryError }}</p>
                <button
                  @click="loadCategories"
                  class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition"
                >
                  تلاش مجدد
                </button>
              </div>

              <!-- Mega Menu Content -->
              <template v-else-if="categories.length > 0">
                <div class="p-8">
                  <div class="grid grid-cols-3 gap-8">
                    <div
                      v-for="category in categories"
                      :key="category.id"
                      class="space-y-4"
                    >
                      <h4 class="font-bold text-secondary-800 flex items-center gap-2">
                        <span class="text-lg">{{ getCategoryIcon(category) }}</span>
                        {{ category.name }}
                      </h4>
                      
                      <div v-if="category.children && category.children.length > 0" class="space-y-2">
                        <RouterLink
                          v-for="subcategory in category.children"
                          :key="subcategory.id"
                          :to="{ path: '/products', query: { category_slug: subcategory.slug } }"
                          class="flex items-center justify-between p-2 rounded-lg text-sm text-secondary-700 hover:bg-primary-50 hover:text-primary-600 transition group"
                          @click="isCategoryMenuOpen = false"
                        >
                          <span class="flex-1">{{ subcategory.name }}</span>
                          <span
                            v-if="subcategory.products_count > 0"
                            class="text-xs text-secondary-400 bg-secondary-100 group-hover:bg-primary-100 group-hover:text-primary-600 px-2 py-1 rounded-full transition"
                          >
                            {{ subcategory.products_count }}
                          </span>
                        </RouterLink>
                      </div>
                      
                      <div v-else class="text-sm text-secondary-500 italic">
                        زیرمجموعه‌ای یافت نشد
                      </div>
                    </div>
                  </div>

                  <!-- View All Categories Link -->
                  <div class="mt-6 pt-6 border-t border-secondary-100 text-center">
                    <RouterLink
                      to="/categories"
                      class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition font-medium"
                      @click="isCategoryMenuOpen = false"
                    >
                      <span>مشاهده همه دسته‌بندی‌ها</span>
                    </RouterLink>
                  </div>
                </div>
              </template>

              <!-- Empty State -->
              <div v-else class="p-8 text-center">
                <div class="w-16 h-16 bg-secondary-100 rounded-full mx-auto mb-4 flex items-center justify-center">
                  <span class="text-2xl">📦</span>
                </div>
                <p class="text-secondary-500">دسته‌بندی یافت نشد</p>
              </div>
            </div>
          </div>
          
          <!-- Nav Links -->
          <template v-if="headerLinks.length > 0">
            <component
              v-for="link in headerLinks"
              :key="link.id"
              :is="link.url.startsWith('http') ? 'a' : RouterLink"
              v-bind="link.url.startsWith('http') ? { href: link.url, target: link.target } : { to: link.url }"
              class="px-4 h-12 flex items-center text-secondary-700 hover:text-primary-600 hover:bg-secondary-50 transition font-medium"
            >
              {{ link.label }}
            </component>
          </template>
          <template v-else>
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
            <RouterLink to="/faq" class="px-4 h-12 flex items-center text-secondary-700 hover:text-primary-600 hover:bg-secondary-50 transition font-medium">
              سوالات متداول
            </RouterLink>
          </template>
          
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
            <div class="border-t border-secondary-100 pt-2 mt-2">
              <button
                @click="toggleMobileCategory(-1)"
                class="flex items-center justify-between w-full px-4 py-3 rounded-lg text-secondary-700 hover:bg-secondary-50"
              >
                <div class="flex items-center gap-3">
                  <Grid3X3 class="w-5 h-5" />
                  <span>دسته‌بندی کالاها</span>
                </div>
                <ChevronDown 
                  :class="{ 'rotate-180': expandedCategories.has(-1) }" 
                  class="w-4 h-4 transition-transform" 
                />
              </button>
              
              <!-- Mobile Categories -->
              <div v-if="expandedCategories.has(-1)" class="mt-2 space-y-1">
                <!-- Loading State -->
                <div v-if="isLoadingCategories" class="px-4 py-3">
                  <div class="animate-pulse space-y-3">
                    <div v-for="i in 3" :key="i" class="space-y-2">
                      <div class="h-5 bg-secondary-200 rounded w-24"></div>
                      <div class="space-y-1">
                        <div v-for="j in 3" :key="j" class="h-3 bg-secondary-200 rounded w-20 ml-4"></div>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Categories -->
                <template v-else-if="categories.length > 0">
                  <div
                    v-for="category in categories"
                    :key="category.id"
                    class="border-b border-secondary-100 last:border-b-0"
                  >
                    <button
                      @click="toggleMobileCategory(category.id)"
                      class="flex items-center justify-between w-full px-4 py-3 hover:bg-primary-50 transition"
                    >
                      <div class="flex items-center gap-2">
                        <span class="text-sm">{{ getCategoryIcon(category) }}</span>
                        <span class="text-sm font-medium text-secondary-700">{{ category.name }}</span>
                      </div>
                      <div class="flex items-center gap-2">
                        <span class="text-xs text-secondary-500">{{ category.children_count }} زیرمجموعه</span>
                        <ChevronDown 
                          :class="{ 'rotate-180': expandedCategories.has(category.id) }" 
                          class="w-3 h-3 text-secondary-400 transition-transform" 
                        />
                      </div>
                    </button>
                    
                    <!-- Subcategories -->
                    <div v-if="expandedCategories.has(category.id)" class="bg-secondary-50">
                      <RouterLink
                        v-for="subcategory in category.children"
                        :key="subcategory.id"
                        :to="{ path: '/products', query: { category_slug: subcategory.slug } }"
                        class="flex items-center justify-between px-8 py-2 text-sm text-secondary-600 hover:bg-secondary-100 transition"
                        @click="isMenuOpen = false"
                      >
                        <span>{{ subcategory.name }}</span>
                        <span
                          v-if="subcategory.products_count > 0"
                          class="text-xs text-secondary-400 bg-white px-2 py-1 rounded-full"
                        >
                          {{ subcategory.products_count }}
                        </span>
                      </RouterLink>
                    </div>
                  </div>
                </template>
                
                <!-- Error State -->
                <div v-else-if="categoryError" class="px-4 py-3 text-center">
                  <p class="text-sm text-red-500 mb-2">{{ categoryError }}</p>
                  <button
                    @click="loadCategories"
                    class="text-xs text-primary-600 hover:text-primary-700 font-medium"
                  >
                    تلاش مجدد
                  </button>
                </div>
              </div>
            </div>
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
            <RouterLink to="/order-tracking" class="flex items-center gap-3 px-4 py-3 rounded-lg text-secondary-700 hover:bg-secondary-50" @click="isMenuOpen = false">
              🔍 پیگیری سفارش
            </RouterLink>
          </li>
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
          <hr class="my-2" />
          <li>
            <RouterLink to="/faq" class="flex items-center gap-3 px-4 py-3 rounded-lg text-secondary-700 hover:bg-secondary-50" @click="isMenuOpen = false">
              ❓ سوالات متداول
            </RouterLink>
          </li>
          <li>
            <RouterLink to="/terms" class="flex items-center gap-3 px-4 py-3 rounded-lg text-secondary-700 hover:bg-secondary-50" @click="isMenuOpen = false">
              📄 قوانین و مقررات
            </RouterLink>
          </li>
          <li>
            <RouterLink to="/privacy" class="flex items-center gap-3 px-4 py-3 rounded-lg text-secondary-700 hover:bg-secondary-50" @click="isMenuOpen = false">
              🔒 حریم خصوصی
            </RouterLink>
          </li>
        </ul>
      </nav>
    </div>
  </header>
</template>
