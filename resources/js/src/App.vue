<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppLayout from '@shared/components/AppLayout.vue'

const route = useRoute()
const isAdminRoute = computed(() => route.path.startsWith('/admin'))
</script>

<template>
  <AppLayout v-if="!isAdminRoute">
    <RouterView />
  </AppLayout>
  <RouterView v-else />
</template>
