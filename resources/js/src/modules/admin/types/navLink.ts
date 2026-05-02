export type NavLinkGroup = 'header' | 'footer_quick' | 'footer_service'

export interface NavLink {
  id: number
  label: string
  url: string
  group: NavLinkGroup
  target: '_self' | '_blank'
  sort_order: number
  is_active: boolean
  created_at?: string
}

export interface NavLinkFormData {
  label: string
  url: string
  group: NavLinkGroup
  target: '_self' | '_blank'
  sort_order: number
  is_active: boolean
}
