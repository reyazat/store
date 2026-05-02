<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { Eye, Search } from 'lucide-vue-next'
import { useAdminList } from '../composables/useAdminList'
import { orderService, type AdminOrder } from '../services/orderService'
import { orderStatusService } from '../services/paymentMethodService'
import type { OrderStatus } from '../types/payment'
import AdminPagination from '../components/AdminPagination.vue'

const { items: orders, isLoading, errorMsg, filters, meta, debouncedReload, reload, goToPage } =
  useAdminList<AdminOrder, { search: string; status: string; per_page: number }>({
    defaultFilters: { search: '', status: '', per_page: 20 },
    fetchFn: (f) => orderService.getAll(f),
  })

const statuses = ref<OrderStatus[]>([])
onMounted(async () => {
  try { statuses.value = await orderStatusService.getAll() } catch { /* non-critical */ }
})

const formatPrice = (price: number) => new Intl.NumberFormat('fa-IR').format(price)
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-secondary-800">مدیریت سفارشات</h1>
        <p v-if="!isLoading" class="text-sm text-secondary-500 mt-1">{{ meta.total }} سفارش یافت شد</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-4 mb-4 flex flex-wrap items-center gap-3">
      <div class="relative flex-1 min-w-48">
        <Search class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-secondary-400" />
        <input
          v-model="filters.search"
          type="text"
          placeholder="جستجو با شماره سفارش یا نام مشتری..."
          class="w-full pr-9 pl-4 h-10 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm"
          @input="debouncedReload"
        />
      </div>
      <select
        v-model="filters.status"
        class="h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 text-sm text-secondary-700"
        @change="reload"
      >
        <option value="">همه وضعیت‌ها</option>
        <option v-for="s in statuses" :key="s.key" :value="s.key">{{ s.label }}</option>
      </select>
      <select
        v-model="filters.per_page"
        class="h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 text-sm text-secondary-700"
      >
        <option :value="20">۲۰ در صفحه</option>
        <option :value="50">۵۰ در صفحه</option>
        <option :value="100">۱۰۰ در صفحه</option>
      </select>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div v-if="errorMsg" class="p-6 text-center">
        <p class="text-red-500 mb-3">{{ errorMsg }}</p>
        <button class="px-4 py-2 bg-primary-500 text-white rounded-lg text-sm hover:bg-primary-600 transition" @click="reload">تلاش مجدد</button>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-secondary-50 border-b border-secondary-100">
            <tr>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">شماره سفارش</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">مشتری</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-secondary-500">تعداد اقلام</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">مبلغ کل</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-secondary-500">وضعیت</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">تاریخ</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-secondary-500">عملیات</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-secondary-100">
            <template v-if="isLoading">
              <tr v-for="i in 8" :key="i">
                <td class="px-6 py-4"><div class="h-4 bg-secondary-200 rounded animate-pulse w-24"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-secondary-200 rounded animate-pulse w-32"></div></td>
                <td class="px-6 py-4 text-center"><div class="h-4 bg-secondary-200 rounded animate-pulse w-8 mx-auto"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-secondary-200 rounded animate-pulse w-24"></div></td>
                <td class="px-6 py-4 text-center"><div class="h-5 bg-secondary-200 rounded-full animate-pulse w-20 mx-auto"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-secondary-200 rounded animate-pulse w-28"></div></td>
                <td class="px-6 py-4"></td>
              </tr>
            </template>
            <tr v-else-if="orders.length === 0">
              <td colspan="7" class="px-6 py-12 text-center text-secondary-400 text-sm">سفارشی یافت نشد</td>
            </tr>
            <tr v-else v-for="order in orders" :key="order.id" class="hover:bg-secondary-50 transition">
              <td class="px-6 py-4 text-sm font-medium text-secondary-800">{{ order.order_number }}</td>
              <td class="px-6 py-4 text-sm text-secondary-600">{{ order.customer_name || order.user?.name }}</td>
              <td class="px-6 py-4 text-center text-sm text-secondary-600">{{ order.items_count }} کالا</td>
              <td class="px-6 py-4 text-sm font-medium text-secondary-800">{{ formatPrice(order.total) }} تومان</td>
              <td class="px-6 py-4 text-center">
                <span
                  class="px-2.5 py-0.5 text-xs rounded-full font-medium"
                  :style="{ backgroundColor: (order.status_color || '#6b7280') + '22', color: order.status_color || '#6b7280' }"
                >
                  {{ order.status_label || order.status }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-secondary-500">{{ order.created_at }}</td>
              <td class="px-6 py-4 text-center">
                <RouterLink :to="`/admin/orders/${order.id}`" class="p-2 hover:bg-secondary-100 rounded-lg inline-flex" title="مشاهده جزئیات">
                  <Eye class="w-4 h-4 text-secondary-600" />
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <AdminPagination
        v-if="!isLoading && !errorMsg && meta.last_page > 1"
        :meta="meta"
        @page="goToPage"
      />
    </div>
  </div>
</template>
