<script setup lang="ts">
import { ref, watch } from 'vue'
import { X } from 'lucide-vue-next'
import { userService } from '../services'
import type { Address } from '@shared/types'

// ── Phone normalization (same logic as LoginPage) ──────────────────────────
const normalizePhone = (raw: string): string => {
  let v = raw.replace(/[\s\-().]/g, '')
  if (v.startsWith('+98')) v = '0' + v.slice(3)
  return v.replace(/\D/g, '')
}

const formatPhoneDisplay = (digits: string): string => {
  if (digits.startsWith('09') && digits.length >= 4) {
    const parts = [digits.slice(0, 4)]
    if (digits.length > 4) parts.push(digits.slice(4, 7))
    if (digits.length > 7) parts.push(digits.slice(7, 11))
    return parts.join(' ')
  }
  return digits
}

interface Props {
  address?: Address | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
  close: []
  saved: []
}>()

const form = ref({
  title: '',
  first_name: '',
  last_name: '',
  phone: '',
  province: '',
  city: '',
  address: '',
  postal_code: '',
  is_default: false,
})

const isSubmitting = ref(false)
const error = ref('')

watch(() => props.address, (addr) => {
  if (addr) {
    const digits = normalizePhone(addr.phone || '').slice(0, 11)
    form.value = {
      title: addr.title || '',
      first_name: addr.first_name,
      last_name: addr.last_name,
      phone: formatPhoneDisplay(digits),
      province: addr.province,
      city: addr.city,
      address: addr.address,
      postal_code: addr.postal_code,
      is_default: addr.is_default,
    }
  }
}, { immediate: true })

watch(() => form.value.phone, (newVal) => {
  const digits = normalizePhone(newVal)
  const capped = digits.slice(0, 11)
  const formatted = formatPhoneDisplay(capped)
  if (formatted !== newVal) {
    form.value.phone = formatted
  }
})

const submit = async () => {
  isSubmitting.value = true
  error.value = ''
  
  try {
    const payload = { ...form.value, phone: normalizePhone(form.value.phone) }
    if (props.address) {
      await userService.updateAddress(props.address.id, payload)
    } else {
      await userService.createAddress(payload)
    }
    emit('saved')
  } catch (err) {
    error.value = 'خطا در ذخیره آدرس'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between p-4 border-b">
        <h2 class="text-lg font-bold">{{ address ? 'ویرایش آدرس' : 'افزودن آدرس' }}</h2>
        <button @click="emit('close')" class="p-1 hover:bg-gray-100 rounded">
          <X class="w-5 h-5" />
        </button>
      </div>
      
      <form @submit.prevent="submit" class="p-4 space-y-4">
        <div v-if="error" class="bg-red-100 text-red-700 p-3 rounded text-sm">
          {{ error }}
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">عنوان آدرس</label>
          <input 
            v-model="form.title"
            type="text"
            placeholder="مثال: خانه، محل کار"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500"
          />
        </div>
        
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">نام *</label>
            <input 
              v-model="form.first_name"
              type="text"
              required
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">نام خانوادگی *</label>
            <input 
              v-model="form.last_name"
              type="text"
              required
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500"
            />
          </div>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-secondary-700 mb-2">شماره موبایل <span class="text-red-400">*</span></label>
          <div class="relative">
            <input
              v-model="form.phone"
              type="text"
              inputmode="numeric"
              placeholder="09xx xxx xxxx"
              autocomplete="tel"
              maxlength="14"
              required
              class="w-full h-14 pl-4 pr-14 text-lg rounded-xl border-2 border-secondary-200 focus:border-primary-500 focus:ring-4 focus:ring-primary-100 outline-none transition text-center tracking-widest font-mono"
              dir="ltr"
            />
            <div class="absolute right-4 top-1/2 -translate-y-1/2 flex items-center gap-1 pointer-events-none">
              <span class="text-sm text-secondary-400 font-medium">IR</span>
              <span class="text-secondary-300">+98</span>
            </div>
          </div>
          <p class="mt-2 text-xs text-secondary-400">شماره موبایل باید با ۰۹ شروع شود</p>
        </div>
        
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">استان *</label>
            <input 
              v-model="form.province"
              type="text"
              required
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">شهر *</label>
            <input 
              v-model="form.city"
              type="text"
              required
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500"
            />
          </div>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">آدرس کامل *</label>
          <textarea 
            v-model="form.address"
            rows="3"
            required
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500"
          ></textarea>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">کد پستی *</label>
          <input 
            v-model="form.postal_code"
            type="text"
            required
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500"
          />
        </div>
        
        <label class="flex items-center gap-2">
          <input 
            v-model="form.is_default"
            type="checkbox"
            class="rounded text-primary-600 focus:ring-primary-500"
          />
          <span class="text-sm text-gray-700">تنظیم به عنوان آدرس پیش‌فرض</span>
        </label>
        
        <div class="flex gap-3 pt-4">
          <button 
            type="submit"
            :disabled="isSubmitting"
            class="flex-1 bg-primary-600 text-white py-2 rounded-lg hover:bg-primary-700 disabled:opacity-50"
          >
            {{ isSubmitting ? 'در حال ذخیره...' : 'ذخیره' }}
          </button>
          <button 
            type="button"
            @click="emit('close')"
            class="px-6 py-2 border rounded-lg hover:bg-gray-100"
          >
            انصراف
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
