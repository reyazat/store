<script setup lang="ts">
import { ChevronRight, ChevronLeft } from 'lucide-vue-next'
import type { PaginationMeta } from '../composables/useAdminList'

defineProps<{ meta: PaginationMeta }>()
const emit = defineEmits<{ (e: 'page', value: number): void }>()
</script>

<template>
  <div class="flex items-center justify-between px-6 py-4 border-t border-secondary-100">
    <p class="text-sm text-secondary-500">
      صفحه {{ meta.current_page }} از {{ meta.last_page }}
      <span class="text-secondary-400">({{ meta.total }} مورد)</span>
    </p>
    <div class="flex items-center gap-1">
      <button
        :disabled="meta.current_page === 1"
        class="p-2 rounded-lg hover:bg-secondary-100 disabled:opacity-40 disabled:cursor-not-allowed transition"
        @click="emit('page', meta.current_page - 1)"
      >
        <ChevronRight class="w-4 h-4" />
      </button>

      <template v-for="p in meta.last_page" :key="p">
        <button
          v-if="p === 1 || p === meta.last_page || Math.abs(p - meta.current_page) <= 1"
          :class="p === meta.current_page
            ? 'bg-primary-500 text-white'
            : 'hover:bg-secondary-100 text-secondary-700'"
          class="w-9 h-9 rounded-lg text-sm font-medium transition"
          @click="emit('page', p)"
        >
          {{ p }}
        </button>
        <span
          v-else-if="Math.abs(p - meta.current_page) === 2"
          class="w-9 h-9 flex items-center justify-center text-secondary-400 text-sm"
        >
          ...
        </span>
      </template>

      <button
        :disabled="meta.current_page === meta.last_page"
        class="p-2 rounded-lg hover:bg-secondary-100 disabled:opacity-40 disabled:cursor-not-allowed transition"
        @click="emit('page', meta.current_page + 1)"
      >
        <ChevronLeft class="w-4 h-4" />
      </button>
    </div>
  </div>
</template>
