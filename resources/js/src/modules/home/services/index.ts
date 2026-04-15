import api from '@shared/utils/api'

export const homeService = {
  async getFeaturedProducts() {
    const response = await api.get('/products', {
      params: { featured: true, limit: 8 }
    })
    return response.data
  },

  async getDiscountedProducts(limit = 8) {
    const response = await api.get('/products', {
      params: { has_discount: true, sort_by_discount: 'desc', limit }
    })
    return response.data
  },

  async getCategories() {
    const response = await api.get('/categories', {
      params: { limit: 20 }
    })
    return response.data
  },

  async getSliders() {
    const response = await api.get('/sliders')
    return response.data.data as Array<{
      id: number
      title: string
      subtitle?: string
      image?: string
      link?: string
      button_text?: string
      button_link?: string
    }>
  },
}
