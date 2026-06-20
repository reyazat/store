<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Save, Loader2 } from 'lucide-vue-next'
import api from '@shared/utils/api'

interface StoreProfile { id: number; name: string; description: string | null; status: string; members_count: number }

const profile     = ref<StoreProfile | null>(null)
const isLoading   = ref(true)
const isSaving    = ref(false)
const errorMsg    = ref('')
const successMsg  = ref('')

const form = ref({ name: '', description: '' })

const loadProfile = async () => {
  isLoading.value = true
  errorMsg.value  = ''
  try {
    const { data } = await api.get('/store/profile')
    profile.value  = data.data
    form.value     = { name: data.data.name, description: data.data.description ?? '' }
  } catch {
    errorMsg.value = 'خطا در بارگذاری پروفایل فروشگاه'
  } finally {
    isLoading.value = false
  }
}

const submitUpdate = async () => {
  isSaving.value  = true
  errorMsg.value  = ''
  successMsg.value = ''
  try {
    const { data } = await api.put('/store/profile', form.value)
    profile.value   = data.data
    successMsg.value = 'پروفایل فروشگاه با موفقیت بروزرسانی شد.'
    setTimeout(() => { successMsg.value = '' }, 3000)
  } catch (e: any) {
    errorMsg.value = e.response?.data?.message || 'خطا در ذخیره تغییرات'
  } finally {
    isSaving.value = false
  }
}

onMounted(loadProfile)
</script>

<template>
  <div dir="rtl" class="max-w-xl">
    <h2 class="text-2xl font-bold text-secondary-800 mb-6">پروفایل فروشگاه</h2>

    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <Loader2 class="w-8 h-8 animate-spin text-primary-500" />
    </div>

    <div v-else-if="errorMsg && !profile" class="bg-red-50 text-red-600 rounded-xl p-4 text-sm">{{ errorMsg }}</div>

    <div v-else class="bg-white rounded-xl shadow-sm p-6 space-y-5">
      <!-- Info row -->
      <div class="flex items-center gap-3 pb-4 border-b border-secondary-100">
        <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center">
          <span class="text-primary-600 font-bold text-lg">{{ profile?.name?.charAt(0) }}</span>
        </div>
        <div>
          <p class="font-bold text-secondary-800">{{ profile?.name }}</p>
          <p class="text-sm text-secondary-400">{{ profile?.members_count }} عضو</p>
        </div>
      </div>

      <form @submit.prevent="submitUpdate" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-secondary-700 mb-1">نام فروشگاه <span class="text-red-500">*</span></label>
          <input v-model="form.name" required class="w-full border border-secondary-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300" />
        </div>
        <div>
          <label class="block text-sm font-medium text-secondary-700 mb-1">توضیحات</label>
          <textarea v-model="form.description" rows="4" placeholder="درباره فروشگاه خود بنویسید..."
            class="w-full border border-secondary-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300 resize-none" />
        </div>

        <div v-if="errorMsg" class="bg-red-50 text-red-600 rounded-lg p-3 text-sm">{{ errorMsg }}</div>
        <div v-if="successMsg" class="bg-green-50 text-green-700 rounded-lg p-3 text-sm">{{ successMsg }}</div>

        <button type="submit" :disabled="isSaving" class="flex items-center gap-2 bg-primary-600 hover:bg-primary-700 disabled:opacity-50 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition">
          <Loader2 v-if="isSaving" class="w-4 h-4 animate-spin" />
          <Save v-else class="w-4 h-4" />
          ذخیره تغییرات
        </button>
      </form>
    </div>
  </div>
</template>
