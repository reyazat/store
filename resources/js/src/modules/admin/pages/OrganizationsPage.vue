<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { Plus, Search, Edit2, UserCheck, X, Loader2, Store, Eye, UserPlus } from 'lucide-vue-next'
import { useRouter } from 'vue-router'
import api from '@shared/utils/api'

const router = useRouter()

// ── Types ──────────────────────────────────────────────────────────────────────
interface StoreOwner { id: number; name: string; email: string }
interface StoreItem {
  id: number
  uuid: string
  name: string
  description: string | null
  status: 'active' | 'inactive' | 'suspended'
  owner: StoreOwner | null
  members_count: number
  created_at: string
}
interface PaginationMeta { current_page: number; last_page: number; per_page: number; total: number }
interface UserOption { id: number; name: string; email: string; organization_id: number | null }

type OwnerMode = 'existing' | 'new'

// ── Store list state ───────────────────────────────────────────────────────────
const stores       = ref<StoreItem[]>([])
const meta         = ref<PaginationMeta>({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const isLoading    = ref(true)
const errorMsg     = ref('')
const search       = ref('')
const statusFilter = ref('')

// ── Modal state ────────────────────────────────────────────────────────────────
const isCreateOpen  = ref(false)
const isEditOpen    = ref(false)
const isAssignOpen  = ref(false)
const actionLoading = ref(false)
const actionError   = ref('')
const selectedStore = ref<StoreItem | null>(null)

// ── Owner mode tabs (shared across Create & Assign modals) ─────────────────────
const createOwnerMode = ref<OwnerMode>('existing')
const assignOwnerMode = ref<OwnerMode>('existing')

// ── Forms ──────────────────────────────────────────────────────────────────────
const createForm = ref({
  name: '',
  description: '',
  status: 'active' as 'active' | 'inactive' | 'suspended',
  // existing owner
  owner_id: null as number | null,
  // new owner
  new_owner: { name: '', email: '', password: '', phone: '' },
})

const editForm = ref({
  name: '',
  description: '',
  status: 'active' as 'active' | 'inactive' | 'suspended',
})

const assignForm = ref({
  // existing owner
  owner_id: null as number | null,
  // new owner
  new_owner: { name: '', email: '', password: '', phone: '' },
})

// ── User search for dropdowns ──────────────────────────────────────────────────
const users          = ref<UserOption[]>([])
const usersLoading   = ref(false)
const createUserSearch = ref('')
const assignUserSearch = ref('')

// ── Loaders ────────────────────────────────────────────────────────────────────
const loadStores = async (page = 1) => {
  isLoading.value = true
  errorMsg.value  = ''
  try {
    const { data } = await api.get('/admin/organizations', {
      params: { search: search.value, status: statusFilter.value, per_page: 15, page },
    })
    stores.value = data.data
    meta.value   = data.meta
  } catch {
    errorMsg.value = 'خطا در بارگذاری فروشگاه‌ها'
  } finally {
    isLoading.value = false
  }
}

const loadUsers = async (searchTerm = '') => {
  usersLoading.value = true
  try {
    const { data } = await api.get('/admin/users', {
      params: { search: searchTerm, per_page: 50, for_owner_selection: 1 },
    })
    users.value = data.data
  } catch { /* silent */ }
  finally { usersLoading.value = false }
}

// Debounced user search watchers
let userSearchTimer: ReturnType<typeof setTimeout> | null = null
const debouncedLoadUsers = (term: string) => {
  if (userSearchTimer) clearTimeout(userSearchTimer)
  userSearchTimer = setTimeout(() => loadUsers(term), 300)
}

watch(createUserSearch, (v) => debouncedLoadUsers(v))
watch(assignUserSearch, (v) => debouncedLoadUsers(v))

// ── Modal openers ──────────────────────────────────────────────────────────────
const openCreate = () => {
  createOwnerMode.value = 'existing'
  createUserSearch.value = ''
  createForm.value = {
    name: '',
    description: '',
    status: 'active',
    owner_id: null,
    new_owner: { name: '', email: '', password: '', phone: '' },
  }
  actionError.value = ''
  loadUsers()
  isCreateOpen.value = true
}

const openEdit = (store: StoreItem) => {
  selectedStore.value = store
  editForm.value = { name: store.name, description: store.description ?? '', status: store.status }
  actionError.value = ''
  isEditOpen.value = true
}

const openAssign = (store: StoreItem) => {
  selectedStore.value = store
  assignOwnerMode.value = 'existing'
  assignUserSearch.value = ''
  assignForm.value = {
    owner_id: null,
    new_owner: { name: '', email: '', password: '', phone: '' },
  }
  actionError.value = ''
  loadUsers()
  isAssignOpen.value = true
}

// ── Submit handlers ────────────────────────────────────────────────────────────
const submitCreate = async () => {
  actionLoading.value = true
  actionError.value   = ''
  try {
    const payload: Record<string, unknown> = {
      name:        createForm.value.name,
      description: createForm.value.description || undefined,
      status:      createForm.value.status,
    }

    if (createOwnerMode.value === 'existing' && createForm.value.owner_id) {
      payload.owner_id = createForm.value.owner_id
    } else if (createOwnerMode.value === 'new') {
      const o = createForm.value.new_owner
      payload.new_owner = {
        name:     o.name,
        email:    o.email,
        password: o.password,
        phone:    o.phone || undefined,
      }
    }

    await api.post('/admin/organizations', payload)
    isCreateOpen.value = false
    await loadStores()
  } catch (e: any) {
    const errors = e.response?.data?.errors
    if (errors) {
      actionError.value = Object.values(errors).flat().join(' — ')
    } else {
      actionError.value = e.response?.data?.message || 'خطا در ایجاد فروشگاه'
    }
  } finally {
    actionLoading.value = false
  }
}

const submitEdit = async () => {
  if (!selectedStore.value) return
  actionLoading.value = true
  actionError.value   = ''
  try {
    await api.put(`/admin/organizations/${selectedStore.value.uuid}`, editForm.value)
    isEditOpen.value = false
    await loadStores()
  } catch (e: any) {
    actionError.value = e.response?.data?.message || 'خطا در بروزرسانی فروشگاه'
  } finally {
    actionLoading.value = false
  }
}

const submitAssign = async () => {
  if (!selectedStore.value) return
  actionLoading.value = true
  actionError.value   = ''
  try {
    const payload: Record<string, unknown> = {}

    if (assignOwnerMode.value === 'existing' && assignForm.value.owner_id) {
      payload.owner_id = assignForm.value.owner_id
    } else if (assignOwnerMode.value === 'new') {
      const o = assignForm.value.new_owner
      payload.new_owner = {
        name:     o.name,
        email:    o.email,
        password: o.password,
        phone:    o.phone || undefined,
      }
    } else {
      actionError.value = 'لطفاً یک مالک انتخاب یا تعریف کنید.'
      actionLoading.value = false
      return
    }

    await api.post(`/admin/organizations/${selectedStore.value.uuid}/assign-owner`, payload)
    isAssignOpen.value = false
    await loadStores()
  } catch (e: any) {
    const errors = e.response?.data?.errors
    if (errors) {
      actionError.value = Object.values(errors).flat().join(' — ')
    } else {
      actionError.value = e.response?.data?.message || 'خطا در تعیین مالک'
    }
  } finally {
    actionLoading.value = false
  }
}

const viewStore = (store: StoreItem) => {
  router.push({ name: 'admin-store-overview', params: { uuid: store.uuid } })
}

// ── Helpers ────────────────────────────────────────────────────────────────────
const statusLabel = (s: string) => ({ active: 'فعال', inactive: 'غیرفعال', suspended: 'معلق' }[s] ?? s)
const statusClass = (s: string) => ({
  active:    'bg-green-100 text-green-700',
  inactive:  'bg-secondary-100 text-secondary-600',
  suspended: 'bg-red-100 text-red-700',
}[s] ?? '')

const inputCls = 'w-full border border-secondary-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300'

onMounted(() => loadStores())
</script>

<template>
  <div dir="rtl">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-2xl font-bold text-secondary-800">مدیریت فروشگاه‌ها</h2>
      <button @click="openCreate" class="flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
        <Plus class="w-4 h-4" />
        فروشگاه جدید
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl p-4 shadow-sm mb-4 flex flex-wrap gap-3">
      <div class="relative flex-1 min-w-48">
        <Search class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-secondary-400" />
        <input v-model="search" @input="loadStores()" placeholder="جستجو در فروشگاه‌ها..." :class="inputCls + ' pr-9'" />
      </div>
      <select v-model="statusFilter" @change="loadStores()" :class="inputCls + ' w-auto'">
        <option value="">همه وضعیت‌ها</option>
        <option value="active">فعال</option>
        <option value="inactive">غیرفعال</option>
        <option value="suspended">معلق</option>
      </select>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div v-if="isLoading" class="flex items-center justify-center py-20">
        <Loader2 class="w-8 h-8 animate-spin text-primary-500" />
      </div>
      <div v-else-if="errorMsg" class="text-red-500 text-sm text-center py-10">{{ errorMsg }}</div>
      <div v-else-if="!stores.length" class="text-secondary-400 text-sm text-center py-16">
        هیچ فروشگاهی یافت نشد
      </div>
      <table v-else class="w-full text-sm">
        <thead class="bg-secondary-50 text-secondary-500 text-right">
          <tr>
            <th class="px-4 py-3 font-medium">نام فروشگاه</th>
            <th class="px-4 py-3 font-medium">مالک</th>
            <th class="px-4 py-3 font-medium">اعضا</th>
            <th class="px-4 py-3 font-medium">وضعیت</th>
            <th class="px-4 py-3 font-medium">عملیات</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-secondary-100">
          <tr v-for="store in stores" :key="store.id" class="hover:bg-secondary-50 transition">
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <Store class="w-4 h-4 text-secondary-400" />
                <span class="font-medium text-secondary-800">{{ store.name }}</span>
              </div>
              <p v-if="store.description" class="text-xs text-secondary-400 mt-0.5 truncate max-w-xs">{{ store.description }}</p>
            </td>
            <td class="px-4 py-3">
              <template v-if="store.owner">
                <p class="font-medium text-secondary-700">{{ store.owner.name }}</p>
                <p class="text-xs text-secondary-400">{{ store.owner.email }}</p>
              </template>
              <span v-else class="text-secondary-400 text-xs italic">تعیین نشده</span>
            </td>
            <td class="px-4 py-3 text-secondary-600">{{ store.members_count }}</td>
            <td class="px-4 py-3">
              <span :class="['text-xs font-medium px-2 py-1 rounded-full', statusClass(store.status)]">
                {{ statusLabel(store.status) }}
              </span>
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-1">
                <button @click="viewStore(store)" class="p-1.5 hover:bg-secondary-100 rounded text-secondary-500 hover:text-blue-600 transition" title="مشاهده پنل فروشگاه">
                  <Eye class="w-4 h-4" />
                </button>
                <button @click="openEdit(store)" class="p-1.5 hover:bg-secondary-100 rounded text-secondary-500 hover:text-primary-600 transition" title="ویرایش">
                  <Edit2 class="w-4 h-4" />
                </button>
                <button @click="openAssign(store)" class="p-1.5 hover:bg-secondary-100 rounded text-secondary-500 hover:text-green-600 transition" title="تعیین / تغییر مالک">
                  <UserCheck class="w-4 h-4" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="flex items-center justify-center gap-2 p-4 border-t border-secondary-100">
        <button
          v-for="p in meta.last_page"
          :key="p"
          @click="loadStores(p)"
          :class="['w-8 h-8 rounded-lg text-sm font-medium transition', p === meta.current_page ? 'bg-primary-600 text-white' : 'hover:bg-secondary-100 text-secondary-600']"
        >{{ p }}</button>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         CREATE STORE MODAL
    ══════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="isCreateOpen" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" dir="rtl">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] flex flex-col">
          <!-- Header -->
          <div class="flex items-center justify-between p-5 border-b border-secondary-100 flex-shrink-0">
            <h3 class="font-bold text-secondary-800 text-lg">ایجاد فروشگاه جدید</h3>
            <button @click="isCreateOpen = false" class="p-1 hover:bg-secondary-100 rounded" aria-label="بستن">
              <X class="w-5 h-5" />
            </button>
          </div>

          <!-- Scrollable body -->
          <form @submit.prevent="submitCreate" class="p-5 space-y-4 overflow-y-auto flex-1">

            <!-- Store info -->
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">نام فروشگاه <span class="text-red-500">*</span></label>
              <input v-model="createForm.name" required :class="inputCls" placeholder="مثال: فروشگاه آنلاین برند X" />
            </div>
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">توضیحات</label>
              <textarea v-model="createForm.description" rows="2" :class="inputCls" />
            </div>
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">وضعیت</label>
              <select v-model="createForm.status" :class="inputCls">
                <option value="active">فعال</option>
                <option value="inactive">غیرفعال</option>
                <option value="suspended">معلق</option>
              </select>
            </div>

            <!-- Owner section -->
            <div class="border border-secondary-200 rounded-xl p-4 space-y-3">
              <p class="text-sm font-semibold text-secondary-700">مالک فروشگاه (اختیاری)</p>

              <!-- Mode tabs -->
              <div class="flex gap-2 text-sm">
                <button
                  type="button"
                  @click="createOwnerMode = 'existing'"
                  :class="['flex-1 py-1.5 rounded-lg border transition font-medium',
                    createOwnerMode === 'existing'
                      ? 'bg-primary-600 text-white border-primary-600'
                      : 'border-secondary-200 text-secondary-600 hover:bg-secondary-50']"
                >
                  کاربر موجود
                </button>
                <button
                  type="button"
                  @click="createOwnerMode = 'new'"
                  :class="['flex-1 py-1.5 rounded-lg border transition font-medium flex items-center justify-center gap-1',
                    createOwnerMode === 'new'
                      ? 'bg-primary-600 text-white border-primary-600'
                      : 'border-secondary-200 text-secondary-600 hover:bg-secondary-50']"
                >
                  <UserPlus class="w-4 h-4" />
                  کاربر جدید
                </button>
              </div>

              <!-- Existing user picker -->
              <template v-if="createOwnerMode === 'existing'">
                <div class="relative">
                  <Search class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-secondary-400 pointer-events-none" />
                  <input
                    v-model="createUserSearch"
                    :class="inputCls + ' pr-9'"
                    placeholder="جستجو در کاربران..."
                  />
                </div>
                <div v-if="usersLoading" class="text-xs text-secondary-400 text-center py-2">در حال بارگذاری...</div>
                <select v-else v-model="createForm.owner_id" :class="inputCls" size="4">
                  <option :value="null">— بدون مالک —</option>
                  <option v-for="u in users" :key="u.id" :value="u.id">
                    {{ u.name }} &lrm;({{ u.email }}){{ u.organization_id ? ' ★' : '' }}
                  </option>
                </select>
                <p v-if="users.some(u => u.organization_id)" class="text-xs text-amber-600">
                  ★ کاربران علامت‌دار در حال حاضر عضو یک فروشگاه دیگرند و از آن منتقل خواهند شد.
                </p>
              </template>

              <!-- New user form -->
              <template v-if="createOwnerMode === 'new'">
                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <label class="block text-xs font-medium text-secondary-600 mb-1">نام و نام‌خانوادگی <span class="text-red-500">*</span></label>
                    <input v-model="createForm.new_owner.name" :required="createOwnerMode === 'new'" :class="inputCls" />
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-secondary-600 mb-1">شماره تماس</label>
                    <input v-model="createForm.new_owner.phone" type="tel" :class="inputCls" />
                  </div>
                </div>
                <div>
                  <label class="block text-xs font-medium text-secondary-600 mb-1">ایمیل <span class="text-red-500">*</span></label>
                  <input v-model="createForm.new_owner.email" type="email" :required="createOwnerMode === 'new'" :class="inputCls" dir="ltr" />
                </div>
                <div>
                  <label class="block text-xs font-medium text-secondary-600 mb-1">رمز عبور اولیه <span class="text-red-500">*</span></label>
                  <input v-model="createForm.new_owner.password" type="password" :required="createOwnerMode === 'new'" :class="inputCls" dir="ltr" />
                  <p class="text-xs text-secondary-400 mt-1">کاربر باید در اولین ورود رمز خود را تغییر دهد.</p>
                </div>
              </template>
            </div>

            <p v-if="actionError" class="text-red-500 text-xs bg-red-50 rounded-lg px-3 py-2">{{ actionError }}</p>

            <div class="flex gap-3 pt-1">
              <button
                type="submit"
                :disabled="actionLoading"
                class="flex-1 bg-primary-600 hover:bg-primary-700 disabled:opacity-50 text-white py-2 rounded-lg text-sm font-medium transition flex items-center justify-center gap-2"
              >
                <Loader2 v-if="actionLoading" class="w-4 h-4 animate-spin" />
                ایجاد فروشگاه
              </button>
              <button type="button" @click="isCreateOpen = false" class="flex-1 border border-secondary-200 hover:bg-secondary-50 py-2 rounded-lg text-sm transition">انصراف</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ══════════════════════════════════════════════════════════════
         EDIT STORE MODAL
    ══════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="isEditOpen" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" dir="rtl">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
          <div class="flex items-center justify-between p-5 border-b border-secondary-100">
            <h3 class="font-bold text-secondary-800">ویرایش فروشگاه</h3>
            <button @click="isEditOpen = false" class="p-1 hover:bg-secondary-100 rounded" aria-label="بستن"><X class="w-5 h-5" /></button>
          </div>
          <form @submit.prevent="submitEdit" class="p-5 space-y-4">
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">نام فروشگاه</label>
              <input v-model="editForm.name" :class="inputCls" />
            </div>
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">توضیحات</label>
              <textarea v-model="editForm.description" rows="3" :class="inputCls" />
            </div>
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">وضعیت</label>
              <select v-model="editForm.status" :class="inputCls">
                <option value="active">فعال</option>
                <option value="inactive">غیرفعال</option>
                <option value="suspended">معلق</option>
              </select>
            </div>
            <p v-if="actionError" class="text-red-500 text-xs bg-red-50 rounded-lg px-3 py-2">{{ actionError }}</p>
            <div class="flex gap-3 pt-1">
              <button
                type="submit"
                :disabled="actionLoading"
                class="flex-1 bg-primary-600 hover:bg-primary-700 disabled:opacity-50 text-white py-2 rounded-lg text-sm font-medium transition flex items-center justify-center gap-2"
              >
                <Loader2 v-if="actionLoading" class="w-4 h-4 animate-spin" />
                ذخیره
              </button>
              <button type="button" @click="isEditOpen = false" class="flex-1 border border-secondary-200 hover:bg-secondary-50 py-2 rounded-lg text-sm transition">انصراف</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ══════════════════════════════════════════════════════════════
         ASSIGN OWNER MODAL
    ══════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="isAssignOpen" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" dir="rtl">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] flex flex-col">
          <!-- Header -->
          <div class="flex items-center justify-between p-5 border-b border-secondary-100 flex-shrink-0">
            <h3 class="font-bold text-secondary-800 text-lg">تعیین / تغییر مالک فروشگاه</h3>
            <button @click="isAssignOpen = false" class="p-1 hover:bg-secondary-100 rounded" aria-label="بستن"><X class="w-5 h-5" /></button>
          </div>

          <form @submit.prevent="submitAssign" class="p-5 space-y-4 overflow-y-auto flex-1">

            <!-- Current store & owner info -->
            <div class="bg-secondary-50 rounded-xl p-3 text-sm space-y-1">
              <p class="text-secondary-500">فروشگاه: <span class="font-semibold text-secondary-800">{{ selectedStore?.name }}</span></p>
              <p class="text-secondary-500">مالک فعلی:
                <template v-if="selectedStore?.owner">
                  <span class="font-semibold text-secondary-800">{{ selectedStore.owner.name }}</span>
                  <span class="text-secondary-400 text-xs mr-1">({{ selectedStore.owner.email }})</span>
                </template>
                <span v-else class="text-amber-600 font-medium">تعیین نشده</span>
              </p>
            </div>

            <!-- Mode tabs -->
            <div class="flex gap-2 text-sm">
              <button
                type="button"
                @click="assignOwnerMode = 'existing'"
                :class="['flex-1 py-1.5 rounded-lg border transition font-medium',
                  assignOwnerMode === 'existing'
                    ? 'bg-primary-600 text-white border-primary-600'
                    : 'border-secondary-200 text-secondary-600 hover:bg-secondary-50']"
              >
                کاربر موجود
              </button>
              <button
                type="button"
                @click="assignOwnerMode = 'new'"
                :class="['flex-1 py-1.5 rounded-lg border transition font-medium flex items-center justify-center gap-1',
                  assignOwnerMode === 'new'
                    ? 'bg-primary-600 text-white border-primary-600'
                    : 'border-secondary-200 text-secondary-600 hover:bg-secondary-50']"
              >
                <UserPlus class="w-4 h-4" />
                کاربر جدید
              </button>
            </div>

            <!-- Existing user picker -->
            <template v-if="assignOwnerMode === 'existing'">
              <div class="relative">
                <Search class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-secondary-400 pointer-events-none" />
                <input
                  v-model="assignUserSearch"
                  :class="inputCls + ' pr-9'"
                  placeholder="جستجو در کاربران..."
                />
              </div>
              <div v-if="usersLoading" class="text-xs text-secondary-400 text-center py-2">در حال بارگذاری...</div>
              <select v-else v-model="assignForm.owner_id" :class="inputCls" required size="5">
                <option :value="null" disabled>— انتخاب کاربر —</option>
                <option v-for="u in users" :key="u.id" :value="u.id">
                  {{ u.name }} &lrm;({{ u.email }}){{ u.organization_id ? ' ★' : '' }}
                </option>
              </select>
              <p v-if="users.some(u => u.organization_id)" class="text-xs text-amber-600">
                ★ کاربران علامت‌دار در حال حاضر عضو یک فروشگاه دیگرند و از آن منتقل خواهند شد.
              </p>
            </template>

            <!-- New user form -->
            <template v-if="assignOwnerMode === 'new'">
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-medium text-secondary-600 mb-1">نام و نام‌خانوادگی <span class="text-red-500">*</span></label>
                  <input v-model="assignForm.new_owner.name" :required="assignOwnerMode === 'new'" :class="inputCls" />
                </div>
                <div>
                  <label class="block text-xs font-medium text-secondary-600 mb-1">شماره تماس</label>
                  <input v-model="assignForm.new_owner.phone" type="tel" :class="inputCls" />
                </div>
              </div>
              <div>
                <label class="block text-xs font-medium text-secondary-600 mb-1">ایمیل <span class="text-red-500">*</span></label>
                <input v-model="assignForm.new_owner.email" type="email" :required="assignOwnerMode === 'new'" :class="inputCls" dir="ltr" />
              </div>
              <div>
                <label class="block text-xs font-medium text-secondary-600 mb-1">رمز عبور اولیه <span class="text-red-500">*</span></label>
                <input v-model="assignForm.new_owner.password" type="password" :required="assignOwnerMode === 'new'" :class="inputCls" dir="ltr" />
                <p class="text-xs text-secondary-400 mt-1">کاربر باید در اولین ورود رمز خود را تغییر دهد.</p>
              </div>
            </template>

            <p v-if="actionError" class="text-red-500 text-xs bg-red-50 rounded-lg px-3 py-2">{{ actionError }}</p>

            <div class="flex gap-3 pt-1">
              <button
                type="submit"
                :disabled="actionLoading"
                class="flex-1 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white py-2 rounded-lg text-sm font-medium transition flex items-center justify-center gap-2"
              >
                <Loader2 v-if="actionLoading" class="w-4 h-4 animate-spin" />
                تعیین مالک
              </button>
              <button type="button" @click="isAssignOpen = false" class="flex-1 border border-secondary-200 hover:bg-secondary-50 py-2 rounded-lg text-sm transition">انصراف</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
