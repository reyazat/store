<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Store, ShoppingCart, Package, Users, Loader2 } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import api from '@shared/utils/api'

const authStore = useAuthStore()

interface StoreSummary {
  store: { id: number; name: string }
  owner: { id: number; name: string; email: string }
}

const summary = ref<StoreSummary | null>(null)
const isLoading = ref(true)
const error = ref('')

const loadDashboard = async () => {
  isLoading.value = true
  error.value = ''
  try {
    const response = await api.get('/store/dashboard')
    summary.value = response.data?.data ?? null
  } catch {
    error.value = 'خطا در بارگذاری اطلاعات فروشگاه'
  } finally {
    isLoading.value = false
  }
}

onMounted(loadDashboard)
</script>

<template>
  <div dir="rtl">
    <div v-if="isLoading" class="flex items-center justify-center py-24">
      <Loader2 class="w-8 h-8 animate-spin text-primary-500" />
    </div>

    <div v-else-if="error" class="bg-red-50 text-red-600 rounded-xl p-4 text-sm">
      {{ error }}
    </div>

    <template v-else>
      <!-- Header -->
      <div class="mb-6">
        <h2 class="text-2xl font-bold text-secondary-800">
          خوش آمدید، {{ authStore.user?.name }}
        </h2>
        <p class="text-secondary-500 mt-1">
          مدیریت فروشگاه
          <span class="font-medium text-primary-600">{{ summary?.store.name }}</span>
        </p>
      </div>

      <!-- Stats Placeholder -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl p-5 shadow-sm flex items-center gap-4">
          <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
            <ShoppingCart class="w-6 h-6 text-blue-600" />
          </div>
          <div>
            <p class="text-secondary-500 text-sm">سفارشات</p>
            <p class="text-2xl font-bold text-secondary-800">—</p>
          </div>
        </div>

        <div class="bg-white rounded-xl p-5 shadow-sm flex items-center gap-4">
          <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
            <Package class="w-6 h-6 text-green-600" />
          </div>
          <div>
            <p class="text-secondary-500 text-sm">محصولات</p>
            <p class="text-2xl font-bold text-secondary-800">—</p>
          </div>
        </div>

        <div class="bg-white rounded-xl p-5 shadow-sm flex items-center gap-4">
          <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
            <Users class="w-6 h-6 text-purple-600" />
          </div>
          <div>
            <p class="text-secondary-500 text-sm">اعضای فروشگاه</p>
            <p class="text-2xl font-bold text-secondary-800">—</p>
          </div>
        </div>

        <div class="bg-white rounded-xl p-5 shadow-sm flex items-center gap-4">
          <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
            <Store class="w-6 h-6 text-orange-600" />
          </div>
          <div>
            <p class="text-secondary-500 text-sm">نام فروشگاه</p>
            <p class="text-base font-bold text-secondary-800 truncate">{{ summary?.store.name }}</p>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
