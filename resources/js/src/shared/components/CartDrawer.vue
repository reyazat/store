<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { X, Minus, Plus, Trash2 } from 'lucide-vue-next'
import { useCartStore } from '@/stores/cart'
import { formatPrice } from '@shared/utils/format'

const cartStore = useCartStore()
</script>

<template>
  <Teleport to="body">
    <!-- Overlay -->
    <Transition name="fade">
      <div 
        v-if="cartStore.isDrawerOpen"
        class="fixed inset-0 bg-black/50 z-50"
        @click="cartStore.closeDrawer"
      />
    </Transition>
    
    <!-- Drawer -->
    <Transition name="slide">
      <div 
        v-if="cartStore.isDrawerOpen"
        class="fixed top-0 left-0 h-full w-full max-w-md bg-white shadow-xl z-50 flex flex-col"
      >
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b">
          <h2 class="text-lg font-bold">سبد خرید</h2>
          <button 
            @click="cartStore.closeDrawer"
            class="p-2 hover:bg-gray-100 rounded-full"
            aria-label="بستن سبد خرید"
          >
            <X class="w-5 h-5" />
          </button>
        </div>
        
        <!-- Items -->
        <div class="flex-1 overflow-y-auto p-4">
          <div v-if="cartStore.items.length === 0" class="text-center py-12 text-gray-500">
            <p>سبد خرید شما خالی است</p>
            <RouterLink 
              to="/products" 
              class="inline-block mt-4 text-primary-600 hover:underline"
              @click="cartStore.closeDrawer"
            >
              مشاهده محصولات
            </RouterLink>
          </div>
          
          <div v-else class="space-y-4">
            <div 
              v-for="item in cartStore.items" 
              :key="item.id"
              class="flex gap-4 p-4 bg-gray-50 rounded-lg"
            >
              <img 
                :src="item.image || '/images/placeholder.jpg'" 
                :alt="item.name"
                class="w-20 h-20 object-cover rounded"
              />
              <div class="flex-1">
                <h3 class="font-medium text-sm">{{ item.name }}</h3>
                <p class="text-primary-600 font-bold mt-1">{{ formatPrice(item.price) }} تومان</p>
                
                <div class="flex items-center gap-2 mt-2">
                  <button 
                    @click="cartStore.updateQuantity(item.id, item.quantity - 1)"
                    class="p-1 hover:bg-gray-200 rounded"
                    :aria-label="`کاهش تعداد ${item.name}`"
                  >
                    <Minus class="w-4 h-4" />
                  </button>
                  <span class="w-8 text-center" :aria-label="`تعداد: ${item.quantity}`">{{ item.quantity }}</span>
                  <button 
                    @click="cartStore.updateQuantity(item.id, item.quantity + 1)"
                    class="p-1 hover:bg-gray-200 rounded"
                    :aria-label="`افزایش تعداد ${item.name}`"
                  >
                    <Plus class="w-4 h-4" />
                  </button>
                  <button 
                    @click="cartStore.removeItem(item.id)"
                    class="p-1 hover:bg-red-100 text-red-600 rounded mr-auto"
                    :aria-label="`حذف ${item.name} از سبد خرید`"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Footer -->
        <div v-if="cartStore.items.length > 0" class="border-t p-4 space-y-4">
          <div class="flex justify-between text-lg font-bold">
            <span>جمع کل:</span>
            <span class="text-primary-600">{{ formatPrice(cartStore.total) }} تومان</span>
          </div>
          <RouterLink 
            to="/cart/checkout"
            class="block w-full bg-primary-600 text-white text-center py-3 rounded-lg hover:bg-primary-700 transition"
            @click="cartStore.closeDrawer"
          >
            تکمیل خرید
          </RouterLink>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-enter-active,
.slide-leave-active {
  transition: transform 0.3s ease;
}

.slide-enter-from,
.slide-leave-to {
  transform: translateX(-100%);
}
</style>
