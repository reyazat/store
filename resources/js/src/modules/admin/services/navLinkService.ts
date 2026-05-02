import api from '@shared/utils/api'
import type { NavLink, NavLinkFormData } from '../types/navLink'

export const navLinkService = {
  async getAll(): Promise<NavLink[]> {
    const response = await api.get('/admin/nav-links')
    return response.data?.data || []
  },

  async create(data: NavLinkFormData): Promise<NavLink> {
    const response = await api.post('/admin/nav-links', data)
    return response.data?.data
  },

  async update(id: number, data: Partial<NavLinkFormData>): Promise<NavLink> {
    const response = await api.put(`/admin/nav-links/${id}`, data)
    return response.data?.data
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/admin/nav-links/${id}`)
  },
}
