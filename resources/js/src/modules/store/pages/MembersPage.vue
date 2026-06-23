<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import {
  UserPlus, Mail, Trash2, Edit2, X, Loader2, ShieldCheck,
  Lock, Unlock, RefreshCw, Users, Crown, Shield, Pen,
  Eye, User, AlertTriangle,
} from 'lucide-vue-next'
import api from '@shared/utils/api'
import { formatDate } from '@shared/utils/format'
import { useAuthStore } from '@/stores/auth'
import { useStorePermissions } from '../composables/useStorePermissions'

// ─── Types ────────────────────────────────────────────────────────────────────

interface Member {
  id: number
  name: string
  email: string
  phone: string | null
  role: string
  is_locked: boolean
  locked_until: string | null
  last_login_at: string | null
  joined_at: string
}

interface Invitation {
  uuid: string
  email: string
  role: string
  status: string
  is_expired: boolean
  expires_at: string | null
  invited_by: string | null
  created_at: string
}

// ─── Composables ──────────────────────────────────────────────────────────────

const authStore = useAuthStore()
const { canManageMembers, canInviteMembers } = useStorePermissions()

// ─── State ────────────────────────────────────────────────────────────────────

const activeTab = ref<'members' | 'invitations'>('members')

const members         = ref<Member[]>([])
const invitations     = ref<Invitation[]>([])
const isLoadingMembers     = ref(true)
const isLoadingInvitations = ref(true)
const listError       = ref('')

// modals
const modal = ref<
  | 'add' | 'invite' | 'editRole' | 'lock' | 'remove'
  | 'cancelInv' | null
>(null)
const modalLoading = ref(false)
const modalError   = ref('')
const selectedMember = ref<Member | null>(null)
const selectedInv    = ref<Invitation | null>(null)

// forms
const addForm    = ref({ email: '', role: 'editor' })
const inviteForm = ref({ email: '', role: 'editor' })
const editForm   = ref({ role: 'editor' })
const lockForm   = ref({ duration_minutes: 1440, reason: '' })

// filter
const roleFilter  = ref('')
const searchQuery = ref('')

// ─── Lookup helpers ───────────────────────────────────────────────────────────

const ROLE_LABEL: Record<string, string> = {
  owner: 'مالک', admin: 'مدیر', editor: 'ویرایشگر',
  user: 'کاربر', member: 'عضو',
}
const ROLE_CLASS: Record<string, string> = {
  owner:  'bg-purple-100 text-purple-700',
  admin:  'bg-blue-100  text-blue-700',
  editor: 'bg-yellow-100 text-yellow-700',
  user:   'bg-teal-100   text-teal-700',
  member: 'bg-green-100  text-green-700',
}
const ROLE_ICON: Record<string, typeof Crown> = {
  owner: Crown, admin: Shield, editor: Pen, user: Eye, member: User,
}
const ROLE_DESC: Record<string, string> = {
  owner:  'دسترسی کامل به همه امکانات فروشگاه',
  admin:  'مدیریت اعضا، سفارشات و محصولات',
  editor: 'ویرایش محصولات و محتوای فروشگاه',
  user:   'مشاهده سفارشات و تنظیمات',
  member: 'مشاهده محصولات فروشگاه',
}

const LOCK_DURATIONS = [
  { label: '۱ ساعت',  value: 60 },
  { label: '۸ ساعت',  value: 480 },
  { label: '۲۴ ساعت', value: 1440 },
  { label: '۷ روز',   value: 10080 },
  { label: '۳۰ روز',  value: 43200 },
  { label: 'دائمی',   value: 525600000 },
]

const roleLabel = (r: string) => ROLE_LABEL[r] ?? r
const roleClass = (r: string) => ROLE_CLASS[r] ?? 'bg-secondary-100 text-secondary-600'

const initials = (name: string) => name.trim().split(/\s+/).slice(0, 2).map(w => w[0]).join('')

const avatarBg: Record<string, string> = {
  owner: 'bg-purple-100 text-purple-700',
  admin: 'bg-blue-100 text-blue-700',
  editor:'bg-yellow-100 text-yellow-700',
  user:  'bg-teal-100 text-teal-700',
  member:'bg-green-100 text-green-700',
}

// ─── Computed ─────────────────────────────────────────────────────────────────

const myId = computed(() => authStore.user?.id)

const stats = computed(() => ({
  total:   members.value.length,
  owner:   members.value.filter(m => m.role === 'owner').length,
  admin:   members.value.filter(m => m.role === 'admin').length,
  editor:  members.value.filter(m => m.role === 'editor').length,
  user:    members.value.filter(m => m.role === 'user').length,
  member:  members.value.filter(m => m.role === 'member').length,
  locked:  members.value.filter(m => m.is_locked).length,
}))

const filteredMembers = computed(() => {
  return members.value.filter(m => {
    const matchesRole  = !roleFilter.value || m.role === roleFilter.value
    const q = searchQuery.value.toLowerCase()
    const matchesSearch = !q || m.name.toLowerCase().includes(q) || m.email.toLowerCase().includes(q)
    return matchesRole && matchesSearch
  })
})

const pendingInvitations = computed(() =>
  invitations.value.filter(i => i.status === 'pending')
)

// ─── Data loading ─────────────────────────────────────────────────────────────

const loadMembers = async () => {
  isLoadingMembers.value = true
  listError.value = ''
  try {
    const { data } = await api.get('/store/members')
    members.value = data.data
  } catch {
    listError.value = 'خطا در بارگذاری اعضا'
  } finally {
    isLoadingMembers.value = false
  }
}

const loadInvitations = async () => {
  isLoadingInvitations.value = true
  try {
    const { data } = await api.get('/store/invitations')
    invitations.value = data.data
  } catch {
    // silent — invitations tab may just be empty
  } finally {
    isLoadingInvitations.value = false
  }
}

// ─── Modal helpers ────────────────────────────────────────────────────────────

const openModal = (
  type: typeof modal.value,
  member?: Member,
  inv?: Invitation,
) => {
  modal.value        = type
  modalError.value   = ''
  modalLoading.value = false
  if (member) {
    selectedMember.value = member
    if (type === 'editRole') editForm.value = { role: member.role }
    if (type === 'lock')     lockForm.value = { duration_minutes: 1440, reason: '' }
  }
  if (inv) selectedInv.value = inv
}

const closeModal = () => {
  modal.value = null
  selectedMember.value = null
  selectedInv.value    = null
}

// ─── Actions ──────────────────────────────────────────────────────────────────

const submitAdd = async () => {
  modalLoading.value = true
  modalError.value   = ''
  try {
    await api.post('/store/members', addForm.value)
    closeModal()
    addForm.value = { email: '', role: 'editor' }
    await loadMembers()
  } catch (e: any) {
    modalError.value = e.response?.data?.message || 'خطا در افزودن عضو'
  } finally {
    modalLoading.value = false
  }
}

const submitInvite = async () => {
  modalLoading.value = true
  modalError.value   = ''
  try {
    await api.post('/store/invitations', inviteForm.value)
    closeModal()
    inviteForm.value = { email: '', role: 'editor' }
    await loadInvitations()
  } catch (e: any) {
    modalError.value = e.response?.data?.message || 'خطا در ارسال دعوتنامه'
  } finally {
    modalLoading.value = false
  }
}

const submitEditRole = async () => {
  if (!selectedMember.value) return
  modalLoading.value = true
  modalError.value   = ''
  try {
    await api.put(`/store/members/${selectedMember.value.id}`, editForm.value)
    closeModal()
    await loadMembers()
  } catch (e: any) {
    modalError.value = e.response?.data?.message || 'خطا در تغییر نقش'
  } finally {
    modalLoading.value = false
  }
}

const submitLock = async () => {
  if (!selectedMember.value) return
  modalLoading.value = true
  modalError.value   = ''
  try {
    await api.post(`/store/members/${selectedMember.value.id}/lock`, lockForm.value)
    closeModal()
    await loadMembers()
  } catch (e: any) {
    modalError.value = e.response?.data?.message || 'خطا در قفل کردن حساب'
  } finally {
    modalLoading.value = false
  }
}

const submitUnlock = async (member: Member) => {
  try {
    await api.post(`/store/members/${member.id}/unlock`)
    await loadMembers()
  } catch (e: any) {
    alert(e.response?.data?.message || 'خطا در آنلاک کردن حساب')
  }
}

const submitRemove = async () => {
  if (!selectedMember.value) return
  modalLoading.value = true
  modalError.value   = ''
  try {
    await api.delete(`/store/members/${selectedMember.value.id}`)
    closeModal()
    await loadMembers()
  } catch (e: any) {
    modalError.value = e.response?.data?.message || 'خطا در حذف عضو'
  } finally {
    modalLoading.value = false
  }
}

const submitCancelInvitation = async () => {
  if (!selectedInv.value) return
  modalLoading.value = true
  modalError.value   = ''
  try {
    await api.delete(`/store/invitations/${selectedInv.value.uuid}`)
    closeModal()
    await loadInvitations()
  } catch (e: any) {
    modalError.value = e.response?.data?.message || 'خطا در لغو دعوتنامه'
  } finally {
    modalLoading.value = false
  }
}

const resendInvitation = async (inv: Invitation) => {
  try {
    await api.post(`/store/invitations/${inv.uuid}/resend`)
    await loadInvitations()
  } catch (e: any) {
    alert(e.response?.data?.message || 'خطا در ارسال مجدد دعوتنامه')
  }
}

onMounted(() => {
  loadMembers()
  loadInvitations()
})
</script>

<template>
  <div dir="rtl" class="space-y-6">

    <!-- ─── Header ─────────────────────────────────────────────────────────── -->
    <div class="flex items-start justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold text-secondary-800">اعضای فروشگاه</h2>
        <p class="text-sm text-secondary-500 mt-1">مدیریت تیم و سطح دسترسی اعضا</p>
      </div>
      <div class="flex items-center gap-2 flex-shrink-0">
        <button
          v-if="canInviteMembers"
          @click="openModal('invite')"
          class="flex items-center gap-2 border border-primary-300 text-primary-700 hover:bg-primary-50 px-4 py-2 rounded-lg text-sm font-medium transition"
        >
          <Mail class="w-4 h-4" />
          دعوت با ایمیل
        </button>
        <button
          v-if="canManageMembers"
          @click="openModal('add')"
          class="flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition"
        >
          <UserPlus class="w-4 h-4" />
          افزودن عضو
        </button>
      </div>
    </div>

    <!-- ─── Stats cards ────────────────────────────────────────────────────── -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
      <div class="bg-white rounded-xl p-3 shadow-sm text-center">
        <div class="text-2xl font-bold text-secondary-800">{{ stats.total }}</div>
        <div class="text-xs text-secondary-500 mt-0.5">کل اعضا</div>
      </div>
      <div
        v-for="r in ['owner','admin','editor','user','member']" :key="r"
        class="bg-white rounded-xl p-3 shadow-sm text-center cursor-pointer transition hover:shadow-md"
        :class="roleFilter === r ? 'ring-2 ring-primary-400' : ''"
        @click="roleFilter = roleFilter === r ? '' : r"
      >
        <div class="text-2xl font-bold text-secondary-800">{{ stats[r as keyof typeof stats] }}</div>
        <div class="text-xs text-secondary-500 mt-0.5">{{ roleLabel(r) }}</div>
      </div>
      <div class="bg-white rounded-xl p-3 shadow-sm text-center">
        <div class="text-2xl font-bold" :class="stats.locked > 0 ? 'text-red-600' : 'text-secondary-800'">
          {{ stats.locked }}
        </div>
        <div class="text-xs text-secondary-500 mt-0.5">قفل‌شده</div>
      </div>
    </div>

    <!-- ─── Tabs ──────────────────────────────────────────────────────────── -->
    <div class="flex border-b border-secondary-200">
      <button
        @click="activeTab = 'members'"
        class="flex items-center gap-2 px-5 py-3 text-sm font-medium border-b-2 transition"
        :class="activeTab === 'members'
          ? 'border-primary-600 text-primary-700'
          : 'border-transparent text-secondary-500 hover:text-secondary-800'"
      >
        <Users class="w-4 h-4" />
        اعضای فعال
        <span class="bg-secondary-100 text-secondary-600 text-xs px-1.5 py-0.5 rounded-full">
          {{ members.length }}
        </span>
      </button>
      <button
        @click="activeTab = 'invitations'; loadInvitations()"
        class="flex items-center gap-2 px-5 py-3 text-sm font-medium border-b-2 transition"
        :class="activeTab === 'invitations'
          ? 'border-primary-600 text-primary-700'
          : 'border-transparent text-secondary-500 hover:text-secondary-800'"
      >
        <Mail class="w-4 h-4" />
        دعوتنامه‌های در انتظار
        <span
          class="text-xs px-1.5 py-0.5 rounded-full"
          :class="pendingInvitations.length > 0
            ? 'bg-amber-100 text-amber-700'
            : 'bg-secondary-100 text-secondary-600'"
        >
          {{ pendingInvitations.length }}
        </span>
      </button>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════════
         TAB 1 — Active Members
    ════════════════════════════════════════════════════════════════════════ -->
    <template v-if="activeTab === 'members'">

      <!-- Filter bar -->
      <div class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
          <input
            v-model="searchQuery"
            type="search"
            placeholder="جستجو در نام یا ایمیل..."
            class="w-full border border-secondary-200 rounded-lg px-3 py-2 text-sm pr-4 focus:outline-none focus:ring-2 focus:ring-primary-300"
          />
        </div>
        <div class="flex gap-2 flex-wrap">
          <button
            @click="roleFilter = ''"
            class="px-3 py-1.5 rounded-full text-xs font-medium transition"
            :class="!roleFilter
              ? 'bg-primary-600 text-white'
              : 'bg-secondary-100 text-secondary-600 hover:bg-secondary-200'"
          >
            همه
          </button>
          <button
            v-for="r in ['owner','admin','editor','user','member']" :key="r"
            @click="roleFilter = roleFilter === r ? '' : r"
            class="px-3 py-1.5 rounded-full text-xs font-medium transition"
            :class="roleFilter === r
              ? 'bg-primary-600 text-white'
              : 'bg-secondary-100 text-secondary-600 hover:bg-secondary-200'"
          >
            {{ roleLabel(r) }}
          </button>
        </div>
      </div>

      <!-- Members table -->
      <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div v-if="isLoadingMembers" class="flex items-center justify-center py-20">
          <Loader2 class="w-8 h-8 animate-spin text-primary-500" />
        </div>
        <div v-else-if="listError" class="text-red-500 text-sm text-center py-10">
          {{ listError }}
        </div>
        <div v-else-if="!filteredMembers.length" class="flex flex-col items-center py-16 text-secondary-400">
          <Users class="w-10 h-10 mb-3 opacity-40" />
          <p class="text-sm">{{ searchQuery || roleFilter ? 'عضوی با این مشخصات یافت نشد' : 'هنوز عضوی اضافه نشده' }}</p>
        </div>

        <table v-else class="w-full text-sm">
          <thead class="bg-secondary-50 text-secondary-500 text-right">
            <tr>
              <th class="px-4 py-3 font-medium">عضو</th>
              <th class="px-4 py-3 font-medium hidden md:table-cell">ایمیل</th>
              <th class="px-4 py-3 font-medium">نقش</th>
              <th class="px-4 py-3 font-medium hidden lg:table-cell">وضعیت</th>
              <th class="px-4 py-3 font-medium hidden xl:table-cell">آخرین ورود</th>
              <th v-if="canManageMembers" class="px-4 py-3 font-medium text-center">عملیات</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-secondary-100">
            <tr
              v-for="m in filteredMembers" :key="m.id"
              class="hover:bg-secondary-50 transition"
              :class="m.is_locked ? 'opacity-70' : ''"
            >
              <!-- Avatar + Name -->
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <div
                    class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0"
                    :class="avatarBg[m.role] ?? 'bg-secondary-100 text-secondary-600'"
                  >
                    {{ initials(m.name) }}
                  </div>
                  <div>
                    <div class="flex items-center gap-1.5">
                      <ShieldCheck v-if="m.role === 'owner'" class="w-3.5 h-3.5 text-purple-500 flex-shrink-0" />
                      <span class="font-medium text-secondary-800">{{ m.name }}</span>
                      <span v-if="m.id === myId" class="text-xs bg-primary-100 text-primary-600 px-1.5 py-0.5 rounded">شما</span>
                    </div>
                    <div class="text-xs text-secondary-400 md:hidden mt-0.5">{{ m.email }}</div>
                  </div>
                </div>
              </td>

              <!-- Email -->
              <td class="px-4 py-3 text-secondary-500 hidden md:table-cell">{{ m.email }}</td>

              <!-- Role badge -->
              <td class="px-4 py-3">
                <span :class="['inline-flex items-center gap-1 text-xs font-medium px-2 py-1 rounded-full', roleClass(m.role)]">
                  <component :is="ROLE_ICON[m.role] ?? User" class="w-3 h-3" />
                  {{ roleLabel(m.role) }}
                </span>
              </td>

              <!-- Status -->
              <td class="px-4 py-3 hidden lg:table-cell">
                <div v-if="m.is_locked" class="flex items-center gap-1.5">
                  <Lock class="w-3.5 h-3.5 text-red-500" />
                  <span class="text-xs text-red-600 font-medium">قفل‌شده</span>
                  <span v-if="m.locked_until" class="text-xs text-red-400 hidden xl:inline">
                    تا {{ formatDate(m.locked_until) }}
                  </span>
                </div>
                <div v-else class="flex items-center gap-1.5">
                  <span class="w-2 h-2 rounded-full bg-green-400"></span>
                  <span class="text-xs text-green-600">فعال</span>
                </div>
              </td>

              <!-- Last login -->
              <td class="px-4 py-3 text-secondary-400 text-xs hidden xl:table-cell">
                {{ m.last_login_at ? formatDate(m.last_login_at) : '—' }}
              </td>

              <!-- Actions -->
              <td v-if="canManageMembers" class="px-4 py-3">
                <div class="flex items-center justify-center gap-1">

                  <!-- Edit role (never for owner from non-owner, never self-demotion if owner) -->
                  <button
                    v-if="m.role !== 'owner' || m.id === myId"
                    @click="openModal('editRole', m)"
                    class="p-1.5 hover:bg-secondary-100 rounded text-secondary-400 hover:text-primary-600 transition"
                    title="تغییر نقش"
                  >
                    <Edit2 class="w-4 h-4" />
                  </button>

                  <!-- Lock -->
                  <button
                    v-if="!m.is_locked && m.id !== myId && m.role !== 'owner'"
                    @click="openModal('lock', m)"
                    class="p-1.5 hover:bg-amber-50 rounded text-secondary-400 hover:text-amber-600 transition"
                    title="قفل کردن حساب"
                  >
                    <Lock class="w-4 h-4" />
                  </button>

                  <!-- Unlock -->
                  <button
                    v-if="m.is_locked"
                    @click="submitUnlock(m)"
                    class="p-1.5 hover:bg-green-50 rounded text-secondary-400 hover:text-green-600 transition"
                    title="آنلاک کردن حساب"
                  >
                    <Unlock class="w-4 h-4" />
                  </button>

                  <!-- Remove -->
                  <button
                    v-if="m.id !== myId && m.role !== 'owner'"
                    @click="openModal('remove', m)"
                    class="p-1.5 hover:bg-red-50 rounded text-secondary-400 hover:text-red-600 transition"
                    title="حذف از فروشگاه"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>

                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </template>

    <!-- ════════════════════════════════════════════════════════════════════════
         TAB 2 — Pending Invitations
    ════════════════════════════════════════════════════════════════════════ -->
    <template v-if="activeTab === 'invitations'">
      <div class="bg-white rounded-xl shadow-sm overflow-hidden">

        <div v-if="isLoadingInvitations" class="flex items-center justify-center py-20">
          <Loader2 class="w-8 h-8 animate-spin text-primary-500" />
        </div>

        <div v-else-if="!pendingInvitations.length"
          class="flex flex-col items-center py-16 text-secondary-400"
        >
          <Mail class="w-10 h-10 mb-3 opacity-40" />
          <p class="text-sm">دعوتنامه‌ای در انتظار وجود ندارد</p>
          <button
            v-if="canInviteMembers"
            @click="openModal('invite')"
            class="mt-4 flex items-center gap-2 text-primary-600 hover:text-primary-700 text-sm font-medium"
          >
            <Mail class="w-4 h-4" />
            ارسال دعوتنامه جدید
          </button>
        </div>

        <table v-else class="w-full text-sm">
          <thead class="bg-secondary-50 text-secondary-500 text-right">
            <tr>
              <th class="px-4 py-3 font-medium">ایمیل</th>
              <th class="px-4 py-3 font-medium">نقش</th>
              <th class="px-4 py-3 font-medium hidden md:table-cell">دعوت‌کننده</th>
              <th class="px-4 py-3 font-medium hidden lg:table-cell">تاریخ انقضا</th>
              <th class="px-4 py-3 font-medium text-center">وضعیت</th>
              <th v-if="canInviteMembers" class="px-4 py-3 font-medium text-center">عملیات</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-secondary-100">
            <tr
              v-for="inv in pendingInvitations" :key="inv.uuid"
              class="hover:bg-secondary-50 transition"
              :class="inv.is_expired ? 'opacity-60' : ''"
            >
              <td class="px-4 py-3 font-medium text-secondary-800">{{ inv.email }}</td>
              <td class="px-4 py-3">
                <span :class="['inline-flex items-center gap-1 text-xs font-medium px-2 py-1 rounded-full', roleClass(inv.role)]">
                  {{ roleLabel(inv.role) }}
                </span>
              </td>
              <td class="px-4 py-3 text-secondary-500 hidden md:table-cell">
                {{ inv.invited_by ?? '—' }}
              </td>
              <td class="px-4 py-3 text-secondary-400 text-xs hidden lg:table-cell">
                {{ inv.expires_at ? formatDate(inv.expires_at) : 'بدون انقضا' }}
              </td>
              <td class="px-4 py-3 text-center">
                <span
                  class="text-xs font-medium px-2 py-1 rounded-full"
                  :class="inv.is_expired
                    ? 'bg-red-100 text-red-600'
                    : 'bg-amber-100 text-amber-700'"
                >
                  {{ inv.is_expired ? 'منقضی‌شده' : 'در انتظار' }}
                </span>
              </td>
              <td v-if="canInviteMembers" class="px-4 py-3">
                <div class="flex items-center justify-center gap-1">
                  <button
                    @click="resendInvitation(inv)"
                    class="p-1.5 hover:bg-blue-50 rounded text-secondary-400 hover:text-blue-600 transition"
                    title="ارسال مجدد"
                  >
                    <RefreshCw class="w-4 h-4" />
                  </button>
                  <button
                    @click="openModal('cancelInv', undefined, inv)"
                    class="p-1.5 hover:bg-red-50 rounded text-secondary-400 hover:text-red-600 transition"
                    title="لغو دعوتنامه"
                  >
                    <X class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

      </div>
    </template>


    <!-- ════════════════════════════════════════════════════════════════════════
         MODALS
    ════════════════════════════════════════════════════════════════════════ -->

    <Teleport to="body">

      <!-- ── Add Member ─────────────────────────────────────────────────── -->
      <div v-if="modal === 'add'" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" dir="rtl">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
          <div class="flex items-center justify-between p-5 border-b border-secondary-100">
            <div>
              <h3 class="font-bold text-secondary-800">افزودن عضو موجود</h3>
              <p class="text-xs text-secondary-500 mt-0.5">کاربری که از قبل در سیستم ثبت‌نام کرده را اضافه کنید</p>
            </div>
            <button @click="closeModal" class="text-secondary-400 hover:text-secondary-600"><X class="w-5 h-5" /></button>
          </div>
          <form @submit.prevent="submitAdd" class="p-5 space-y-4">
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">
                ایمیل کاربر <span class="text-red-500">*</span>
              </label>
              <input
                v-model="addForm.email"
                type="email" required
                placeholder="example@email.com"
                class="w-full border border-secondary-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">نقش</label>
              <select
                v-model="addForm.role"
                class="w-full border border-secondary-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300"
              >
                <option v-for="r in ['admin','editor','user','member']" :key="r" :value="r">
                  {{ roleLabel(r) }} — {{ ROLE_DESC[r] }}
                </option>
              </select>
            </div>
            <p v-if="modalError" class="text-red-500 text-xs bg-red-50 rounded-lg px-3 py-2">{{ modalError }}</p>
            <div class="flex gap-3 pt-1">
              <button
                type="submit" :disabled="modalLoading"
                class="flex-1 bg-primary-600 hover:bg-primary-700 disabled:opacity-50 text-white py-2 rounded-lg text-sm font-medium transition"
              >
                <Loader2 v-if="modalLoading" class="w-4 h-4 animate-spin inline ml-1" />
                افزودن
              </button>
              <button type="button" @click="closeModal" class="flex-1 border border-secondary-200 hover:bg-secondary-50 py-2 rounded-lg text-sm transition">
                انصراف
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- ── Invite by Email ──────────────────────────────────────────────── -->
      <div v-if="modal === 'invite'" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" dir="rtl">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
          <div class="flex items-center justify-between p-5 border-b border-secondary-100">
            <div>
              <h3 class="font-bold text-secondary-800">دعوت با ایمیل</h3>
              <p class="text-xs text-secondary-500 mt-0.5">لینک دعوت به ایمیل کاربر ارسال می‌شود</p>
            </div>
            <button @click="closeModal" class="text-secondary-400 hover:text-secondary-600"><X class="w-5 h-5" /></button>
          </div>
          <form @submit.prevent="submitInvite" class="p-5 space-y-4">
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">
                ایمیل <span class="text-red-500">*</span>
              </label>
              <input
                v-model="inviteForm.email"
                type="email" required
                placeholder="example@email.com"
                class="w-full border border-secondary-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">نقش پس از پذیرش</label>
              <select
                v-model="inviteForm.role"
                class="w-full border border-secondary-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300"
              >
                <option v-for="r in ['admin','editor','user','member']" :key="r" :value="r">
                  {{ roleLabel(r) }} — {{ ROLE_DESC[r] }}
                </option>
              </select>
            </div>
            <p v-if="modalError" class="text-red-500 text-xs bg-red-50 rounded-lg px-3 py-2">{{ modalError }}</p>
            <div class="flex gap-3 pt-1">
              <button
                type="submit" :disabled="modalLoading"
                class="flex-1 bg-primary-600 hover:bg-primary-700 disabled:opacity-50 text-white py-2 rounded-lg text-sm font-medium transition"
              >
                <Loader2 v-if="modalLoading" class="w-4 h-4 animate-spin inline ml-1" />
                ارسال دعوتنامه
              </button>
              <button type="button" @click="closeModal" class="flex-1 border border-secondary-200 hover:bg-secondary-50 py-2 rounded-lg text-sm transition">
                انصراف
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- ── Edit Role ───────────────────────────────────────────────────── -->
      <div v-if="modal === 'editRole' && selectedMember" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" dir="rtl">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
          <div class="flex items-center justify-between p-5 border-b border-secondary-100">
            <h3 class="font-bold text-secondary-800">تغییر نقش</h3>
            <button @click="closeModal" class="text-secondary-400 hover:text-secondary-600"><X class="w-5 h-5" /></button>
          </div>
          <form @submit.prevent="submitEditRole" class="p-5 space-y-4">
            <div class="flex items-center gap-3 bg-secondary-50 rounded-xl p-3">
              <div
                class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0"
                :class="avatarBg[selectedMember.role] ?? 'bg-secondary-100 text-secondary-600'"
              >{{ initials(selectedMember.name) }}</div>
              <div>
                <div class="font-medium text-secondary-800">{{ selectedMember.name }}</div>
                <div class="text-xs text-secondary-500">{{ selectedMember.email }}</div>
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">نقش جدید</label>
              <select
                v-model="editForm.role"
                class="w-full border border-secondary-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300"
              >
                <option v-for="r in ['admin','editor','user','member']" :key="r" :value="r">
                  {{ roleLabel(r) }} — {{ ROLE_DESC[r] }}
                </option>
              </select>
            </div>
            <p v-if="modalError" class="text-red-500 text-xs bg-red-50 rounded-lg px-3 py-2">{{ modalError }}</p>
            <div class="flex gap-3 pt-1">
              <button
                type="submit" :disabled="modalLoading"
                class="flex-1 bg-primary-600 hover:bg-primary-700 disabled:opacity-50 text-white py-2 rounded-lg text-sm font-medium transition"
              >
                <Loader2 v-if="modalLoading" class="w-4 h-4 animate-spin inline ml-1" />
                ذخیره
              </button>
              <button type="button" @click="closeModal" class="flex-1 border border-secondary-200 hover:bg-secondary-50 py-2 rounded-lg text-sm transition">
                انصراف
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- ── Lock Account ────────────────────────────────────────────────── -->
      <div v-if="modal === 'lock' && selectedMember" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" dir="rtl">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
          <div class="flex items-center justify-between p-5 border-b border-secondary-100">
            <h3 class="font-bold text-secondary-800">قفل کردن حساب</h3>
            <button @click="closeModal" class="text-secondary-400 hover:text-secondary-600"><X class="w-5 h-5" /></button>
          </div>
          <form @submit.prevent="submitLock" class="p-5 space-y-4">
            <div class="flex items-center gap-3 bg-amber-50 border border-amber-200 rounded-xl p-3">
              <AlertTriangle class="w-5 h-5 text-amber-500 flex-shrink-0" />
              <div>
                <div class="text-sm font-medium text-amber-800">{{ selectedMember.name }}</div>
                <div class="text-xs text-amber-600">{{ selectedMember.email }}</div>
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">مدت قفل</label>
              <select
                v-model.number="lockForm.duration_minutes"
                class="w-full border border-secondary-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300"
              >
                <option v-for="d in LOCK_DURATIONS" :key="d.value" :value="d.value">
                  {{ d.label }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-secondary-700 mb-1">
                دلیل قفل <span class="text-red-500">*</span>
              </label>
              <textarea
                v-model="lockForm.reason"
                required rows="3"
                placeholder="دلیل قفل کردن این حساب را بنویسید..."
                class="w-full border border-secondary-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300 resize-none"
              />
            </div>
            <p v-if="modalError" class="text-red-500 text-xs bg-red-50 rounded-lg px-3 py-2">{{ modalError }}</p>
            <div class="flex gap-3 pt-1">
              <button
                type="submit" :disabled="modalLoading || !lockForm.reason.trim()"
                class="flex-1 bg-amber-600 hover:bg-amber-700 disabled:opacity-50 text-white py-2 rounded-lg text-sm font-medium transition"
              >
                <Loader2 v-if="modalLoading" class="w-4 h-4 animate-spin inline ml-1" />
                قفل کردن
              </button>
              <button type="button" @click="closeModal" class="flex-1 border border-secondary-200 hover:bg-secondary-50 py-2 rounded-lg text-sm transition">
                انصراف
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- ── Confirm Remove ──────────────────────────────────────────────── -->
      <div v-if="modal === 'remove' && selectedMember" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" dir="rtl">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm">
          <div class="flex items-center justify-between p-5 border-b border-secondary-100">
            <h3 class="font-bold text-secondary-800">حذف از فروشگاه</h3>
            <button @click="closeModal" class="text-secondary-400 hover:text-secondary-600"><X class="w-5 h-5" /></button>
          </div>
          <div class="p-5 space-y-4">
            <p class="text-sm text-secondary-600">
              آیا از حذف <span class="font-semibold text-secondary-800">{{ selectedMember.name }}</span>
              از فروشگاه مطمئن هستید؟
            </p>
            <p class="text-xs text-secondary-400">
              دسترسی کاربر به تمام امکانات فروشگاه قطع می‌شود. می‌توانید بعداً دوباره او را اضافه کنید.
            </p>
            <p v-if="modalError" class="text-red-500 text-xs bg-red-50 rounded-lg px-3 py-2">{{ modalError }}</p>
            <div class="flex gap-3">
              <button
                @click="submitRemove" :disabled="modalLoading"
                class="flex-1 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white py-2 rounded-lg text-sm font-medium transition"
              >
                <Loader2 v-if="modalLoading" class="w-4 h-4 animate-spin inline ml-1" />
                حذف
              </button>
              <button @click="closeModal" class="flex-1 border border-secondary-200 hover:bg-secondary-50 py-2 rounded-lg text-sm transition">
                انصراف
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Confirm Cancel Invitation ──────────────────────────────────── -->
      <div v-if="modal === 'cancelInv' && selectedInv" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" dir="rtl">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm">
          <div class="flex items-center justify-between p-5 border-b border-secondary-100">
            <h3 class="font-bold text-secondary-800">لغو دعوتنامه</h3>
            <button @click="closeModal" class="text-secondary-400 hover:text-secondary-600"><X class="w-5 h-5" /></button>
          </div>
          <div class="p-5 space-y-4">
            <p class="text-sm text-secondary-600">
              دعوتنامه ارسال‌شده به
              <span class="font-semibold text-secondary-800">{{ selectedInv.email }}</span>
              لغو شود؟
            </p>
            <p v-if="modalError" class="text-red-500 text-xs bg-red-50 rounded-lg px-3 py-2">{{ modalError }}</p>
            <div class="flex gap-3">
              <button
                @click="submitCancelInvitation" :disabled="modalLoading"
                class="flex-1 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white py-2 rounded-lg text-sm font-medium transition"
              >
                <Loader2 v-if="modalLoading" class="w-4 h-4 animate-spin inline ml-1" />
                لغو دعوتنامه
              </button>
              <button @click="closeModal" class="flex-1 border border-secondary-200 hover:bg-secondary-50 py-2 rounded-lg text-sm transition">
                انصراف
              </button>
            </div>
          </div>
        </div>
      </div>

    </Teleport>

  </div>
</template>
