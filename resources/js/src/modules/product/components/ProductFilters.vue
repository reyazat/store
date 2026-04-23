<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { X } from 'lucide-vue-next'
import type { Brand } from '@modules/category/services/brandsService'
import type { Category } from '@shared/types'

export interface FiltersState {
  search: string
  category_slug: string | null
  manufacturer_ids: number[]
  min_price: number | null
  max_price: number | null
  has_discount: boolean
  in_stock: boolean
}

const props = defineProps<{
  modelValue: FiltersState
  brands: Brand[]
  categories: Category[]
  loading?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: FiltersState]
  'apply': []
  'reset': []
}>()

const local = ref<FiltersState>({ ...props.modelValue })

watch(
  () => props.modelValue,
  (value) => {
    local.value = { ...value }
  },
  { deep: true }
)

const toggleBrand = (id: number) => {
  const next = new Set(local.value.manufacturer_ids)
  if (next.has(id)) {
    next.delete(id)
  } else {
    next.add(id)
  }
  local.value.manufacturer_ids = Array.from(next)
  commit()
}

const commit = () => {
  emit('update:modelValue', { ...local.value })
}

const applyPriceRange = () => {
  commit()
  emit('apply')
}

const handleCategoryChange = (slug: string) => {
  local.value.category_slug = slug || null
  commit()
}

const handleToggle = (key: 'has_discount' | 'in_stock') => {
  local.value[key] = !local.value[key]
  commit()
}

const reset = () => {
  local.value = {
    search: '',
    category_slug: null,
    manufacturer_ids: [],
    min_price: null,
    max_price: null,
    has_discount: false,
    in_stock: false,
  }
  commit()
  emit('reset')
}

const hasActive = computed(() =>
  local.value.category_slug !== null ||
  local.value.manufacturer_ids.length > 0 ||
  local.value.min_price !== null ||
  local.value.max_price !== null ||
  local.value.has_discount ||
  local.value.in_stock ||
  local.value.search.length > 0
)
</script>

<template>
  <aside class="bg-white rounded-xl border border-secondary-100 p-5 space-y-6 sticky top-4">
    <header class="flex items-center justify-between">
      <h2 class="font-bold text-secondary-800">فیلترها</h2>
      <button
        v-if="hasActive"
        type="button"
        class="text-sm text-primary-600 hover:text-primary-700 flex items-center gap-1"
        @click="reset"
      >
        <X class="w-4 h-4" />
        حذف همه
      </button>
    </header>

    <!-- Category -->
    <section>
      <h3 class="text-sm font-semibold text-secondary-700 mb-2">دسته‌بندی</h3>
      <label class="sr-only" for="filter-category">دسته‌بندی</label>
      <select
        id="filter-category"
        :value="local.category_slug ?? ''"
        @change="handleCategoryChange(($event.target as HTMLSelectElement).value)"
        class="w-full rounded-lg border border-secondary-200 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
      >
        <option value="">همه دسته‌بندی‌ها</option>
        <option v-for="category in categories" :key="category.id" :value="category.slug">
          {{ category.name }}
        </option>
      </select>
    </section>

    <!-- Price Range -->
    <section>
      <h3 class="text-sm font-semibold text-secondary-700 mb-2">بازه قیمت (تومان)</h3>
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="sr-only" for="filter-min-price">حداقل</label>
          <input
            id="filter-min-price"
            v-model.number="local.min_price"
            type="number"
            min="0"
            placeholder="از"
            class="w-full rounded-lg border border-secondary-200 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
          />
        </div>
        <div>
          <label class="sr-only" for="filter-max-price">حداکثر</label>
          <input
            id="filter-max-price"
            v-model.number="local.max_price"
            type="number"
            min="0"
            placeholder="تا"
            class="w-full rounded-lg border border-secondary-200 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
          />
        </div>
      </div>
      <button
        type="button"
        class="mt-2 w-full rounded-lg bg-secondary-100 hover:bg-secondary-200 text-sm py-2 font-medium text-secondary-700 transition"
        @click="applyPriceRange"
      >
        اعمال قیمت
      </button>
    </section>

    <!-- Brands -->
    <section v-if="brands.length > 0">
      <h3 class="text-sm font-semibold text-secondary-700 mb-2">برند</h3>
      <ul class="max-h-60 overflow-y-auto space-y-1 pr-1">
        <li v-for="brand in brands" :key="brand.id">
          <label class="flex items-center gap-2 text-sm cursor-pointer hover:text-primary-600">
            <input
              type="checkbox"
              :checked="local.manufacturer_ids.includes(brand.id)"
              class="rounded text-primary-500 focus:ring-primary-400"
              @change="toggleBrand(brand.id)"
            />
            <span class="flex-1">{{ brand.name }}</span>
          </label>
        </li>
      </ul>
    </section>

    <!-- Quick toggles -->
    <section class="space-y-2">
      <label class="flex items-center gap-2 text-sm cursor-pointer">
        <input
          type="checkbox"
          :checked="local.has_discount"
          class="rounded text-primary-500 focus:ring-primary-400"
          @change="handleToggle('has_discount')"
        />
        فقط محصولات دارای تخفیف
      </label>
      <label class="flex items-center gap-2 text-sm cursor-pointer">
        <input
          type="checkbox"
          :checked="local.in_stock"
          class="rounded text-primary-500 focus:ring-primary-400"
          @change="handleToggle('in_stock')"
        />
        فقط کالاهای موجود
      </label>
    </section>
  </aside>
</template>
