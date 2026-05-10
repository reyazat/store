export interface Faq {
  id: number
  question: string
  answer: string
  category: string | null
  sort_order: number
  is_active: boolean
  created_at?: string
  updated_at?: string
}

export interface FaqFormData {
  question: string
  answer: string
  category: string
  sort_order: number
  is_active: boolean
}
