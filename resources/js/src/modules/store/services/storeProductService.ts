/**
 * Store product service.
 *
 * - List: GET /store/products  (auto-filtered by the authenticated store owner's org)
 * - Create/Update/Delete: delegates to the same endpoints used by the product form
 *   (POST /products, PUT /products/{slug}, DELETE /products/{slug})
 *
 * TODO: When ProductFormDrawer is moved to shared/, consolidate this with adminProductService.
 */
import api from '@shared/utils/api'
import type { AdminProduct } from '@/modules/admin/services/productService'
import type { PaginationMeta } from '@/modules/admin/composables/useAdminList'

export interface StoreProductFilters {
  search?: string
  status?: number | null
  per_page?: number
  page?: number
}

export const storeProductService = {
  /** List products belonging to this store (scoped by the backend via store.owner middleware) */
  async getAll(filters: StoreProductFilters = {}): Promise<{ data: AdminProduct[]; meta: PaginationMeta }> {
    const params: Record<string, string | number> = {}
    if (filters.search)                                          params.search   = filters.search
    if (filters.status !== null && filters.status !== undefined) params.status   = filters.status
    if (filters.per_page)                                        params.per_page = filters.per_page
    if (filters.page)                                            params.page     = filters.page

    const response = await api.get('/store/products', { params })
    return {
      data: response.data?.data || [],
      meta: response.data?.meta || { current_page: 1, last_page: 1, per_page: 20, total: 0 },
    }
  },
}
