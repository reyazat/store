import api from '@shared/utils/api'
import type { PaginationMeta } from '../composables/useAdminList'

export interface AdminCoupon {
  id: number
  organization_id: number | null
  is_platform: boolean
  code: string
  name: string | null
  description: string | null
  type: 'percentage' | 'fixed'
  value: number
  min_purchase: number | null       // was: min_order_amount
  max_discount: number | null       // was: max_discount_amount
  usage_limit: number | null
  usage_per_user: number | null
  used_count: number
  status: boolean                   // was: is_active
  is_valid: boolean
  starts_at: string | null
  expires_at: string | null
  created_at: string | null
}

export interface CouponFormData {
  code: string
  name: string | null
  description: string | null
  type: 'percentage' | 'fixed'
  value: number
  min_purchase: number | null
  max_discount: number | null
  usage_limit: number | null
  usage_per_user: number | null
  status: boolean
  starts_at: string | null
  expires_at: string | null
}

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
