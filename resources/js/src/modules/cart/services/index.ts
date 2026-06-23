import api from '@shared/utils/api'

export const cartService = {
  async getCart() {
    const response = await api.get('/user/cart')
    return response.data
  },
  
  async addItem(listingId: number, quantity: number = 1, variantId?: number) {
    const payload: any = { listing_id: listingId, quantity }
    if (variantId) {
      payload.variant_id = variantId
    }
    const response = await api.post('/user/cart', payload)
    return response.data
  },
  
  async updateItem(itemId: number, quantity: number) {
    const response = await api.put(`/user/cart/${itemId}`, { quantity })
    return response.data
  },
  
  async removeItem(itemId: number) {
    const response = await api.delete(`/user/cart/${itemId}`)
    return response.data
  },
  
  async clearCart() {
    const response = await api.post('/user/cart/clear')
    return response.data
  },
  
  async getAddresses() {
    const response = await api.get('/user/addresses')
    return response.data
  },
  
  async createOrder(addressId: number) {
    const response = await api.post('/user/orders', { address_id: addressId })
    return response.data
  },

  async getPaymentMethods() {
    const response = await api.get('/payment/methods')
    return response.data
  },

  async initiatePayment(orderId: number, driver: string) {
    const response = await api.post('/user/payment/initiate', { order_id: orderId, driver })
    return response.data
  },

  async applyCoupon(code: string) {
    const response = await api.post('/user/cart/coupon', { code })
    return response.data
  },

  async removeCoupon() {
    const response = await api.delete('/user/cart/coupon')
    return response.data
  },

  async uploadReceipt(transactionId: number, file: File, cardLastFour?: string) {
    const formData = new FormData()
    formData.append('receipt', file)
    if (cardLastFour) formData.append('card_last_four', cardLastFour)
    const response = await api.post(`/user/payment/transactions/${transactionId}/receipt`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data
  },
}
