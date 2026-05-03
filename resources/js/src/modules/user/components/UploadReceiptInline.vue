<script setup lang="ts">
import { ref } from 'vue'
import { Upload, CheckCircle } from 'lucide-vue-next'
import { cartService } from '@/modules/cart/services'

const props = defineProps<{
  transactionId: number
  orderId: number
}>()

const emit = defineEmits<{
  uploaded: []
}>()

const receiptFile      = ref<File | null>(null)
const receiptPreview   = ref<string | null>(null)
const cardLastFour     = ref('')
const isUploading      = ref(false)
const errorMsg         = ref('')
const successMsg       = ref('')

const handleFileSelect = (e: Event) => {
  const input = e.target as HTMLInputElement
  const file  = input.files?.[0]
  if (!file) return
  receiptFile.value    = file
  receiptPreview.value = URL.createObjectURL(file)
  errorMsg.value       = ''
}

const submit = async () => {
  if (!receiptFile.value) { errorMsg.value = 'لطفاً تصویر رسید را انتخاب کنید'; return }
  isUploading.value = true
  errorMsg.value    = ''
  try {
    await cartService.uploadReceipt(props.transactionId, receiptFile.value, cardLastFour.value || undefined)
    successMsg.value = 'رسید با موفقیت ارسال شد. در انتظار تأیید مدیر است.'
    emit('uploaded')
  } catch (e: any) {
    errorMsg.value = e.response?.data?.message || 'خطا در ارسال رسید'
  } finally {
    isUploading.value = false
  }
}
</script>

<template>
  <div class="space-y-3">
    <div v-if="successMsg" class="flex items-center gap-2 text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-2">
      <CheckCircle class="w-4 h-4 flex-shrink-0" />
      {{ successMsg }}
    </div>

    <template v-if="!successMsg">
      <div
        :class="['relative border-2 border-dashed rounded-xl p-4 text-center cursor-pointer transition', receiptPreview ? 'border-primary-300' : 'border-gray-300 hover:border-gray-400']"
      >
        <input type="file" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full" @change="handleFileSelect" />
        <img v-if="receiptPreview" :src="receiptPreview" class="max-h-32 mx-auto object-contain rounded" />
        <div v-else class="py-3 text-gray-400">
          <Upload class="w-7 h-7 mx-auto mb-1" />
          <p class="text-xs">انتخاب تصویر رسید (PNG/JPG، حداکثر ۴ مگابایت)</p>
        </div>
      </div>

      <input
        v-model="cardLastFour"
        type="text"
        maxlength="4"
        dir="ltr"
        placeholder="۴ رقم آخر کارت (اختیاری)"
        class="w-full h-9 px-3 rounded-lg border border-gray-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm font-mono"
      />

      <p v-if="errorMsg" class="text-xs text-red-500">{{ errorMsg }}</p>

      <button
        @click="submit"
        :disabled="isUploading || !receiptFile"
        class="w-full py-2.5 bg-primary-500 hover:bg-primary-600 disabled:opacity-50 text-white rounded-xl text-sm font-medium transition flex items-center justify-center gap-2"
      >
        <Upload class="w-4 h-4" />
        {{ isUploading ? 'در حال ارسال...' : 'ارسال رسید' }}
      </button>
    </template>
  </div>
</template>
