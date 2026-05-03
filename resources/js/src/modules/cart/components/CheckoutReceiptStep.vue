<script setup lang="ts">
import { ref } from 'vue'
import { Upload, Copy, CheckCircle, Loader2 } from 'lucide-vue-next'
import { formatPrice } from '@shared/utils/format'

interface CardInstructions {
  card_number: string
  card_holder: string
  bank_name?: string
  amount: number | string
  instructions?: string
}

const props = defineProps<{
  instructions: CardInstructions | null
  isUploading: boolean
}>()

const emit = defineEmits<{
  submit: [file: File, cardLastFour: string | undefined]
}>()

const receiptFile    = ref<File | null>(null)
const receiptPreview = ref<string | null>(null)
const cardLastFour   = ref('')
const copied         = ref(false)

const handleFileSelect = (e: Event) => {
  const input = e.target as HTMLInputElement
  const file  = input.files?.[0]
  if (!file) return
  receiptFile.value    = file
  receiptPreview.value = URL.createObjectURL(file)
}

const removeFile = () => {
  receiptFile.value    = null
  receiptPreview.value = null
}

const copyCardNumber = async (number: string) => {
  try {
    await navigator.clipboard.writeText(number.replace(/\s/g, ''))
    copied.value = true
    setTimeout(() => (copied.value = false), 2000)
  } catch { /* ignore */ }
}

const handleSubmit = () => {
  if (!receiptFile.value) return
  emit('submit', receiptFile.value, cardLastFour.value || undefined)
}
</script>

<template>
  <div>
    <!-- Card info box -->
    <div v-if="instructions" class="mb-6 rounded-2xl overflow-hidden border border-amber-200">
      <div class="bg-amber-50 px-5 py-3 border-b border-amber-200">
        <p class="font-bold text-amber-800 text-sm">اطلاعات کارت مقصد — لطفاً مبلغ را واریز کنید</p>
      </div>
      <div class="bg-white px-5 py-4 space-y-3">
        <div class="flex items-center justify-between">
          <span class="text-xs text-gray-500 w-24">شماره کارت</span>
          <div class="flex items-center gap-2">
            <span class="font-mono text-base tracking-widest text-gray-800 select-all">
              {{ instructions.card_number }}
            </span>
            <button
              @click="copyCardNumber(instructions.card_number)"
              :class="['p-1.5 rounded-lg transition', copied ? 'bg-green-100 text-green-600' : 'bg-gray-100 hover:bg-gray-200 text-gray-500']"
              title="کپی شماره کارت"
            >
              <CheckCircle v-if="copied" class="w-4 h-4" />
              <Copy v-else class="w-4 h-4" />
            </button>
          </div>
        </div>

        <div class="flex items-center justify-between">
          <span class="text-xs text-gray-500 w-24">صاحب کارت</span>
          <span class="text-sm font-medium text-gray-800">{{ instructions.card_holder }}</span>
        </div>

        <div v-if="instructions.bank_name" class="flex items-center justify-between">
          <span class="text-xs text-gray-500 w-24">بانک</span>
          <span class="text-sm text-gray-700">{{ instructions.bank_name }}</span>
        </div>

        <div class="flex items-center justify-between pt-2 border-t border-amber-100">
          <span class="text-xs text-gray-500 w-24">مبلغ واریزی</span>
          <span class="text-base font-bold text-amber-700">
            {{ formatPrice(Number(instructions.amount)) }} تومان
          </span>
        </div>

        <p v-if="instructions.instructions" class="text-xs text-amber-700 bg-amber-50 rounded-lg px-3 py-2 mt-1">
          {{ instructions.instructions }}
        </p>
      </div>
    </div>

    <!-- Upload section -->
    <div class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          تصویر رسید پرداخت <span class="text-red-500">*</span>
        </label>

        <!-- Preview -->
        <div v-if="receiptPreview" class="relative mb-2">
          <img :src="receiptPreview" class="max-h-44 rounded-xl border border-gray-200 object-contain w-full" alt="پیش‌نمایش رسید" />
          <button
            @click="removeFile"
            class="absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shadow transition"
            title="حذف تصویر"
          >
            ×
          </button>
        </div>

        <!-- Drop zone -->
        <div
          v-else
          :class="[
            'relative border-2 border-dashed rounded-xl p-6 text-center cursor-pointer transition',
            'border-gray-300 hover:border-primary-400 hover:bg-primary-50',
          ]"
        >
          <input
            type="file"
            accept="image/*"
            class="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
            @change="handleFileSelect"
          />
          <Upload class="w-8 h-8 mx-auto text-gray-400 mb-2" />
          <p class="text-sm text-gray-600 font-medium">کلیک کنید یا تصویر را اینجا رها کنید</p>
          <p class="text-xs text-gray-400 mt-1">PNG, JPG — حداکثر ۴ مگابایت</p>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          ۴ رقم آخر کارت مبدأ <span class="text-gray-400 text-xs">(اختیاری)</span>
        </label>
        <input
          v-model="cardLastFour"
          type="text"
          maxlength="4"
          dir="ltr"
          inputmode="numeric"
          placeholder="1234"
          class="w-full h-10 px-4 rounded-lg border border-gray-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm font-mono transition"
        />
      </div>

      <button
        @click="handleSubmit"
        :disabled="isUploading || !receiptFile"
        class="w-full py-3.5 bg-primary-500 hover:bg-primary-600 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-xl font-semibold transition flex items-center justify-center gap-2"
      >
        <Loader2 v-if="isUploading" class="w-4 h-4 animate-spin" />
        <Upload v-else class="w-4 h-4" />
        {{ isUploading ? 'در حال ارسال رسید...' : 'ارسال رسید و تکمیل سفارش' }}
      </button>
    </div>
  </div>
</template>
