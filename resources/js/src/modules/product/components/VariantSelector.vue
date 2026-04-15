<script setup lang="ts">
import { computed } from 'vue'
import type { Option, SelectedVariantOptions } from '../types'

interface Props {
  options: Option[]
  selectedOptions: SelectedVariantOptions
}

interface Emits {
  (e: 'select', optionId: number, valueId: number): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const handleSelect = (optionId: number, valueId: number) => {
  emit('select', optionId, valueId)
}

const isValueSelected = (optionId: number, valueId: number) => {
  return props.selectedOptions[optionId] === valueId
}

const getColorClass = (value: string): string => {
  const colorMap: Record<string, string> = {
    'Black': 'bg-gray-900',
    'White': 'bg-white border-gray-300',
    'Red': 'bg-red-500',
    'Blue': 'bg-blue-500',
    'Green': 'bg-green-500',
    'Yellow': 'bg-yellow-400',
    'Pink': 'bg-pink-400',
    'Purple': 'bg-purple-500',
    'Gray': 'bg-gray-400',
    'Silver': 'bg-gray-300',
  }
  return colorMap[value] || 'bg-gray-200'
}
</script>

<template>
  <div class="space-y-6">
    <div v-for="option in options" :key="option.id" class="variant-option">
      <div class="flex items-center justify-between mb-3">
        <label class="text-sm font-medium text-gray-700">
          {{ option.name }}
          <span v-if="option.required" class="text-red-500">*</span>
        </label>
        <span v-if="selectedOptions[option.id]" class="text-sm text-gray-500">
          {{ option.values.find(v => v.id === selectedOptions[option.id])?.value }}
        </span>
      </div>

      <!-- Color Selector -->
      <div v-if="option.slug === 'color'" class="flex flex-wrap gap-2">
        <button
          v-for="value in option.values"
          :key="value.id"
          @click="handleSelect(option.id, value.id)"
          :class="[
            'w-10 h-10 rounded-full border-2 transition-all relative',
            isValueSelected(option.id, value.id)
              ? 'border-primary-600 ring-2 ring-primary-200 scale-110'
              : 'border-gray-200 hover:border-gray-400',
            getColorClass(value.value)
          ]"
          :title="value.value"
        >
          <span
            v-if="isValueSelected(option.id, value.id)"
            class="absolute inset-0 flex items-center justify-center"
          >
            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
              <path
                fill-rule="evenodd"
                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                clip-rule="evenodd"
              />
            </svg>
          </span>
        </button>
      </div>

      <!-- Size/Storage/Material/Style Selector -->
      <div v-else class="flex flex-wrap gap-2">
        <button
          v-for="value in option.values"
          :key="value.id"
          @click="handleSelect(option.id, value.id)"
          :class="[
            'px-4 py-2 border rounded-lg text-sm font-medium transition-all',
            isValueSelected(option.id, value.id)
              ? 'border-primary-600 bg-primary-50 text-primary-700'
              : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400'
          ]"
        >
          {{ value.value }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.variant-option {
  @apply transition-all;
}
</style>
