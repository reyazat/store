<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Plus, Search, Edit2, UserCheck, X, Loader2, Store, Eye } from 'lucide-vue-next'
import { useRouter } from 'vue-router'
import api from '@shared/utils/api'

const router = useRouter()

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
interface UserOption { id: number; name: string; email: string }

const stores      = ref<StoreItem[]>([])
const meta        = ref<PaginationMeta>({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const isLoading   = ref(true)
const errorMsg    = ref('')
const search      = ref('')
const statusFilter = ref('')

const isCreateOpen   = ref(false)
const isEditOpen     = ref(false)
const isAssignOpen   = ref(false)
const actionLoading  = ref(false)
const actionError    = ref('')
const selectedStore  = ref<StoreItem | null>(null)

const createForm = ref({ name: '', description: '', status: 'active', owner_id: '' })
const editForm   = ref({ name: '', description: '', status: 'active' })
const assignForm = ref({ owner_id: '' })

const users     = ref<UserOption[]>([])
const userSearch = ref('')

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

const loadUsers = async () => {
  try {
    const { data } = await api.get('/admin/users', { params: { search: userSearch.value, per_page: 50 } })
    users.value = data.data
  } catch { /* silent */ }
}

const openCreate = () => {
  createForm.value = { name: '', description: '', status: 'active', owner_id: '' }
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
  assignForm.value = { owner_id: '' }
  actionError.value = ''
  loadUsers()
  isAssignOpen.value = true
}

const submitCreate = async () => {
  actionLoading.value = true
  actionError.value   = ''
  try {
    await api.post('/admin/organizations', {
      ...createForm.value,
      owner_id: createForm.value.owner_id || undefined,
    })
    isCreateOpen.value = false
    await loadStores()
  } catch (e: any) {
    actionError.value = e.response?.data?.message || 'خطا در ایجاد فروشگاه'
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
  if (!selectedStore.value || !assignForm.value.owner_id) return
  actionLoading.value = true
  actionError.value   = ''
  try {
    await api.post(`/admin/organizations/${selectedStore.value.uuid}/assign-owner`, {
      owner_id: Number(assignForm.value.owner_id),
    })
    isAssignOpen.value = false
    await loadStores()
  } catch (e: any) {
    actionError.value = e.response?.data?.message || 'خطا در تعیین مالک'
  } finally {
    actionLoading.value = false
  }
}

const viewStore = (store: StoreItem) => {
  router.push({ name: 'admin-store-overview', params: { uuid: store.uuid } })
}

const statusLabel = (s: string) => ({ active: 'فعال', inactive: 'غیرفعال', suspended: 'معلق' }[s] ?? s)
const statusClass = (s: string) => ({
  active: 'bg-green-100 text-green-700',
  inactive: 'bg-secondary-100 text-secondary-600',
  suspended: 'bg-red-100 text-red-700',
}[s] ?? '')

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
        <input v-model="search" @input="loadStores()" placeholder="جستجو..." class="w-full pr-9 pl-3 py-2 border border-secondary-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-300" />
      </div>
      <select v-model="statusFilter" @change="loadStores()" class="border border-secondary-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300">
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
              <span v-else class="text-secondary-400 text-xs">تعیین نشده</span>
            </td>
            <td class="px-4 py-3 text-secondary-600">{{ store.members_count }}</td>
            <td class="px-4 py-3">
              <span :class="['text-xs font-medium px-2 py-1 rounded-full', statusClass(store.status)]">
                {{ statusLabel(store.status) }}
              </span>
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <button @click="viewStore(store)" class="p-1.5 hover:bg-secondary-100 rounded text-secondary-500 hover:text-blue-600 transition" title="مشاهده پنل فروشگاه">
                  <Eye class="w-4 h-4" />
                </button>
                <button @click="openEdit(store)" class="p-1.5 hover:bg-secondary-100 rounded text-secondary-500 hover:text-primary-600 transition" title="ویرایش">
                  <Edit2 class="w-4 h-4" />
                </button>
                <button @click="openAssign(store)" class="p-1.5 hover:bg-secondary-100 rounded text-secondary-500 hover:text-green-600 transition" title="تعیین مالک">
                  <UserCheck class="w-4 h-4" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="flex items-center justify-center gap-2 p-4 border-t border-secondary-100">
        <button v-for="p in meta.last_page" :key="p" @click="loadStores(p)"
          :class="['w-8 h-8 rounded-lg text-sm font-medium transition', p === meta.current_page ? 'bg-primary-600 text-white' : 'hover:bg-secondary-100 text-secondary-600']">
          {{ p }}
        </button>
      </div>
    </div>

    <!-- Create Modal -->
    <Teleport to="body">
      <div v-if="isCreateOpen" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" dir="rtl">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
          <div class="flex items-center justify-between p-5 border-b border-secondary-100">
            <h3 class="font-bold text-secondary-800">ایجاد فروشگاه جدید</h3>
            <button @click="isCreateOpen = false" class="p-1 hover:bg-secondary-100 rounded"><X class="w-5 h-5" /></button>
          </div>
          <form @submit.prevent="submitCreate" class="p-5 space-y-4">
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">نام فروشگاه <span class="text-red-500">*</span></label>
              <input v-model="createForm.name" required class="w-full border border-secondary-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300" />
            </div>
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">توضیحات</label>
              <textarea v-model="createForm.description" rows="3" class="w-full border border-secondary-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300" />
            </div>
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">وضعیت</label>
              <select v-model="createForm.status" class="w-full border border-secondary-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300">
                <option value="active">فعال</option>
                <option value="inactive">غیرفعال</option>
                <option value="suspended">معلق</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">مالک (اختیاری)</label>
              <select v-model="createForm.owner_id" class="w-full border border-secondary-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300">
                <option value="">انتخاب کنید</option>
                <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} ({{ u.email }})</option>
              </select>
            </div>
            <p v-if="actionError" class="text-red-500 text-xs">{{ actionError }}</p>
            <div class="flex gap-3 pt-2">
              <button type="submit" :disabled="actionLoading" class="flex-1 bg-primary-600 hover:bg-primary-700 disabled:opacity-50 text-white py-2 rounded-lg text-sm font-medium transition">
                <Loader2 v-if="actionLoading" class="w-4 h-4 animate-spin inline ml-1" />
                ایجاد فروشگاه
              </button>
              <button type="button" @click="isCreateOpen = false" class="flex-1 border border-secondary-200 hover:bg-secondary-50 py-2 rounded-lg text-sm transition">انصراف</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Edit Modal -->
    <Teleport to="body">
      <div v-if="isEditOpen" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" dir="rtl">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
          <div class="flex items-center justify-between p-5 border-b border-secondary-100">
            <h3 class="font-bold text-secondary-800">ویرایش فروشگاه</h3>
            <button @click="isEditOpen = false" class="p-1 hover:bg-secondary-100 rounded"><X class="w-5 h-5" /></button>
          </div>
          <form @submit.prevent="submitEdit" class="p-5 space-y-4">
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">نام فروشگاه</label>
              <input v-model="editForm.name" class="w-full border border-secondary-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300" />
            </div>
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">توضیحات</label>
              <textarea v-model="editForm.description" rows="3" class="w-full border border-secondary-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300" />
            </div>
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">وضعیت</label>
              <select v-model="editForm.status" class="w-full border border-secondary-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300">
                <option value="active">فعال</option>
                <option value="inactive">غیرفعال</option>
                <option value="suspended">معلق</option>
              </select>
            </div>
            <p v-if="actionError" class="text-red-500 text-xs">{{ actionError }}</p>
            <div class="flex gap-3 pt-2">
              <button type="submit" :disabled="actionLoading" class="flex-1 bg-primary-600 hover:bg-primary-700 disabled:opacity-50 text-white py-2 rounded-lg text-sm font-medium transition">
                <Loader2 v-if="actionLoading" class="w-4 h-4 animate-spin inline ml-1" />
                ذخیره
              </button>
              <button type="button" @click="isEditOpen = false" class="flex-1 border border-secondary-200 hover:bg-secondary-50 py-2 rounded-lg text-sm transition">انصراف</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Assign Owner Modal -->
    <Teleport to="body">
      <div v-if="isAssignOpen" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" dir="rtl">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm">
          <div class="flex items-center justify-between p-5 border-b border-secondary-100">
            <h3 class="font-bold text-secondary-800">تعیین مالک فروشگاه</h3>
            <button @click="isAssignOpen = false" class="p-1 hover:bg-secondary-100 rounded"><X class="w-5 h-5" /></button>
          </div>
          <form @submit.prevent="submitAssign" class="p-5 space-y-4">
            <p class="text-sm text-secondary-600">فروشگاه: <span class="font-medium">{{ selectedStore?.name }}</span></p>
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">کاربر <span class="text-red-500">*</span></label>
              <select v-model="assignForm.owner_id" required class="w-full border border-secondary-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300">
                <option value="">انتخاب کاربر</option>
                <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} ({{ u.email }})</option>
              </select>
            </div>
            <p v-if="actionError" class="text-red-500 text-xs">{{ actionError }}</p>
            <div class="flex gap-3 pt-2">
              <button type="submit" :disabled="actionLoading || !assignForm.owner_id" class="flex-1 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white py-2 rounded-lg text-sm font-medium transition">
                <Loader2 v-if="actionLoading" class="w-4 h-4 animate-spin inline ml-1" />
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
