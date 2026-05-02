import api from '@shared/utils/api'
import type { PaginationMeta } from '../composables/useAdminList'
import type { IamUser, IamUserFilters, AuditLog, AuditLogFilters, LockAccountPayload } from '../types/iam'

export const iamService = {
  async getUsers(filters: IamUserFilters = {}): Promise<{ data: IamUser[]; meta: PaginationMeta }> {
    const params: Record<string, string | number> = {}
    if (filters.search)   params.search   = filters.search
    if (filters.status)   params.status   = filters.status
    if (filters.per_page) params.per_page = filters.per_page
    if (filters.page)     params.page     = filters.page

    const response = await api.get('/admin/users', { params })

    return {
      data: response.data?.data || [],
      meta: response.data?.meta || { current_page: 1, last_page: 1, per_page: 15, total: 0 },
    }
  },

  async getUserById(id: number): Promise<IamUser> {
    const response = await api.get(`/admin/users/${id}`)
    return response.data?.data
  },

  async lockAccount(payload: LockAccountPayload): Promise<void> {
    await api.post(`/admin/users/${payload.user_id}/lock`, {
      reason: payload.reason,
      duration_minutes: payload.duration_minutes,
    })
  },

  async unlockAccount(userId: number): Promise<void> {
    await api.post(`/admin/users/${userId}/unlock`)
  },

  async getAuditLogs(filters: AuditLogFilters = {}): Promise<{ data: AuditLog[]; meta: PaginationMeta }> {
    const params: Record<string, string | number> = {}
    if (filters.user_id)      params.user_id      = filters.user_id
    if (filters.log_name)     params.log_name     = filters.log_name
    if (filters.event)        params.event        = filters.event
    if (filters.ip)           params.ip           = filters.ip
    if (filters.start_date)   params.start_date   = filters.start_date
    if (filters.end_date)     params.end_date     = filters.end_date
    if (filters.subject_type) params.subject_type = filters.subject_type
    if (filters.subject_id)   params.subject_id   = filters.subject_id
    if (filters.per_page)     params.per_page     = filters.per_page
    if (filters.page)         params.page         = filters.page

    const response = await api.get('/v1/audit/logs', { params })

    const responseData = response.data?.data
    return {
      data: responseData?.logs || [],
      meta: responseData?.pagination || { current_page: 1, last_page: 1, per_page: 15, total: 0 },
    }
  },

  async getAuditLogById(id: number): Promise<AuditLog> {
    const response = await api.get(`/v1/audit/logs/${id}`)
    return response.data?.data?.log
  },
}
