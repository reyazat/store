<script setup lang="ts">
import { ref } from 'vue'
import { Save, Store, Truck, CreditCard, Bell } from 'lucide-vue-next'

const activeTab = ref('general')

const tabs = [
  { id: 'general', name: 'عمومی', icon: Store },
  { id: 'shipping', name: 'ارسال', icon: Truck },
  { id: 'payment', name: 'پرداخت', icon: CreditCard },
  { id: 'notifications', name: 'اعلان‌ها', icon: Bell },
]
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-secondary-800">تنظیمات</h1>
    </div>

    <div class="bg-white rounded-xl shadow-sm">
      <!-- Tabs -->
      <div class="border-b border-secondary-100">
        <div class="flex gap-1 p-2">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              'flex items-center gap-2 px-4 py-2 rounded-lg transition',
              activeTab === tab.id 
                ? 'bg-primary-50 text-primary-600' 
                : 'text-secondary-600 hover:bg-secondary-50'
            ]"
          >
            <component :is="tab.icon" class="w-5 h-5" />
            {{ tab.name }}
          </button>
        </div>
      </div>

      <!-- Content -->
      <div class="p-6">
        <!-- General Settings -->
        <div v-if="activeTab === 'general'" class="space-y-6">
          <div>
            <label class="block text-sm font-medium text-secondary-700 mb-2">نام فروشگاه</label>
            <input 
              type="text" 
              value="فروشگاه اتکا"
              class="w-full max-w-md h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-secondary-700 mb-2">توضیحات</label>
            <textarea 
              rows="3"
              class="w-full max-w-md px-4 py-2 rounded-lg border border-secondary-200 focus:border-primary-500"
            >شرکت تجارت الکترونیک و فناوری اتکا</textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-secondary-700 mb-2">ایمیل پشتیبانی</label>
            <input 
              type="email" 
              value="support@atka.ir"
              class="w-full max-w-md h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500"
            />
          </div>
        </div>

        <!-- Shipping Settings -->
        <div v-if="activeTab === 'shipping'" class="space-y-6">
          <div>
            <label class="block text-sm font-medium text-secondary-700 mb-2">هزینه ارسال پیش‌فرض</label>
            <input 
              type="number" 
              value="50000"
              class="w-full max-w-md h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-secondary-700 mb-2">حداقل خرید برای ارسال رایگان</label>
            <input 
              type="number" 
              value="500000"
              class="w-full max-w-md h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500"
            />
          </div>
        </div>

        <!-- Payment Settings -->
        <div v-if="activeTab === 'payment'" class="space-y-6">
          <p class="text-secondary-500">تنظیمات درگاه پرداخت از طریق فایل .env انجام می‌شود.</p>
        </div>

        <!-- Notification Settings -->
        <div v-if="activeTab === 'notifications'" class="space-y-6">
          <label class="flex items-center gap-3">
            <input type="checkbox" checked class="w-4 h-4 rounded border-secondary-300 text-primary-600" />
            <span class="text-secondary-700">ارسال ایمیل برای سفارش جدید</span>
          </label>
          <label class="flex items-center gap-3">
            <input type="checkbox" checked class="w-4 h-4 rounded border-secondary-300 text-primary-600" />
            <span class="text-secondary-700">ارسال SMS برای تغییر وضعیت سفارش</span>
          </label>
        </div>

        <!-- Save Button -->
        <div class="mt-8 pt-6 border-t border-secondary-100">
          <button class="flex items-center gap-2 px-6 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition">
            <Save class="w-5 h-5" />
            ذخیره تغییرات
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
