<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Store, ShoppingCart, Package, Users, MessageSquare, Clock, Loader2, AlertCircle } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import api from '@shared/utils/api'

const authStore = useAuthStore()
const router    = useRouter()

interface StoreStats {
  total_products:  number
  total_orders:    number
  pending_orders:  number
  pending_reviews: number
}

interface DashboardData {
  store: { id: number; uuid: string; name: string; status: string; members_count: number }
  owner: { id: number; name: string; email: string }
  team:  { owners: number; admins: number; editors: number; members: number }
  stats: StoreStats
}

const data      = ref<DashboardData | null>(null)
const isLoading = ref(true)
const error     = ref('')

const load = async () => {
  isLoading.value = true
  error.value     = ''
  try {
    const res   = await api.get('/store/dashboard')
    data.value  = res.data?.data ?? null
  } catch {
    error.value = 'خطا در بارگذاری اطلاعات فروشگاه'
  } finally {
    isLoading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div dir="rtl">
    <!-- Loading -->
    <div v-if="isLoading" class="flex items-center justify-center py-24">
      <Loader2 class="w-8 h-8 animate-spin text-primary-500" />
    </div>

    <!-- Error -->
    <div v-else-if="error" class="flex items-center gap-3 bg-red-50 text-red-600 rounded-xl p-4 text-sm">
      <AlertCircle class="w-5 h-5 flex-shrink-0" />
      {{ error }}
    </div>

    <template v-else-if="data">
      <!-- Header -->
      <div class="mb-6">
        <h2 class="text-2xl font-bold text-secondary-800">
          خوش آمدید، {{ authStore.user?.name }}
        </h2>
        <p class="text-secondary-500 mt-1">
          مدیریت فروشگاه
          <span class="font-semibold text-primary-600">{{ data.store.name }}</span>
        </p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Total Orders -->
        <button
          @click="router.push('/store/orders')"
          class="bg-white rounded-xl p-5 shadow-sm flex items-center gap-4 hover:shadow-md transition text-right w-full"
        >
          <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0">
            <ShoppingCart class="w-6 h-6 text-blue-600" />
          </div>
          <div>
            <p class="text-secondary-500 text-sm">کل سفارشات</p>
            <p class="text-2xl font-bold text-secondary-800">{{ data.stats.total_orders }}</p>
          </div>
        </button>

        <!-- Pending Orders -->
        <button
          @click="router.push('/store/orders')"
          class="bg-white rounded-xl p-5 shadow-sm flex items-center gap-4 hover:shadow-md transition text-right w-full"
        >
          <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center flex-shrink-0">
            <Clock class="w-6 h-6 text-amber-600" />
          </div>
          <div>
            <p class="text-secondary-500 text-sm">سفارشات در انتظار</p>
            <p class="text-2xl font-bold text-secondary-800">{{ data.stats.pending_orders }}</p>
          </div>
        </button>

        <!-- Products -->
        <button
          @click="router.push('/store/products')"
          class="bg-white rounded-xl p-5 shadow-sm flex items-center gap-4 hover:shadow-md transition text-right w-full"
        >
          <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
            <Package class="w-6 h-6 text-green-600" />
          </div>
          <div>
            <p class="text-secondary-500 text-sm">محصولات</p>
            <p class="text-2xl font-bold text-secondary-800">{{ data.stats.total_products }}</p>
          </div>
        </button>

        <!-- Pending Reviews -->
        <button
          @click="router.push('/store/reviews')"
          class="bg-white rounded-xl p-5 shadow-sm flex items-center gap-4 hover:shadow-md transition text-right w-full"
        >
          <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center flex-shrink-0">
            <MessageSquare class="w-6 h-6 text-purple-600" />
          </div>
          <div>
            <p class="text-secondary-500 text-sm">نظرات در انتظار</p>
            <p class="text-2xl font-bold text-secondary-800">{{ data.stats.pending_reviews }}</p>
          </div>
        </button>
      </div>

      <!-- Store & Team info -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Store Info -->
        <div class="bg-white rounded-xl p-5 shadow-sm">
          <div class="flex items-center gap-3 mb-4">
            <Store class="w-5 h-5 text-primary-600" />
            <h3 class="font-bold text-secondary-800">اطلاعات فروشگاه</h3>
          </div>
          <div class="space-y-2 text-sm">
            <div class="flex items-center justify-between">
              <span class="text-secondary-500">نام فروشگاه</span>
              <span class="font-medium text-secondary-800">{{ data.store.name }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-secondary-500">وضعیت</span>
              <span :class="[
                'text-xs font-medium px-2 py-0.5 rounded-full',
                data.store.status === 'active' ? 'bg-green-100 text-green-700' :
                data.store.status === 'suspended' ? 'bg-red-100 text-red-600' :
                'bg-secondary-100 text-secondary-500'
              ]">
                {{ data.store.status === 'active' ? 'فعال' : data.store.status === 'suspended' ? 'معلق' : 'غیرفعال' }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-secondary-500">تعداد اعضا</span>
              <span class="font-medium text-secondary-800">{{ data.store.members_count }}</span>
            </div>
          </div>
        </div>

        <!-- Team Breakdown -->
        <div class="bg-white rounded-xl p-5 shadow-sm">
          <div class="flex items-center gap-3 mb-4">
            <Users class="w-5 h-5 text-primary-600" />
            <h3 class="font-bold text-secondary-800">تیم فروشگاه</h3>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div class="bg-purple-50 rounded-lg p-3 text-center">
              <p class="text-xl font-bold text-purple-700">{{ data.team.owners }}</p>
              <p class="text-xs text-purple-500 mt-0.5">مالک</p>
            </div>
            <div class="bg-blue-50 rounded-lg p-3 text-center">
              <p class="text-xl font-bold text-blue-700">{{ data.team.admins }}</p>
              <p class="text-xs text-blue-500 mt-0.5">مدیر</p>
            </div>
            <div class="bg-yellow-50 rounded-lg p-3 text-center">
              <p class="text-xl font-bold text-yellow-700">{{ data.team.editors }}</p>
              <p class="text-xs text-yellow-500 mt-0.5">ویرایشگر</p>
            </div>
            <div class="bg-green-50 rounded-lg p-3 text-center">
              <p class="text-xl font-bold text-green-700">{{ data.team.members }}</p>
              <p class="text-xs text-green-500 mt-0.5">عضو</p>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
