<script setup lang="ts">
import { computed } from 'vue'
import { CreditCard, Banknote, Check, ChevronLeft, Loader2 } from 'lucide-vue-next'

interface PaymentMethodOption {
  id: number
  driver: string
  label: string
  description: string | null
}

const props = defineProps<{
  paymentMethods: PaymentMethodOption[]
  selectedDriver: string | null
  isSubmitting: boolean
}>()

const emit = defineEmits<{
  'update:selectedDriver': [driver: string]
  back: []
  submit: []
}>()

const driverIcon = (driver: string) => driver === 'card_to_card' ? Banknote : CreditCard

const submitLabel = computed(() => {
  if (props.isSubmitting) return 'در حال پردازش...'
  if (props.selectedDriver === 'card_to_card') return 'مشاهده اطلاعات کارت'
  return 'پرداخت آنلاین'
})
</script>

<template>
  <div>
    <button
      @click="emit('back')"
      class="flex items-center gap-1 text-sm text-gray-500 hover:text-primary-600 mb-5 transition"
    >
      <ChevronLeft class="w-4 h-4" />
      بازگشت — تغییر آدرس
    </button>

    <div v-if="paymentMethods.length === 0" class="py-10 text-center text-gray-500 text-sm">
      هیچ روش پرداختی فعال نیست. لطفاً با پشتیبانی تماس بگیرید.
    </div>

    <div v-else class="space-y-3 mb-6">
      <label
        v-for="method in paymentMethods"
        :key="method.id"
        :class="[
          'flex items-center gap-4 p-4 border-2 rounded-xl cursor-pointer transition',
          selectedDriver === method.driver
            ? 'border-primary-500 bg-primary-50'
            : 'border-gray-200 hover:border-primary-300 hover:bg-gray-50',
        ]"
      >
        <input
          type="radio"
          :value="method.driver"
          :checked="selectedDriver === method.driver"
          @change="emit('update:selectedDriver', method.driver)"
          class="sr-only"
        />
        <div
          :class="[
            'w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0 transition',
            selectedDriver === method.driver ? 'bg-primary-100 text-primary-600' : 'bg-gray-100 text-gray-400',
          ]"
        >
          <component :is="driverIcon(method.driver)" class="w-5 h-5" />
        </div>
        <div class="flex-1">
          <p class="font-semibold text-gray-800 text-sm">{{ method.label }}</p>
          <p v-if="method.description" class="text-xs text-gray-500 mt-0.5">{{ method.description }}</p>
        </div>
        <div
          :class="[
            'w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition',
            selectedDriver === method.driver ? 'border-primary-500 bg-primary-500' : 'border-gray-300',
          ]"
        >
          <Check v-if="selectedDriver === method.driver" class="w-3 h-3 text-white" />
        </div>
      </label>
    </div>

    <button
      @click="emit('submit')"
      :disabled="isSubmitting || !selectedDriver || paymentMethods.length === 0"
      class="w-full py-3.5 bg-primary-500 hover:bg-primary-600 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-xl font-semibold transition flex items-center justify-center gap-2"
    >
      <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
      {{ submitLabel }}
    </button>
  </div>
</template>
