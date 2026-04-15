<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>

<template>
  <div class="container mx-auto px-4 py-16 text-center">
    <div class="max-w-md mx-auto">
      <h1 class="text-6xl font-bold text-gray-300 mb-4">404</h1>
      <h2 class="text-2xl font-bold mb-4">صفحه مورد نظر یافت نشد</h2>
      <p class="text-gray-600 mb-8">
        متأسفانه صفحه‌ای که به دنبال آن هستید وجود ندارد یا حذف شده است.
      </p>
      <RouterLink 
        to="/" 
        class="inline-block bg-primary-600 text-white px-8 py-3 rounded-lg hover:bg-primary-700 transition"
      >
        بازگشت به صفحه اصلی
      </RouterLink>
    </div>
  </div>
</template>
