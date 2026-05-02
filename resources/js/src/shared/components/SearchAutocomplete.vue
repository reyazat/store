<script setup lang="ts">
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { Search, X, ArrowLeft } from 'lucide-vue-next'
import api from '@shared/utils/api'

interface AutocompleteProduct {
  id: number
  name: string
  slug: string
  main_image: string | null
  price: number
  old_price: number | null
  discount_percent: number | null
}

const router = useRouter()

const query = ref('')
const suggestions = ref<AutocompleteProduct[]>([])
const isLoading = ref(false)
const isOpen = ref(false)
const activeIndex = ref(-1)
const inputRef = ref<HTMLInputElement | null>(null)
const containerRef = ref<HTMLDivElement | null>(null)

let debounceTimer: ReturnType<typeof setTimeout> | null = null
let abortController: AbortController | null = null

const MIN_CHARS = 3
const DEBOUNCE_MS = 300

const fetchSuggestions = async (q: string) => {
  if (abortController) {
    abortController.abort()
  }

  abortController = new AbortController()
  isLoading.value = true

  try {
    const response = await api.get('/products/autocomplete', {
      params: { q },
      signal: abortController.signal,
    })
    suggestions.value = response.data?.data || []
    isOpen.value = true
    activeIndex.value = -1
  } catch (err: unknown) {
    if ((err as { name?: string })?.name !== 'CanceledError' && (err as { name?: string })?.name !== 'AbortError') {
      suggestions.value = []
    }
  } finally {
    isLoading.value = false
  }
}

watch(query, (val) => {
  if (debounceTimer) clearTimeout(debounceTimer)
  activeIndex.value = -1

  if (val.trim().length < MIN_CHARS) {
    suggestions.value = []
    isOpen.value = false
    isLoading.value = false
    return
  }

  isLoading.value = true
  debounceTimer = setTimeout(() => fetchSuggestions(val.trim()), DEBOUNCE_MS)
})

const navigateToSearch = () => {
  const q = query.value.trim()
  if (!q) return
  closeDropdown()
  router.push({ path: '/search', query: { q } })
}

const navigateToProduct = (slug: string) => {
  closeDropdown()
  router.push(`/products/${slug}`)
}

const closeDropdown = () => {
  isOpen.value = false
  activeIndex.value = -1
}

const clearQuery = () => {
  query.value = ''
  suggestions.value = []
  isOpen.value = false
  inputRef.value?.focus()
}

const handleKeydown = (e: KeyboardEvent) => {
  if (!isOpen.value || suggestions.value.length === 0) {
    if (e.key === 'Enter') navigateToSearch()
    return
  }

  if (e.key === 'ArrowDown') {
    e.preventDefault()
    activeIndex.value = Math.min(activeIndex.value + 1, suggestions.value.length)
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    activeIndex.value = Math.max(activeIndex.value - 1, -1)
  } else if (e.key === 'Enter') {
    e.preventDefault()
    if (activeIndex.value === suggestions.value.length) {
      navigateToSearch()
    } else if (activeIndex.value >= 0) {
      navigateToProduct(suggestions.value[activeIndex.value].slug)
    } else {
      navigateToSearch()
    }
  } else if (e.key === 'Escape') {
    closeDropdown()
    inputRef.value?.blur()
  }
}

const handleClickOutside = (e: MouseEvent) => {
  if (containerRef.value && !containerRef.value.contains(e.target as Node)) {
    closeDropdown()
  }
}

const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('fa-IR').format(price)
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
  if (debounceTimer) clearTimeout(debounceTimer)
  if (abortController) abortController.abort()
})
</script>

<template>
  <div ref="containerRef" class="relative w-full">
    <!-- Input -->
    <div class="relative">
      <input
        ref="inputRef"
        v-model="query"
        type="search"
        autocomplete="off"
        placeholder="جستجو در بین هزاران محصول..."
        class="w-full h-12 pl-24 pr-5 rounded-xl border-2 border-secondary-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-100 transition-all text-secondary-700 placeholder-secondary-400"
        @keydown="handleKeydown"
        @focus="isOpen = suggestions.length > 0"
        aria-label="جستجوی محصول"
        aria-autocomplete="list"
        :aria-expanded="isOpen"
        aria-controls="search-suggestions"
      />

      <!-- Clear button -->
      <button
        v-if="query.length > 0"
        type="button"
        class="absolute left-14 top-1/2 -translate-y-1/2 p-1 text-secondary-400 hover:text-secondary-600 transition"
        @click="clearQuery"
        aria-label="پاک کردن جستجو"
        tabindex="-1"
      >
        <X class="w-4 h-4" />
      </button>

      <!-- Search button -->
      <button
        type="button"
        class="absolute left-1 top-1 h-10 px-4 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition flex items-center gap-2"
        @click="navigateToSearch"
        aria-label="جستجو"
      >
        <Search class="w-5 h-5" />
        <span class="hidden lg:inline text-sm">جستجو</span>
      </button>
    </div>

    <!-- Dropdown -->
    <div
      v-if="isOpen || (isLoading && query.length >= 3)"
      id="search-suggestions"
      role="listbox"
      class="absolute top-full right-0 left-0 mt-1 bg-white border border-secondary-200 rounded-xl shadow-2xl z-[60] overflow-hidden"
    >
      <!-- Loading skeleton -->
      <div v-if="isLoading && suggestions.length === 0" class="p-3 space-y-2">
        <div v-for="i in 3" :key="i" class="flex items-center gap-3 animate-pulse">
          <div class="w-12 h-12 bg-secondary-100 rounded-lg flex-shrink-0"></div>
          <div class="flex-1 space-y-2">
            <div class="h-3.5 bg-secondary-100 rounded w-3/4"></div>
            <div class="h-3 bg-secondary-100 rounded w-1/3"></div>
          </div>
        </div>
      </div>

      <!-- Results -->
      <template v-else-if="suggestions.length > 0">
        <ul>
          <li
            v-for="(product, idx) in suggestions"
            :key="product.id"
            role="option"
            :aria-selected="activeIndex === idx"
            :class="[
              'flex items-center gap-3 px-4 py-3 cursor-pointer transition',
              activeIndex === idx ? 'bg-primary-50' : 'hover:bg-secondary-50',
            ]"
            @click="navigateToProduct(product.slug)"
            @mouseenter="activeIndex = idx"
          >
            <!-- Product image -->
            <div class="w-12 h-12 rounded-lg overflow-hidden bg-secondary-100 flex-shrink-0 border border-secondary-100">
              <img
                v-if="product.main_image"
                :src="product.main_image"
                :alt="product.name"
                class="w-full h-full object-cover"
                loading="lazy"
              />
              <div v-else class="w-full h-full flex items-center justify-center text-secondary-300 text-xl">📦</div>
            </div>

            <!-- Product info -->
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-secondary-800 truncate">{{ product.name }}</p>
              <div class="flex items-center gap-2 mt-0.5">
                <span class="text-sm font-bold text-primary-600">
                  {{ formatPrice(product.price) }} تومان
                </span>
                <span
                  v-if="product.discount_percent"
                  class="text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded-full font-medium"
                >
                  {{ product.discount_percent }}٪
                </span>
                <span
                  v-if="product.old_price"
                  class="text-xs text-secondary-400 line-through"
                >
                  {{ formatPrice(product.old_price) }}
                </span>
              </div>
            </div>

            <!-- Arrow indicator for keyboard nav -->
            <ArrowLeft
              v-if="activeIndex === idx"
              class="w-4 h-4 text-primary-400 flex-shrink-0"
            />
          </li>
        </ul>

        <!-- See all results -->
        <div class="border-t border-secondary-100">
          <button
            type="button"
            :class="[
              'w-full flex items-center justify-between px-4 py-3 transition text-sm font-medium',
              activeIndex === suggestions.length ? 'bg-primary-50 text-primary-700' : 'hover:bg-secondary-50 text-primary-600',
            ]"
            @click="navigateToSearch"
            @mouseenter="activeIndex = suggestions.length"
          >
            <span>مشاهده همه نتایج برای «{{ query }}»</span>
            <Search class="w-4 h-4" />
          </button>
        </div>
      </template>

      <!-- No results -->
      <div v-else-if="!isLoading && query.length >= MIN_CHARS" class="px-4 py-6 text-center">
        <p class="text-sm text-secondary-500 mb-3">نتیجه‌ای برای «{{ query }}» یافت نشد</p>
        <button
          type="button"
          class="text-sm text-primary-600 hover:text-primary-700 font-medium flex items-center gap-1 mx-auto"
          @click="navigateToSearch"
        >
          <Search class="w-4 h-4" />
          جستجوی پیشرفته
        </button>
      </div>
    </div>
  </div>
</template>
