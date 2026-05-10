import api from '@shared/utils/api'

export interface HierarchicalCategory {
  id: number
  name: string
  slug: string
  image: string | null
  icon: string | null
  children_count: number
  direct_products_count: number
  children: Array<{
    id: number
    name: string
    slug: string
    image: string | null
    products_count: number
  }>
}

export const categoryService = {
  async getCategories() {
    const response = await api.get('/categories')
    return response.data
  },
  
  async getCategory(slug: string) {
    const response = await api.get(`/categories/${slug}`)
    return response.data
  },

  async getHierarchicalCategories(): Promise<{ data: HierarchicalCategory[] }> {
    const response = await api.get('/categories/hierarchical')
    return response.data
  },
}
