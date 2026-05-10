<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { Breadcrumb } from '@shared/components'
import { categoryService, type HierarchicalCategory } from '../services'
import { ChevronDown, ChevronLeft, Grid3x3, List } from 'lucide-vue-next'

const router = useRouter()
const categories = ref<HierarchicalCategory[]>([])
const selectedCategory = ref<HierarchicalCategory | null>(null)
const selectedSubcategory = ref<{ id: number; name: string; slug: string } | null>(null)
const isLoading = ref(true)
const viewMode = ref<'grid' | 'list'>('grid')

// Computed properties
const sortedCategories = computed(() => {
  return categories.value.sort((a, b) => b.children_count - a.children_count)
})

const hasCategories = computed(() => categories.value.length > 0)

// Initialize with category having most subcategories
onMounted(async () => {
  try {
    const response = await categoryService.getHierarchicalCategories()
    categories.value = response.data
    
    // Select category with most subcategories by default
    if (categories.value.length > 0) {
      selectedCategory.value = sortedCategories.value[0]
    }
  } catch (error) {
    console.error('Failed to load categories:', error)
  } finally {
    isLoading.value = false
  }
})

// Methods
const selectCategory = (category: HierarchicalCategory) => {
  selectedCategory.value = category
  selectedSubcategory.value = null
}

const selectSubcategory = (subcategory: { id: number; name: string; slug: string }) => {
  selectedSubcategory.value = subcategory
  // Navigate to products page with subcategory filter
  router.push({
    path: '/products',
    query: { category_slug: subcategory.slug }
  })
}

const DEFAULT_IMAGE = 'https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=400&q=80'
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm">
      <div class="container mx-auto px-4 py-4">
        <Breadcrumb :items="[{ title: 'دسته‌بندی‌ها' }]" />
        
        <div class="flex items-center justify-between mt-4">
          <h1 class="text-2xl md:text-3xl font-bold text-gray-900">دسته‌بندی‌ها</h1>
          
          <!-- View Mode Toggle (Desktop only) -->
          <div class="hidden md:flex items-center gap-2 bg-gray-100 rounded-lg p-1">
            <button
              @click="viewMode = 'grid'"
              :class="[
                'px-3 py-1.5 rounded-md text-sm font-medium transition-colors',
                viewMode === 'grid' 
                  ? 'bg-white text-gray-900 shadow-sm' 
                  : 'text-gray-600 hover:text-gray-900'
              ]"
            >
              <Grid3x3 class="w-4 h-4" />
            </button>
            <button
              @click="viewMode = 'list'"
              :class="[
                'px-3 py-1.5 rounded-md text-sm font-medium transition-colors',
                viewMode === 'list' 
                  ? 'bg-white text-gray-900 shadow-sm' 
                  : 'text-gray-600 hover:text-gray-900'
              ]"
            >
              <List class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="container mx-auto px-4 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Categories Sidebar Skeleton -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-xl p-4 shadow-sm">
            <div v-for="i in 4" :key="i" class="mb-3 last:mb-0">
              <div class="flex items-center gap-3 p-3 rounded-lg">
                <div class="w-12 h-12 bg-gray-200 rounded-lg"></div>
                <div class="flex-1">
                  <div class="h-4 bg-gray-200 rounded mb-2"></div>
                  <div class="h-3 bg-gray-200 rounded w-2/3"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Subcategories Content Skeleton -->
        <div class="lg:col-span-3">
          <div class="bg-white rounded-xl p-6 shadow-sm">
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
              <div v-for="i in 6" :key="i" class="animate-pulse">
                <div class="aspect-square bg-gray-200 rounded-lg mb-2"></div>
                <div class="h-4 bg-gray-200 rounded"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div v-else-if="hasCategories" class="container mx-auto px-4 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Categories Sidebar -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-xl shadow-sm overflow-hidden sticky top-4">
            <div class="p-4 border-b border-gray-100">
              <h2 class="font-semibold text-gray-900">دسته‌بندی اصلی</h2>
            </div>
            
            <div class="max-h-96 overflow-y-auto">
              <div
                v-for="category in sortedCategories"
                :key="category.id"
                @click="selectCategory(category)"
                :class="[
                  'flex items-center gap-3 p-4 cursor-pointer transition-colors border-b border-gray-50 last:border-b-0',
                  selectedCategory?.id === category.id
                    ? 'bg-primary-50 border-r-4 border-r-primary-500'
                    : 'hover:bg-gray-50'
                ]"
              >
                <div class="w-12 h-12 rounded-lg overflow-hidden flex-shrink-0">
                  <img
                    :src="category.image || DEFAULT_IMAGE"
                    :alt="category.name"
                    class="w-full h-full object-cover"
                  />
                </div>
                
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2">
                    <span v-if="category.icon" class="text-lg">{{ category.icon }}</span>
                    <h3 class="font-medium text-gray-900 truncate">{{ category.name }}</h3>
                  </div>
                  <p class="text-sm text-gray-500">
                  {{ category.children_count }} زیرمجموعه
                  <span v-if="category.direct_products_count > 0" class="mr-2">
                    • {{ category.direct_products_count }} محصول مستقیم
                  </span>
                </p>
                </div>
                
                <ChevronDown 
                  :class="[
                    'w-4 h-4 text-gray-400 transition-transform',
                    selectedCategory?.id === category.id ? 'rotate-180' : ''
                  ]"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Subcategories Content -->
        <div class="lg:col-span-3">
          <div v-if="selectedCategory" class="bg-white rounded-xl shadow-sm overflow-hidden">
            <!-- Category Header -->
            <div class="relative h-32 md:h-48 bg-gradient-to-br from-primary-500 to-primary-600">
              <div class="absolute inset-0 bg-black bg-opacity-20"></div>
              <div class="relative h-full flex items-center justify-center text-white">
                <div class="text-center">
                  <div class="flex items-center justify-center gap-3 mb-2">
                    <span v-if="selectedCategory.icon" class="text-4xl">{{ selectedCategory.icon }}</span>
                    <h2 class="text-2xl md:text-3xl font-bold">{{ selectedCategory.name }}</h2>
                  </div>
                  <p class="text-primary-100">{{ selectedCategory.children_count }} زیرمجموعه</p>
                </div>
              </div>
            </div>

            <!-- Subcategories Grid/List -->
            <div class="p-6">
              <div v-if="selectedCategory.children.length > 0">
                <!-- Grid View -->
                <div v-if="viewMode === 'grid'" class="grid grid-cols-2 md:grid-cols-3 gap-4">
                  <div
                    v-for="subcategory in selectedCategory.children"
                    :key="subcategory.id"
                    @click="selectSubcategory(subcategory)"
                    class="group cursor-pointer"
                  >
                    <div class="aspect-square bg-gray-50 rounded-lg overflow-hidden mb-3 group-hover:shadow-lg transition-shadow">
                      <img
                        :src="subcategory.image || DEFAULT_IMAGE"
                        :alt="subcategory.name"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                      />
                    </div>
                    <h3 class="font-medium text-gray-900 text-center group-hover:text-primary-600 transition-colors">
                      {{ subcategory.name }}
                    </h3>
                    <p v-if="subcategory.products_count > 0" class="text-sm text-gray-500 text-center">
                      {{ subcategory.products_count }} محصول
                    </p>
                  </div>
                </div>

                <!-- List View -->
                <div v-else class="space-y-3">
                  <div
                    v-for="subcategory in selectedCategory.children"
                    :key="subcategory.id"
                    @click="selectSubcategory(subcategory)"
                    class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors group"
                  >
                    <div class="w-16 h-16 rounded-lg overflow-hidden flex-shrink-0">
                      <img
                        :src="subcategory.image || DEFAULT_IMAGE"
                        :alt="subcategory.name"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                      />
                    </div>
                    
                    <div class="flex-1 min-w-0">
                      <h3 class="font-medium text-gray-900 group-hover:text-primary-600 transition-colors">
                        {{ subcategory.name }}
                      </h3>
                      <p v-if="subcategory.products_count > 0" class="text-sm text-gray-500">
                        {{ subcategory.products_count }} محصول
                      </p>
                    </div>
                    
                    <ChevronLeft class="w-5 h-5 text-gray-400 group-hover:text-primary-600 transition-colors" />
                  </div>
                </div>
              </div>

              <!-- Empty State -->
              <div v-else class="text-center py-12">
                <div class="w-24 h-24 bg-gray-100 rounded-full mx-auto mb-4 flex items-center justify-center">
                  <span class="text-3xl text-gray-400">📦</span>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">موردی یافت نشد</h3>
                <p class="text-gray-500">این دسته‌بندی هنوز زیرمجموعه‌ای ندارد</p>
              </div>
            </div>
          </div>

          <!-- No Category Selected -->
          <div v-else class="bg-white rounded-xl shadow-sm p-12 text-center">
            <div class="w-24 h-24 bg-gray-100 rounded-full mx-auto mb-4 flex items-center justify-center">
              <span class="text-3xl text-gray-400">📂</span>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">دسته‌بندی را انتخاب کنید</h3>
            <p class="text-gray-500">از لیست سمت راست یک دسته‌بندی اصلی برای مشاهده زیرمجموعه‌ها انتخاب کنید</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="container mx-auto px-4 py-16 text-center">
      <div class="w-32 h-32 bg-gray-100 rounded-full mx-auto mb-6 flex items-center justify-center">
        <span class="text-4xl text-gray-400">📦</span>
      </div>
      <h2 class="text-2xl font-bold text-gray-900 mb-3">هیچ دسته‌بندی‌ای یافت نشد</h2>
      <p class="text-gray-600 mb-8">در حال حاضر دسته‌بندی‌ای برای نمایش وجود ندارد</p>
      <RouterLink
        to="/"
        class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
      >
        بازگشت به صفحه اصلی
      </RouterLink>
    </div>
  </div>
</template>
