import api from '@shared/utils/api'

export interface ProductReview {
  id: number
  rating: number
  title: string | null
  body: string
  status: 'pending' | 'approved' | 'rejected'
  approved_at: string | null
  reply: string | null
  replied_at: string | null
  created_at: string
  user: { id: number; name: string } | null
  product?: { id: number; name: string; slug: string } | null
}

export interface ReviewFormData {
  rating: number
  title?: string
  body: string
}

export interface ReviewMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export const reviewService = {
  // ── Public / Buyer ─────────────────────────────────────────────────────────

  async getProductReviews(productSlug: string): Promise<ProductReview[]> {
    const response = await api.get(`/products/${productSlug}/reviews`)
    return response.data?.data || []
  },

  async submitReview(productSlug: string, data: ReviewFormData): Promise<ProductReview> {
    const response = await api.post(`/products/${productSlug}/reviews`, data)
    return response.data.data
  },

  // ── Store owner ────────────────────────────────────────────────────────────

  async storeGetAll(
    page = 1,
    perPage = 15,
    filters: { status?: string; rating?: number } = {},
  ): Promise<{ data: ProductReview[]; meta: ReviewMeta }> {
    const params: Record<string, string | number> = { page, per_page: perPage }
    if (filters.status) params.status = filters.status
    if (filters.rating) params.rating = filters.rating
    const response = await api.get('/store/reviews', { params })
    return { data: response.data.data, meta: response.data.meta }
  },

  async storeApprove(id: number): Promise<ProductReview> {
    const response = await api.post(`/store/reviews/${id}/approve`)
    return response.data.data
  },

  async storeReject(id: number): Promise<ProductReview> {
    const response = await api.post(`/store/reviews/${id}/reject`)
    return response.data.data
  },

  async storeReply(id: number, body: string): Promise<ProductReview> {
    const response = await api.post(`/store/reviews/${id}/reply`, { body })
    return response.data.data
  },
}
