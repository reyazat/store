<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { Breadcrumb } from '@shared/components'
import { useCartStore } from '@/stores/cart'
import { cartService } from '../services'
import type { Address } from '@shared/types'
import { ShoppingCart } from 'lucide-vue-next'
import CheckoutSteps         from '../components/CheckoutSteps.vue'
import CheckoutOrderSummary  from '../components/CheckoutOrderSummary.vue'
import CheckoutAddressStep   from '../components/CheckoutAddressStep.vue'
import CheckoutPaymentStep   from '../components/CheckoutPaymentStep.vue'
import CheckoutReceiptStep   from '../components/CheckoutReceiptStep.vue'

interface PaymentMethodOption {
  id: number
  driver: string
  label: string
  description: string | null
}

interface CardInstructions {
  card_number: string
  card_holder: string
  bank_name?: string
  amount: number | string
  instructions?: string
}

const router    = useRouter()
const cartStore = useCartStore()

type Step = 'address' | 'payment' | 'receipt'

const step             = ref<Step>('address')
const addresses        = ref<Address[]>([])
const paymentMethods   = ref<PaymentMethodOption[]>([])
const selectedAddressId = ref<number | null>(null)
const selectedDriver   = ref<string | null>(null)
const isLoading        = ref(true)
const isSubmitting     = ref(false)
const isUploadingReceipt = ref(false)
const errorMsg         = ref('')
const createdOrderId   = ref<number | null>(null)
const transactionId    = ref<number | null>(null)
const cardInstructions = ref<CardInstructions | null>(null)

onMounted(async () => {
  try {
    const [addrRes, methodsRes] = await Promise.all([
      cartService.getAddresses(),
      cartService.getPaymentMethods(),
    ])
    addresses.value      = addrRes.data || []
    paymentMethods.value = methodsRes.data || []
    const def = addresses.value.find(a => a.is_default)
    if (def) selectedAddressId.value = def.id
  } catch {
    errorMsg.value = 'خطا در بارگذاری اطلاعات. لطفاً صفحه را رفرش کنید.'
  } finally {
    isLoading.value = false
  }
})

const onAddressAdded = (newAddress: Address) => {
  addresses.value = [...addresses.value, newAddress]
}

const goToPayment = () => {
  errorMsg.value = ''
  step.value = 'payment'
}

const submitOrder = async () => {
  if (!selectedDriver.value || !selectedAddressId.value) return
  isSubmitting.value = true
  errorMsg.value     = ''
  try {
    const orderRes = await cartService.createOrder(selectedAddressId.value)
    if (!orderRes.success) throw new Error(orderRes.message)

    const orderId: number = orderRes.data.id
    createdOrderId.value  = orderId

    const payRes = await cartService.initiatePayment(orderId, selectedDriver.value)

    cartStore.clearCart()

    if (!payRes.success) {
      router.push(`/user/orders/${orderId}`)
      return
    }

    if (payRes.redirect_url) {
      window.location.href = payRes.redirect_url
    } else if (payRes.instructions) {
      transactionId.value    = payRes.transaction.id
      cardInstructions.value = payRes.instructions
      step.value             = 'receipt'
    } else {
      router.push(`/user/orders/${orderId}`)
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

const submitReceipt = async (file: File, lastFour?: string) => {
  if (!transactionId.value) return
  isUploadingReceipt.value = true
  errorMsg.value = ''
  try {
    await cartService.uploadReceipt(transactionId.value, file, lastFour)
    router.push(`/user/orders/${createdOrderId.value}`)
  } catch (e: any) {
    errorMsg.value = e.response?.data?.message || 'خطا در ارسال رسید'
  } finally {
    isUploadingReceipt.value = false
  }
}

const stepTitle: Record<Step, string> = {
  address: 'آدرس تحویل',
  payment: 'روش پرداخت',
  receipt: 'آپلود رسید پرداخت',
}
</script>

<template>
  <div class="container mx-auto px-4 py-8 max-w-5xl">
    <Breadcrumb :items="[{ title: 'سبد خرید', url: '/cart' }, { title: 'تکمیل خرید' }]" />
    <h1 class="text-2xl font-bold mb-6">تکمیل خرید</h1>

    <!-- Empty cart guard -->
    <div v-if="!isLoading && cartStore.items.length === 0 && !createdOrderId" class="bg-white rounded-2xl shadow-sm p-12 text-center">
      <ShoppingCart class="w-14 h-14 text-gray-300 mx-auto mb-4" />
      <p class="text-gray-600 font-medium mb-2">سبد خرید شما خالی است</p>
      <p class="text-sm text-gray-400 mb-6">برای تکمیل خرید ابتدا محصولاتی به سبد اضافه کنید.</p>
      <RouterLink to="/products" class="inline-block px-6 py-2.5 bg-primary-500 hover:bg-primary-600 text-white rounded-xl font-medium transition text-sm">
        مشاهده محصولات
      </RouterLink>
    </div>

    <template v-else>
      <CheckoutSteps :current="step" />

      <div v-if="errorMsg" class="mb-5 p-3.5 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm flex items-start gap-2">
        <span class="mt-0.5">⚠️</span>
        <span>{{ errorMsg }}</span>
      </div>

      <div v-if="isLoading" class="grid lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-3">
          <div v-for="i in 3" :key="i" class="animate-pulse h-20 bg-gray-100 rounded-xl" />
        </div>
        <div class="animate-pulse h-48 bg-gray-100 rounded-xl" />
      </div>

      <div v-else class="grid lg:grid-cols-3 gap-8 items-start">
        <!-- Main content -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-5">{{ stepTitle[step] }}</h2>

            <!-- Step 1: Address -->
            <CheckoutAddressStep
              v-if="step === 'address'"
              :addresses="addresses"
              :selected-address-id="selectedAddressId"
              @update:selected-address-id="selectedAddressId = $event"
              @address-added="onAddressAdded"
              @next="goToPayment"
            />

            <!-- Step 2: Payment -->
            <CheckoutPaymentStep
              v-else-if="step === 'payment'"
              :payment-methods="paymentMethods"
              :selected-driver="selectedDriver"
              :is-submitting="isSubmitting"
              @update:selected-driver="selectedDriver = $event"
              @back="step = 'address'"
              @submit="submitOrder"
            />

            <!-- Step 3: Receipt -->
            <CheckoutReceiptStep
              v-else-if="step === 'receipt'"
              :instructions="cardInstructions"
              :is-uploading="isUploadingReceipt"
              @submit="submitReceipt"
            />
          </div>
        </div>

        <!-- Sidebar -->
        <div>
          <CheckoutOrderSummary />
        </div>
      </div>
    </template>
  </div>
</template>
