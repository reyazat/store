<script setup lang="ts">
import { MapPin, CreditCard, Upload, Check } from 'lucide-vue-next'

const props = defineProps<{
  current: 'address' | 'payment' | 'receipt'
}>()

const steps = [
  { key: 'address', label: 'آدرس', icon: MapPin },
  { key: 'payment', label: 'پرداخت', icon: CreditCard },
  { key: 'receipt', label: 'رسید', icon: Upload },
] as const

type StepKey = typeof steps[number]['key']

const stepIndex = (key: StepKey) => steps.findIndex(s => s.key === key)
const currentIndex = stepIndex(props.current)

const stateOf = (key: StepKey) => {
  const idx = stepIndex(key)
  if (idx < currentIndex) return 'done'
  if (idx === currentIndex) return 'active'
  return 'upcoming'
}
</script>

<template>
  <nav aria-label="مراحل خرید" class="mb-8">
    <ol class="flex items-center">
      <li
        v-for="(step, i) in steps"
        :key="step.key"
        class="flex items-center"
        :class="i < steps.length - 1 ? 'flex-1' : ''"
      >
        <div class="flex flex-col items-center gap-1 min-w-[60px]">
          <div
            :class="[
              'w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300',
              stateOf(step.key) === 'done'     ? 'bg-primary-500 text-white'          : '',
              stateOf(step.key) === 'active'   ? 'bg-primary-600 text-white ring-4 ring-primary-100 scale-110' : '',
              stateOf(step.key) === 'upcoming' ? 'bg-gray-100 text-gray-400'          : '',
            ]"
          >
            <Check v-if="stateOf(step.key) === 'done'" class="w-5 h-5" />
            <component v-else :is="step.icon" class="w-4 h-4" />
          </div>
          <span
            :class="[
              'text-xs font-medium whitespace-nowrap',
              stateOf(step.key) === 'done'   ? 'text-primary-600' : '',
              stateOf(step.key) === 'active' ? 'text-primary-700' : '',
              stateOf(step.key) === 'upcoming' ? 'text-gray-400'  : '',
            ]"
          >
            {{ step.label }}
          </span>
        </div>

        <div
          v-if="i < steps.length - 1"
          :class="[
            'h-0.5 flex-1 mx-2 mb-4 rounded transition-all duration-500',
            stepIndex(steps[i + 1].key) <= currentIndex ? 'bg-primary-400' : 'bg-gray-200',
          ]"
        />
      </li>
    </ol>
  </nav>
</template>
