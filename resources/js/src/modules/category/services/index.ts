import api from '@shared/utils/api'

export const categoryService = {
  async getCategories() {
    const response = await api.get('/categories')
    return response.data
  },
  
  async getCategory(slug: string) {
    const response = await api.get(`/categories/${slug}`)
    return response.data
  },
}
