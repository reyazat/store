import api from '@shared/utils/api'
import type { PaginationMeta } from '../composables/useAdminList'

export interface AdminOption {
  id: number
  name: string
  slug: string
  type: 'radio' | 'checkbox' | 'select'
  sorted: number
  created_at: string
  updated_at: string
}

export interface AdminOptionDetail extends AdminOption {
  values: Array<{
    id: number
    option_id: number
    value: string
    sorted: number
    created_at: string
    updated_at: string
  }>
}

export interface AdminOptionFormData {
  name: string
  slug: string
  type: 'radio' | 'checkbox' | 'select'
  sorted: number
}

export interface AdminOptionValueFormData {
  id?: number
  value: string
  sorted: number
}

export const optionService = {
  async getAll(params: {
    search?: string
    per_page?: number
    page?: number
  } = {}): Promise<{ data: AdminOption[]; meta: PaginationMeta }> {
    const response = await api.get('/options', { params })
    return {
      data: response.data?.data || [],
      meta: response.data?.meta || { current_page: 1, last_page: 1, per_page: 20, total: 0 },
    }
  },

  async getById(id: number): Promise<AdminOptionDetail> {
    const response = await api.get(`/options/${id}`)
    return response.data.data
  },

  async create(data: AdminOptionFormData): Promise<AdminOptionDetail> {
    const response = await api.post('/options', data)
    return response.data.data
  },

  async update(id: number, data: AdminOptionFormData): Promise<AdminOptionDetail> {
    const response = await api.put(`/options/${id}`, data)
    return response.data.data
  },

  async destroy(id: number): Promise<void> {
    await api.delete(`/options/${id}`)
  },

  // Option Values management
  async getValues(optionId: number): Promise<AdminOptionDetail['values']> {
    const response = await api.get('/option-values', {
      params: { option_id: optionId, per_page: 100 },
    })
    return response.data?.data || []
  },

  async createValue(data: { option_id: number; value: string; sorted: number }): Promise<AdminOptionDetail['values'][0]> {
    const response = await api.post('/option-values', data)
    return response.data.data
  },

  async updateValue(id: number, data: { value: string; sorted: number }): Promise<AdminOptionDetail['values'][0]> {
    const response = await api.put(`/option-values/${id}`, data)
    return response.data.data
  },

  async destroyValue(id: number): Promise<void> {
    await api.delete(`/option-values/${id}`)
  },
}
