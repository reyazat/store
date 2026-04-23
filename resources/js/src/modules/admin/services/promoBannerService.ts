import api from '@shared/utils/api'
import type { PromoBanner, PromoBannerFormData } from '../types/promoBanner'

export const promoBannerService = {
  async getAll(): Promise<PromoBanner[]> {
    const response = await api.get('/admin/promo-banners')
    return response.data?.data || []
  },

  async getById(id: number): Promise<PromoBanner> {
    const response = await api.get(`/admin/promo-banners/${id}`)
    return response.data?.data
  },

  async create(data: PromoBannerFormData): Promise<PromoBanner> {
    const response = await api.post('/admin/promo-banners', data)
    return response.data?.data
  },

  async update(id: number, data: PromoBannerFormData): Promise<PromoBanner> {
    const response = await api.post(`/admin/promo-banners/${id}`, data)
    return response.data?.data
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/admin/promo-banners/${id}`)
  },
}
