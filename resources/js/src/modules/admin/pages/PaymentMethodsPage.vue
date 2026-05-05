<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Save, CreditCard, Banknote, ToggleLeft, ToggleRight, Copy, CheckCheck, Info } from 'lucide-vue-next'
import { paymentMethodService, orderStatusService } from '../services/paymentMethodService'
import type { AdminPaymentMethod, OrderStatus } from '../types/payment'

const methods = ref<AdminPaymentMethod[]>([])
const statuses = ref<OrderStatus[]>([])
const isLoading = ref(true)
const savingId = ref<number | null>(null)
const successMsg = ref('')
const errorMsg = ref('')

const cardToCardConfig = ref({
  card_number: '',
  card_holder: '',
  bank_name: '',
  instructions: '',
})

const zarinpalConfig = ref({
  merchant_id: '',
  sandbox: 'true',
})

const activeTab = ref<string>('')

const showMsg = (success: boolean, msg: string) => {
  if (success) { successMsg.value = msg; setTimeout(() => (successMsg.value = ''), 3000) }
  else { errorMsg.value = msg; setTimeout(() => (errorMsg.value = ''), 4000) }
}

onMounted(async () => {
  try {
    ;[methods.value, statuses.value] = await Promise.all([
      paymentMethodService.getAll(),
      orderStatusService.getAll(),
    ])
    if (methods.value.length) {
      activeTab.value = methods.value[0].driver
      syncConfigs()
    }
  } catch {
    showMsg(false, 'خطا در بارگذاری اطلاعات')
  } finally {
    isLoading.value = false
  }
})

const syncConfigs = () => {
  const c2c = methods.value.find(m => m.driver === 'card_to_card')
  if (c2c?.config) {
    cardToCardConfig.value = {
      card_number: c2c.config.card_number ?? '',
      card_holder: c2c.config.card_holder ?? '',
      bank_name: c2c.config.bank_name ?? '',
      instructions: c2c.config.instructions ?? '',
    }
  }
  const zp = methods.value.find(m => m.driver === 'zarinpal')
  if (zp?.config) {
    zarinpalConfig.value = {
      merchant_id: zp.config.merchant_id ?? '',
      sandbox: String(zp.config.sandbox ?? 'true'),
    }
  }
}

const toggleActive = async (method: AdminPaymentMethod) => {
  try {
    const updated = await paymentMethodService.update(method.id, { is_active: !method.is_active })
    const idx = methods.value.findIndex(m => m.id === method.id)
    if (idx !== -1) methods.value[idx] = updated
    showMsg(true, updated.is_active ? `روش "${updated.label}" فعال شد` : `روش "${updated.label}" غیرفعال شد`)
  } catch (e: any) {
    // Handle validation errors
    const errors = e.response?.data?.errors
    if (errors && typeof errors === 'object') {
      const firstError = Object.values(errors).flat()[0] as string
      showMsg(false, firstError || 'خطا در تغییر وضعیت')
    } else {
      showMsg(false, e.response?.data?.message || 'خطا در تغییر وضعیت')
    }
  }
}

const saveMethod = async (method: AdminPaymentMethod) => {
  savingId.value = method.id
  try {
    const config = method.driver === 'card_to_card'
      ? { ...cardToCardConfig.value }
      : { ...zarinpalConfig.value }

    const updated = await paymentMethodService.update(method.id, {
      config,
      success_order_status: method.success_order_status,
      failure_order_status: method.failure_order_status,
      cancel_order_status: method.cancel_order_status,
    })

    const idx = methods.value.findIndex(m => m.id === method.id)
    if (idx !== -1) methods.value[idx] = updated
    showMsg(true, 'تنظیمات ذخیره شد')
  } catch (e: any) {
    const errors = e.response?.data?.errors
    if (errors && typeof errors === 'object') {
      const messages = Object.values(errors).flat().join(' | ')
      showMsg(false, messages || 'خطا در ذخیره تنظیمات')
    } else {
      showMsg(false, e.response?.data?.message || 'خطا در ذخیره تنظیمات')
    }
  } finally {
    savingId.value = null
  }
}

const driverIcon = (driver: string) => driver === 'card_to_card' ? Banknote : CreditCard

const copiedField = ref<string | null>(null)

const copyToClipboard = async (text: string, field: string) => {
  try {
    await navigator.clipboard.writeText(text)
    copiedField.value = field
    setTimeout(() => (copiedField.value = null), 2000)
  } catch {
    /* fallback for older browsers */
    const el = document.createElement('textarea')
    el.value = text
    document.body.appendChild(el)
    el.select()
    document.execCommand('copy')
    document.body.removeChild(el)
    copiedField.value = field
    setTimeout(() => (copiedField.value = null), 2000)
  }
}

const zarinpalCallbackUrl = `${window.location.protocol}//${window.location.host}/api/v1/payment/zarinpal/callback`
const zarinpalAllowedDomain = window.location.hostname
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-secondary-800">روش‌های پرداخت</h1>
    </div>

    <div v-if="isLoading" class="bg-white rounded-xl shadow-sm p-8 text-center">
      <div class="animate-spin w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full mx-auto"></div>
    </div>

    <template v-else>
      <transition name="fade">
        <div v-if="successMsg" class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm flex items-center gap-2">
          <span>✓</span> {{ successMsg }}
        </div>
      </transition>
      <transition name="fade">
        <div v-if="errorMsg" class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm flex items-center gap-2">
          <span>✕</span> {{ errorMsg }}
        </div>
      </transition>

      <div class="grid lg:grid-cols-3 gap-6">
        <!-- Method Cards -->
        <div
          v-for="method in methods"
          :key="method.id"
          class="bg-white rounded-xl shadow-sm p-5 flex flex-col gap-4"
        >
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
              <div :class="['w-10 h-10 rounded-lg flex items-center justify-center', method.is_active ? 'bg-primary-100 text-primary-600' : 'bg-secondary-100 text-secondary-400']">
                <component :is="driverIcon(method.driver)" class="w-5 h-5" />
              </div>
              <div>
                <p class="font-semibold text-secondary-800">{{ method.label }}</p>
                <p class="text-xs text-secondary-400 mt-0.5">{{ method.description }}</p>
              </div>
            </div>
            <button @click="toggleActive(method)" class="flex-shrink-0 mt-1" title="فعال/غیرفعال">
              <component
                :is="method.is_active ? ToggleRight : ToggleLeft"
                :class="['w-8 h-8 transition', method.is_active ? 'text-primary-500' : 'text-secondary-300']"
              />
            </button>
          </div>

          <button
            @click="activeTab = method.driver"
            :class="['w-full py-2 rounded-lg text-sm font-medium border transition', activeTab === method.driver ? 'bg-primary-50 border-primary-200 text-primary-700' : 'border-secondary-200 text-secondary-600 hover:bg-secondary-50']"
          >
            تنظیمات
          </button>
        </div>
      </div>

      <!-- Settings Panel -->
      <div class="mt-6 bg-white rounded-xl shadow-sm p-6">
        <template v-for="method in methods" :key="method.id">
          <div v-if="activeTab === method.driver">
            <h2 class="text-lg font-semibold text-secondary-800 mb-5">تنظیمات {{ method.label }}</h2>

            <!-- Card to Card Config -->
            <div v-if="method.driver === 'card_to_card'" class="grid md:grid-cols-2 gap-5 max-w-2xl">
              <div>
                <label class="block text-sm font-medium text-secondary-700 mb-1">شماره کارت مقصد</label>
                <input v-model="cardToCardConfig.card_number" type="text" dir="ltr" placeholder="xxxx-xxxx-xxxx-xxxx"
                  class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-secondary-700 mb-1">نام صاحب کارت</label>
                <input v-model="cardToCardConfig.card_holder" type="text"
                  class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-secondary-700 mb-1">نام بانک</label>
                <input v-model="cardToCardConfig.bank_name" type="text"
                  class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500" />
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-secondary-700 mb-1">راهنمای پرداخت (نمایش به کاربر)</label>
                <textarea v-model="cardToCardConfig.instructions" rows="3"
                  class="w-full px-4 py-2 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 resize-none"></textarea>
              </div>
            </div>

            <!-- Zarinpal Config -->
            <div v-if="method.driver === 'zarinpal'" class="space-y-6 max-w-2xl">

              <!-- Integration Info Box -->
              <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                <div class="flex items-center gap-2 mb-3">
                  <Info class="w-4 h-4 text-blue-600 flex-shrink-0" />
                  <p class="text-sm font-semibold text-blue-800">اطلاعات مورد نیاز برای ثبت درگاه در پنل زرین‌پال</p>
                </div>
                <p class="text-xs text-blue-600 mb-4">
                  هنگام ثبت و تنظیم درگاه در
                  <a href="https://www.zarinpal.com/panel/loginRegister" target="_blank" rel="noopener" class="underline font-medium">پنل زرین‌پال</a>،
                  آدرس‌های زیر را دقیقاً وارد کنید.
                </p>

                <div class="space-y-3">
                  <!-- Callback URL -->
                  <div>
                    <p class="text-xs font-medium text-blue-700 mb-1">
                      Callback URL
                      <span class="font-normal text-blue-500 mr-1">(آدرسی که زرین‌پال پس از پرداخت کاربر را به آن برمی‌گرداند)</span>
                    </p>
                    <div class="flex items-center gap-2 bg-white border border-blue-200 rounded-lg px-3 py-2">
                      <code class="flex-1 text-xs text-gray-700 font-mono break-all select-all" dir="ltr">{{ zarinpalCallbackUrl }}</code>
                      <button
                        @click="copyToClipboard(zarinpalCallbackUrl, 'callback')"
                        class="flex-shrink-0 p-1.5 rounded-md hover:bg-blue-100 transition text-blue-500"
                        title="کپی"
                      >
                        <CheckCheck v-if="copiedField === 'callback'" class="w-4 h-4 text-green-500" />
                        <Copy v-else class="w-4 h-4" />
                      </button>
                    </div>
                  </div>

                  <!-- Allowed Domain -->
                  <div>
                    <p class="text-xs font-medium text-blue-700 mb-1">
                      Allowed Domain / آدرس سایت
                      <span class="font-normal text-blue-500 mr-1">(دامنه مجاز برای پرداخت)</span>
                    </p>
                    <div class="flex items-center gap-2 bg-white border border-blue-200 rounded-lg px-3 py-2">
                      <code class="flex-1 text-xs text-gray-700 font-mono select-all" dir="ltr">{{ zarinpalAllowedDomain }}</code>
                      <button
                        @click="copyToClipboard(zarinpalAllowedDomain, 'domain')"
                        class="flex-shrink-0 p-1.5 rounded-md hover:bg-blue-100 transition text-blue-500"
                        title="کپی"
                      >
                        <CheckCheck v-if="copiedField === 'domain'" class="w-4 h-4 text-green-500" />
                        <Copy v-else class="w-4 h-4" />
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Config Fields -->
              <div class="grid md:grid-cols-2 gap-5">
                <div>
                  <label class="block text-sm font-medium text-secondary-700 mb-1">Merchant ID</label>
                  <input v-model="zarinpalConfig.merchant_id" type="text" dir="ltr" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                    class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-secondary-700 mb-1">محیط تست یا عملیاتی</label>
                  <select v-model="zarinpalConfig.sandbox"
                    class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500">
                    <option value="true">محیط تست (Sandbox)</option>
                    <option value="false">محیط عملیاتی (Live)</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Status Mapping -->
            <div class="mt-6 pt-6 border-t border-secondary-100">
              <h3 class="text-sm font-semibold text-secondary-700 mb-4">وضعیت سفارش پس از پرداخت</h3>
              <div class="grid md:grid-cols-3 gap-4 max-w-2xl">
                <div>
                  <label class="block text-xs text-secondary-500 mb-1">پرداخت موفق</label>
                  <select v-model="method.success_order_status"
                    class="w-full h-9 px-3 rounded-lg border border-secondary-200 text-sm focus:border-primary-500">
                    <option v-for="s in statuses" :key="s.key" :value="s.key">{{ s.label }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs text-secondary-500 mb-1">پرداخت ناموفق</label>
                  <select v-model="method.failure_order_status"
                    class="w-full h-9 px-3 rounded-lg border border-secondary-200 text-sm focus:border-primary-500">
                    <option v-for="s in statuses" :key="s.key" :value="s.key">{{ s.label }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs text-secondary-500 mb-1">پرداخت لغو شده</label>
                  <select v-model="method.cancel_order_status"
                    class="w-full h-9 px-3 rounded-lg border border-secondary-200 text-sm focus:border-primary-500">
                    <option v-for="s in statuses" :key="s.key" :value="s.key">{{ s.label }}</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="mt-6">
              <button
                :disabled="savingId === method.id"
                @click="saveMethod(method)"
                class="flex items-center gap-2 px-6 py-2 bg-primary-500 hover:bg-primary-600 disabled:opacity-60 text-white rounded-lg transition text-sm"
              >
                <Save class="w-4 h-4" />
                {{ savingId === method.id ? 'در حال ذخیره...' : 'ذخیره تنظیمات' }}
              </button>
            </div>
          </div>
        </template>
      </div>
    </template>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
