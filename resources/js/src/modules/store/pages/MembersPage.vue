<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { UserPlus, Trash2, Edit2, X, Loader2, ShieldCheck } from 'lucide-vue-next'
import api from '@shared/utils/api'

interface Member { id: number; name: string; email: string; role: string }

const members     = ref<Member[]>([])
const isLoading   = ref(true)
const errorMsg    = ref('')

const isAddOpen     = ref(false)
const isEditOpen    = ref(false)
const isDeleteOpen  = ref(false)
const actionLoading = ref(false)
const actionError   = ref('')
const selected      = ref<Member | null>(null)

const addForm  = ref({ email: '', role: 'member' })
const editForm = ref({ role: 'member' })

const roleLabel = (r: string) => ({ owner: 'مالک', admin: 'مدیر', editor: 'ویرایشگر', member: 'عضو' }[r] ?? r)
const roleClass = (r: string) => ({
  owner: 'bg-purple-100 text-purple-700',
  admin: 'bg-blue-100 text-blue-700',
  editor: 'bg-yellow-100 text-yellow-700',
  member: 'bg-green-100 text-green-700',
}[r] ?? 'bg-secondary-100 text-secondary-600')

const loadMembers = async () => {
  isLoading.value = true
  errorMsg.value  = ''
  try {
    const { data } = await api.get('/store/members')
    members.value  = data.data
  } catch {
    errorMsg.value = 'خطا در بارگذاری اعضا'
  } finally {
    isLoading.value = false
  }
}

const openAdd = () => {
  addForm.value  = { email: '', role: 'member' }
  actionError.value = ''
  isAddOpen.value = true
}

const openEdit = (m: Member) => {
  selected.value  = m
  editForm.value  = { role: m.role }
  actionError.value = ''
  isEditOpen.value = true
}

const openDelete = (m: Member) => {
  selected.value   = m
  actionError.value = ''
  isDeleteOpen.value = true
}

const submitAdd = async () => {
  actionLoading.value = true
  actionError.value   = ''
  try {
    await api.post('/store/members', addForm.value)
    isAddOpen.value = false
    await loadMembers()
  } catch (e: any) {
    actionError.value = e.response?.data?.message || 'خطا در افزودن عضو'
  } finally {
    actionLoading.value = false
  }
}

const submitEdit = async () => {
  if (!selected.value) return
  actionLoading.value = true
  actionError.value   = ''
  try {
    await api.put(`/store/members/${selected.value.id}`, editForm.value)
    isEditOpen.value = false
    await loadMembers()
  } catch (e: any) {
    actionError.value = e.response?.data?.message || 'خطا در بروزرسانی نقش'
  } finally {
    actionLoading.value = false
  }
}

const confirmDelete = async () => {
  if (!selected.value) return
  actionLoading.value = true
  actionError.value   = ''
  try {
    await api.delete(`/store/members/${selected.value.id}`)
    isDeleteOpen.value = false
    await loadMembers()
  } catch (e: any) {
    actionError.value = e.response?.data?.message || 'خطا در حذف عضو'
  } finally {
    actionLoading.value = false
  }
}

onMounted(loadMembers)
</script>

<template>
  <div dir="rtl">
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-2xl font-bold text-secondary-800">اعضای فروشگاه</h2>
      <button @click="openAdd" class="flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
        <UserPlus class="w-4 h-4" />
        افزودن عضو
      </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div v-if="isLoading" class="flex items-center justify-center py-20">
        <Loader2 class="w-8 h-8 animate-spin text-primary-500" />
      </div>
      <div v-else-if="errorMsg" class="text-red-500 text-sm text-center py-10">{{ errorMsg }}</div>
      <div v-else-if="!members.length" class="text-secondary-400 text-sm text-center py-16">هیچ عضوی یافت نشد</div>
      <table v-else class="w-full text-sm">
        <thead class="bg-secondary-50 text-secondary-500 text-right">
          <tr>
            <th class="px-4 py-3 font-medium">نام</th>
            <th class="px-4 py-3 font-medium">ایمیل</th>
            <th class="px-4 py-3 font-medium">نقش</th>
            <th class="px-4 py-3 font-medium">عملیات</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-secondary-100">
          <tr v-for="m in members" :key="m.id" class="hover:bg-secondary-50">
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <ShieldCheck v-if="m.role === 'owner'" class="w-4 h-4 text-purple-500" />
                <span class="font-medium text-secondary-800">{{ m.name }}</span>
              </div>
            </td>
            <td class="px-4 py-3 text-secondary-500">{{ m.email }}</td>
            <td class="px-4 py-3">
              <span :class="['text-xs font-medium px-2 py-1 rounded-full', roleClass(m.role)]">
                {{ roleLabel(m.role) }}
              </span>
            </td>
            <td class="px-4 py-3">
              <div v-if="m.role !== 'owner'" class="flex items-center gap-2">
                <button @click="openEdit(m)" class="p-1.5 hover:bg-secondary-100 rounded text-secondary-500 hover:text-primary-600 transition" title="تغییر نقش">
                  <Edit2 class="w-4 h-4" />
                </button>
                <button @click="openDelete(m)" class="p-1.5 hover:bg-red-50 rounded text-secondary-500 hover:text-red-600 transition" title="حذف">
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Add Member Modal -->
    <Teleport to="body">
      <div v-if="isAddOpen" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" dir="rtl">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm">
          <div class="flex items-center justify-between p-5 border-b border-secondary-100">
            <h3 class="font-bold text-secondary-800">افزودن عضو جدید</h3>
            <button @click="isAddOpen = false"><X class="w-5 h-5" /></button>
          </div>
          <form @submit.prevent="submitAdd" class="p-5 space-y-4">
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">ایمیل کاربر <span class="text-red-500">*</span></label>
              <input v-model="addForm.email" type="email" required class="w-full border border-secondary-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300" />
            </div>
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">نقش</label>
              <select v-model="addForm.role" class="w-full border border-secondary-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300">
                <option value="admin">مدیر</option>
                <option value="editor">ویرایشگر</option>
                <option value="member">عضو</option>
              </select>
            </div>
            <p v-if="actionError" class="text-red-500 text-xs">{{ actionError }}</p>
            <div class="flex gap-3 pt-1">
              <button type="submit" :disabled="actionLoading" class="flex-1 bg-primary-600 hover:bg-primary-700 disabled:opacity-50 text-white py-2 rounded-lg text-sm font-medium transition">
                <Loader2 v-if="actionLoading" class="w-4 h-4 animate-spin inline ml-1" />افزودن
              </button>
              <button type="button" @click="isAddOpen = false" class="flex-1 border border-secondary-200 hover:bg-secondary-50 py-2 rounded-lg text-sm transition">انصراف</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Edit Role Modal -->
    <Teleport to="body">
      <div v-if="isEditOpen" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" dir="rtl">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm">
          <div class="flex items-center justify-between p-5 border-b border-secondary-100">
            <h3 class="font-bold text-secondary-800">تغییر نقش</h3>
            <button @click="isEditOpen = false"><X class="w-5 h-5" /></button>
          </div>
          <form @submit.prevent="submitEdit" class="p-5 space-y-4">
            <p class="text-sm text-secondary-600">کاربر: <span class="font-medium">{{ selected?.name }}</span></p>
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">نقش جدید</label>
              <select v-model="editForm.role" class="w-full border border-secondary-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300">
                <option value="admin">مدیر</option>
                <option value="editor">ویرایشگر</option>
                <option value="member">عضو</option>
              </select>
            </div>
            <p v-if="actionError" class="text-red-500 text-xs">{{ actionError }}</p>
            <div class="flex gap-3 pt-1">
              <button type="submit" :disabled="actionLoading" class="flex-1 bg-primary-600 hover:bg-primary-700 disabled:opacity-50 text-white py-2 rounded-lg text-sm font-medium transition">
                <Loader2 v-if="actionLoading" class="w-4 h-4 animate-spin inline ml-1" />ذخیره
              </button>
              <button type="button" @click="isEditOpen = false" class="flex-1 border border-secondary-200 hover:bg-secondary-50 py-2 rounded-lg text-sm transition">انصراف</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Delete Confirm Modal -->
    <Teleport to="body">
      <div v-if="isDeleteOpen" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" dir="rtl">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
          <h3 class="font-bold text-secondary-800 mb-2">حذف عضو</h3>
          <p class="text-sm text-secondary-600 mb-5">آیا از حذف <span class="font-medium">{{ selected?.name }}</span> مطمئن هستید؟</p>
          <p v-if="actionError" class="text-red-500 text-xs mb-3">{{ actionError }}</p>
          <div class="flex gap-3">
            <button @click="confirmDelete" :disabled="actionLoading" class="flex-1 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white py-2 rounded-lg text-sm font-medium transition">
              <Loader2 v-if="actionLoading" class="w-4 h-4 animate-spin inline ml-1" />حذف
            </button>
            <button @click="isDeleteOpen = false" class="flex-1 border border-secondary-200 hover:bg-secondary-50 py-2 rounded-lg text-sm transition">انصراف</button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
