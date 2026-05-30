<script setup lang="ts">
import { ref } from 'vue'
import { Search, Eye, X, Calendar, User, Globe, FileText } from 'lucide-vue-next'
import { formatDateTime } from '@shared/utils/format'
import { useAdminList } from '../composables/useAdminList'
import { iamService } from '../services/iamService'
import type { AuditLog } from '../types/iam'
import AdminPagination from '../components/AdminPagination.vue'

const { items: logs, isLoading, errorMsg, filters, meta, debouncedReload, reload, goToPage } =
  useAdminList<AuditLog, { event: string; log_name: string; ip: string; start_date: string; end_date: string; per_page: number }>({
    defaultFilters: { event: '', log_name: '', ip: '', start_date: '', end_date: '', per_page: 20 },
    fetchFn: (f) => iamService.getAuditLogs(f),
  })

const selectedLog = ref<AuditLog | null>(null)
const isDetailOpen = ref(false)
const detailLoading = ref(false)

const openDetail = async (log: AuditLog) => {
  selectedLog.value = log
  isDetailOpen.value = true
  detailLoading.value = true
  try {
    const full = await iamService.getAuditLogById(log.id)
    selectedLog.value = full
  } catch {
    // Use list data as fallback
  } finally {
    detailLoading.value = false
  }
}

const closeDetail = () => {
  isDetailOpen.value = false
  selectedLog.value = null
}

const eventClass = (event: string | null) => {
  const map: Record<string, string> = {
    login:   'bg-green-100 text-green-700',
    logout:  'bg-secondary-100 text-secondary-600',
    created: 'bg-blue-100 text-blue-700',
    updated: 'bg-yellow-100 text-yellow-700',
    deleted: 'bg-red-100 text-red-700',
    locked:  'bg-orange-100 text-orange-700',
    unlocked:'bg-teal-100 text-teal-700',
  }
  return map[event ?? ''] ?? 'bg-secondary-100 text-secondary-500'
}

const formatProperties = (props: Record<string, unknown>): string => {
  try {
    return JSON.stringify(props, null, 2)
  } catch {
    return String(props)
  }
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-secondary-800">لاگ‌های فعالیت</h1>
        <p v-if="!isLoading" class="text-sm text-secondary-500 mt-1">{{ meta.total }} رکورد یافت شد</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-4 mb-4 flex flex-wrap items-center gap-3">
      <div class="relative flex-1 min-w-40">
        <Search class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-secondary-400" />
        <input
          v-model="filters.event"
          type="text"
          placeholder="رویداد (مثال: login)"
          class="w-full pr-9 pl-4 h-10 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm"
          @input="debouncedReload"
        />
      </div>
      <input
        v-model="filters.log_name"
        type="text"
        placeholder="نام لاگ"
        class="h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 text-sm w-36"
        @input="debouncedReload"
      />
      <div class="relative">
        <Globe class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-secondary-400" />
        <input
          v-model="filters.ip"
          type="text"
          placeholder="آدرس IP"
          class="h-10 pr-9 pl-3 rounded-lg border border-secondary-200 focus:border-primary-500 text-sm w-36"
          @input="debouncedReload"
        />
      </div>
      <div class="flex items-center gap-2">
        <div class="relative">
          <Calendar class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-secondary-400" />
          <input
            v-model="filters.start_date"
            type="date"
            class="h-10 pr-9 pl-3 rounded-lg border border-secondary-200 focus:border-primary-500 text-sm"
            @change="reload"
          />
        </div>
        <span class="text-secondary-400 text-sm">تا</span>
        <input
          v-model="filters.end_date"
          type="date"
          class="h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 text-sm"
          @change="reload"
        />
      </div>
      <select
        v-model="filters.per_page"
        class="h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 text-sm text-secondary-700"
        @change="reload"
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
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">توضیحات</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-secondary-500">رویداد</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">نام لاگ</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">کاربر انجام‌دهنده</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">IP</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">تاریخ</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-secondary-500">جزئیات</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-secondary-100">
            <template v-if="isLoading">
              <tr v-for="i in 10" :key="i">
                <td class="px-6 py-4"><div class="h-4 bg-secondary-200 rounded animate-pulse w-48"></div></td>
                <td class="px-6 py-4 text-center"><div class="h-5 bg-secondary-200 rounded-full animate-pulse w-16 mx-auto"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-secondary-200 rounded animate-pulse w-20"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-secondary-200 rounded animate-pulse w-32"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-secondary-200 rounded animate-pulse w-28"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-secondary-200 rounded animate-pulse w-28"></div></td>
                <td class="px-6 py-4"></td>
              </tr>
            </template>
            <tr v-else-if="logs.length === 0">
              <td colspan="7" class="px-6 py-12 text-center text-secondary-400 text-sm">رکوردی یافت نشد</td>
            </tr>
            <tr v-else v-for="log in logs" :key="log.id" class="hover:bg-secondary-50 transition">
              <td class="px-6 py-4 text-sm text-secondary-800 max-w-xs truncate">{{ log.description }}</td>
              <td class="px-6 py-4 text-center">
                <span v-if="log.event" class="px-2.5 py-0.5 text-xs rounded-full font-medium" :class="eventClass(log.event)">
                  {{ log.event }}
                </span>
                <span v-else class="text-secondary-300 text-xs">—</span>
              </td>
              <td class="px-6 py-4 text-sm text-secondary-500">{{ log.log_name }}</td>
              <td class="px-6 py-4 text-sm text-secondary-600">
                <div v-if="log.causer?.data" class="flex items-center gap-1.5">
                  <User class="w-3.5 h-3.5 text-secondary-400 flex-shrink-0" />
                  {{ log.causer.data.name }}
                </div>
                <span v-else class="text-secondary-300">—</span>
              </td>
              <td class="px-6 py-4 text-sm text-secondary-500 font-mono text-xs">
                {{ (log.properties?.ip as string) || '—' }}
              </td>
              <td class="px-6 py-4 text-sm text-secondary-500">{{ formatDateTime(log.created_at) }}</td>
              <td class="px-6 py-4 text-center">
                <button
                  class="p-1.5 hover:bg-secondary-100 rounded-lg transition"
                  title="مشاهده جزئیات"
                  @click="openDetail(log)"
                >
                  <Eye class="w-4 h-4 text-secondary-600" />
                </button>
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

    <!-- Log Detail Modal -->
    <Teleport to="body">
      <div
        v-if="isDetailOpen && selectedLog"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @click.self="closeDetail"
      >
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between p-6 border-b border-secondary-100">
            <h2 class="text-lg font-bold text-secondary-800">جزئیات لاگ #{{ selectedLog.id }}</h2>
            <button class="p-1.5 hover:bg-secondary-100 rounded-lg transition" @click="closeDetail">
              <X class="w-5 h-5 text-secondary-500" />
            </button>
          </div>

          <div v-if="detailLoading" class="p-6 space-y-3">
            <div class="h-4 bg-secondary-200 rounded animate-pulse"></div>
            <div class="h-4 bg-secondary-200 rounded animate-pulse w-3/4"></div>
            <div class="h-4 bg-secondary-200 rounded animate-pulse w-1/2"></div>
          </div>

          <div v-else class="p-6 space-y-5">
            <!-- Description & Event -->
            <div class="flex items-start justify-between gap-4">
              <div class="flex-1">
                <p class="text-xs text-secondary-400 mb-1">توضیحات</p>
                <p class="text-secondary-800 font-medium">{{ selectedLog.description }}</p>
              </div>
              <span
                v-if="selectedLog.event"
                class="px-2.5 py-0.5 text-xs rounded-full font-medium flex-shrink-0"
                :class="eventClass(selectedLog.event)"
              >{{ selectedLog.event }}</span>
            </div>

            <!-- Meta -->
            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
              <div>
                <dt class="text-secondary-400 text-xs mb-0.5">نام لاگ</dt>
                <dd class="text-secondary-800">{{ selectedLog.log_name }}</dd>
              </div>
              <div>
                <dt class="text-secondary-400 text-xs mb-0.5">تاریخ</dt>
                <dd class="text-secondary-800">{{ formatDate(selectedLog.created_at) }}</dd>
              </div>
              <div v-if="selectedLog.causer">
                <dt class="text-secondary-400 text-xs mb-0.5">کاربر انجام‌دهنده</dt>
                <dd class="text-secondary-800">{{ selectedLog.causer.data?.name || '—' }}</dd>
                <dd class="text-xs text-secondary-400">{{ selectedLog.causer.data?.email }}</dd>
              </div>
              <div v-if="selectedLog.subject">
                <dt class="text-secondary-400 text-xs mb-0.5">موضوع (Subject)</dt>
                <dd class="text-secondary-800 text-xs font-mono break-all">{{ selectedLog.subject.type }}</dd>
                <dd class="text-xs text-secondary-400">ID: {{ selectedLog.subject.id }}</dd>
              </div>
              <div v-if="(selectedLog.properties?.ip as string)">
                <dt class="text-secondary-400 text-xs mb-0.5">آدرس IP</dt>
                <dd class="text-secondary-800 font-mono text-xs">{{ selectedLog.properties.ip as string }}</dd>
              </div>
              <div v-if="(selectedLog.properties?.user_agent as string)">
                <dt class="text-secondary-400 text-xs mb-0.5">مرورگر</dt>
                <dd class="text-secondary-600 text-xs truncate">{{ (selectedLog.properties.user_agent as string) }}</dd>
              </div>
            </dl>

            <!-- Raw Properties -->
            <div v-if="Object.keys(selectedLog.properties || {}).length">
              <div class="flex items-center gap-2 mb-2">
                <FileText class="w-4 h-4 text-secondary-400" />
                <p class="text-xs font-medium text-secondary-500">پارامترهای اضافی</p>
              </div>
              <pre class="bg-secondary-50 rounded-lg p-3 text-xs text-secondary-700 overflow-x-auto font-mono leading-relaxed">{{ formatProperties(selectedLog.properties) }}</pre>
            </div>
          </div>

          <div class="p-6 border-t border-secondary-100 flex justify-end">
            <button
              class="px-4 py-2 text-sm bg-secondary-100 text-secondary-700 rounded-lg hover:bg-secondary-200 transition"
              @click="closeDetail"
            >بستن</button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
