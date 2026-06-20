<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowRight,
  Store,
  Users,
  Package,
  Crown,
  ShieldCheck,
  Edit3,
  UserRound,
  Loader2,
  AlertCircle,
  CheckCircle,
  XCircle,
  Clock,
} from 'lucide-vue-next'
import api from '@shared/utils/api'

const route  = useRoute()
const router = useRouter()

interface StoreProfile {
  id: number
  uuid: string
  name: string
  description: string | null
  logo: string | null
  status: 'active' | 'inactive' | 'suspended'
  members_count: number
  created_at: string
}

interface StoreOwner {
  id: number
  uuid: string
  name: string
  email: string
  phone: string | null
}

interface TeamMember {
  id: number
  name: string
  email: string
  role: string
}

interface TeamData {
  total: number
  owners: number
  admins: number
  editors: number
  members: number
  list: TeamMember[]
}

interface Product {
  id: number
  name: string
  slug: string
  base_sku: string
  main_image: string | null
  status: number
  price: number | null
  stock: number
  created_at: string
}

interface ProductsMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

const isLoading = ref(true)
const errorMsg  = ref('')

const store    = ref<StoreProfile | null>(null)
const owner    = ref<StoreOwner | null>(null)
const team     = ref<TeamData | null>(null)
const products = ref<Product[]>([])
const productsMeta = ref<ProductsMeta>({ current_page: 1, last_page: 1, per_page: 10, total: 0 })

const activeTab = ref<'overview' | 'team' | 'products'>('overview')

const loadOverview = async (page = 1) => {
  isLoading.value = true
  errorMsg.value  = ''
  try {
    const { data } = await api.get(`/admin/organizations/${route.params.uuid}/overview`, {
      params: { per_page: 10, page },
    })
    store.value    = data.data.store
    owner.value    = data.data.owner
    team.value     = data.data.team
    products.value = data.data.products.data
    productsMeta.value = data.data.products.meta
  } catch {
    errorMsg.value = 'خطا در بارگذاری اطلاعات فروشگاه'
  } finally {
    isLoading.value = false
  }
}

const roleLabel = (r: string) => ({
  owner: 'مالک', admin: 'مدیر', editor: 'ویرایشگر', member: 'عضو',
}[r] ?? r)

const roleClass = (r: string) => ({
  owner:  'bg-yellow-100 text-yellow-700',
  admin:  'bg-blue-100 text-blue-700',
  editor: 'bg-purple-100 text-purple-700',
  member: 'bg-secondary-100 text-secondary-600',
}[r] ?? 'bg-secondary-100 text-secondary-600')

const statusLabel = (s: string) => ({ active: 'فعال', inactive: 'غیرفعال', suspended: 'معلق' }[s] ?? s)
const statusClass = (s: string) => ({
  active:    'bg-green-100 text-green-700',
  inactive:  'bg-secondary-100 text-secondary-600',
  suspended: 'bg-red-100 text-red-700',
}[s] ?? '')

const formatPrice = (v: number | null) =>
  v != null ? new Intl.NumberFormat('fa-IR').format(v) + ' تومان' : '—'

onMounted(() => loadOverview())
</script>

<template>
  <div dir="rtl">
    <!-- Back button -->
    <button
      @click="router.push({ name: 'admin-organizations' })"
      class="flex items-center gap-2 text-sm text-secondary-500 hover:text-primary-600 mb-5 transition"
    >
      <ArrowRight class="w-4 h-4" />
      بازگشت به لیست فروشگاه‌ها
    </button>

    <!-- Loading -->
    <div v-if="isLoading" class="flex items-center justify-center py-32">
      <Loader2 class="w-10 h-10 animate-spin text-primary-500" />
    </div>

    <!-- Error -->
    <div v-else-if="errorMsg" class="flex items-center gap-3 bg-red-50 text-red-600 rounded-xl p-5">
      <AlertCircle class="w-5 h-5 flex-shrink-0" />
      {{ errorMsg }}
    </div>

    <template v-else-if="store">
      <!-- Store Header -->
      <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
        <div class="flex items-start justify-between flex-wrap gap-4">
          <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-xl bg-primary-50 flex items-center justify-center flex-shrink-0">
              <img v-if="store.logo" :src="store.logo" :alt="store.name" class="w-full h-full object-cover rounded-xl" />
              <Store v-else class="w-8 h-8 text-primary-400" />
            </div>
            <div>
              <h1 class="text-xl font-bold text-secondary-800">{{ store.name }}</h1>
              <p v-if="store.description" class="text-sm text-secondary-500 mt-1 max-w-md">{{ store.description }}</p>
              <div class="flex items-center gap-3 mt-2">
                <span :class="['text-xs font-medium px-2.5 py-1 rounded-full', statusClass(store.status)]">
                  {{ statusLabel(store.status) }}
                </span>
                <span class="text-xs text-secondary-400">
                  تاریخ ایجاد: {{ new Date(store.created_at).toLocaleDateString('fa-IR') }}
                </span>
              </div>
            </div>
          </div>

          <!-- Stats badges -->
          <div class="flex items-center gap-3 flex-wrap">
            <div class="text-center bg-secondary-50 rounded-xl px-4 py-2">
              <p class="text-xl font-bold text-secondary-800">{{ team?.total ?? 0 }}</p>
              <p class="text-xs text-secondary-500">اعضا</p>
            </div>
            <div class="text-center bg-secondary-50 rounded-xl px-4 py-2">
              <p class="text-xl font-bold text-secondary-800">{{ productsMeta.total }}</p>
              <p class="text-xs text-secondary-500">محصول</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Owner Card -->
      <div v-if="owner" class="bg-white rounded-2xl shadow-sm p-5 mb-6">
        <h2 class="text-sm font-semibold text-secondary-500 mb-3 flex items-center gap-2">
          <Crown class="w-4 h-4 text-yellow-500" /> مالک فروشگاه
        </h2>
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center flex-shrink-0">
            <span class="text-yellow-700 font-bold text-sm">{{ owner.name.charAt(0) }}</span>
          </div>
          <div>
            <p class="font-medium text-secondary-800">{{ owner.name }}</p>
            <p class="text-sm text-secondary-500">{{ owner.email }}</p>
            <p v-if="owner.phone" class="text-xs text-secondary-400">{{ owner.phone }}</p>
          </div>
        </div>
      </div>
      <div v-else class="bg-amber-50 border border-amber-200 rounded-2xl p-4 mb-6 flex items-center gap-3 text-amber-700 text-sm">
        <AlertCircle class="w-4 h-4 flex-shrink-0" />
        این فروشگاه هنوز مالک ندارد.
      </div>

      <!-- Tabs -->
      <div class="flex gap-1 bg-secondary-100 p-1 rounded-xl mb-5 w-fit">
        <button
          v-for="tab in [{ id: 'overview', label: 'خلاصه تیم' }, { id: 'team', label: 'اعضا' }, { id: 'products', label: 'محصولات' }]"
          :key="tab.id"
          @click="activeTab = tab.id as any"
          :class="['px-4 py-2 rounded-lg text-sm font-medium transition', activeTab === tab.id ? 'bg-white shadow-sm text-primary-600' : 'text-secondary-500 hover:text-secondary-700']"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- Tab: Overview -->
      <div v-if="activeTab === 'overview' && team" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl p-4 shadow-sm text-center">
          <Crown class="w-6 h-6 text-yellow-500 mx-auto mb-2" />
          <p class="text-2xl font-bold text-secondary-800">{{ team.owners }}</p>
          <p class="text-xs text-secondary-500 mt-1">مالک</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm text-center">
          <ShieldCheck class="w-6 h-6 text-blue-500 mx-auto mb-2" />
          <p class="text-2xl font-bold text-secondary-800">{{ team.admins }}</p>
          <p class="text-xs text-secondary-500 mt-1">مدیر</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm text-center">
          <Edit3 class="w-6 h-6 text-purple-500 mx-auto mb-2" />
          <p class="text-2xl font-bold text-secondary-800">{{ team.editors }}</p>
          <p class="text-xs text-secondary-500 mt-1">ویرایشگر</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm text-center">
          <UserRound class="w-6 h-6 text-secondary-400 mx-auto mb-2" />
          <p class="text-2xl font-bold text-secondary-800">{{ team.members }}</p>
          <p class="text-xs text-secondary-500 mt-1">عضو</p>
        </div>
      </div>

      <!-- Tab: Team -->
      <div v-else-if="activeTab === 'team'" class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div v-if="!team?.list?.length" class="text-secondary-400 text-sm text-center py-12">
          هیچ عضوی یافت نشد
        </div>
        <table v-else class="w-full text-sm">
          <thead class="bg-secondary-50 text-secondary-500 text-right">
            <tr>
              <th class="px-4 py-3 font-medium">نام</th>
              <th class="px-4 py-3 font-medium">ایمیل</th>
              <th class="px-4 py-3 font-medium">نقش</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-secondary-100">
            <tr v-for="member in team!.list" :key="member.id" class="hover:bg-secondary-50 transition">
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <div class="w-7 h-7 rounded-full bg-primary-100 flex items-center justify-center flex-shrink-0">
                    <span class="text-primary-600 font-bold text-xs">{{ member.name.charAt(0) }}</span>
                  </div>
                  <span class="font-medium text-secondary-800">{{ member.name }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-secondary-500">{{ member.email }}</td>
              <td class="px-4 py-3">
                <span :class="['text-xs font-medium px-2 py-1 rounded-full', roleClass(member.role)]">
                  {{ roleLabel(member.role) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Tab: Products -->
      <div v-else-if="activeTab === 'products'" class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div v-if="!products.length" class="text-secondary-400 text-sm text-center py-12">
          این فروشگاه هنوز محصولی ندارد
        </div>
        <table v-else class="w-full text-sm">
          <thead class="bg-secondary-50 text-secondary-500 text-right">
            <tr>
              <th class="px-4 py-3 font-medium">محصول</th>
              <th class="px-4 py-3 font-medium">SKU</th>
              <th class="px-4 py-3 font-medium">قیمت</th>
              <th class="px-4 py-3 font-medium">موجودی</th>
              <th class="px-4 py-3 font-medium">وضعیت</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-secondary-100">
            <tr v-for="product in products" :key="product.id" class="hover:bg-secondary-50 transition">
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <img
                    v-if="product.main_image"
                    :src="product.main_image"
                    :alt="product.name"
                    class="w-9 h-9 rounded-lg object-cover bg-secondary-100 flex-shrink-0"
                  />
                  <div v-else class="w-9 h-9 rounded-lg bg-secondary-100 flex items-center justify-center flex-shrink-0">
                    <Package class="w-4 h-4 text-secondary-400" />
                  </div>
                  <span class="font-medium text-secondary-800">{{ product.name }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-secondary-500 font-mono text-xs">{{ product.base_sku }}</td>
              <td class="px-4 py-3 text-secondary-700">{{ formatPrice(product.price) }}</td>
              <td class="px-4 py-3 text-secondary-600">{{ product.stock }}</td>
              <td class="px-4 py-3">
                <span v-if="product.status === 1" class="flex items-center gap-1 text-green-600 text-xs">
                  <CheckCircle class="w-3.5 h-3.5" /> فعال
                </span>
                <span v-else class="flex items-center gap-1 text-secondary-400 text-xs">
                  <XCircle class="w-3.5 h-3.5" /> غیرفعال
                </span>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="productsMeta.last_page > 1" class="flex items-center justify-center gap-2 p-4 border-t border-secondary-100">
          <button
            v-for="p in productsMeta.last_page"
            :key="p"
            @click="loadOverview(p)"
            :class="['w-8 h-8 rounded-lg text-sm font-medium transition', p === productsMeta.current_page ? 'bg-primary-600 text-white' : 'hover:bg-secondary-100 text-secondary-600']"
          >
            {{ p }}
          </button>
        </div>
      </div>
    </template>
  </div>
</template>
