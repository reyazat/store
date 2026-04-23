import api from '@shared/utils/api'
import type { ApiResponse } from '@shared/types'

export interface Brand {
  id: number
  name: string
  slug: string
  image?: string | null
  status?: number
}

export const brandsService = {
  async getBrands(params: { per_page?: number; search?: string } = {}): Promise<ApiResponse<Brand[]>> {
    const response = await api.get<ApiResponse<Brand[]>>('/manufacturers', {
      params: { status: 1, per_page: 60, ...params },
    })
    return response.data
  },

  async getBrand(slug: string): Promise<ApiResponse<Brand>> {
    const response = await api.get<ApiResponse<Brand>>(`/manufacturers/${slug}`)
    return response.data
  },
}
