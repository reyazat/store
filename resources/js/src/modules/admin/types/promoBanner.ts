export interface PromoBanner {
  id: number
  title: string
  subtitle?: string | null
  icon?: string | null
  image?: string | null
  link?: string | null
  cta_text?: string | null
  gradient_from?: string | null
  gradient_to?: string | null
  placement: string
  sort_order: number
  is_active: boolean
  created_at?: string
  updated_at?: string
}

export interface PromoBannerFormData {
  title: string
  subtitle: string
  icon: string
  link: string
  cta_text: string
  gradient_from: string
  gradient_to: string
  placement: string
  sort_order: number
  is_active: boolean
}
