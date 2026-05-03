<script setup lang="ts">
import { ref, computed } from 'vue'
import { Plus, MapPin, Check, ChevronRight } from 'lucide-vue-next'
import type { Address } from '@shared/types'
import { userService } from '@modules/user/services'

const props = defineProps<{
  addresses: Address[]
  selectedAddressId: number | null
}>()

const emit = defineEmits<{
  'update:selectedAddressId': [id: number]
  next: []
  addressAdded: [address: Address]
}>()

type FormView = 'list' | 'add'
const view = ref<FormView>(props.addresses.length === 0 ? 'add' : 'list')

const isSubmitting = ref(false)
const formError    = ref('')

const form = ref({
  title:       '',
  first_name:  '',
  last_name:   '',
  phone:       '',
  province:    '',
  city:        '',
  address:     '',
  postal_code: '',
  is_default:  false,
})

const resetForm = () => {
  form.value = { title: '', first_name: '', last_name: '', phone: '', province: '', city: '', address: '', postal_code: '', is_default: false }
  formError.value = ''
}

const saveAddress = async () => {
  formError.value = ''
  isSubmitting.value = true
  try {
    const res = await userService.createAddress(form.value)
    const newAddress: Address = res.data
    emit('addressAdded', newAddress)
    emit('update:selectedAddressId', newAddress.id)
    resetForm()
    view.value = 'list'
  } catch (e: any) {
    formError.value = e.response?.data?.message || 'خطا در ذخیره آدرس'
  } finally {
    isSubmitting.value = false
  }
}

const canProceed = computed(() => props.selectedAddressId !== null)

const inputCls = 'w-full h-10 px-3 rounded-lg border border-gray-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm transition'
const labelCls = 'block text-sm font-medium text-gray-700 mb-1'
</script>

<template>
  <div>
    <!-- Address list -->
    <div v-if="view === 'list'">
      <div class="space-y-3 mb-4">
        <label
          v-for="addr in addresses"
          :key="addr.id"
          :class="[
            'flex items-start gap-3 p-4 border-2 rounded-xl cursor-pointer transition group',
            selectedAddressId === addr.id
              ? 'border-primary-500 bg-primary-50'
              : 'border-gray-200 hover:border-primary-300 hover:bg-gray-50',
          ]"
        >
          <input type="radio" :value="addr.id" :checked="selectedAddressId === addr.id"
            @change="emit('update:selectedAddressId', addr.id)"
            class="sr-only"
          />
          <div
            :class="[
              'mt-0.5 w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition',
              selectedAddressId === addr.id ? 'border-primary-500 bg-primary-500' : 'border-gray-300',
            ]"
          >
            <Check v-if="selectedAddressId === addr.id" class="w-3 h-3 text-white" />
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-0.5">
              <MapPin class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" />
              <p class="font-medium text-gray-800 text-sm">{{ addr.first_name }} {{ addr.last_name }}</p>
              <span v-if="addr.is_default" class="text-xs bg-primary-100 text-primary-700 px-2 py-0.5 rounded-full">پیش‌فرض</span>
              <span v-if="addr.title" class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">{{ addr.title }}</span>
            </div>
            <p class="text-sm text-gray-500">{{ addr.province }}، {{ addr.city }}، {{ addr.address }}</p>
            <p class="text-xs text-gray-400 mt-0.5">کد پستی: {{ addr.postal_code }} — تلفن: {{ addr.phone }}</p>
          </div>
        </label>
      </div>

      <button
        @click="view = 'add'"
        class="w-full py-2.5 border-2 border-dashed border-gray-300 hover:border-primary-400 hover:bg-primary-50 text-gray-500 hover:text-primary-600 rounded-xl text-sm font-medium transition flex items-center justify-center gap-2 mb-5"
      >
        <Plus class="w-4 h-4" />
        افزودن آدرس جدید
      </button>

      <button
        @click="emit('next')"
        :disabled="!canProceed"
        class="w-full py-3 bg-primary-500 hover:bg-primary-600 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-xl font-semibold transition flex items-center justify-center gap-2"
      >
        ادامه — انتخاب روش پرداخت
        <ChevronRight class="w-4 h-4" />
      </button>
    </div>

    <!-- Inline add-address form -->
    <div v-else>
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-base font-bold text-gray-800">آدرس جدید</h3>
        <button
          v-if="addresses.length > 0"
          @click="view = 'list'; resetForm()"
          class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1"
        >
          بازگشت به لیست
        </button>
      </div>

      <div v-if="formError" class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
        {{ formError }}
      </div>

      <form @submit.prevent="saveAddress" class="space-y-4">
        <div>
          <label :class="labelCls">عنوان آدرس <span class="text-gray-400 text-xs">(اختیاری، مثال: خانه، کار)</span></label>
          <input v-model="form.title" type="text" placeholder="خانه" :class="inputCls" />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label :class="labelCls">نام <span class="text-red-400">*</span></label>
            <input v-model="form.first_name" type="text" required :class="inputCls" />
          </div>
          <div>
            <label :class="labelCls">نام خانوادگی <span class="text-red-400">*</span></label>
            <input v-model="form.last_name" type="text" required :class="inputCls" />
          </div>
        </div>

        <div>
          <label :class="labelCls">شماره موبایل <span class="text-red-400">*</span></label>
          <input v-model="form.phone" type="tel" required dir="ltr" placeholder="09xxxxxxxxx" :class="inputCls" />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label :class="labelCls">استان <span class="text-red-400">*</span></label>
            <input v-model="form.province" type="text" required :class="inputCls" />
          </div>
          <div>
            <label :class="labelCls">شهر <span class="text-red-400">*</span></label>
            <input v-model="form.city" type="text" required :class="inputCls" />
          </div>
        </div>

        <div>
          <label :class="labelCls">آدرس کامل <span class="text-red-400">*</span></label>
          <textarea v-model="form.address" rows="2" required
            class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-sm resize-none transition"
          />
        </div>

        <div>
          <label :class="labelCls">کد پستی <span class="text-red-400">*</span></label>
          <input v-model="form.postal_code" type="text" required maxlength="10" dir="ltr" :class="inputCls" />
        </div>

        <label class="flex items-center gap-2.5 cursor-pointer">
          <input v-model="form.is_default" type="checkbox"
            class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 cursor-pointer"
          />
          <span class="text-sm text-gray-700">تنظیم به عنوان آدرس پیش‌فرض</span>
        </label>

        <div class="flex gap-3 pt-1">
          <button
            type="submit"
            :disabled="isSubmitting"
            class="flex-1 py-3 bg-primary-500 hover:bg-primary-600 disabled:opacity-50 text-white rounded-xl font-semibold text-sm transition"
          >
            {{ isSubmitting ? 'در حال ذخیره...' : 'ذخیره و ادامه' }}
          </button>
          <button
            v-if="addresses.length > 0"
            type="button"
            @click="view = 'list'; resetForm()"
            class="px-5 py-3 border border-gray-200 hover:bg-gray-50 text-gray-600 rounded-xl text-sm transition"
          >
            انصراف
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
