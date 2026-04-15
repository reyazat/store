<script setup lang="ts">
import { ref, watch } from 'vue'
import { X } from 'lucide-vue-next'
import { userService } from '../services'
import type { Address } from '@shared/types'

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
    form.value = {
      title: addr.title || '',
      first_name: addr.first_name,
      last_name: addr.last_name,
      phone: addr.phone,
      province: addr.province,
      city: addr.city,
      address: addr.address,
      postal_code: addr.postal_code,
      is_default: addr.is_default,
    }
  }
}, { immediate: true })

const submit = async () => {
  isSubmitting.value = true
  error.value = ''
  
  try {
    if (props.address) {
      await userService.updateAddress(props.address.id, form.value)
    } else {
      await userService.createAddress(form.value)
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
          <label class="block text-sm font-medium text-gray-700 mb-1">تلفن *</label>
          <input 
            v-model="form.phone"
            type="tel"
            required
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500"
          />
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
