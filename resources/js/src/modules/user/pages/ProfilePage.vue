<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Breadcrumb } from '@shared/components'
import { userService } from '../services'
import UserSidebar from '../components/UserSidebar.vue'

const user = ref({
  name: '',
  email: '',
  phone: '',
})
const passwords = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: '',
})
const isLoading = ref(true)
const isSaving = ref(false)
const isChangingPassword = ref(false)
const message = ref('')
const error = ref('')

onMounted(async () => {
  try {
    const response = await userService.getProfile()
    user.value = response.data
  } catch (err) {
    console.error('Failed to load profile:', err)
  } finally {
    isLoading.value = false
  }
})

const updateProfile = async () => {
  isSaving.value = true
  message.value = ''
  error.value = ''
  
  try {
    await userService.updateProfile(user.value)
    message.value = 'پروفایل با موفقیت به‌روزرسانی شد'
  } catch (err) {
    error.value = 'خطا در به‌روزرسانی پروفایل'
  } finally {
    isSaving.value = false
  }
}

const changePassword = async () => {
  if (passwords.value.new_password !== passwords.value.new_password_confirmation) {
    error.value = 'رمز عبور جدید و تکرار آن مطابقت ندارند'
    return
  }
  
  isChangingPassword.value = true
  message.value = ''
  error.value = ''
  
  try {
    await userService.changePassword(passwords.value)
    message.value = 'رمز عبور با موفقیت تغییر کرد'
    passwords.value = { current_password: '', new_password: '', new_password_confirmation: '' }
  } catch (err) {
    error.value = 'خطا در تغییر رمز عبور'
  } finally {
    isChangingPassword.value = false
  }
}
</script>

<template>
  <div class="container mx-auto px-4 py-8">
    <Breadcrumb :items="[{ title: 'پنل کاربری', url: '/user/dashboard' }, { title: 'پروفایل' }]" />
    
    <h1 class="text-2xl font-bold mb-8">اطلاعات حساب</h1>
    
    <div class="grid lg:grid-cols-4 gap-8">
      <UserSidebar />
      
      <div class="lg:col-span-3 space-y-6">
        <!-- Messages -->
        <div v-if="message" class="bg-green-100 text-green-700 p-4 rounded-lg">
          {{ message }}
        </div>
        <div v-if="error" class="bg-red-100 text-red-700 p-4 rounded-lg">
          {{ error }}
        </div>
        
        <!-- Profile Form -->
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-lg font-bold mb-4">اطلاعات شخصی</h2>
          
          <form @submit.prevent="updateProfile" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">نام</label>
              <input 
                v-model="user.name"
                type="text"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">ایمیل</label>
              <input 
                v-model="user.email"
                type="email"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">تلفن</label>
              <input 
                v-model="user.phone"
                type="tel"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500"
              />
            </div>
            
            <button 
              type="submit"
              :disabled="isSaving"
              class="bg-primary-600 text-white px-6 py-2 rounded-lg hover:bg-primary-700 disabled:opacity-50"
            >
              {{ isSaving ? 'در حال ذخیره...' : 'ذخیره تغییرات' }}
            </button>
          </form>
        </div>
        
        <!-- Change Password -->
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-lg font-bold mb-4">تغییر رمز عبور</h2>
          
          <form @submit.prevent="changePassword" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">رمز عبور فعلی</label>
              <input 
                v-model="passwords.current_password"
                type="password"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">رمز عبور جدید</label>
              <input 
                v-model="passwords.new_password"
                type="password"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">تکرار رمز عبور جدید</label>
              <input 
                v-model="passwords.new_password_confirmation"
                type="password"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500"
              />
            </div>
            
            <button 
              type="submit"
              :disabled="isChangingPassword"
              class="bg-primary-600 text-white px-6 py-2 rounded-lg hover:bg-primary-700 disabled:opacity-50"
            >
              {{ isChangingPassword ? 'در حال تغییر...' : 'تغییر رمز عبور' }}
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
