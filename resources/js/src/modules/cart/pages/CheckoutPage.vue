<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Breadcrumb } from '@shared/components'
import { useCartStore } from '@/stores/cart'
import { cartService } from '../services'
import { formatPrice } from '@shared/utils/format'
import type { Address } from '@shared/types'
import { CreditCard, Banknote, Upload, CheckCircle, MapPin } from 'lucide-vue-next'

interface PaymentMethodOption {
  id: number
  driver: string
  label: string
  description: string | null
}

const router = useRouter()
const cartStore = useCartStore()

type Step = 'address' | 'payment' | 'receipt'

const step = ref<Step>('address')
const addresses = ref<Address[]>([])
const paymentMethods = ref<PaymentMethodOption[]>([])
const selectedAddressId = ref<number | null>(null)
const selectedDriver = ref<string | null>(null)
const isLoading = ref(false)
const isSubmitting = ref(false)
const errorMsg = ref('')

// Card-to-card
const createdOrderId = ref<number | null>(null)
const transactionId = ref<number | null>(null)
const cardInstructions = ref<Record<string, string> | null>(null)
const receiptFile = ref<File | null>(null)
const cardLastFour = ref('')
const isUploadingReceipt = ref(false)
const receiptPreview = ref<string | null>(null)

onMounted(async () => {
  isLoading.value = true
  try {
    await syncCartToServer()
    const [addrRes, methodsRes] = await Promise.all([
      cartService.getAddresses(),
      cartService.getPaymentMethods(),
    ])
    addresses.value = addrRes.data || []
    paymentMethods.value = methodsRes.data || []
    const def = addresses.value.find(a => a.is_default)
    if (def) selectedAddressId.value = def.id
  } catch {
    errorMsg.value = 'خطا در بارگذاری اطلاعات'
  } finally {
    isLoading.value = false
  }
})

const syncCartToServer = async () => {
  for (const item of cartStore.items) {
    if (item.listingId) {
      try { await cartService.addItem(item.listingId, item.quantity, item.variantId) } catch { }
    }
  }
}

const goToPaymentStep = () => {
  if (!selectedAddressId.value) { errorMsg.value = 'لطفاً یک آدرس انتخاب کنید'; return }
  errorMsg.value = ''
  step.value = 'payment'
}

const submitOrder = async () => {
  if (!selectedDriver.value) { errorMsg.value = 'لطفاً روش پرداخت را انتخاب کنید'; return }
  if (cartStore.items.length === 0) { errorMsg.value = 'سبد خرید خالی است'; return }

  isSubmitting.value = true
  errorMsg.value = ''
  try {
    const orderRes = await cartService.createOrder(selectedAddressId.value!)
    if (!orderRes.success) throw new Error(orderRes.message)

    const orderId: number = orderRes.data.id
    createdOrderId.value = orderId
    cartStore.clearCart()

    const payRes = await cartService.initiatePayment(orderId, selectedDriver.value)
    if (!payRes.success) {
      router.push(`/user/orders/${orderId}`)
      return
    }

    if (payRes.redirect_url) {
      window.location.href = payRes.redirect_url
    } else if (payRes.instructions) {
      transactionId.value = payRes.transaction.id
      cardInstructions.value = payRes.instructions
      step.value = 'receipt'
    }
  } catch (e: any) {
    if (createdOrderId.value) {
      router.push(`/user/orders/${createdOrderId.value}`)
      return
    }
    errorMsg.value = e.response?.data?.message || e.message || 'خطا در ثبت سفارش'
  } finally {
    isSubmitting.value = false
  }
}

const handleReceiptFileSelect = (e: Event) => {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return
  receiptFile.value = file
  receiptPreview.value = URL.createObjectURL(file)
}

const submitReceipt = async () => {
  if (!receiptFile.value || !transactionId.value) return
  isUploadingReceipt.value = true
  errorMsg.value = ''
  try {
    await cartService.uploadReceipt(transactionId.value, receiptFile.value, cardLastFour.value || undefined)
    router.push(`/user/orders/${createdOrderId.value}`)
  } catch (e: any) {
    errorMsg.value = e.response?.data?.message || 'خطا در ارسال رسید'
  } finally {
    isUploadingReceipt.value = false
  }
}

const driverIcon = (driver: string) => driver === 'card_to_card' ? Banknote : CreditCard
</script>

<template>
  <div class="container mx-auto px-4 py-8">
    <Breadcrumb :items="[{ title: 'سبد خرید', url: '/cart' }, { title: 'تکمیل خرید' }]" />
    <h1 class="text-2xl font-bold mb-6">تکمیل خرید</h1>

    <!-- Step indicator -->
    <div class="flex items-center gap-2 mb-8 text-sm">
      <span :class="['flex items-center gap-1.5 font-medium', step === 'address' ? 'text-primary-600' : 'text-gray-400']">
        <MapPin class="w-4 h-4" /> آدرس
      </span>
      <span class="text-gray-300 mx-1">›</span>
      <span :class="['flex items-center gap-1.5 font-medium', step === 'payment' ? 'text-primary-600' : 'text-gray-400']">
        <CreditCard class="w-4 h-4" /> روش پرداخت
      </span>
      <span class="text-gray-300 mx-1">›</span>
      <span :class="['flex items-center gap-1.5 font-medium', step === 'receipt' ? 'text-primary-600' : 'text-gray-400']">
        <Upload class="w-4 h-4" /> آپلود رسید
      </span>
    </div>

    <div v-if="errorMsg" class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">{{ errorMsg }}</div>

    <div v-if="isLoading" class="space-y-4">
      <div v-for="i in 3" :key="i" class="animate-pulse h-20 bg-gray-100 rounded-lg"></div>
    </div>

    <div v-else class="grid lg:grid-cols-3 gap-8">
      <div class="lg:col-span-2">

        <!-- Step 1: Address -->
        <div v-if="step === 'address'" class="bg-white rounded-xl shadow-sm p-6">
          <h2 class="text-lg font-bold mb-4">آدرس تحویل</h2>
          <div v-if="addresses.length === 0" class="text-center py-8 text-gray-500">
            <p class="mb-3">هیچ آدرسی ثبت نشده است</p>
            <RouterLink to="/user/addresses" class="text-primary-600 hover:underline text-sm">افزودن آدرس جدید</RouterLink>
          </div>
          <div v-else class="space-y-3">
            <label
              v-for="address in addresses"
              :key="address.id"
              :class="['block p-4 border-2 rounded-xl cursor-pointer transition', selectedAddressId === address.id ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:border-gray-300']"
            >
              <input type="radio" :value="address.id" v-model="selectedAddressId" class="hidden" />
              <div class="flex items-start justify-between">
                <div>
                  <p class="font-medium text-gray-800">{{ address.first_name }} {{ address.last_name }}</p>
                  <p class="text-sm text-gray-500 mt-1">{{ address.province }}، {{ address.city }}، {{ address.address }}</p>
                  <p class="text-sm text-gray-500 mt-0.5">کد پستی: {{ address.postal_code }} — تلفن: {{ address.phone }}</p>
                </div>
                <span v-if="address.is_default" class="text-xs bg-primary-100 text-primary-700 px-2 py-1 rounded-full whitespace-nowrap">پیش‌فرض</span>
              </div>
            </label>
          </div>
          <button
            @click="goToPaymentStep"
            :disabled="!selectedAddressId"
            class="mt-6 w-full py-3 bg-primary-500 hover:bg-primary-600 disabled:opacity-50 text-white rounded-xl font-medium transition"
          >
            ادامه — انتخاب روش پرداخت
          </button>
        </div>

        <!-- Step 2: Payment Method -->
        <div v-if="step === 'payment'" class="bg-white rounded-xl shadow-sm p-6">
          <div class="flex items-center gap-3 mb-5">
            <button @click="step = 'address'" class="text-sm text-primary-600 hover:underline">← تغییر آدرس</button>
            <h2 class="text-lg font-bold">روش پرداخت</h2>
          </div>

          <div v-if="paymentMethods.length === 0" class="text-center py-8 text-gray-500">
            هیچ روش پرداختی فعال نیست. لطفاً با پشتیبانی تماس بگیرید.
          </div>

          <div v-else class="space-y-3">
            <label
              v-for="method in paymentMethods"
              :key="method.id"
              :class="['flex items-center gap-4 p-4 border-2 rounded-xl cursor-pointer transition', selectedDriver === method.driver ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:border-gray-300']"
            >
              <input type="radio" :value="method.driver" v-model="selectedDriver" class="hidden" />
              <div :class="['w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0', selectedDriver === method.driver ? 'bg-primary-100 text-primary-600' : 'bg-gray-100 text-gray-400']">
                <component :is="driverIcon(method.driver)" class="w-5 h-5" />
              </div>
              <div class="flex-1">
                <p class="font-medium text-gray-800">{{ method.label }}</p>
                <p v-if="method.description" class="text-sm text-gray-500 mt-0.5">{{ method.description }}</p>
              </div>
              <CheckCircle v-if="selectedDriver === method.driver" class="w-5 h-5 text-primary-500 flex-shrink-0" />
            </label>
          </div>

          <button
            @click="submitOrder"
            :disabled="isSubmitting || !selectedDriver"
            class="mt-6 w-full py-3 bg-primary-500 hover:bg-primary-600 disabled:opacity-50 text-white rounded-xl font-medium transition"
          >
            {{ isSubmitting ? 'در حال پردازش...' : selectedDriver === 'card_to_card' ? 'مشاهده اطلاعات کارت' : 'پرداخت آنلاین' }}
          </button>
        </div>

        <!-- Step 3: Card-to-Card Receipt Upload -->
        <div v-if="step === 'receipt'" class="bg-white rounded-xl shadow-sm p-6">
          <h2 class="text-lg font-bold mb-5">آپلود رسید پرداخت</h2>

          <div v-if="cardInstructions" class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-xl">
            <h3 class="font-semibold text-amber-800 mb-3">اطلاعات کارت مقصد</h3>
            <div class="space-y-2 text-sm text-amber-900">
              <div class="flex items-center gap-2">
                <span class="text-amber-600 font-medium w-24">شماره کارت:</span>
                <span class="font-mono text-base tracking-wider">{{ cardInstructions.card_number }}</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-amber-600 font-medium w-24">صاحب کارت:</span>
                <span>{{ cardInstructions.card_holder }}</span>
              </div>
              <div v-if="cardInstructions.bank_name" class="flex items-center gap-2">
                <span class="text-amber-600 font-medium w-24">بانک:</span>
                <span>{{ cardInstructions.bank_name }}</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-amber-600 font-medium w-24">مبلغ:</span>
                <span class="font-bold">{{ formatPrice(Number(cardInstructions.amount)) }} تومان</span>
              </div>
              <p v-if="cardInstructions.instructions" class="mt-3 text-amber-700 text-xs border-t border-amber-200 pt-2">
                {{ cardInstructions.instructions }}
              </p>
            </div>
          </div>

          <div class="space-y-4 max-w-md">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">تصویر رسید پرداخت <span class="text-red-500">*</span></label>
              <div :class="['relative border-2 border-dashed rounded-xl p-4 text-center transition cursor-pointer', receiptPreview ? 'border-primary-300' : 'border-gray-300 hover:border-gray-400']">
                <input type="file" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer" @change="handleReceiptFileSelect" />
                <img v-if="receiptPreview" :src="receiptPreview" class="max-h-40 mx-auto object-contain rounded" />
                <div v-else class="py-4 text-gray-400">
                  <Upload class="w-8 h-8 mx-auto mb-2" />
                  <p class="text-sm">کلیک کنید یا فایل را اینجا رها کنید</p>
                  <p class="text-xs mt-1">PNG, JPG — حداکثر ۴ مگابایت</p>
                </div>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">۴ رقم آخر کارت مبدأ (اختیاری)</label>
              <input v-model="cardLastFour" type="text" maxlength="4" dir="ltr" placeholder="مثال: 1234"
                class="w-full h-10 px-4 rounded-lg border border-gray-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm font-mono" />
            </div>

            <button
              @click="submitReceipt"
              :disabled="isUploadingReceipt || !receiptFile"
              class="w-full py-3 bg-primary-500 hover:bg-primary-600 disabled:opacity-50 text-white rounded-xl font-medium transition flex items-center justify-center gap-2"
            >
              <Upload class="w-5 h-5" />
              {{ isUploadingReceipt ? 'در حال ارسال...' : 'ارسال رسید و تکمیل سفارش' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Order Summary Sidebar -->
      <div>
        <div class="bg-white rounded-xl shadow-sm p-6 sticky top-24">
          <h2 class="text-lg font-bold mb-4">خلاصه سفارش</h2>
          <div class="space-y-3 text-sm">
            <div class="flex justify-between text-gray-600">
              <span>تعداد محصولات:</span>
              <span>{{ cartStore.itemsCount }} عدد</span>
            </div>
            <div class="flex justify-between text-gray-600">
              <span>جمع سبد خرید:</span>
              <span>{{ formatPrice(cartStore.total) }} تومان</span>
            </div>
            <div class="flex justify-between text-gray-600">
              <span>هزینه ارسال:</span>
              <span class="text-green-600">رایگان</span>
            </div>
            <hr class="border-gray-100" />
            <div class="flex justify-between text-base font-bold">
              <span>مبلغ قابل پرداخت:</span>
              <span class="text-primary-600">{{ formatPrice(cartStore.total) }} تومان</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
