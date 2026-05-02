import api from '@shared/utils/api'
import type { PaginationMeta } from '../composables/useAdminList'

export interface AdminProduct {
  id: number
  name: string
  slug: string
  main_image: string | null
  price: number
  stock: number | null
  status: number
  manufacturer?: string
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
}
