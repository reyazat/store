<script setup lang="ts">
import { ref } from 'vue'
import { Search, Edit, Trash2, Shield } from 'lucide-vue-next'

const users = ref<any[]>([])
const isLoading = ref(false)
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-secondary-800">مدیریت کاربران</h1>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-8 text-center">
      <Shield class="w-16 h-16 text-secondary-300 mx-auto mb-4" />
      <h2 class="text-xl font-bold text-secondary-800 mb-2">مدیریت کاربران</h2>
      <p class="text-secondary-500">این بخش از طریق پکیج IAM مدیریت می‌شود.</p>
    </div>
  </div>
</template>
