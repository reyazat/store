import { ref, onMounted } from 'vue'
import api from '@shared/utils/api'

export interface NavLinkItem {
  id: number
  label: string
  url: string
  group: string
  target: '_self' | '_blank'
  sort_order: number
  is_active: boolean
}

type NavLinkGroup = 'header' | 'footer_quick' | 'footer_service'

const cache = new Map<NavLinkGroup, NavLinkItem[]>()

const fetchGroup = async (group: NavLinkGroup): Promise<NavLinkItem[]> => {
  if (cache.has(group)) return cache.get(group)!
  try {
    const response = await api.get(`/nav-links/${group}`)
    const links: NavLinkItem[] = response.data?.data || []
    cache.set(group, links)
    return links
  } catch {
    return []
  }
}

export function useNavLinks(group: NavLinkGroup) {
  const links = ref<NavLinkItem[]>([])
  const isLoading = ref(false)

  const load = async () => {
    isLoading.value = true
    links.value = await fetchGroup(group)
    isLoading.value = false
  }

  onMounted(load)

  return { links, isLoading }
}
