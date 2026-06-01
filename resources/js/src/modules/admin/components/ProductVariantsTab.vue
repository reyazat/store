<script setup lang="ts">
import { computed, ref } from 'vue'
import { Plus, Trash2, ChevronDown, ChevronUp, AlertCircle, Shuffle } from 'lucide-vue-next'
import type { AdminProductFormData, AdminOption, AdminProductVariantFormRow } from '../types/product'

interface Props {
  form: AdminProductFormData
  availableOptions: AdminOption[]
  errors: Record<string, string>
}

const props = defineProps<Props>()

const expandedVariants = ref<Set<number>>(new Set())

// Computed: options selected for this product
const selectedOptions = computed((): AdminOption[] => {
  return props.form.options
    .map(fo => props.availableOptions.find(opt => opt.id === fo.option_id))
    .filter((opt): opt is AdminOption => opt !== undefined)
})

// Toggle an option for this product
function toggleOption(optionId: number): void {
  const idx = props.form.options.findIndex(o => o.option_id === optionId)
  if (idx === -1) {
    props.form.options.push({ option_id: optionId, required: false })
  } else {
    props.form.options.splice(idx, 1)
    // Remove variants that use values from this option
    props.form.variants = props.form.variants.map(v => ({
      ...v,
      option_values: v.option_values.filter(ov => ov.option_id !== optionId),
    }))
  }
}

function isOptionSelected(optionId: number): boolean {
  return props.form.options.some(o => o.option_id === optionId)
}

// Auto-generate all variant combinations (cartesian product)
function generateVariants(): void {
  if (selectedOptions.value.length === 0) return

  const combinations = cartesianProduct(
    selectedOptions.value.map(opt =>
      opt.values.map(val => ({ option_id: opt.id, option_value_id: val.id, value: val.value, option_name: opt.name }))
    )
  )

  const baseSku = props.form.base_sku || 'VAR'

  props.form.variants = combinations.map((combo, idx) => {
    const skuSuffix = combo.map(c => c.value.replace(/\s+/g, '').substring(0, 4).toUpperCase()).join('-')
    return {
      sku:           `${baseSku}-${skuSuffix}`,
      barcode:       '',
      weight:        '',
      length:        '',
      width:         '',
      height:        '',
      status:        1,
      price:         '',
      stock:         0,
      option_values: combo.map(c => ({ option_id: c.option_id, option_value_id: c.option_value_id })),
    }
  })
}

function cartesianProduct<T>(arrays: T[][]): T[][] {
  return arrays.reduce<T[][]>(
    (acc, arr) => acc.flatMap(combo => arr.map(item => [...combo, item])),
    [[]]
  )
}

function addVariant(): void {
  props.form.variants.push({
    sku:           '',
    barcode:       '',
    weight:        '',
    length:        '',
    width:         '',
    height:        '',
    status:        1,
    price:         '',
    stock:         0,
    option_values: selectedOptions.value.map(opt => ({ option_id: opt.id, option_value_id: 0 })),
  })
  const newIdx = props.form.variants.length - 1
  expandedVariants.value.add(newIdx)
}

function removeVariant(idx: number): void {
  props.form.variants.splice(idx, 1)
  expandedVariants.value.delete(idx)
}

function toggleExpand(idx: number): void {
  if (expandedVariants.value.has(idx)) {
    expandedVariants.value.delete(idx)
  } else {
    expandedVariants.value.add(idx)
  }
}

function getVariantLabel(variant: AdminProductVariantFormRow): string {
  if (variant.option_values.length === 0) return variant.sku || 'ورینت جدید'
  return variant.option_values
    .map(ov => {
      const opt = selectedOptions.value.find(o => o.id === ov.option_id)
      const val = opt?.values.find(v => v.id === ov.option_value_id)
      return val?.value ?? ''
    })
    .filter(Boolean)
    .join(' / ') || variant.sku || 'ورینت جدید'
}

function getOptionValueForVariant(variant: AdminProductVariantFormRow, optionId: number): number {
  return variant.option_values.find(ov => ov.option_id === optionId)?.option_value_id ?? 0
}

function setOptionValue(variant: AdminProductVariantFormRow, optionId: number, valueId: number): void {
  const idx = variant.option_values.findIndex(ov => ov.option_id === optionId)
  if (idx === -1) {
    variant.option_values.push({ option_id: optionId, option_value_id: valueId })
  } else {
    variant.option_values[idx].option_value_id = valueId
  }
}
</script>

<template>
  <div class="space-y-6">

    <!-- Step 1: Select Options -->
    <div>
      <div class="flex items-center justify-between mb-3">
        <div>
          <h3 class="text-sm font-semibold text-secondary-800">گام ۱ — انتخاب ویژگی‌های ورینت</h3>
          <p class="text-xs text-secondary-500 mt-0.5">ویژگی‌هایی که این محصول بر اساس آن‌ها ورینت دارد را انتخاب کنید</p>
        </div>
      </div>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="opt in availableOptions"
          :key="opt.id"
          type="button"
          :class="[
            'flex items-center gap-2 px-3 py-2 rounded-lg border text-sm font-medium transition',
            isOptionSelected(opt.id)
              ? 'bg-primary-50 border-primary-400 text-primary-700'
              : 'bg-white border-secondary-200 text-secondary-600 hover:border-secondary-400'
          ]"
          @click="toggleOption(opt.id)"
        >
          <span
            :class="[
              'w-4 h-4 rounded border-2 flex items-center justify-center flex-shrink-0',
              isOptionSelected(opt.id) ? 'border-primary-500 bg-primary-500' : 'border-secondary-300'
            ]"
          >
            <svg v-if="isOptionSelected(opt.id)" class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 12 12">
              <path d="M2 6l3 3 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </span>
          {{ opt.name }}
          <span class="text-xs text-secondary-400">({{ opt.values.length }} مقدار)</span>
        </button>
        <p v-if="availableOptions.length === 0" class="text-sm text-secondary-400 py-2">
          هیچ ویژگی‌ای یافت نشد
        </p>
      </div>
    </div>

    <!-- Divider -->
    <div class="border-t border-secondary-100" />

    <!-- Step 2: Manage Variants -->
    <div>
      <div class="flex items-center justify-between mb-3">
        <div>
          <h3 class="text-sm font-semibold text-secondary-800">گام ۲ — مدیریت ورینت‌ها</h3>
          <p class="text-xs text-secondary-500 mt-0.5">{{ form.variants.length }} ورینت تعریف شده</p>
        </div>
        <div class="flex items-center gap-2">
          <button
            v-if="selectedOptions.length > 0"
            type="button"
            class="flex items-center gap-1.5 px-3 py-1.5 text-sm bg-amber-50 text-amber-700 border border-amber-200 rounded-lg hover:bg-amber-100 transition"
            @click="generateVariants"
          >
            <Shuffle class="w-3.5 h-3.5" />
            تولید خودکار ترکیب‌ها
          </button>
          <button
            type="button"
            class="flex items-center gap-1.5 px-3 py-1.5 text-sm bg-primary-50 text-primary-700 border border-primary-200 rounded-lg hover:bg-primary-100 transition"
            @click="addVariant"
          >
            <Plus class="w-3.5 h-3.5" />
            افزودن ورینت
          </button>
        </div>
      </div>

      <!-- No variants empty state -->
      <div
        v-if="form.variants.length === 0"
        class="border-2 border-dashed border-secondary-200 rounded-xl p-8 text-center"
      >
        <div class="w-12 h-12 bg-secondary-100 rounded-full flex items-center justify-center mx-auto mb-3">
          <Plus class="w-6 h-6 text-secondary-400" />
        </div>
        <p class="text-sm font-medium text-secondary-600 mb-1">هیچ ورینتی تعریف نشده</p>
        <p v-if="selectedOptions.length > 0" class="text-xs text-secondary-400 mb-3">
          از دکمه «تولید خودکار» برای ساخت تمام ترکیب‌ها استفاده کنید
        </p>
        <p v-else class="text-xs text-secondary-400 mb-3">
          ابتدا ویژگی‌های مورد نظر را انتخاب کنید
        </p>
      </div>

      <!-- Variants list -->
      <div v-else class="space-y-2">
        <div
          v-for="(variant, idx) in form.variants"
          :key="idx"
          class="border border-secondary-200 rounded-xl overflow-hidden"
          :class="{ 'border-red-300': errors[`variants.${idx}.sku`] }"
        >
          <!-- Variant header (collapsed) -->
          <div
            class="flex items-center gap-3 px-4 py-3 bg-secondary-50 cursor-pointer hover:bg-secondary-100 transition"
            @click="toggleExpand(idx)"
          >
            <!-- Status badge -->
            <span
              :class="variant.status === 1 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'"
              class="text-xs px-2 py-0.5 rounded-full font-medium flex-shrink-0"
            >
              {{ variant.status === 1 ? 'فعال' : 'غیرفعال' }}
            </span>

            <!-- Variant label -->
            <span class="flex-1 text-sm font-medium text-secondary-800 truncate">
              {{ getVariantLabel(variant) }}
            </span>

            <!-- Price/stock preview -->
            <span v-if="variant.price" class="text-xs text-secondary-500 flex-shrink-0">
              {{ Number(variant.price).toLocaleString('fa-IR') }} تومان
            </span>
            <span class="text-xs text-secondary-400 flex-shrink-0 ml-1">
              موجودی: {{ variant.stock }}
            </span>

            <!-- SKU -->
            <span class="text-xs font-mono text-secondary-400 hidden sm:block flex-shrink-0" dir="ltr">
              {{ variant.sku }}
            </span>

            <ChevronDown v-if="!expandedVariants.has(idx)" class="w-4 h-4 text-secondary-400 flex-shrink-0" />
            <ChevronUp   v-else class="w-4 h-4 text-secondary-400 flex-shrink-0" />
          </div>

          <!-- Variant detail (expanded) -->
          <div v-if="expandedVariants.has(idx)" class="p-4 space-y-4 bg-white border-t border-secondary-100">

            <!-- Option values row -->
            <div v-if="selectedOptions.length > 0" class="grid grid-cols-2 md:grid-cols-3 gap-3">
              <div v-for="opt in selectedOptions" :key="opt.id">
                <label class="block text-xs font-medium text-secondary-600 mb-1">{{ opt.name }}</label>
                <select
                  :value="getOptionValueForVariant(variant, opt.id)"
                  class="w-full h-9 px-2 rounded-lg border border-secondary-200 focus:border-primary-400 text-sm text-secondary-700"
                  @change="setOptionValue(variant, opt.id, Number(($event.target as HTMLSelectElement).value))"
                >
                  <option :value="0">انتخاب کنید</option>
                  <option v-for="val in opt.values" :key="val.id" :value="val.id">
                    {{ val.value }}
                  </option>
                </select>
              </div>
            </div>

            <!-- SKU + Barcode -->
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-secondary-600 mb-1">
                  SKU <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="variant.sku"
                  type="text"
                  dir="ltr"
                  placeholder="PRODUCT-COLOR-SIZE"
                  class="w-full h-9 px-3 rounded-lg border border-secondary-200 focus:border-primary-400 text-sm font-mono"
                  :class="{ 'border-red-400': errors[`variants.${idx}.sku`] }"
                />
                <p v-if="errors[`variants.${idx}.sku`]" class="mt-0.5 text-xs text-red-500">{{ errors[`variants.${idx}.sku`] }}</p>
              </div>
              <div>
                <label class="block text-xs font-medium text-secondary-600 mb-1">بارکد</label>
                <input
                  v-model="variant.barcode"
                  type="text"
                  dir="ltr"
                  placeholder="1234567890"
                  class="w-full h-9 px-3 rounded-lg border border-secondary-200 focus:border-primary-400 text-sm font-mono"
                />
              </div>
            </div>

            <!-- Price + Stock -->
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-secondary-600 mb-1">قیمت (تومان)</label>
                <input
                  v-model="variant.price"
                  type="number"
                  min="0"
                  dir="ltr"
                  placeholder="0"
                  class="w-full h-9 px-3 rounded-lg border border-secondary-200 focus:border-primary-400 text-sm"
                />
              </div>
              <div>
                <label class="block text-xs font-medium text-secondary-600 mb-1">موجودی انبار</label>
                <input
                  v-model.number="variant.stock"
                  type="number"
                  min="0"
                  dir="ltr"
                  placeholder="0"
                  class="w-full h-9 px-3 rounded-lg border border-secondary-200 focus:border-primary-400 text-sm"
                />
              </div>
            </div>

            <!-- Status + Actions -->
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <button
                  type="button"
                  :class="['relative w-10 h-5 rounded-full transition flex-shrink-0', variant.status === 1 ? 'bg-primary-500' : 'bg-secondary-300']"
                  @click="variant.status = variant.status === 1 ? 0 : 1"
                >
                  <span :class="['absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transition-all', variant.status === 1 ? 'left-5' : 'left-0.5']" />
                </button>
                <span class="text-xs text-secondary-600">{{ variant.status === 1 ? 'فعال' : 'غیرفعال' }}</span>
              </div>
              <button
                type="button"
                class="flex items-center gap-1 text-xs text-red-500 hover:text-red-700 transition"
                @click="removeVariant(idx)"
              >
                <Trash2 class="w-3.5 h-3.5" />
                حذف ورینت
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Global variants error -->
    <div v-if="errors.variants" class="flex items-center gap-2 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
      <AlertCircle class="w-4 h-4 flex-shrink-0" />
      {{ errors.variants }}
    </div>
  </div>
</template>
