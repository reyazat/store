import api from '@shared/utils/api'

export interface SiteFeature {
  id: number
  title: string
  description?: string | null
  icon?: string | null
  sort_order: number
  is_active: boolean
}

export interface PromoBanner {
  id: number
  title: string
  subtitle?: string | null
  icon?: string | null
  image?: string | null
  link?: string | null
  cta_text?: string | null
  gradient_from?: string | null
  gradient_to?: string | null
  placement: string
  sort_order: number
  is_active: boolean
}

export const homeService = {
  async getFeaturedProducts() {
    const response = await api.get('/products', {
      params: { featured: true, limit: 8 }
    })
    return response.data
  },

  async getDiscountedProducts(limit = 8) {
    const response = await api.get('/products', {
      params: { has_discount: true, sort: 'discount', limit }
    })
    return response.data
  },

  async getNewestProducts(limit = 10) {
    const response = await api.get('/products', {
      params: { sort: 'newest', limit }
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

  async getSiteFeatures(): Promise<SiteFeature[]> {
    const response = await api.get('/site-features')
    return (response.data?.data ?? []) as SiteFeature[]
  },

  async getPromoBanners(placement = 'home'): Promise<PromoBanner[]> {
    const response = await api.get('/promo-banners', { params: { placement } })
    return (response.data?.data ?? []) as PromoBanner[]
  },

  async subscribeNewsletter(email: string, source = 'home') {
    const response = await api.post('/newsletter/subscribe', { email, source })
    return response.data
  },
}
