export interface IamUser {
  id: number
  uuid: string
  name: string
  email: string
  phone: string | null
  status: 'active' | 'inactive' | 'suspended'
  is_super_admin: boolean
  email_verified_at: string | null
  phone_verified_at: string | null
  last_login_at: string | null
  last_login_ip: string | null
  password_expires_at: string | null
  must_change_password: boolean
  mfa_enabled: boolean
  is_locked: boolean
  locked_until: string | null
  created_at: string
  updated_at: string
  organization?: {
    uuid: string
    name: string
    created_at: string
    updated_at: string
  } | null
}

export interface IamUserFilters {
  search?: string
  status?: string
  per_page?: number
  page?: number
}

export interface AuditSubjectCauser {
  type: string
  id: number
  data: {
    id: number
    name: string
    email: string
  } | null
}

export interface AuditLog {
  id: number
  log_name: string
  description: string
  event: string | null
  subject: AuditSubjectCauser | null
  causer: AuditSubjectCauser | null
  properties: Record<string, unknown>
  created_at: string
  updated_at: string
}

export interface AuditLogFilters {
  user_id?: number | string
  log_name?: string
  event?: string
  ip?: string
  start_date?: string
  end_date?: string
  subject_type?: string
  subject_id?: number | string
  per_page?: number
  page?: number
}

export interface LockAccountPayload {
  user_id: number
  reason: string
  duration_minutes?: number
}
