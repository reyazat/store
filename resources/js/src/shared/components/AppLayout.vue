<script setup lang="ts">
import AppHeader from './AppHeader.vue'
import AppFooter from './AppFooter.vue'
import CartDrawer from './CartDrawer.vue'
</script>

<template>
  <div class="min-h-screen flex flex-col bg-gray-50" dir="rtl">
    <AppHeader />
    
    <main class="flex-1">
      <slot />
    </main>
    
    <AppFooter />
    <CartDrawer />
  </div>
</template>
