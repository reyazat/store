import api from '@shared/utils/api'
import type { LoginCredentials, User } from '@shared/types'

export interface IamTokens {
  access_token: string
  refresh_token: string
  token_type: string
  expires_in: number
}

export interface IamLoginResponse {
  user: User & { is_super_admin: boolean }
  tokens: IamTokens
}

export const authService = {
  async login(credentials: LoginCredentials): Promise<IamLoginResponse> {
    const response = await api.post('/auth/login', credentials)
    // IAM response shape: { success, message, data: { user, tokens } }
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
    // IAM response shape: { success, message, data: { uuid, name, ... } }
    return response.data.data
  },

  async getMyRoles(): Promise<string[]> {
    const response = await api.get('/auth/me/roles')
    // IAM response shape: { success, message, data: { roles: [...] } }
    return response.data.data?.roles || []
  },

  async getMyPermissions(): Promise<string[]> {
    const response = await api.get('/auth/me/permissions')
    return response.data.data?.permissions || []
  },

  async refreshToken(refreshToken: string): Promise<IamTokens> {
    const response = await api.post('/auth/refresh', { refresh_token: refreshToken })
    return response.data.data
  },
}
