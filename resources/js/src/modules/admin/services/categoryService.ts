import api from '@shared/utils/api'

export interface AdminCategory {
  id: number
  parent_id: number | null
  name: string
  slug: string
  image: string | null
  icon: string | null
  products_count: number
  status: number
  created_at: string
}

export interface AdminCategoryMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface AdminCategoryListResponse {
  data: AdminCategory[]
  meta: AdminCategoryMeta
}

export interface CategoryFilters {
  search?: string
  status?: number | null
  parent_id?: number | null
  per_page?: number
  page?: number
}

export const categoryService = {
  async getAll(filters: CategoryFilters = {}): Promise<AdminCategoryListResponse> {
    const params: Record<string, string | number> = {}

    if (filters.search)                        params.search   = filters.search
    if (filters.status    !== null && filters.status    !== undefined) params.status    = filters.status
    if (filters.parent_id !== null && filters.parent_id !== undefined) params.parent_id = filters.parent_id
    if (filters.per_page)                      params.per_page = filters.per_page
    if (filters.page)                          params.page     = filters.page

    const response = await api.get('/categories', { params })

    return {
      data: response.data?.data || [],
      meta: response.data?.meta || { current_page: 1, last_page: 1, per_page: 20, total: 0 },
    }
  },

  async destroy(id: number): Promise<void> {
    await api.delete(`/categories/${id}`)
  },
}
