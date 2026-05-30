<script setup lang="ts">
import { ref, computed } from 'vue'
import { Search, Eye, Lock, Unlock, X, CheckCircle, XCircle, ShieldAlert, Shield } from 'lucide-vue-next'
import { formatDate } from '@shared/utils/format'
import { useAdminList } from '../composables/useAdminList'
import { iamService } from '../services/iamService'
import type { IamUser } from '../types/iam'
import AdminPagination from '../components/AdminPagination.vue'

const { items: users, isLoading, errorMsg, filters, meta, debouncedReload, reload, goToPage } =
  useAdminList<IamUser, { search: string; status: string; per_page: number }>({
    defaultFilters: { search: '', status: '', per_page: 15 },
    fetchFn: (f) => iamService.getUsers(f),
  })

const selectedUser = ref<IamUser | null>(null)
const isDetailOpen = ref(false)
const isLockOpen = ref(false)
const lockReason = ref('')
const lockDuration = ref(30)
const actionLoading = ref(false)
const actionError = ref<string | null>(null)

const openDetail = (user: IamUser) => {
  selectedUser.value = user
  isDetailOpen.value = true
}

const closeDetail = () => {
  isDetailOpen.value = false
  selectedUser.value = null
}

const openLockDialog = (user: IamUser) => {
  selectedUser.value = user
  lockReason.value = ''
  lockDuration.value = 30
  actionError.value = null
  isLockOpen.value = true
}

const closeLockDialog = () => {
  isLockOpen.value = false
}

const confirmLock = async () => {
  if (!selectedUser.value || !lockReason.value.trim()) return
  actionLoading.value = true
  actionError.value = null
  try {
    await iamService.lockAccount({
      user_id: selectedUser.value.id,
      reason: lockReason.value,
      duration_minutes: lockDuration.value,
    })
    isLockOpen.value = false
    reload()
  } catch {
    actionError.value = 'قفل کردن حساب با خطا مواجه شد'
  } finally {
    actionLoading.value = false
  }
}

const handleUnlock = async (user: IamUser) => {
  actionLoading.value = true
  actionError.value = null
  try {
    await iamService.unlockAccount(user.id)
    reload()
  } catch {
    actionError.value = 'باز کردن قفل حساب با خطا مواجه شد'
  } finally {
    actionLoading.value = false
  }
}

const statusLabel = (status: string) => {
  const map: Record<string, string> = { active: 'فعال', inactive: 'غیرفعال', suspended: 'تعلیق شده' }
  return map[status] ?? status
}

const statusClass = (status: string) => {
  const map: Record<string, string> = {
    active:    'bg-green-100 text-green-700',
    inactive:  'bg-secondary-100 text-secondary-500',
    suspended: 'bg-red-100 text-red-700',
  }
  return map[status] ?? 'bg-secondary-100 text-secondary-500'
}

const detailUser = computed(() => selectedUser.value)
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-secondary-800">مدیریت کاربران</h1>
        <p v-if="!isLoading" class="text-sm text-secondary-500 mt-1">{{ meta.total }} کاربر یافت شد</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-4 mb-4 flex flex-wrap items-center gap-3">
      <div class="relative flex-1 min-w-48">
        <Search class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-secondary-400" />
        <input
          v-model="filters.search"
          type="text"
          placeholder="جستجو با نام یا ایمیل..."
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
        <option value="active">فعال</option>
        <option value="inactive">غیرفعال</option>
        <option value="suspended">تعلیق شده</option>
      </select>
      <select
        v-model="filters.per_page"
        class="h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 text-sm text-secondary-700"
      >
        <option :value="15">۱۵ در صفحه</option>
        <option :value="30">۳۰ در صفحه</option>
        <option :value="50">۵۰ در صفحه</option>
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
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">نام</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">ایمیل</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">تلفن</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-secondary-500">وضعیت</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-secondary-500">MFA</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-secondary-500">قفل</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-secondary-500">تاریخ ثبت</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-secondary-500">عملیات</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-secondary-100">
            <template v-if="isLoading">
              <tr v-for="i in 8" :key="i">
                <td class="px-6 py-4"><div class="h-4 bg-secondary-200 rounded animate-pulse w-28"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-secondary-200 rounded animate-pulse w-40"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-secondary-200 rounded animate-pulse w-28"></div></td>
                <td class="px-6 py-4 text-center"><div class="h-5 bg-secondary-200 rounded-full animate-pulse w-16 mx-auto"></div></td>
                <td class="px-6 py-4 text-center"><div class="h-5 bg-secondary-200 rounded-full animate-pulse w-10 mx-auto"></div></td>
                <td class="px-6 py-4 text-center"><div class="h-5 bg-secondary-200 rounded-full animate-pulse w-10 mx-auto"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-secondary-200 rounded animate-pulse w-24"></div></td>
                <td class="px-6 py-4"></td>
              </tr>
            </template>
            <tr v-else-if="users.length === 0">
              <td colspan="8" class="px-6 py-12 text-center text-secondary-400 text-sm">کاربری یافت نشد</td>
            </tr>
            <tr v-else v-for="user in users" :key="user.uuid" class="hover:bg-secondary-50 transition">
              <td class="px-6 py-4 text-sm font-medium text-secondary-800">
                <div class="flex items-center gap-2">
                  <Shield v-if="user.is_super_admin" class="w-3.5 h-3.5 text-primary-500 flex-shrink-0" />
                  {{ user.name }}
                </div>
              </td>
              <td class="px-6 py-4 text-sm text-secondary-600">
                <div class="flex items-center gap-1.5">
                  {{ user.email }}
                  <CheckCircle v-if="user.email_verified_at" class="w-3.5 h-3.5 text-green-500 flex-shrink-0" />
                  <XCircle v-else class="w-3.5 h-3.5 text-secondary-300 flex-shrink-0" />
                </div>
              </td>
              <td class="px-6 py-4 text-sm text-secondary-600">{{ user.phone || '—' }}</td>
              <td class="px-6 py-4 text-center">
                <span class="px-2.5 py-0.5 text-xs rounded-full font-medium" :class="statusClass(user.status)">
                  {{ statusLabel(user.status) }}
                </span>
              </td>
              <td class="px-6 py-4 text-center">
                <CheckCircle v-if="user.mfa_enabled" class="w-4 h-4 text-green-500 mx-auto" />
                <XCircle v-else class="w-4 h-4 text-secondary-300 mx-auto" />
              </td>
              <td class="px-6 py-4 text-center">
                <ShieldAlert v-if="user.is_locked" class="w-4 h-4 text-red-500 mx-auto" title="قفل شده" />
                <span v-else class="text-secondary-300 text-xs">—</span>
              </td>
              <td class="px-6 py-4 text-sm text-secondary-500">{{ formatDate(user.created_at) }}</td>
              <td class="px-6 py-4">
                <div class="flex items-center justify-center gap-1">
                  <button
                    class="p-1.5 hover:bg-secondary-100 rounded-lg transition"
                    title="مشاهده جزئیات"
                    @click="openDetail(user)"
                  >
                    <Eye class="w-4 h-4 text-secondary-600" />
                  </button>
                  <button
                    v-if="!user.is_locked"
                    class="p-1.5 hover:bg-red-50 rounded-lg transition"
                    title="قفل کردن حساب"
                    :disabled="actionLoading"
                    @click="openLockDialog(user)"
                  >
                    <Lock class="w-4 h-4 text-red-500" />
                  </button>
                  <button
                    v-else
                    class="p-1.5 hover:bg-green-50 rounded-lg transition"
                    title="باز کردن قفل حساب"
                    :disabled="actionLoading"
                    @click="handleUnlock(user)"
                  >
                    <Unlock class="w-4 h-4 text-green-600" />
                  </button>
                </div>
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

    <!-- User Detail Modal -->
    <Teleport to="body">
      <div
        v-if="isDetailOpen && detailUser"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @click.self="closeDetail"
      >
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between p-6 border-b border-secondary-100">
            <h2 class="text-lg font-bold text-secondary-800">جزئیات کاربر</h2>
            <button class="p-1.5 hover:bg-secondary-100 rounded-lg transition" @click="closeDetail">
              <X class="w-5 h-5 text-secondary-500" />
            </button>
          </div>
          <div class="p-6 space-y-4">
            <!-- Avatar & Name -->
            <div class="flex items-center gap-4">
              <div class="w-14 h-14 rounded-full bg-primary-100 flex items-center justify-center flex-shrink-0">
                <span class="text-primary-600 font-bold text-xl">{{ detailUser.name.charAt(0) }}</span>
              </div>
              <div>
                <p class="font-bold text-secondary-800 flex items-center gap-2">
                  {{ detailUser.name }}
                  <Shield v-if="detailUser.is_super_admin" class="w-4 h-4 text-primary-500" title="سوپر ادمین" />
                </p>
                <span class="px-2 py-0.5 text-xs rounded-full font-medium mt-1 inline-block" :class="statusClass(detailUser.status)">
                  {{ statusLabel(detailUser.status) }}
                </span>
              </div>
            </div>

            <!-- Fields -->
            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
              <div>
                <dt class="text-secondary-400 text-xs mb-0.5">ایمیل</dt>
                <dd class="text-secondary-800 font-medium break-all">{{ detailUser.email }}</dd>
                <dd class="text-xs mt-0.5" :class="detailUser.email_verified_at ? 'text-green-600' : 'text-secondary-400'">
                  {{ detailUser.email_verified_at ? 'تأیید شده' : 'تأیید نشده' }}
                </dd>
              </div>
              <div>
                <dt class="text-secondary-400 text-xs mb-0.5">تلفن</dt>
                <dd class="text-secondary-800">{{ detailUser.phone || '—' }}</dd>
                <dd class="text-xs mt-0.5" :class="detailUser.phone_verified_at ? 'text-green-600' : 'text-secondary-400'">
                  {{ detailUser.phone ? (detailUser.phone_verified_at ? 'تأیید شده' : 'تأیید نشده') : '' }}
                </dd>
              </div>
              <div>
                <dt class="text-secondary-400 text-xs mb-0.5">احراز هویت دو مرحله‌ای</dt>
                <dd :class="detailUser.mfa_enabled ? 'text-green-600' : 'text-secondary-400'">
                  {{ detailUser.mfa_enabled ? 'فعال' : 'غیرفعال' }}
                </dd>
              </div>
              <div>
                <dt class="text-secondary-400 text-xs mb-0.5">وضعیت قفل</dt>
                <dd :class="detailUser.is_locked ? 'text-red-600' : 'text-green-600'">
                  {{ detailUser.is_locked ? 'قفل شده' : 'باز' }}
                </dd>
                <dd v-if="detailUser.locked_until" class="text-xs text-secondary-400 mt-0.5">تا {{ formatDate(detailUser.locked_until) }}</dd>
              </div>
              <div>
                <dt class="text-secondary-400 text-xs mb-0.5">آخرین ورود</dt>
                <dd class="text-secondary-800">{{ formatDate(detailUser.last_login_at) }}</dd>
                <dd v-if="detailUser.last_login_ip" class="text-xs text-secondary-400 font-mono mt-0.5">{{ detailUser.last_login_ip }}</dd>
              </div>
              <div>
                <dt class="text-secondary-400 text-xs mb-0.5">انقضای رمز</dt>
                <dd class="text-secondary-800">{{ formatDate(detailUser.password_expires_at) }}</dd>
              </div>
              <div>
                <dt class="text-secondary-400 text-xs mb-0.5">تاریخ ثبت‌نام</dt>
                <dd class="text-secondary-800">{{ formatDate(detailUser.created_at) }}</dd>
              </div>
              <div>
                <dt class="text-secondary-400 text-xs mb-0.5">آخرین بروزرسانی</dt>
                <dd class="text-secondary-800">{{ formatDate(detailUser.updated_at) }}</dd>
              </div>
              <div v-if="detailUser.must_change_password" class="col-span-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs rounded-lg bg-yellow-100 text-yellow-700">
                  <ShieldAlert class="w-3.5 h-3.5" />
                  کاربر باید رمز خود را تغییر دهد
                </span>
              </div>
              <div v-if="detailUser.organization" class="col-span-2">
                <dt class="text-secondary-400 text-xs mb-0.5">سازمان</dt>
                <dd class="text-secondary-800">{{ detailUser.organization.name }}</dd>
              </div>
            </dl>
          </div>
          <div class="p-6 border-t border-secondary-100 flex justify-end gap-3">
            <button
              v-if="!detailUser.is_locked"
              class="px-4 py-2 text-sm text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition"
              @click="() => { closeDetail(); openLockDialog(detailUser!) }"
            >قفل کردن حساب</button>
            <button
              v-else
              class="px-4 py-2 text-sm text-green-600 border border-green-200 rounded-lg hover:bg-green-50 transition"
              :disabled="actionLoading"
              @click="() => { closeDetail(); handleUnlock(detailUser!) }"
            >باز کردن قفل</button>
            <button
              class="px-4 py-2 text-sm bg-secondary-100 text-secondary-700 rounded-lg hover:bg-secondary-200 transition"
              @click="closeDetail"
            >بستن</button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Lock Account Dialog -->
    <Teleport to="body">
      <div
        v-if="isLockOpen && selectedUser"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @click.self="closeLockDialog"
      >
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
          <div class="flex items-center justify-between p-6 border-b border-secondary-100">
            <h2 class="text-lg font-bold text-secondary-800">قفل کردن حساب</h2>
            <button class="p-1.5 hover:bg-secondary-100 rounded-lg" @click="closeLockDialog">
              <X class="w-5 h-5 text-secondary-500" />
            </button>
          </div>
          <div class="p-6 space-y-4">
            <p class="text-sm text-secondary-600">
              حساب کاربری <strong>{{ selectedUser.name }}</strong> قفل خواهد شد.
            </p>
            <div>
              <label for="lock-reason" class="block text-sm font-medium text-secondary-700 mb-1">دلیل قفل کردن <span class="text-red-500">*</span></label>
              <textarea
                id="lock-reason"
                v-model="lockReason"
                rows="3"
                class="w-full rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 px-3 py-2 text-sm resize-none"
                placeholder="دلیل قفل کردن را وارد کنید..."
              ></textarea>
            </div>
            <div>
              <label for="lock-duration" class="block text-sm font-medium text-secondary-700 mb-1">مدت قفل (دقیقه)</label>
              <select
                id="lock-duration"
                v-model="lockDuration"
                class="w-full h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 text-sm text-secondary-700"
              >
                <option :value="30">۳۰ دقیقه</option>
                <option :value="60">۱ ساعت</option>
                <option :value="1440">۲۴ ساعت</option>
                <option :value="10080">۱ هفته</option>
              </select>
            </div>
            <p v-if="actionError" class="text-sm text-red-600">{{ actionError }}</p>
          </div>
          <div class="p-6 border-t border-secondary-100 flex justify-end gap-3">
            <button
              class="px-4 py-2 text-sm bg-secondary-100 text-secondary-700 rounded-lg hover:bg-secondary-200 transition"
              @click="closeLockDialog"
            >انصراف</button>
            <button
              class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition disabled:opacity-50"
              :disabled="actionLoading || !lockReason.trim()"
              @click="confirmLock"
            >{{ actionLoading ? 'در حال پردازش...' : 'قفل کردن' }}</button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
