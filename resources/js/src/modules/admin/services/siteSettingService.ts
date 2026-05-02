import api from '@shared/utils/api'
import type { SiteSettings } from '../types/siteSetting'

export const siteSettingService = {
  async getAll(): Promise<SiteSettings> {
    const response = await api.get('/admin/site-settings')
    return response.data?.data || {}
  },

  async update(settings: SiteSettings): Promise<SiteSettings> {
    const response = await api.put('/admin/site-settings', { settings })
    return response.data?.data || {}
  },

  async uploadFile(key: 'logo' | 'favicon', file: File): Promise<string> {
    const formData = new FormData()
    formData.append('key', key)
    formData.append('file', file)
    const response = await api.post('/admin/site-settings/upload-file', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data?.data?.url || ''
  },
}
