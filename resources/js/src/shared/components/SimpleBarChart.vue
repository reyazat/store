<script setup lang="ts">
import { computed } from 'vue'

export interface BarDataPoint {
  label: string
  value: number
  color?: string
}

const props = withDefaults(
  defineProps<{
    data: BarDataPoint[]
    height?: number
    showValue?: boolean
    valuePrefix?: string
    valueSuffix?: string
  }>(),
  {
    height: 160,
    showValue: true,
    valuePrefix: '',
    valueSuffix: '',
  }
)

const maxValue = computed(() => {
  if (!props.data.length) return 0
  return Math.max(...props.data.map((d) => d.value))
})

const barColor = (index: number, point: BarDataPoint): string => {
  if (point.color) return point.color
  const palette = ['bg-primary-500', 'bg-blue-500', 'bg-green-500', 'bg-purple-500', 'bg-orange-500', 'bg-pink-500']
  return palette[index % palette.length]
}

const formatValue = (value: number): string => {
  return new Intl.NumberFormat('fa-IR').format(value)
}

const barHeight = (value: number): number => {
  if (maxValue.value === 0) return 4
  return Math.max(4, (value / maxValue.value) * (props.height - 40))
}
</script>

<template>
  <div class="w-full">
    <div
      v-if="data.length === 0"
      class="flex items-center justify-center text-secondary-400 text-sm"
      :style="{ height: `${height}px` }"
    >
      داده‌ای برای نمایش وجود ندارد
    </div>

    <div v-else class="flex items-end justify-between gap-2" :style="{ height: `${height}px` }">
      <div
        v-for="(point, index) in data"
        :key="index"
        class="flex-1 flex flex-col items-center justify-end gap-2 group"
      >
        <span
          v-if="showValue"
          class="text-xs text-secondary-600 opacity-0 group-hover:opacity-100 transition"
        >
          {{ valuePrefix }}{{ formatValue(point.value) }}{{ valueSuffix }}
        </span>
        <div
          :class="['w-full rounded-t-md transition-all hover:opacity-80', barColor(index, point)]"
          :style="{ height: `${barHeight(point.value)}px` }"
          :title="`${point.label}: ${valuePrefix}${formatValue(point.value)}${valueSuffix}`"
        ></div>
        <span class="text-xs text-secondary-500 truncate w-full text-center">{{ point.label }}</span>
      </div>
    </div>
  </div>
</template>
