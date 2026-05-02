import { ref, onMounted } from 'vue'
import api from '@shared/utils/api'

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
  [key: string]: string | undefined
}

const settings = ref<SiteSettings>({})
const isLoaded = ref(false)

const applyToDocument = (data: SiteSettings) => {
  if (data.site_name) {
    document.title = data.site_name
  }
  if (data.favicon) {
    const existing = document.querySelector<HTMLLinkElement>('link[rel="icon"]')
    if (existing) {
      existing.href = data.favicon
    } else {
      const link = document.createElement('link')
      link.rel = 'icon'
      link.href = data.favicon
      document.head.appendChild(link)
    }
  }
}

const fetchSettings = async () => {
  if (isLoaded.value) return
  try {
    const response = await api.get('/site-settings')
    settings.value = response.data?.data || {}
    isLoaded.value = true
    applyToDocument(settings.value)
  } catch {
    // silently fail — static fallbacks remain in place
  }
}

export function useSiteSettings() {
  onMounted(() => {
    if (!isLoaded.value) fetchSettings()
  })

  return { settings, isLoaded, fetchSettings }
}
