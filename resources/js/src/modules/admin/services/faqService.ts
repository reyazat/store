import api from '@shared/utils/api'
import type { Faq, FaqFormData } from '../types/faq'

export const faqService = {
  async getAll(): Promise<Faq[]> {
    const response = await api.get('/admin/faqs')
    return response.data?.data || []
  },

  async create(data: FaqFormData): Promise<Faq> {
    const response = await api.post('/admin/faqs', data)
    return response.data?.data
  },

  async update(id: number, data: FaqFormData): Promise<Faq> {
    const response = await api.put(`/admin/faqs/${id}`, data)
    return response.data?.data
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/admin/faqs/${id}`)
  },
}
