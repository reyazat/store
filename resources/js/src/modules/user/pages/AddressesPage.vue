<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Plus, Edit2, Trash2 } from 'lucide-vue-next'
import { Breadcrumb } from '@shared/components'
import { userService } from '../services'
import UserSidebar from '../components/UserSidebar.vue'
import AddressForm from '../components/AddressForm.vue'
import type { Address } from '@shared/types'

const addresses = ref<Address[]>([])
const isLoading = ref(true)
const showForm = ref(false)
const editingAddress = ref<Address | null>(null)

const fetchAddresses = async () => {
  isLoading.value = true
  try {
    const response = await userService.getAddresses()
    addresses.value = response.data || []
  } catch (error) {
    console.error('Failed to load addresses:', error)
  } finally {
    isLoading.value = false
  }
}

const openAddForm = () => {
  editingAddress.value = null
  showForm.value = true
}

const openEditForm = (address: Address) => {
  editingAddress.value = address
  showForm.value = true
}

const closeForm = () => {
  showForm.value = false
  editingAddress.value = null
}

const handleSaved = () => {
  closeForm()
  fetchAddresses()
}

const deleteAddress = async (id: number) => {
  if (!confirm('آیا از حذف این آدرس اطمینان دارید؟')) return
  
  try {
    await userService.deleteAddress(id)
    addresses.value = addresses.value.filter(a => a.id !== id)
  } catch (error) {
    console.error('Failed to delete address:', error)
  }
}

onMounted(fetchAddresses)
</script>

<template>
  <div class="container mx-auto px-4 py-8">
    <Breadcrumb :items="[{ title: 'پنل کاربری', url: '/user/dashboard' }, { title: 'آدرس‌ها' }]" />
    
    <h1 class="text-2xl font-bold mb-8">آدرس‌های من</h1>
    
    <div class="grid lg:grid-cols-4 gap-8">
      <UserSidebar />
      
      <div class="lg:col-span-3">
        <div class="bg-white rounded-lg shadow">
          <div class="p-6 border-b flex justify-between items-center">
            <h2 class="text-lg font-bold">لیست آدرس‌ها</h2>
            <button 
              @click="openAddForm"
              class="flex items-center gap-2 bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700"
            >
              <Plus class="w-4 h-4" />
              افزودن آدرس
            </button>
          </div>
          
          <div class="p-6">
            <div v-if="isLoading" class="space-y-4">
              <div v-for="i in 2" :key="i" class="animate-pulse p-4 border rounded-lg">
                <div class="h-4 bg-gray-200 rounded w-1/4 mb-2"></div>
                <div class="h-4 bg-gray-200 rounded w-3/4"></div>
              </div>
            </div>
            
            <div v-else-if="addresses.length === 0" class="text-center py-8 text-gray-500">
              هیچ آدرسی ثبت نشده است
            </div>
            
            <div v-else class="space-y-4">
              <div 
                v-for="address in addresses" 
                :key="address.id"
                class="p-4 border rounded-lg"
              >
                <div class="flex justify-between items-start mb-2">
                  <div class="flex items-center gap-2">
                    <span class="font-medium">{{ address.title || 'آدرس' }}</span>
                    <span v-if="address.is_default" class="text-xs bg-primary-100 text-primary-700 px-2 py-1 rounded">
                      پیش‌فرض
                    </span>
                  </div>
                  <div class="flex gap-2">
                    <button @click="openEditForm(address)" class="p-1 hover:bg-gray-100 rounded">
                      <Edit2 class="w-4 h-4 text-gray-500" />
                    </button>
                    <button @click="deleteAddress(address.id)" class="p-1 hover:bg-red-100 rounded">
                      <Trash2 class="w-4 h-4 text-red-500" />
                    </button>
                  </div>
                </div>
                <p class="text-gray-600">{{ address.province }}، {{ address.city }}، {{ address.address }}</p>
                <p class="text-sm text-gray-500 mt-1">
                  {{ address.first_name }} {{ address.last_name }} - {{ address.phone }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Address Form Modal -->
    <AddressForm 
      v-if="showForm"
      :address="editingAddress"
      @close="closeForm"
      @saved="handleSaved"
    />
  </div>
</template>
