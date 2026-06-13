export interface SiteFeature {
  id: number
  title: string
  description?: string | null
  icon?: string | null
  sort_order: number
  is_active: boolean
  created_at?: string
  updated_at?: string
}

export interface SiteFeatureFormData {
  title: string
  description?: string | null
  icon?: string | null
  sort_order: number
  is_active: boolean
}
