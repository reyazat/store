import api from '@shared/utils/api'
import type { PaginationMeta } from '../composables/useAdminList'

export interface AdminCoupon {
  id: number
  code: string
  name: string | null
  type: 'percentage' | 'fixed'
  value: number
  min_order_amount: number | null
  max_discount_amount: number | null
  usage_limit: number | null
  used_count: number
  is_active: boolean
  starts_at: string | null
  expires_at: string | null
}

export type CouponFormData = Omit<AdminCoupon, 'id' | 'used_count'>

export interface CouponFilters {
  search?: string
  status?: string
  per_page?: number
  page?: number
}

export const couponService = {
  async getAll(filters: CouponFilters = {}): Promise<{ data: AdminCoupon[]; meta: PaginationMeta }> {
    const params: Record<string, string | number> = {}

    if (filters.search)   params.search   = filters.search
    if (filters.status)   params.status   = filters.status
    if (filters.per_page) params.per_page = filters.per_page
    if (filters.page)     params.page     = filters.page

    const response = await api.get('/admin/coupons', { params })

    return {
      data: response.data?.data || [],
      meta: response.data?.meta || { current_page: 1, last_page: 1, per_page: 20, total: 0 },
    }
  },

  async create(data: CouponFormData): Promise<AdminCoupon> {
    const response = await api.post('/admin/coupons', data)
    return response.data?.data
  },

  async update(id: number, data: Partial<CouponFormData>): Promise<AdminCoupon> {
    const response = await api.put(`/admin/coupons/${id}`, data)
    return response.data?.data
  },

  async destroy(id: number): Promise<void> {
    await api.delete(`/admin/coupons/${id}`)
  },
}
