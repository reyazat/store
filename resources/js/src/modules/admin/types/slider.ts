export interface Slider {
  id: number
  title: string
  subtitle?: string
  image?: string
  link?: string
  button_text?: string
  button_link?: string
  sort_order: number
  is_active: boolean
  created_at: string
}

export interface SliderFormData {
  title: string
  subtitle: string
  link: string
  button_text: string
  button_link: string
  sort_order: number
  is_active: boolean
  image?: File | null
}
