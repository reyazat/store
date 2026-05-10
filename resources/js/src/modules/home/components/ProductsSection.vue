<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { ProductCard } from '@shared/components'
import type { Component } from 'vue'
import type { Product } from '@shared/types'

interface Props {
  title: string
  icon: Component
  iconClass?: string
  products: Product[]
  viewAllLink: string
  loading?: boolean
  limit?: number
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  limit: 5,
  iconClass: 'text-primary-500',
})
</script>

<template>
  <section class="py-8">
    <div class="container mx-auto px-4">
      <div class="bg-white rounded-2xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl md:text-2xl font-bold text-secondary-800 flex items-center gap-2">
            <component :is="icon" class="w-6 h-6" :class="iconClass" />
            {{ title }}
          </h2>
          <RouterLink
            :to="viewAllLink"
            class="text-primary-600 hover:text-primary-700 font-medium flex items-center gap-1 text-sm transition-colors"
          >
            مشاهده همه
            <svg class="w-4 h-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </RouterLink>
        </div>

        <!-- Loading skeleton -->
        <div v-if="loading" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
          <div v-for="i in limit" :key="i" class="animate-pulse">
            <div class="aspect-square bg-secondary-100 rounded-xl mb-3"></div>
            <div class="h-4 bg-secondary-100 rounded mb-2"></div>
            <div class="h-4 bg-secondary-100 rounded w-2/3"></div>
          </div>
        </div>

        <!-- Products grid -->
        <div v-else-if="products.length" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
          <ProductCard
            v-for="product in products.slice(0, limit)"
            :key="product.id"
            :product="product"
          />
        </div>

        <!-- Empty state -->
        <div v-else class="py-12 text-center text-secondary-400 text-sm">
          محصولی یافت نشد
        </div>
      </div>
    </div>
  </section>
</template>
