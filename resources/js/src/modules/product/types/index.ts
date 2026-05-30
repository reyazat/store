export interface OptionValue {
  id: number
  value: string
  sorted: number
}

export interface Option {
  id: number
  name: string
  slug: string
  type: string
  required: boolean
  values: OptionValue[]
}

export interface VariantOptionValue {
  id: number
  option_id: number
  value: string
  option_name: string | null
}

export interface ListingVariant {
  id: number
  price_override: number | null
  stock_quantity: number
  listing_id: number
}

export interface ProductVariant {
  id: number
  sku: string
  barcode: string | null
  weight: number | null
  length: number | null
  width: number | null
  height: number | null
  status: number
  option_values: VariantOptionValue[]
  listing_variant: ListingVariant | null
}

export interface ProductListing {
  id: number
  vendor_id: number
  base_price: number
  stock_quantity: number
  min_order_qty: number
  status: string
}

export interface ProductImage {
  id: number
  file_name: string
  sorted: number
}

export interface ProductCategory {
  id: number
  name: string
  slug: string
}

export interface ProductManufacturer {
  id: number
  name: string
  slug: string
}

export interface Product {
  id: number
  name: string
  slug: string
  base_sku: string
  short_description: string | null
  description: string | null
  main_image: string
  manufacturer_id: number | null
  manufacturer?: ProductManufacturer
  tax_class_id: number
  weight: number
  length: number
  width: number
  height: number
  date_available: string | null
  meta_title: string | null
  meta_description: string | null
  viewed: number
  sorted: number
  status: number
  discount_percent: number | null
  discount_start_at: string | null
  discount_end_at: string | null
  created_at: string
  updated_at: string
  categories?: ProductCategory[]
  images?: ProductImage[]
  options?: Option[]
  variants?: ProductVariant[]
  listings?: ProductListing[]
}

export interface SelectedVariantOptions {
  [optionId: number]: number
}
