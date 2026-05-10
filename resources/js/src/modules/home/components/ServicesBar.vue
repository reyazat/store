<script setup lang="ts">
import { resolveIcon } from '@shared/utils/icons'
import type { SiteFeature } from '../services'

interface Props {
  features: SiteFeature[]
  loading?: boolean
}

withDefaults(defineProps<Props>(), {
  loading: false,
})
</script>

<template>
  <section class="py-6 bg-white border-t">
    <div class="container mx-auto px-4">
      <!-- Loading skeleton -->
      <div v-if="loading" class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div
          v-for="i in 4"
          :key="i"
          class="flex items-center gap-3 p-4 rounded-xl bg-secondary-50 animate-pulse"
        >
          <div class="w-12 h-12 bg-secondary-100 rounded-full flex-shrink-0"></div>
          <div class="flex-1 space-y-2">
            <div class="h-3 w-20 bg-secondary-100 rounded"></div>
            <div class="h-3 w-28 bg-secondary-100 rounded"></div>
          </div>
        </div>
      </div>

      <!-- Features grid -->
      <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div
          v-for="feature in features"
          :key="feature.id"
          class="flex items-center gap-3 p-4 rounded-xl bg-secondary-50"
        >
          <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
            <component :is="resolveIcon(feature.icon)" class="w-6 h-6 text-primary-600" />
          </div>
          <div>
            <h4 class="font-bold text-secondary-800">{{ feature.title }}</h4>
            <p v-if="feature.description" class="text-sm text-secondary-500">{{ feature.description }}</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
