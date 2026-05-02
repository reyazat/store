import api from '@shared/utils/api'
import type { AdminPaymentMethod, OrderStatus } from '../types/payment'

export const paymentMethodService = {
  async getAll(): Promise<AdminPaymentMethod[]> {
    const response = await api.get('/admin/payment-methods')
    return response.data?.data || []
  },

  async getOne(id: number): Promise<AdminPaymentMethod> {
    const response = await api.get(`/admin/payment-methods/${id}`)
    return response.data?.data
  },

  async update(id: number, data: Partial<AdminPaymentMethod>): Promise<AdminPaymentMethod> {
    const response = await api.put(`/admin/payment-methods/${id}`, data)
    return response.data?.data
  },
}

export const orderStatusService = {
  async getAll(): Promise<OrderStatus[]> {
    const response = await api.get('/admin/order-statuses')
    return response.data?.data || []
  },

  async create(data: Omit<OrderStatus, 'id'>): Promise<OrderStatus> {
    const response = await api.post('/admin/order-statuses', data)
    return response.data?.data
  },

  async update(id: number, data: Partial<OrderStatus>): Promise<OrderStatus> {
    const response = await api.put(`/admin/order-statuses/${id}`, data)
    return response.data?.data
  },

  async destroy(id: number): Promise<void> {
    await api.delete(`/admin/order-statuses/${id}`)
  },
}
