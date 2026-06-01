<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { Save, Store, Phone, Share2, Info, Image as ImageIcon, Truck, Bell, CreditCard } from 'lucide-vue-next'
import { siteSettingService } from '../services/siteSettingService'
import type { SiteSettings } from '../types/siteSetting'

const isLoading = ref(true)
const isSaving = ref(false)
const successMessage = ref('')
const errorMessage = ref('')
const activeTab = ref('general')

const logoFile = ref<File | null>(null)
const faviconFile = ref<File | null>(null)
const logoPreview = ref<string | null>(null)
const faviconPreview = ref<string | null>(null)
const isUploadingLogo = ref(false)
const isUploadingFavicon = ref(false)

const form = ref<SiteSettings>({
  site_name: '',
  site_description: '',
  site_about: '',
  copyright_text: '',
  logo: '',
  logo_alt: '',
  favicon: '',
  support_hours: '',
  phone: '',
  phone_link: '',
  email: '',
  address: '',
  social_instagram: '',
  social_telegram: '',
  social_whatsapp: '',
  shipping_cost: '',
  free_shipping_min: '',
  notify_new_order_email: '1',
  notify_status_change_sms: '1',
})

const tabs = [
  { id: 'general',       name: 'عمومی',             icon: Store },
  { id: 'identity',      name: 'هویت بصری',         icon: ImageIcon },
  { id: 'contact',       name: 'تماس',              icon: Phone },
  { id: 'social',        name: 'شبکه‌های اجتماعی', icon: Share2 },
  { id: 'shipping',      name: 'ارسال',             icon: Truck },
  { id: 'notifications', name: 'اعلان‌ها',          icon: Bell },
  { id: 'payment',       name: 'پرداخت',            icon: CreditCard },
  { id: 'about',         name: 'درباره',            icon: Info },
]

const loadSettings = async () => {
  isLoading.value = true
  try {
    const data = await siteSettingService.getAll()
    Object.keys(form.value).forEach(key => {
      if (data[key] !== undefined) {
        form.value[key] = data[key]
      }
    })
    logoPreview.value = form.value.logo || null
    faviconPreview.value = form.value.favicon || null
  } catch {
    errorMessage.value = 'خطا در بارگذاری تنظیمات'
  } finally {
    isLoading.value = false
  }
}

const handleFileSelect = (key: 'logo' | 'favicon', event: Event) => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return
  if (key === 'logo') {
    logoFile.value = file
    logoPreview.value = URL.createObjectURL(file)
  } else {
    faviconFile.value = file
    faviconPreview.value = URL.createObjectURL(file)
  }
}

const uploadFile = async (key: 'logo' | 'favicon') => {
  const file = key === 'logo' ? logoFile.value : faviconFile.value
  if (!file) return

  if (key === 'logo') isUploadingLogo.value = true
  else isUploadingFavicon.value = true

  try {
    const url = await siteSettingService.uploadFile(key, file)
    form.value[key] = url
    if (key === 'logo') { logoPreview.value = url; logoFile.value = null }
    else { faviconPreview.value = url; faviconFile.value = null }
    successMessage.value = key === 'logo' ? 'لوگو با موفقیت آپلود شد' : 'فاوآیکون با موفقیت آپلود شد'
    setTimeout(() => { successMessage.value = '' }, 3000)
  } catch {
    errorMessage.value = 'خطا در آپلود فایل'
  } finally {
    if (key === 'logo') isUploadingLogo.value = false
    else isUploadingFavicon.value = false
  }
}

const save = async () => {
  isSaving.value = true
  successMessage.value = ''
  errorMessage.value = ''
  try {
    const updated = await siteSettingService.update(form.value)
    Object.keys(form.value).forEach(key => {
      if (updated[key] !== undefined) {
        form.value[key] = updated[key]
      }
    })
    successMessage.value = 'تنظیمات با موفقیت ذخیره شد'
    setTimeout(() => { successMessage.value = '' }, 3000)
  } catch {
    errorMessage.value = 'خطا در ذخیره تنظیمات'
  } finally {
    isSaving.value = false
  }
}

onMounted(loadSettings)

onUnmounted(() => {
  if (logoPreview.value && logoPreview.value.startsWith('blob:')) {
    URL.revokeObjectURL(logoPreview.value)
  }
  if (faviconPreview.value && faviconPreview.value.startsWith('blob:')) {
    URL.revokeObjectURL(faviconPreview.value)
  }
})
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-secondary-800">تنظیمات سایت</h1>
    </div>

    <div v-if="isLoading" class="bg-white rounded-xl shadow-sm p-8 text-center">
      <div class="animate-spin w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full mx-auto"></div>
    </div>

    <div v-else class="bg-white rounded-xl shadow-sm">
      <!-- Tabs -->
      <div class="border-b border-secondary-100">
        <div class="flex gap-1 p-2 overflow-x-auto">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            type="button"
            @click="activeTab = tab.id"
            :class="[
              'flex items-center gap-2 px-4 py-2 rounded-lg transition whitespace-nowrap',
              activeTab === tab.id
                ? 'bg-primary-50 text-primary-600'
                : 'text-secondary-600 hover:bg-secondary-50'
            ]"
          >
            <component :is="tab.icon" class="w-4 h-4" />
            {{ tab.name }}
          </button>
        </div>
      </div>

      <!-- Alerts -->
      <transition name="fade">
        <div v-if="successMessage" class="mx-6 mt-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm flex items-center gap-2">
          <span class="inline-block w-4 h-4 flex-shrink-0">✓</span>
          {{ successMessage }}
        </div>
      </transition>
      <transition name="fade">
        <div v-if="errorMessage" class="mx-6 mt-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm flex items-center gap-2">
          <span class="inline-block w-4 h-4 flex-shrink-0">✕</span>
          {{ errorMessage }}
        </div>
      </transition>

      <form @submit.prevent="save" class="p-6">
        <!-- Identity Tab -->
        <div v-if="activeTab === 'identity'" class="space-y-8 max-w-xl">
          <!-- Logo -->
          <section>
            <h3 class="text-base font-semibold text-secondary-800 mb-4">لوگوی فروشگاه</h3>
            <div class="flex items-start gap-6">
              <div class="w-32 h-20 rounded-xl border-2 border-dashed border-secondary-200 flex items-center justify-center overflow-hidden bg-secondary-50 flex-shrink-0">
                <img v-if="logoPreview" :src="logoPreview" alt="پیش‌نمایش لوگو" class="max-w-full max-h-full object-contain p-2" />
                <ImageIcon v-else class="w-8 h-8 text-secondary-300" />
              </div>
              <div class="flex-1 space-y-3">
                <div>
                  <label for="logo_alt" class="block text-sm font-medium text-secondary-700 mb-1">متن جایگزین لوگو (alt)</label>
                  <input
                    id="logo_alt"
                    v-model="form.logo_alt"
                    type="text"
                    class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                  />
                </div>
                <div>
                  <label for="logo_file" class="block text-sm font-medium text-secondary-700 mb-1">آپلود لوگو (PNG, JPG, SVG, WEBP حداکثر ۲MB)</label>
                  <input
                    id="logo_file"
                    type="file"
                    accept="image/*"
                    class="block w-full text-sm text-secondary-600 file:ml-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
                    @change="handleFileSelect('logo', $event)"
                  />
                </div>
                <button
                  v-if="logoFile"
                  type="button"
                  :disabled="isUploadingLogo"
                  class="flex items-center gap-2 px-4 py-2 bg-primary-500 hover:bg-primary-600 disabled:opacity-60 text-white rounded-lg text-sm transition"
                  @click="uploadFile('logo')"
                >
                  <Save class="w-4 h-4" />
                  {{ isUploadingLogo ? 'در حال آپلود...' : 'ذخیره لوگو' }}
                </button>
              </div>
            </div>
          </section>

          <!-- Favicon -->
          <section>
            <h3 class="text-base font-semibold text-secondary-800 mb-4">فاوآیکون (Favicon)</h3>
            <div class="flex items-start gap-6">
              <div class="w-16 h-16 rounded-xl border-2 border-dashed border-secondary-200 flex items-center justify-center overflow-hidden bg-secondary-50 flex-shrink-0">
                <img v-if="faviconPreview" :src="faviconPreview" alt="پیش‌نمایش فاوآیکون" class="max-w-full max-h-full object-contain p-1" />
                <ImageIcon v-else class="w-6 h-6 text-secondary-300" />
              </div>
              <div class="flex-1 space-y-3">
                <div>
                  <label for="favicon_file" class="block text-sm font-medium text-secondary-700 mb-1">آپلود فاوآیکون (PNG, ICO, SVG حداکثر ۲MB)</label>
                  <input
                    id="favicon_file"
                    type="file"
                    accept="image/*"
                    class="block w-full text-sm text-secondary-600 file:ml-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
                    @change="handleFileSelect('favicon', $event)"
                  />
                </div>
                <button
                  v-if="faviconFile"
                  type="button"
                  :disabled="isUploadingFavicon"
                  class="flex items-center gap-2 px-4 py-2 bg-primary-500 hover:bg-primary-600 disabled:opacity-60 text-white rounded-lg text-sm transition"
                  @click="uploadFile('favicon')"
                >
                  <Save class="w-4 h-4" />
                  {{ isUploadingFavicon ? 'در حال آپلود...' : 'ذخیره فاوآیکون' }}
                </button>
              </div>
            </div>
          </section>
        </div>

        <!-- General Tab -->
        <div v-if="activeTab === 'general'" class="space-y-5 max-w-xl">
          <div>
            <label for="site_name" class="block text-sm font-medium text-secondary-700 mb-1">نام سایت</label>
            <input
              id="site_name"
              v-model="form.site_name"
              type="text"
              class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
            />
          </div>
          <div>
            <label for="site_description" class="block text-sm font-medium text-secondary-700 mb-1">توضیح کوتاه</label>
            <input
              id="site_description"
              v-model="form.site_description"
              type="text"
              class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
            />
          </div>
          <div>
            <label for="copyright_text" class="block text-sm font-medium text-secondary-700 mb-1">متن کپی‌رایت</label>
            <input
              id="copyright_text"
              v-model="form.copyright_text"
              type="text"
              class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
            />
          </div>
        </div>

        <!-- Contact Tab -->
        <div v-if="activeTab === 'contact'" class="space-y-5 max-w-xl">
          <div>
            <label for="phone" class="block text-sm font-medium text-secondary-700 mb-1">شماره تلفن (نمایشی)</label>
            <input
              id="phone"
              v-model="form.phone"
              type="text"
              dir="ltr"
              class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
            />
          </div>
          <div>
            <label for="phone_link" class="block text-sm font-medium text-secondary-700 mb-1">لینک تلفن (مثال: tel:02112345678)</label>
            <input
              id="phone_link"
              v-model="form.phone_link"
              type="text"
              dir="ltr"
              class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
            />
          </div>
          <div>
            <label for="email" class="block text-sm font-medium text-secondary-700 mb-1">ایمیل</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              dir="ltr"
              class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
            />
          </div>
          <div>
            <label for="support_hours" class="block text-sm font-medium text-secondary-700 mb-1">ساعات پشتیبانی</label>
            <input
              id="support_hours"
              v-model="form.support_hours"
              type="text"
              class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
            />
          </div>
          <div>
            <label for="address" class="block text-sm font-medium text-secondary-700 mb-1">آدرس</label>
            <input
              id="address"
              v-model="form.address"
              type="text"
              class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
            />
          </div>
        </div>

        <!-- Social Tab -->
        <div v-if="activeTab === 'social'" class="space-y-5 max-w-xl">
          <div>
            <label for="social_instagram" class="block text-sm font-medium text-secondary-700 mb-1">لینک اینستاگرام</label>
            <input
              id="social_instagram"
              v-model="form.social_instagram"
              type="url"
              dir="ltr"
              placeholder="https://instagram.com/..."
              class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
            />
          </div>
          <div>
            <label for="social_telegram" class="block text-sm font-medium text-secondary-700 mb-1">لینک تلگرام</label>
            <input
              id="social_telegram"
              v-model="form.social_telegram"
              type="url"
              dir="ltr"
              placeholder="https://t.me/..."
              class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
            />
          </div>
          <div>
            <label for="social_whatsapp" class="block text-sm font-medium text-secondary-700 mb-1">لینک واتساپ</label>
            <input
              id="social_whatsapp"
              v-model="form.social_whatsapp"
              type="url"
              dir="ltr"
              placeholder="https://wa.me/..."
              class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
            />
          </div>
        </div>

        <!-- Shipping Tab -->
        <div v-if="activeTab === 'shipping'" class="space-y-5 max-w-xl">
          <div>
            <label for="shipping_cost" class="block text-sm font-medium text-secondary-700 mb-1">هزینه ارسال پیش‌فرض (تومان)</label>
            <input
              id="shipping_cost"
              v-model="form.shipping_cost"
              type="number"
              min="0"
              dir="ltr"
              placeholder="مثال: 50000"
              class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
            />
          </div>
          <div>
            <label for="free_shipping_min" class="block text-sm font-medium text-secondary-700 mb-1">حداقل مبلغ سفارش برای ارسال رایگان (تومان)</label>
            <input
              id="free_shipping_min"
              v-model="form.free_shipping_min"
              type="number"
              min="0"
              dir="ltr"
              placeholder="مثال: 500000 — برای غیرفعال کردن خالی بگذارید"
              class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
            />
            <p class="mt-1 text-xs text-secondary-400">خالی گذاشتن = ارسال رایگان فعال نیست</p>
          </div>
        </div>

        <!-- Notifications Tab -->
        <div v-if="activeTab === 'notifications'" class="space-y-4 max-w-xl">
          <p class="text-sm text-secondary-500 mb-2">این تنظیمات رفتار اعلان‌های خودکار سیستم را کنترل می‌کنند.</p>
          <label class="flex items-center gap-3 p-4 rounded-lg border border-secondary-200 cursor-pointer hover:bg-secondary-50 transition">
            <input
              v-model="form.notify_new_order_email"
              true-value="1"
              false-value="0"
              type="checkbox"
              class="w-4 h-4 rounded border-secondary-300 text-primary-600"
            />
            <div>
              <p class="text-sm font-medium text-secondary-800">ارسال ایمیل برای سفارش جدید</p>
              <p class="text-xs text-secondary-400 mt-0.5">با دریافت هر سفارش جدید، ایمیل به ادمین ارسال می‌شود</p>
            </div>
          </label>
          <label class="flex items-center gap-3 p-4 rounded-lg border border-secondary-200 cursor-pointer hover:bg-secondary-50 transition">
            <input
              v-model="form.notify_status_change_sms"
              true-value="1"
              false-value="0"
              type="checkbox"
              class="w-4 h-4 rounded border-secondary-300 text-primary-600"
            />
            <div>
              <p class="text-sm font-medium text-secondary-800">ارسال SMS برای تغییر وضعیت سفارش</p>
              <p class="text-xs text-secondary-400 mt-0.5">مشتری هنگام تغییر وضعیت سفارش، پیامک دریافت می‌کند</p>
            </div>
          </label>
        </div>

        <!-- Payment Tab -->
        <div v-if="activeTab === 'payment'" class="max-w-xl">
          <div class="flex items-start gap-3 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <CreditCard class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" />
            <div>
              <p class="text-sm font-semibold text-blue-800 mb-1">تنظیمات درگاه پرداخت</p>
              <p class="text-sm text-blue-700">پیکربندی درگاه پرداخت (کلید API، شناسه ترمینال و غیره) از طریق فایل <code class="font-mono bg-blue-100 px-1 rounded">.env</code> انجام می‌شود.</p>
              <p class="text-xs text-blue-600 mt-2">متغیرهای مرتبط: <code class="font-mono">PAYMENT_GATEWAY</code>, <code class="font-mono">PAYMENT_MERCHANT_ID</code></p>
            </div>
          </div>
        </div>

        <!-- About Tab -->
        <div v-if="activeTab === 'about'" class="space-y-5 max-w-xl">
          <div>
            <label for="site_about" class="block text-sm font-medium text-secondary-700 mb-1">درباره سایت (متن فوتر)</label>
            <textarea
              id="site_about"
              v-model="form.site_about"
              rows="5"
              class="w-full px-4 py-2 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 resize-none"
            ></textarea>
          </div>
        </div>

        <!-- Save Button (hidden for payment tab) -->
        <div v-if="activeTab !== 'payment'" class="mt-8 pt-6 border-t border-secondary-100">
          <button
            type="submit"
            :disabled="isSaving"
            class="flex items-center gap-2 px-6 py-2 bg-primary-500 hover:bg-primary-600 disabled:opacity-60 text-white rounded-lg transition"
          >
            <Save class="w-5 h-5" />
            {{ isSaving ? 'در حال ذخیره...' : 'ذخیره تغییرات' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
