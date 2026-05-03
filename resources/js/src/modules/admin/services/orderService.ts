import api from '@shared/utils/api'
import type { PaginationMeta } from '../composables/useAdminList'

export interface AdminOrderItem {
  id: number
  product_name: string
  vendor_name: string | null
  sku: string | null
  quantity: number
  price: number
  subtotal: number
}

export interface AdminOrderAddress {
  first_name: string
  last_name: string
  phone: string
  address: string
  city: string
  province: string
  postal_code: string
}

export interface AdminPaymentTransaction {
  id: number
  driver: string
  gateway_ref: string | null
  status: 'pending' | 'paid' | 'failed' | 'cancelled'
  amount: number
  card_last_four: string | null
  receipt_path: string | null
  paid_at: string | null
}

export interface AdminOrder {
  id: number
  order_number: string
  customer_name: string | null
  user: { name: string; email: string } | null
  items_count: number
  total: number
  status: string
  status_label: string
  status_color: string
  payment_status: string
  created_at: string
}

export interface AdminOrderDetail extends AdminOrder {
  subtotal: number
  tax: number
  shipping: number
  discount: number
  notes: string | null
  payment_method: string | null
  items: AdminOrderItem[]
  admin_notes: string | null
  address: AdminOrderAddress | null
  latest_transaction: AdminPaymentTransaction | null
  paid_at: string | null
  shipped_at: string | null
  delivered_at: string | null
  cancelled_at: string | null
}

export interface OrderFilters {
  search?: string
  status?: string
  payment_status?: string
  per_page?: number
  page?: number
}

export const orderService = {
  async getAll(filters: OrderFilters = {}): Promise<{ data: AdminOrder[]; meta: PaginationMeta }> {
    const params: Record<string, string | number> = {}

    if (filters.search)         params.search         = filters.search
    if (filters.status)         params.status         = filters.status
    if (filters.payment_status) params.payment_status = filters.payment_status
    if (filters.per_page)       params.per_page       = filters.per_page
    if (filters.page)           params.page           = filters.page

    const response = await api.get('/admin/orders', { params })

    return {
      data: response.data?.data || [],
      meta: response.data?.meta || { current_page: 1, last_page: 1, per_page: 20, total: 0 },
    }
  },

  async getById(id: number | string): Promise<AdminOrderDetail> {
    const response = await api.get(`/admin/orders/${id}`)
    return response.data?.data
  },

  async updateStatus(id: number | string, status: string): Promise<void> {
    await api.patch(`/admin/orders/${id}/status`, { status })
  },

  async updateNotes(id: number | string, adminNotes: string): Promise<AdminOrderDetail> {
    const response = await api.patch(`/admin/orders/${id}/notes`, { admin_notes: adminNotes })
    return response.data?.data
  },

  async confirmReceipt(transactionId: number): Promise<AdminOrderDetail> {
    const response = await api.post(`/admin/transactions/${transactionId}/confirm-receipt`)
    return response.data?.data
  },

  async rejectReceipt(transactionId: number): Promise<AdminOrderDetail> {
    const response = await api.post(`/admin/transactions/${transactionId}/reject-receipt`)
    return response.data?.data
  },
}
