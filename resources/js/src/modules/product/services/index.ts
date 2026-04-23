import api from '@shared/utils/api'

export type ProductSort =
  | 'newest'
  | 'oldest'
  | 'price_asc'
  | 'price_desc'
  | 'popular'
  | 'name_asc'
  | 'name_desc'
  | 'discount'

export interface ProductFilters {
  page?: number
  per_page?: number
  limit?: number
  search?: string
  category_id?: number
  category_slug?: string
  manufacturer_id?: number
  manufacturer_ids?: number[]
  manufacturer_slug?: string
  has_discount?: boolean
  featured?: boolean
  in_stock?: boolean
  min_price?: number
  max_price?: number
  sort?: ProductSort
}

export const productService = {
  async getProducts(filters: ProductFilters = {}) {
    const response = await api.get('/products', { params: filters })
    return response.data
  },

  async getProduct(slug: string) {
    const response = await api.get(`/products/${slug}`)
    return response.data
  },
}
