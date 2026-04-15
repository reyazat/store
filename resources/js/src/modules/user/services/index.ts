import api from '@shared/utils/api'

export const userService = {
  async getProfile() {
    const response = await api.get('/user/profile')
    return response.data
  },
  
  async updateProfile(data: any) {
    const response = await api.put('/user/profile', data)
    return response.data
  },
  
  async changePassword(data: any) {
    const response = await api.post('/user/profile/change-password', data)
    return response.data
  },
  
  async getOrders(params: any = {}) {
    const response = await api.get('/user/orders', { params })
    return response.data
  },
  
  async getOrder(id: number) {
    const response = await api.get(`/user/orders/${id}`)
    return response.data
  },
  
  async getAddresses() {
    const response = await api.get('/user/addresses')
    return response.data
  },
  
  async createAddress(data: any) {
    const response = await api.post('/user/addresses', data)
    return response.data
  },
  
  async updateAddress(id: number, data: any) {
    const response = await api.put(`/user/addresses/${id}`, data)
    return response.data
  },
  
  async deleteAddress(id: number) {
    const response = await api.delete(`/user/addresses/${id}`)
    return response.data
  },
  
  async getWishlist() {
    const response = await api.get('/user/wishlist')
    return response.data
  },
  
  async addToWishlist(productId: number) {
    const response = await api.post('/user/wishlist', { product_id: productId })
    return response.data
  },
  
  async removeFromWishlist(id: number) {
    const response = await api.delete(`/user/wishlist/${id}`)
    return response.data
  },
}
