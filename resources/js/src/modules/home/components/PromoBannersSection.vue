<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { resolveIcon } from '@shared/utils/icons'
import type { PromoBanner } from '../services'

interface Props {
  banners: PromoBanner[]
}

defineProps<Props>()

const gradientClass = (banner: PromoBanner): string => {
  const from = banner.gradient_from ? `from-${banner.gradient_from}` : 'from-primary-500'
  const to = banner.gradient_to ? `to-${banner.gradient_to}` : 'to-primary-700'
  return `bg-gradient-to-br ${from} ${to}`
}
</script>

<template>
  <section class="py-4">
    <div class="container mx-auto px-4">
      <div class="grid md:grid-cols-3 gap-4">
        <component
          :is="banner.link ? RouterLink : 'div'"
          v-for="banner in banners"
          :key="banner.id"
          :to="banner.link || undefined"
          :class="[gradientClass(banner), 'rounded-2xl p-6 text-white relative overflow-hidden group cursor-pointer block']"
        >
          <div class="relative z-10">
            <component :is="resolveIcon(banner.icon)" class="w-10 h-10 mb-3 opacity-80" />
            <h3 class="text-xl font-bold mb-1">{{ banner.title }}</h3>
            <p v-if="banner.subtitle" class="text-white/80 text-sm mb-4">{{ banner.subtitle }}</p>
            <span
              v-if="banner.cta_text"
              class="text-sm font-medium flex items-center gap-1 group-hover:gap-2 transition-all"
            >
              {{ banner.cta_text }}
              <svg class="w-4 h-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </span>
          </div>
          <div class="absolute -bottom-6 -left-6 w-24 h-24 bg-white/10 rounded-full group-hover:scale-150 transition-transform"></div>
        </component>
      </div>
    </div>
  </section>
</template>
