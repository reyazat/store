export interface AdminProductCategory {
  id: number
  name: string
  slug: string
  parent_id: number | null
  status: number
}

export interface AdminProductManufacturer {
  id: number
  name: string
  slug: string
}

export interface AdminProductImage {
  id?: number
  file_name: string
  sorted: number
  _preview?: string
  _file?: File
  _toDelete?: boolean
}

export interface AdminProductVariant {
  id?: number
  sku: string
  barcode: string
  weight: number | null
  length: number | null
  width: number | null
  height: number | null
  status: number
  option_values: Array<{
    option_id: number
    option_value_id: number
  }>
}

export interface AdminProductDetail {
  id: number
  name: string
  slug: string
  base_sku: string
  short_description: string | null
  description: string | null
  main_image: string | null
  manufacturer_id: number | null
  tax_class_id: number
  weight: number
  length: number
  width: number
  height: number
  date_available: string | null
  meta_title: string | null
  meta_description: string | null
  sorted: number
  status: number
  discount_percent: number | null
  discount_start_at: string | null
  discount_end_at: string | null
  created_at: string
  updated_at: string
  categories: Array<{ id: number; name: string; slug: string }>
  images: AdminProductImage[]
  related_products?: Array<{ id: number; name: string; slug: string; main_image: string | null }>
}

export interface AdminProductFormData {
  name: string
  slug: string
  base_sku: string
  short_description: string
  description: string
  main_image: string
  main_image_file: File | null
  manufacturer_id: number | null
  tax_class_id: number | null
  weight: number | string
  length: number | string
  width: number | string
  height: number | string
  date_available: string
  meta_title: string
  meta_description: string
  sorted: number | string
  status: number
  category_ids: number[]
  discount_percent: number | string
  discount_start_at: string
  discount_end_at: string
  images: AdminProductImage[]
  related_product_ids: number[]
}
