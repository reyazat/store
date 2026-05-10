<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { ChevronDown, Search, HelpCircle } from 'lucide-vue-next'
import Breadcrumb from '@shared/components/Breadcrumb.vue'
import { faqService } from '../services/faqService'
import type { FaqItem } from '../services/faqService'

const faqs = ref<FaqItem[]>([])
const isLoading = ref(true)
const openId = ref<number | null>(null)
const searchQuery = ref('')
const activeCategory = ref<string | null>(null)

onMounted(async () => {
  try {
    faqs.value = await faqService.getAll()
  } finally {
    isLoading.value = false
  }
})

const categories = computed(() => {
  const cats = faqs.value
    .map((f) => f.category)
    .filter((c): c is string => !!c)
  return [...new Set(cats)]
})

const filtered = computed(() => {
  let list = faqs.value
  if (activeCategory.value) {
    list = list.filter((f) => f.category === activeCategory.value)
  }
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.trim().toLowerCase()
    list = list.filter(
      (f) =>
        f.question.toLowerCase().includes(q) ||
        f.answer.toLowerCase().includes(q),
    )
  }
  return list
})

const toggle = (id: number) => {
  openId.value = openId.value === id ? null : id
}
</script>

<template>
  <div class="min-h-screen bg-secondary-50" dir="rtl">
    <div class="container mx-auto px-4 py-8">
      <Breadcrumb :items="[{ title: 'سوالات متداول' }]" />

      <!-- Hero -->
      <div class="text-center py-10">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-primary-100 rounded-2xl mb-4">
          <HelpCircle class="w-8 h-8 text-primary-600" />
        </div>
        <h1 class="text-3xl font-bold text-secondary-800 mb-3">سوالات متداول</h1>
        <p class="text-secondary-500 max-w-xl mx-auto">
          پاسخ سوالات رایج خود را اینجا بیابید. اگر پاسخ سوال خود را نیافتید با پشتیبانی ما تماس بگیرید.
        </p>
      </div>

      <!-- Search -->
      <div class="max-w-2xl mx-auto mb-8">
        <div class="relative">
          <Search class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400" />
          <input
            v-model="searchQuery"
            type="search"
            placeholder="جستجو در سوالات..."
            class="w-full pr-12 pl-4 py-3 rounded-xl border border-secondary-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm"
          />
        </div>
      </div>

      <!-- Category tabs -->
      <div v-if="categories.length > 0" class="flex flex-wrap gap-2 justify-center mb-8">
        <button
          @click="activeCategory = null"
          :class="[
            'px-4 py-2 rounded-full text-sm font-medium transition',
            activeCategory === null
              ? 'bg-primary-500 text-white'
              : 'bg-white border border-secondary-200 text-secondary-600 hover:border-primary-400 hover:text-primary-600',
          ]"
        >
          همه
        </button>
        <button
          v-for="cat in categories"
          :key="cat"
          @click="activeCategory = cat"
          :class="[
            'px-4 py-2 rounded-full text-sm font-medium transition',
            activeCategory === cat
              ? 'bg-primary-500 text-white'
              : 'bg-white border border-secondary-200 text-secondary-600 hover:border-primary-400 hover:text-primary-600',
          ]"
        >
          {{ cat }}
        </button>
      </div>

      <!-- Loading -->
      <div v-if="isLoading" class="flex justify-center py-16">
        <div class="animate-spin w-10 h-10 border-4 border-primary-500 border-t-transparent rounded-full"></div>
      </div>

      <!-- Empty search result -->
      <div v-else-if="filtered.length === 0" class="text-center py-16">
        <p class="text-secondary-500">سوالی با این مشخصات یافت نشد.</p>
        <button
          @click="searchQuery = ''; activeCategory = null"
          class="mt-4 text-primary-600 hover:underline text-sm"
        >
          پاک کردن فیلتر
        </button>
      </div>

      <!-- Accordion -->
      <div v-else class="max-w-3xl mx-auto space-y-3">
        <div
          v-for="faq in filtered"
          :key="faq.id"
          class="bg-white rounded-xl border border-secondary-100 overflow-hidden shadow-sm"
        >
          <button
            type="button"
            class="w-full flex items-center justify-between gap-4 px-6 py-4 text-right hover:bg-secondary-50 transition"
            @click="toggle(faq.id)"
            :aria-expanded="openId === faq.id"
          >
            <span class="font-medium text-secondary-800 text-sm md:text-base">{{ faq.question }}</span>
            <ChevronDown
              :class="['w-5 h-5 text-secondary-400 flex-shrink-0 transition-transform duration-200', openId === faq.id && 'rotate-180']"
            />
          </button>
          <div
            v-show="openId === faq.id"
            class="px-6 pb-5 text-secondary-600 text-sm leading-7 border-t border-secondary-50"
          >
            <p class="pt-4 whitespace-pre-line">{{ faq.answer }}</p>
          </div>
        </div>
      </div>

      <!-- Contact CTA -->
      <div class="max-w-3xl mx-auto mt-12 p-6 bg-primary-50 rounded-2xl text-center border border-primary-100">
        <p class="text-secondary-700 mb-3">هنوز سوالی دارید؟</p>
        <p class="text-secondary-500 text-sm mb-4">تیم پشتیبانی ما آماده پاسخگویی به سوالات شما است.</p>
        <a
          href="tel:02100000000"
          class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition text-sm font-medium"
        >
          تماس با پشتیبانی
        </a>
      </div>
    </div>
  </div>
</template>
