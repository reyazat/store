<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { Search } from 'lucide-vue-next'
</script>

<template>
  <section class="relative bg-gradient-to-br from-indigo-600 via-purple-600 to-indigo-700 text-white overflow-hidden">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-10">
      <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>
    </div>
    
    <div class="container mx-auto px-4 py-16 md:py-24 relative">
      <div class="grid md:grid-cols-2 gap-8 items-center">
        <!-- Right Side - Content -->
        <div class="text-center md:text-right order-2 md:order-1">
          <h1 class="text-3xl md:text-5xl font-bold mb-4 leading-tight">
            بهترین‌ها را با <span class="text-yellow-300">بهترین قیمت</span> بخرید
          </h1>
          <p class="text-lg md:text-xl text-indigo-100 mb-8">
            از میان هزاران محصول با کیفیت، بهترین انتخاب را داشته باشید
          </p>
          
          <!-- Search Box -->
          <div class="max-w-xl mx-auto md:mx-0 mb-6">
            <div class="relative">
              <input
                type="text"
                placeholder="جستجوی محصولات..."
                class="w-full px-6 py-4 pr-12 rounded-full text-gray-800 text-lg focus:outline-none focus:ring-4 focus:ring-yellow-300 shadow-xl"
              />
              <button class="absolute left-2 top-1/2 -translate-y-1/2 bg-yellow-400 text-gray-800 p-3 rounded-full hover:bg-yellow-300 transition">
                <Search class="w-5 h-5" />
              </button>
            </div>
          </div>
          
          <div class="flex flex-wrap gap-3 justify-center md:justify-start">
            <RouterLink 
              to="/products" 
              class="bg-yellow-400 text-gray-900 px-6 py-3 rounded-full font-bold hover:bg-yellow-300 transition shadow-lg"
            >
              مشاهده محصولات
            </RouterLink>
            <RouterLink 
              to="/categories" 
              class="bg-white/20 backdrop-blur-sm text-white px-6 py-3 rounded-full font-bold hover:bg-white/30 transition border-2 border-white/30"
            >
              دسته‌بندی‌ها
            </RouterLink>
          </div>
        </div>
        
        <!-- Left Side - Image/Illustration -->
        <div class="order-1 md:order-2">
          <div class="relative">
            <!-- Shopping Bag Icon -->
            <div class="w-64 h-64 md:w-80 md:h-80 mx-auto bg-white/10 backdrop-blur-sm rounded-full flex items-center justify-center border-4 border-white/20">
              <svg class="w-32 h-32 md:w-40 md:h-40 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
              </svg>
            </div>
            
            <!-- Floating Elements -->
            <div class="absolute top-0 right-0 w-20 h-20 bg-yellow-400 rounded-full opacity-20 animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-16 h-16 bg-pink-400 rounded-full opacity-20 animate-pulse" style="animation-delay: 1s;"></div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Wave Bottom -->
    <div class="absolute bottom-0 left-0 right-0">
      <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full">
        <path d="M0 0L60 10C120 20 240 40 360 46.7C480 53 600 47 720 43.3C840 40 960 40 1080 46.7C1200 53 1320 67 1380 73.3L1440 80V120H1380C1320 120 1200 120 1080 120C960 120 840 120 720 120C600 120 480 120 360 120C240 120 120 120 60 120H0V0Z" fill="#F9FAFB"/>
      </svg>
    </div>
  </section>
</template>

<style scoped>
@keyframes pulse {
  0%, 100% {
    opacity: 0.2;
    transform: scale(1);
  }
  50% {
    opacity: 0.3;
    transform: scale(1.1);
  }
}

.animate-pulse {
  animation: pulse 3s ease-in-out infinite;
}
</style>
