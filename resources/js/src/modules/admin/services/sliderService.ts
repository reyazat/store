import api from '@shared/utils/api'
import type { Slider, SliderFormData } from '../types/slider'

export const sliderService = {
  async getAll(): Promise<Slider[]> {
    const response = await api.get('/admin/sliders')
    return response.data.data
  },

  async create(formData: SliderFormData): Promise<Slider> {
    const payload = buildFormData(formData)
    const response = await api.post('/admin/sliders', payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data.data
  },

  async update(id: number, formData: SliderFormData): Promise<Slider> {
    const payload = buildFormData(formData)
    const response = await api.post(`/admin/sliders/${id}`, payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data.data
  },

  async remove(id: number): Promise<void> {
    await api.delete(`/admin/sliders/${id}`)
  },

  async reorder(ids: number[]): Promise<void> {
    await api.post('/admin/sliders-reorder', { ids })
  },
}

function buildFormData(data: SliderFormData): FormData {
  const fd = new FormData()
  fd.append('title', data.title)
  fd.append('subtitle', data.subtitle ?? '')
  fd.append('link', data.link ?? '')
  fd.append('button_text', data.button_text ?? '')
  fd.append('button_link', data.button_link ?? '')
  fd.append('sort_order', String(data.sort_order ?? 0))
  fd.append('is_active', data.is_active ? '1' : '0')
  if (data.image instanceof File) {
    fd.append('image', data.image)
  }
  return fd
}
