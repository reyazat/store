import { ref, computed, watch } from 'vue'
import type { Ref } from 'vue'
import type { Product, ProductVariant, SelectedVariantOptions } from '../types'

export function useProductVariants(productRef: Ref<Product | null>) {
  const selectedOptions = ref<SelectedVariantOptions>({})
  const selectedVariant = ref<ProductVariant | null>(null)

  const findVariantByOptions = (options: SelectedVariantOptions): ProductVariant | null => {
    const variants = productRef.value?.variants
    if (!variants) return null

    const optionIds = Object.keys(options).map(Number)
    if (optionIds.length === 0) return null

    return variants.find(variant =>
      optionIds.every(optionId => {
        const selectedValueId = options[optionId]
        return variant.option_values.some(
          ov => ov.option_id === optionId && ov.id === selectedValueId
        )
      })
    ) ?? null
  }

  const availableOptions = computed(() => {
    const p = productRef.value
    if (!p?.options || !p.variants) return []

    return p.options.map(option => {
      const availableValues = option.values.filter(value =>
        p.variants!.some(variant => {
          const matchesOtherOptions = Object.entries(selectedOptions.value).every(([optId, valueId]) => {
            if (Number(optId) === option.id) return true
            return variant.option_values.some(ov => ov.option_id === Number(optId) && ov.id === valueId)
          })
          const hasThisValue = variant.option_values.some(ov => ov.option_id === option.id && ov.id === value.id)
          return matchesOtherOptions && hasThisValue
        })
      )
      return { ...option, values: availableValues }
    })
  })

  const selectOption = (optionId: number, valueId: number) => {
    selectedOptions.value = { ...selectedOptions.value, [optionId]: valueId }
  }

  const currentPrice = computed(() => {
    if (selectedVariant.value?.listing_variant) {
      return Number(selectedVariant.value.listing_variant.price_override)
        || productRef.value?.listings?.[0]?.base_price
        || 0
    }
    return productRef.value?.listings?.[0]?.base_price || 0
  })

  const currentStock = computed(() => {
    if (selectedVariant.value?.listing_variant) {
      return selectedVariant.value.listing_variant.stock_quantity
    }
    return productRef.value?.listings?.[0]?.stock_quantity ?? 0
  })

  const isInStock = computed(() => currentStock.value > 0)

  const isVariantSelected = computed(() => {
    const options = productRef.value?.options
    if (!options || options.length === 0) return true
    return options.every(option => selectedOptions.value[option.id] !== undefined)
  })

  const currentListingVariantId = computed(() =>
    selectedVariant.value?.listing_variant?.id ?? null
  )

  // Keep selectedVariant in sync whenever selectedOptions changes
  watch(selectedOptions, newOptions => {
    selectedVariant.value = findVariantByOptions(newOptions)
  }, { deep: true })

  // Re-initialize when product loads/changes
  watch(productRef, p => {
    if (!p?.variants?.length) return

    const first = p.variants.find(v => v.listing_variant && v.listing_variant.stock_quantity > 0)
      ?? p.variants[0]

    if (first && p.options?.length) {
      const defaults: SelectedVariantOptions = {}
      first.option_values.forEach(ov => { defaults[ov.option_id] = ov.id })
      selectedOptions.value = defaults
    }
  }, { immediate: true })

  return {
    selectedOptions,
    selectedVariant,
    availableOptions,
    selectOption,
    currentPrice,
    currentStock,
    isInStock,
    isVariantSelected,
    currentListingVariantId,
  }
}
