import api from '@shared/utils/api'

export interface ProductReview {
  id: number
  rating: number
  title: string | null
  body: string
  status: 'pending' | 'approved' | 'rejected'
  approved_at: string | null
  created_at: string
  user: { id: number; name: string } | null
  product?: { id: number; name: string; slug: string } | null
}

export interface ReviewFormData {
  rating: number
  title?: string
  body: string
}

export interface AdminReviewMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export const reviewService = {
  async getProductReviews(productSlug: string): Promise<ProductReview[]> {
    const response = await api.get(`/products/${productSlug}/reviews`)
    return response.data?.data || []
  },

  async submitReview(productSlug: string, data: ReviewFormData): Promise<ProductReview> {
    const response = await api.post(`/products/${productSlug}/reviews`, data)
    return response.data.data
  },

  async adminGetAll(page = 1, perPage = 20): Promise<{ data: ProductReview[]; meta: AdminReviewMeta }> {
    const response = await api.get('/admin/reviews', { params: { page, per_page: perPage } })
    return { data: response.data.data, meta: response.data.meta }
  },

  async approve(id: number): Promise<ProductReview> {
    const response = await api.post(`/admin/reviews/${id}/approve`)
    return response.data.data
  },

  async reject(id: number): Promise<ProductReview> {
    const response = await api.post(`/admin/reviews/${id}/reject`)
    return response.data.data
  },

  async destroy(id: number): Promise<void> {
    await api.delete(`/admin/reviews/${id}`)
  },
}
