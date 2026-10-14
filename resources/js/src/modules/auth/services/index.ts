import { plainAxios } from '@shared/utils/api'
import api from '@shared/utils/api'
import type { LoginCredentials, User } from '@shared/types'

export interface IamTokens {
  access_token: string
  refresh_token: string
  token_type: string
  expires_in: number
}

export interface LoginPayload extends LoginCredentials {
  remember_me?: boolean
  device_name?: string
}

export interface IamLoginResponse {
  user?: User & { is_super_admin: boolean }
  tokens?: IamTokens
  // Action-required fields
  action_required?: 'mfa_verification' | 'change_password'
  temp_token?: string
  mfa_methods?: string[]
  user_id?: number
}

export interface ForceChangeLoginResponse {
  success: boolean
  message: string
  data?: {
    user: User & { is_super_admin: boolean }
    tokens: IamTokens
    password_changed_at: string
    password_expires_at: string
    must_change_password: boolean
    force_change_reason?: string
  }
}

// MFA specific interfaces
export interface MfaRequiredResponse {
  action_required: 'mfa_verification'
  temp_token: string
  mfa_methods: string[]
}

export interface MfaVerifyPayload {
  login_token: string
  code: string
  type: 'totp'
}

export interface MfaRecoveryPayload {
  login_token: string
  recovery_code: string
}

export interface MfaVerifyResponse {
  success: boolean
  message: string
  data?: {
    user: User & { is_super_admin: boolean }
    tokens: IamTokens
    recovery_code_used?: boolean
    remaining_codes?: number
    attempts_remaining?: number
  }
  code?: number
}

// OTP specific interfaces
export interface SendOtpPayload {
  identifier: string
  type: 'login_verification' | 'password_reset' | 'email_verification' | 'phone_verification'
  channel: 'email' | 'sms'
}

export interface VerifyOtpPayload {
  identifier: string
  code: string
  type: 'login_verification' | 'password_reset' | 'email_verification' | 'phone_verification'
  channel?: 'email' | 'sms'
}

export interface OtpLoginResponse {
  success: boolean
  message: string
  data?: {
    verified: boolean
    type: string
    user?: User & { is_super_admin: boolean }
    tokens?: IamTokens
    attempts_remaining?: number
    // Action-required fields (e.g. forced password change during OTP login)
    action_required?: 'change_password'
    temp_token?: string
    user_id?: number
  }
}

// Password reset interfaces
export interface ForgotPasswordResponse {
  success: boolean
  message: string
  data?: {
    channels: string[]
    expires_at: string
    identifier: string
  }
}

export interface VerifyPasswordResetOtpPayload {
  identifier: string
  code: string
}

export interface VerifyPasswordResetOtpResponse {
  success: boolean
  message: string
  data?: {
    verified: boolean
    reset_token?: string
    expires_in?: number
    attempts_remaining?: number
  }
}

export interface ResetPasswordWithOtpPayload {
  identifier: string
  reset_token: string
  password: string
  password_confirmation: string
}

export interface ResetPasswordWithOtpResponse {
  success: boolean
  message: string
}

// Password change interfaces
export interface ForceChangePasswordPayload {
  new_password: string
  new_password_confirmation: string
}

export interface ForceChangePasswordResponse {
  success: boolean
  message: string
  data?: {
    password_changed_at: string
    password_expires_at: string
    must_change_password: boolean
  }
}

export const authService = {
  async login(credentials: LoginPayload): Promise<IamLoginResponse> {
    const response = await api.post('/auth/login', credentials)
    // IAM response shape: { success, message, data: { user, tokens, action_required, ... } }
    return response.data.data
  },

  async register(payload: {
    name: string
    email: string
    password: string
    password_confirmation: string
    phone?: string
  }): Promise<User> {
    // IAM register endpoint: POST /api/v1/users/register
    const response = await api.post('/users/register', payload)
    return response.data.data
  },

  async logout(refreshToken: string): Promise<void> {
    await api.post('/auth/logout', { refresh_token: refreshToken })
  },

  async getMe(): Promise<User & { is_super_admin: boolean }> {
    const response = await api.get('/auth/me')
    // IAM response shape: { success, message, data: { user: { ... } } }
    return response.data.data.user
  },

  async getMyRoles(): Promise<string[]> {
    const response = await api.get('/auth/me/roles')
    // IAM response shape: { success, message, data: { roles: [{id, name, ...}] } }
    // Backend returns role objects; extract the name string for use in auth store
    const roles = response.data.data?.roles || []
    return roles.map((r: { name: string } | string) => (typeof r === 'string' ? r : r.name))
  },

  async getMyPermissions(): Promise<string[]> {
    const response = await api.get('/auth/me/permissions')
    return response.data.data?.all_permissions || []
  },

  async refreshToken(refreshToken: string): Promise<IamTokens> {
    // Use plainAxios to avoid interceptor loop
    const response = await plainAxios.post('/auth/refresh', { refresh_token: refreshToken })
    return response.data.data
  },

  // ========== Password Reset with OTP Methods ==========

  async forgotPassword(identifier: string): Promise<ForgotPasswordResponse> {
    const response = await api.post('/password/forgot', { identifier })
    return response.data
  },

  async verifyPasswordResetOtp(payload: VerifyPasswordResetOtpPayload): Promise<VerifyPasswordResetOtpResponse> {
    const response = await api.post('/password/verify-otp', payload)
    return response.data
  },

  async resetPasswordWithOtp(payload: ResetPasswordWithOtpPayload): Promise<ResetPasswordWithOtpResponse> {
    const response = await api.post('/password/reset-with-otp', payload)
    return response.data
  },

  // Legacy method - kept for backwards compatibility
  async resetPassword(payload: {
    token: string
    email: string
    password: string
    password_confirmation: string
  }): Promise<void> {
    await api.post('/password/reset', payload)
  },

  // ========== MFA Methods ==========

  async verifyMfaLogin(payload: MfaVerifyPayload): Promise<MfaVerifyResponse> {
    const response = await api.post('/mfa/verify-login', payload)
    return response.data
  },

  async verifyMfaRecovery(payload: MfaRecoveryPayload): Promise<MfaVerifyResponse> {
    const response = await api.post('/mfa/verify-recovery-login', payload)
    return response.data
  },

  async getMfaStatus(): Promise<{
    success: boolean
    data?: {
      mfa_enabled: boolean
      totp_configured: boolean
      recovery_codes_count: number
    }
  }> {
    const response = await api.get('/mfa/status')
    return response.data
  },

  async enableMfa(currentPassword: string): Promise<{
    success: boolean
    data?: {
      secret: string
      qr_code: string
      backup_codes: string[]
    }
  }> {
    const response = await api.post('/mfa/enable', { current_password: currentPassword })
    return response.data
  },

  async disableMfa(currentPassword: string): Promise<{ success: boolean; message: string }> {
    const response = await api.post('/mfa/disable', {
      current_password: currentPassword,
      confirmation: 'DISABLE MFA'
    })
    return response.data
  },

  // ========== OTP Methods ==========

  async sendOtp(payload: SendOtpPayload): Promise<{
    success: boolean
    message: string
    data?: {
      identifier: string
      expires_at: string
      attempts_remaining: number
    }
  }> {
    const response = await api.post('/otp/send', payload)
    return response.data
  },

  async verifyOtp(payload: VerifyOtpPayload): Promise<OtpLoginResponse> {
    const response = await api.post('/otp/verify', payload)
    return response.data
  },

  // ========== Force Password Change ==========

  /** Called from authenticated context (e.g. profile/settings page) */
  async forceChangePassword(payload: ForceChangePasswordPayload): Promise<ForceChangePasswordResponse> {
    const response = await api.post('/password/force-change', payload)
    return response.data
  },

  /** Called during login flow when `action_required === 'change_password'`.
   *  Sends the temp_token as Authorization header and returns full session tokens on success. */
  async forceChangePasswordLogin(
    payload: ForceChangePasswordPayload,
    tempToken: string
  ): Promise<ForceChangeLoginResponse> {
    const response = await api.post('/password/force-change-login', payload, {
      headers: { Authorization: `Bearer ${tempToken}` },
    })
    return response.data
  },

  async checkPasswordExpiry(): Promise<{
    success: boolean
    data?: {
      password_expires_at: string
      days_until_expiry: number
      is_expired: boolean
      must_change_password: boolean
    }
  }> {
    const response = await api.get('/password/expiry')
    return response.data
  },

  // ========== Login with Phone ==========

  async loginWithPhone(phone: string): Promise<{
    success: boolean
    message: string
    data?: {
      action_required?: 'otp_verification'
      identifier?: string
    }
  }> {
    // First send OTP for login
    const response = await this.sendOtp({
      identifier: phone,
      type: 'login_verification',
      channel: 'sms'
    })
    return response
  },

  async verifyPhoneLogin(payload: VerifyOtpPayload): Promise<OtpLoginResponse> {
    const response = await this.verifyOtp({
      identifier: payload.identifier,
      code: payload.code,
      type: 'login_verification',
      channel: 'sms'
    })
    return response
  }
}
