<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Breadcrumb } from '@shared/components'
import { useCartStore } from '@/stores/cart'
import { cartService } from '../services'
import { formatPrice } from '@shared/utils/format'
import type { Address } from '@shared/types'

const router = useRouter()
const cartStore = useCartStore()

const addresses = ref<Address[]>([])
const selectedAddressId = ref<number | null>(null)
const isLoading = ref(false)
const isSubmitting = ref(false)

onMounted(async () => {
  isLoading.value = true
  try {
    // Sync local cart items to server before checkout
    await syncCartToServer()

    const response = await cartService.getAddresses()
    addresses.value = response.data
    const defaultAddress = addresses.value.find(a => a.is_default)
    if (defaultAddress) {
      selectedAddressId.value = defaultAddress.id
    }
  } catch (error) {
    console.error('Failed to load checkout data:', error)
  } finally {
    isLoading.value = false
  }
})

const syncCartToServer = async () => {
  const localItems = cartStore.items
  if (localItems.length === 0) return

  for (const item of localItems) {
    if (item.listingId) {
      try {
        await cartService.addItem(item.listingId, item.quantity, item.variantId)
      } catch {
        // item may already be in server cart — ignore duplicate errors
      }
    }
  }
}

const submitOrder = async () => {
  if (!selectedAddressId.value) {
    alert('لطفاً یک آدرس انتخاب کنید')
    return
  }

  if (cartStore.items.length === 0) {
    alert('سبد خرید خالی است')
    return
  }

  isSubmitting.value = true
  try {
    const response = await cartService.createOrder(selectedAddressId.value)
    if (response.success) {
      cartStore.clearCart()
      router.push(`/user/orders/${response.data.id}`)
    }
  } catch (error: any) {
    console.error('Failed to create order:', error)
    const msg = error.response?.data?.message || 'خطا در ثبت سفارش'
    alert(msg)
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="container mx-auto px-4 py-8">
    <Breadcrumb :items="[{ title: 'سبد خرید', url: '/cart' }, { title: 'تکمیل خرید' }]" />
    
    <h1 class="text-2xl font-bold mb-8">تکمیل خرید</h1>
    
    <div class="grid lg:grid-cols-3 gap-8">
      <!-- Address Selection -->
      <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-lg font-bold mb-4">انتخاب آدرس</h2>
          
          <div v-if="isLoading" class="space-y-4">
            <div v-for="i in 2" :key="i" class="animate-pulse p-4 border rounded-lg">
              <div class="h-4 bg-gray-200 rounded w-1/4 mb-2"></div>
              <div class="h-4 bg-gray-200 rounded w-3/4"></div>
            </div>
          </div>
          
          <div v-else-if="addresses.length === 0" class="text-center py-8">
            <p class="text-gray-500 mb-4">هیچ آدرسی ثبت نشده است</p>
            <RouterLink to="/user/addresses" class="text-primary-600 hover:underline">
              افزودن آدرس جدید
            </RouterLink>
          </div>
          
          <div v-else class="space-y-4">
            <label 
              v-for="address in addresses" 
              :key="address.id"
              :class="[
                'block p-4 border rounded-lg cursor-pointer transition',
                selectedAddressId === address.id 
                  ? 'border-primary-600 bg-primary-50' 
                  : 'hover:border-gray-400'
              ]"
            >
              <input 
                type="radio" 
                :value="address.id" 
                v-model="selectedAddressId"
                class="hidden"
              />
              <div class="flex justify-between">
                <span class="font-medium">{{ address.title || 'آدرس' }}</span>
                <span v-if="address.is_default" class="text-xs bg-primary-100 text-primary-700 px-2 py-1 rounded">
                  پیش‌فرض
                </span>
              </div>
              <p class="text-gray-600 mt-1">
                {{ address.province }}، {{ address.city }}، {{ address.address }}
              </p>
              <p class="text-sm text-gray-500 mt-1">
                {{ address.first_name }} {{ address.last_name }} - {{ address.phone }}
              </p>
            </label>
          </div>
        </div>
      </div>
      
      <!-- Order Summary -->
      <div>
        <div class="bg-white rounded-lg shadow p-6 sticky top-24">
          <h2 class="text-lg font-bold mb-4">خلاصه سفارش</h2>
          
          <div class="space-y-3 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-600">تعداد محصولات:</span>
              <span>{{ cartStore.itemsCount }} عدد</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">جمع سبد خرید:</span>
              <span>{{ formatPrice(cartStore.total) }} تومان</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">هزینه ارسال:</span>
              <span class="text-green-600">رایگان</span>
            </div>
            <hr />
            <div class="flex justify-between text-lg font-bold">
              <span>مبلغ قابل پرداخت:</span>
              <span class="text-primary-600">{{ formatPrice(cartStore.total) }} تومان</span>
            </div>
          </div>
          
          <button 
            @click="submitOrder"
            :disabled="isSubmitting || !selectedAddressId"
            class="w-full mt-6 bg-primary-600 text-white py-3 rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
          >
            {{ isSubmitting ? 'در حال ثبت...' : 'ثبت سفارش' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
