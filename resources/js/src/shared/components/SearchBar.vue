<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { Search } from 'lucide-vue-next'

const router = useRouter()
const query = ref('')

const handleSearch = () => {
  if (query.value.trim()) {
    router.push({ path: '/search', query: { q: query.value } })
  }
}
</script>

<template>
  <form @submit.prevent="handleSearch" class="relative">
    <input
      v-model="query"
      type="text"
      placeholder="جستجوی محصولات..."
      class="w-full pl-4 pr-12 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
    />
    <button
      type="submit"
      class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary-600"
    >
      <Search class="w-5 h-5" />
    </button>
  </form>
</template>
