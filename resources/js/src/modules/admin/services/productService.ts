import api from '@shared/utils/api'
import type { PaginationMeta } from '../composables/useAdminList'
import type { AdminProductDetail, AdminProductFormData, AdminProductCategory, AdminProductManufacturer, AdminOption } from '../types/product'

export interface AdminProduct {
  id: number
  name: string
  slug: string
  base_sku: string
  main_image: string | null
  price: number
  stock: number | null
  status: number
  manufacturer?: string
  discount_percent: number | null
  created_at: string
}

export interface ProductFilters {
  search?: string
  status?: number | null
  per_page?: number
  page?: number
}

export const productService = {
  async getAll(filters: ProductFilters = {}): Promise<{ data: AdminProduct[]; meta: PaginationMeta }> {
    const params: Record<string, string | number> = {}

    if (filters.search)                                           params.search   = filters.search
    if (filters.status !== null && filters.status !== undefined)  params.status   = filters.status
    if (filters.per_page)                                         params.per_page = filters.per_page
    if (filters.page)                                             params.page     = filters.page

    const response = await api.get('/products', { params })

    return {
      data: response.data?.data || [],
      meta: response.data?.meta || { current_page: 1, last_page: 1, per_page: 20, total: 0 },
    }
  },

  async getBySlug(slug: string): Promise<AdminProductDetail> {
    const response = await api.get(`/products/${slug}`)
    return response.data.data
  },

  async getAdminProductDetail(slug: string): Promise<AdminProductDetail> {
    const response = await api.get(`/admin/products/${slug}`)
    return response.data.data
  },

  async getAvailableOptions(): Promise<AdminOption[]> {
    const response = await api.get('/admin/products/available-options')
    return response.data?.data || []
  },

  async create(data: AdminProductFormData): Promise<AdminProductDetail> {
    const payload = buildPayload(data)
    const response = await api.post('/products', payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data.data
  },

  async update(slug: string, data: AdminProductFormData): Promise<AdminProductDetail> {
    const payload = buildPayload(data)
    payload.append('_method', 'PUT')
    const response = await api.post(`/products/${slug}`, payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data.data
  },

  async destroy(slug: string): Promise<void> {
    await api.delete(`/products/${slug}`)
  },

  async searchRelated(query: string, excludeSlug?: string | null): Promise<Array<{ id: number; name: string; slug: string; main_image: string | null }>> {
    if (!query || query.length < 2) return []
    const params: Record<string, string> = { q: query }
    if (excludeSlug) params.exclude = excludeSlug
    const response = await api.get('/products/autocomplete', { params })
    return (response.data?.data || []).filter(
      (p: { slug: string }) => p.slug !== excludeSlug
    )
  },

  async getCategories(params: { search?: string; per_page?: number } = {}): Promise<{ data: AdminProductCategory[] }> {
    const response = await api.get('/categories', { params: { per_page: 100, ...params } })
    return { data: response.data?.data || [] }
  },

  async createCategory(payload: { name: string; slug: string; parent_id: number | null }): Promise<AdminProductCategory> {
    const response = await api.post('/admin/categories', payload)
    return response.data.data
  },

  async getManufacturers(params: { search?: string; per_page?: number } = {}): Promise<{ data: AdminProductManufacturer[] }> {
    const response = await api.get('/manufacturers', { params: { per_page: 100, ...params } })
    return { data: response.data?.data || [] }
  },

  async createManufacturer(payload: { name: string; slug: string }): Promise<AdminProductManufacturer> {
    const response = await api.post('/manufacturers', payload)
    return response.data.data
  },

  async getTaxClasses(): Promise<Array<{ id: number; name: string; rate: number }>> {
    const response = await api.get('/tax-classes', { params: { per_page: 100 } })
    return response.data?.data || []
  },
}

function buildPayload(data: AdminProductFormData): FormData {
  const fd = new FormData()

  fd.append('name',              data.name)
  fd.append('slug',              data.slug)
  fd.append('base_sku',         data.base_sku)
  fd.append('short_description', data.short_description ?? '')
  fd.append('description',      data.description ?? '')
  fd.append('weight',           String(data.weight || 0))
  fd.append('length',           String(data.length || 0))
  fd.append('width',            String(data.width || 0))
  fd.append('height',           String(data.height || 0))
  fd.append('sorted',           String(data.sorted || 0))
  fd.append('status',           String(data.status ?? 1))

  if (data.tax_class_id)     fd.append('tax_class_id',    String(data.tax_class_id))
  if (data.manufacturer_id)  fd.append('manufacturer_id', String(data.manufacturer_id))
  if (data.date_available)   fd.append('date_available',  data.date_available)
  if (data.meta_title)       fd.append('meta_title',      data.meta_title)
  if (data.meta_description) fd.append('meta_description', data.meta_description)

  if (data.main_image_file instanceof File) {
    fd.append('main_image_file', data.main_image_file)
  } else if (data.main_image) {
    fd.append('main_image', data.main_image)
  }

  data.category_ids.forEach(id => fd.append('category_ids[]', String(id)))

  if (data.discount_percent !== '' && data.discount_percent !== null) {
    fd.append('discount_percent', String(data.discount_percent))
    if (data.discount_start_at) fd.append('discount_start_at', data.discount_start_at)
    if (data.discount_end_at)   fd.append('discount_end_at',   data.discount_end_at)
  }

  data.images
    .filter(img => !img._toDelete)
    .forEach((img, i) => {
      if (img._file instanceof File) {
        fd.append(`images[${i}][file]`,      img._file)
      } else {
        fd.append(`images[${i}][file_name]`, img.file_name)
      }
      fd.append(`images[${i}][sorted]`, String(img.sorted ?? i))
    })

  data.related_product_ids.forEach(id => fd.append('related_product_ids[]', String(id)))

  data.options.forEach((opt, i) => {
    fd.append(`options[${i}][option_id]`, String(opt.option_id))
    fd.append(`options[${i}][required]`,  String(opt.required ? 1 : 0))
  })

  data.variants.forEach((v, i) => {
    if (v.id) fd.append(`variants[${i}][id]`, String(v.id))
    fd.append(`variants[${i}][sku]`,    v.sku)
    fd.append(`variants[${i}][status]`, String(v.status ?? 1))
    if (v.barcode)  fd.append(`variants[${i}][barcode]`, v.barcode)
    if (v.weight)   fd.append(`variants[${i}][weight]`,  String(v.weight))
    if (v.length)   fd.append(`variants[${i}][length]`,  String(v.length))
    if (v.width)    fd.append(`variants[${i}][width]`,   String(v.width))
    if (v.height)   fd.append(`variants[${i}][height]`,  String(v.height))
    if (v.price !== '' && v.price !== null && v.price !== undefined) {
      fd.append(`variants[${i}][price]`, String(v.price))
    }
    fd.append(`variants[${i}][stock]`, String(v.stock ?? 0))
    v.option_values.forEach((ov, j) => {
      fd.append(`variants[${i}][option_values][${j}][option_id]`,       String(ov.option_id))
      fd.append(`variants[${i}][option_values][${j}][option_value_id]`, String(ov.option_value_id))
    })
  })

  return fd
}
