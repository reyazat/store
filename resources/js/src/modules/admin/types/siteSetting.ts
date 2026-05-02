export interface SiteSettings {
  site_name?: string
  site_description?: string
  site_about?: string
  copyright_text?: string
  logo?: string
  logo_alt?: string
  favicon?: string
  support_hours?: string
  phone?: string
  phone_link?: string
  email?: string
  address?: string
  social_instagram?: string
  social_telegram?: string
  social_whatsapp?: string
  shipping_cost?: string
  free_shipping_min?: string
  notify_new_order_email?: string
  notify_status_change_sms?: string
  [key: string]: string | undefined
}
