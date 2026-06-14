import api from '@shared/utils/api'

export interface AdminCategory {
  id: number
  parent_id: number | null
  name: string
  slug: string
  image: string | null
  icon: string | null
  products_count: number
  direct_products_count: number
  children_count: number
  status: number
  sorted: number
  created_at: string
}

export interface AdminCategoryDetail {
  id: number
  parent_id: number | null
  name: string
  slug: string
  description: string | null
  image: string | null
  icon: string | null
  meta_title: string | null
  meta_description: string | null
  status: number
  sorted: number
  created_at: string
  updated_at: string
  parent: { id: number; name: string; slug: string } | null
  children: Array<{ id: number; name: string; slug: string }>
}

export interface CategoryFormData {
  parent_id: number | null
  name: string
  slug: string
  description: string
  image: string | null
  image_file: File | null
  icon: string
  meta_title: string
  meta_description: string
  status: number
  sorted: number
}

export interface AdminCategoryMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface AdminCategoryListResponse {
  data: AdminCategory[]
  meta: AdminCategoryMeta
}

export interface CategoryFilters {
  search?: string
  status?: number | null
  parent_id?: number | null
  per_page?: number
  page?: number
  root_only?: boolean
}

export const categoryService = {
  async getAll(filters: CategoryFilters = {}): Promise<AdminCategoryListResponse> {
    const params: Record<string, string | number> = {}

    if (filters.search)                                                   params.search    = filters.search
    if (filters.status    !== null && filters.status    !== undefined)     params.status    = filters.status
    if (filters.parent_id !== null && filters.parent_id !== undefined)     params.parent_id = filters.parent_id
    if (filters.per_page)                                                  params.per_page  = filters.per_page
    if (filters.page)                                                      params.page      = filters.page

    const response = await api.get('/categories', { params })

    return {
      data: response.data?.data || [],
      meta: response.data?.meta || { current_page: 1, last_page: 1, per_page: 20, total: 0 },
    }
  },

  async getById(slug: string): Promise<AdminCategoryDetail> {
    const response = await api.get(`/categories/${slug}`)
    return response.data.data
  },

  async create(data: CategoryFormData): Promise<AdminCategoryDetail> {
    const formData = buildFormData(data)
    const response = await api.post('/admin/categories', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data.data
  },

  async update(slug: string, data: CategoryFormData): Promise<AdminCategoryDetail> {
    const formData = buildFormData(data)
    formData.append('_method', 'PUT')
    const response = await api.post(`/admin/categories/${slug}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data.data
  },

  async reorder(ids: number[]): Promise<void> {
    await api.post('/admin/categories/reorder', { ids })
  },

  async destroy(slug: string): Promise<void> {
    await api.delete(`/admin/categories/${slug}`)
  },

  async getParentOptions(): Promise<Array<{ id: number; name: string; slug: string; parent_id: number | null; level: number }>> {
    const response = await api.get('/categories', { params: { per_page: 200, status: 1 } })
    const categories = response.data?.data || []

    // Build a map for quick lookup
    const categoryMap = new Map<number, AdminCategory>(categories.map((c: AdminCategory) => [c.id, c]))

    // Calculate level for each category
    const getLevel = (cat: AdminCategory): number => {
      let level = 0
      let currentId: number | null = cat.parent_id
      while (currentId !== null) {
        const parent = categoryMap.get(currentId)
        if (!parent) break
        level++
        currentId = parent.parent_id
      }
      return level
    }

    return categories
      .map((c: AdminCategory) => ({
        id: c.id,
        name: c.name,
        slug: c.slug,
        parent_id: c.parent_id,
        level: getLevel(c),
      }))
      .sort((a: { level: number; name: string }, b: { level: number; name: string }) => {
        // Sort by level first, then by name
        if (a.level !== b.level) return a.level - b.level
        return a.name.localeCompare(b.name, 'fa')
      })
  },
}

function buildFormData(data: CategoryFormData): FormData {
  const fd = new FormData()

  if (data.parent_id !== null) fd.append('parent_id', String(data.parent_id))
  fd.append('name', data.name)
  fd.append('slug', data.slug)
  if (data.description)      fd.append('description', data.description)
  if (data.icon)             fd.append('icon', data.icon)
  if (data.meta_title)       fd.append('meta_title', data.meta_title)
  if (data.meta_description) fd.append('meta_description', data.meta_description)
  fd.append('status', String(data.status))
  fd.append('sorted', String(data.sorted))

  if (data.image_file)       fd.append('image_file', data.image_file)
  else if (data.image)       fd.append('image', data.image)

  return fd
}
