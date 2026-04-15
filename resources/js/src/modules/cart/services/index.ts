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
}
