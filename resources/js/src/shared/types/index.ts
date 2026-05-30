export interface Product {
  id: number
  name: string
  slug: string
  description?: string
  main_image?: string
  price: number
  old_price?: number
  discount_percent?: number
  manufacturer?: string
  listing_id?: number
  status: number
  stock?: number
}

export interface Category {
  id: number
  name: string
  slug: string
  description?: string
  image?: string
  icon?: string | null
  parent_id?: number
  children?: Category[]
  products_count?: number
}

export interface CartItem {
  id: number
  productId: number
  listingId?: number
  variantId?: number
  name: string
  price: number
  image?: string
  quantity: number
}

export interface Address {
  id: number
  title?: string
  first_name: string
  last_name: string
  phone: string
  email?: string
  province: string
  city: string
  address: string
  postal_code: string
  is_default: boolean
}

export interface Order {
  id: number
  order_number: string
  status: 'pending' | 'processing' | 'shipped' | 'delivered' | 'cancelled'
  payment_status: 'pending' | 'paid' | 'failed' | 'refunded'
  total: number
  items_count: number
  created_at: string
}

export interface User {
  id: number
  name: string
  email: string
  phone?: string
  roles?: string[]
  permissions?: string[]
  is_admin?: boolean
  is_super_admin?: boolean
}

export interface LoginCredentials {
  email: string
  password: string
}

export interface AuthResponse {
  access_token: string
  token_type: string
  expires_in: number
  user: User
}

export interface ApiResponse<T> {
  success: boolean
  data: T
  message?: string
  meta?: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}
