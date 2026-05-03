<script setup lang="ts">
import { onMounted } from 'vue'
import AppHeader from './AppHeader.vue'
import AppFooter from './AppFooter.vue'
import CartDrawer from './CartDrawer.vue'
import { useWishlistStore } from '@/stores/wishlist'

const wishlistStore = useWishlistStore()
onMounted(() => { wishlistStore.init() })
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
