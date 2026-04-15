import api from '@shared/utils/api'

interface ProductFilters {
  page?: number
  per_page?: number
  search?: string
  category_id?: number
  manufacturer_id?: number
  has_discount?: boolean
  sort_by?: string
  sort_order?: string
  sort_by_discount?: string
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
