/**
 * Store product service.
 *
 * - List:   GET /store/products           (auto-filtered by the authenticated store owner's org)
 * - Detail: GET /store/products/{slug}    (full admin-style detail for the edit form, ownership-checked)
 * - Create/Update/Delete: delegates to the shared endpoints used by ProductFormDrawer
 *   (POST /products, PUT /products/{slug}, DELETE /products/{slug})
 *
 * The detail endpoint is store-specific so store owners are NOT blocked by the
 * admin middleware that guards GET /admin/products/{slug}.
 *
 * TODO: When ProductFormDrawer is moved to shared/, consolidate this with adminProductService.
 */
import api from '@shared/utils/api'
import type { AdminProduct, AdminProductDetail } from '@/modules/admin/services/productService'
import type { PaginationMeta } from '@/modules/admin/composables/useAdminList'

export interface StoreProductFilters {
  search?: string
  status?: number | null
  per_page?: number
  page?: number
}

export const storeProductService = {
  /** List products belonging to this store (scoped by backend via store.owner middleware) */
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

  /**
   * Fetch full product detail for the edit form.
   * Uses GET /store/products/{slug} (store-scoped, ownership-checked)
   * instead of GET /admin/products/{slug} (admin-only endpoint).
   */
  async getDetail(slug: string): Promise<AdminProductDetail> {
    const response = await api.get(`/store/products/${slug}`)
    return response.data.data
  },
}
