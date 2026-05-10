import api from '@shared/utils/api'

export interface FaqItem {
  id: number
  question: string
  answer: string
  category: string | null
  sort_order: number
}

export const faqService = {
  async getAll(): Promise<FaqItem[]> {
    const response = await api.get('/faqs')
    return response.data?.data || []
  },
}
