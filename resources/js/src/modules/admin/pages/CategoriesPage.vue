<script setup lang="ts">
import { Plus, Edit, Trash2, Search } from 'lucide-vue-next'
import { useAdminList } from '../composables/useAdminList'
import { categoryService, type AdminCategory } from '../services/categoryService'
import AdminPagination from '../components/AdminPagination.vue'

const { items: categories, isLoading, errorMsg, filters, meta, debouncedReload, reload, goToPage } =
  useAdminList<AdminCategory, { search: string; status: number | null; per_page: number }>({
    defaultFilters: { search: '', status: null, per_page: 20 },
    fetchFn: (f) => categoryService.getAll(f),
  })
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-secondary-800">مدیریت دسته‌بندی‌ها</h1>
        <p v-if="!isLoading" class="text-sm text-secondary-500 mt-1">
          {{ meta.total }} دسته‌بندی یافت شد
        </p>
      </div>
      <button class="flex items-center gap-2 px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition">
        <Plus class="w-5 h-5" />
        افزودن دسته‌بندی
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-4 mb-4 flex flex-wrap items-center gap-3">
      <!-- Search -->
      <div class="relative flex-1 min-w-48">
        <Search class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-secondary-400" />
        <input
          v-model="filters.search"
          type="text"
          placeholder="جستجو در نام یا slug..."
          class="w-full pr-9 pl-4 h-10 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm"
          @input="debouncedReload"
        />
      </div>

      <!-- Status filter -->
      <select
        v-model="filters.status"
        class="h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm text-secondary-700"
        @change="reload"
      >
        <option :value="null">همه وضعیت‌ها</option>
        <option :value="1">فعال</option>
        <option :value="0">غیرفعال</option>
      </select>

      <!-- Per page -->
      <select
        v-model="filters.per_page"
        class="h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm text-secondary-700"
      >
        <option :value="20">۲۰ در صفحه</option>
        <option :value="50">۵۰ در صفحه</option>
        <option :value="100">۱۰۰ در صفحه</option>
      </select>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <!-- Error -->
      <div v-if="errorMsg" class="p-6 text-center">
        <p class="text-red-500 mb-3">{{ errorMsg }}</p>
        <button
          class="px-4 py-2 bg-primary-500 text-white rounded-lg text-sm hover:bg-primary-600 transition"
          @click="reload"
        >
          تلاش مجدد
        </button>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-secondary-50 border-b border-secondary-100">
            <tr>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 uppercase">نام</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500 uppercase">Slug</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-secondary-500 uppercase">محصولات</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-secondary-500 uppercase">وضعیت</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-secondary-500 uppercase">عملیات</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-secondary-100">
            <!-- Loading skeleton -->
            <template v-if="isLoading">
              <tr v-for="i in filters.per_page > 20 ? 20 : filters.per_page" :key="i">
                <td class="px-6 py-4">
                  <div class="h-4 bg-secondary-200 rounded animate-pulse w-32"></div>
                </td>
                <td class="px-6 py-4">
                  <div class="h-4 bg-secondary-200 rounded animate-pulse w-24"></div>
                </td>
                <td class="px-6 py-4 text-center">
                  <div class="h-4 bg-secondary-200 rounded animate-pulse w-8 mx-auto"></div>
                </td>
                <td class="px-6 py-4 text-center">
                  <div class="h-5 bg-secondary-200 rounded-full animate-pulse w-14 mx-auto"></div>
                </td>
                <td class="px-6 py-4"></td>
              </tr>
            </template>

            <!-- Empty state -->
            <tr v-else-if="categories.length === 0">
              <td colspan="5" class="px-6 py-12 text-center text-secondary-400 text-sm">
                دسته‌بندی‌ای یافت نشد
              </td>
            </tr>

            <!-- Rows -->
            <tr
              v-else
              v-for="category in categories"
              :key="category.id"
              class="hover:bg-secondary-50 transition"
            >
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <span v-if="category.icon" class="text-lg">{{ category.icon }}</span>
                  <div>
                    <p class="text-sm font-medium text-secondary-800">{{ category.name }}</p>
                    <p v-if="category.parent_id" class="text-xs text-secondary-400">زیردسته</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-sm text-secondary-500 font-mono">{{ category.slug }}</td>
              <td class="px-6 py-4 text-center">
                <span class="inline-flex items-center justify-center min-w-8 px-2 py-0.5 rounded-full text-xs font-medium bg-secondary-100 text-secondary-600">
                  {{ category.products_count }}
                </span>
              </td>
              <td class="px-6 py-4 text-center">
                <span
                  :class="category.status === 1
                    ? 'bg-green-100 text-green-700'
                    : 'bg-red-100 text-red-600'"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                >
                  {{ category.status === 1 ? 'فعال' : 'غیرفعال' }}
                </span>
              </td>
              <td class="px-6 py-4 text-center">
                <div class="flex items-center justify-center gap-1">
                  <button
                    class="p-2 hover:bg-secondary-100 rounded-lg transition"
                    title="ویرایش"
                  >
                    <Edit class="w-4 h-4 text-secondary-600" />
                  </button>
                  <button
                    class="p-2 hover:bg-red-100 rounded-lg transition"
                    title="حذف"
                  >
                    <Trash2 class="w-4 h-4 text-red-500" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <AdminPagination
        v-if="!isLoading && !errorMsg && meta.last_page > 1"
        :meta="meta"
        @page="goToPage"
      />
    </div>
  </div>
</template>
