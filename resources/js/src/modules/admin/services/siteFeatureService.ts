import api from '@shared/utils/api'
import type { SiteFeature, SiteFeatureFormData } from '../types/siteFeature'

export const siteFeatureService = {
  async getAll(): Promise<SiteFeature[]> {
    const response = await api.get('/admin/site-features')
    return response.data?.data || []
  },

  async getById(id: number): Promise<SiteFeature> {
    const response = await api.get(`/admin/site-features/${id}`)
    return response.data?.data
  },

  async create(data: SiteFeatureFormData): Promise<SiteFeature> {
    const response = await api.post('/admin/site-features', data)
    return response.data?.data
  },

  async update(id: number, data: SiteFeatureFormData): Promise<SiteFeature> {
    const response = await api.put(`/admin/site-features/${id}`, data)
    return response.data?.data
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/admin/site-features/${id}`)
  },
}
