<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { Home, ChevronLeft } from 'lucide-vue-next'

interface BreadcrumbItem {
  title: string
  url?: string
}

interface Props {
  items: BreadcrumbItem[]
}

defineProps<Props>()
</script>

<template>
  <nav aria-label="مسیر" class="py-3">
    <ol class="flex items-center flex-wrap gap-1 text-sm">
      <li>
        <RouterLink to="/" class="text-gray-400 hover:text-primary-600 transition-colors">
          <Home class="w-4 h-4" />
        </RouterLink>
      </li>
      
      <li 
        v-for="(item, index) in items" 
        :key="index"
        class="flex items-center gap-1"
      >
        <ChevronLeft class="w-4 h-4 text-gray-300" />
        <RouterLink 
          v-if="item.url && index !== items.length - 1"
          :to="item.url"
          class="text-gray-400 hover:text-primary-600 transition-colors"
        >
          {{ item.title }}
        </RouterLink>
        <span v-else class="text-gray-700">{{ item.title }}</span>
      </li>
    </ol>
  </nav>
</template>
