import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { userService } from '@modules/user/services'
import { useAuthStore } from './auth'

export const useWishlistStore = defineStore('wishlist', () => {
  const authStore = useAuthStore()

  const entryMap      = ref<Map<number, number>>(new Map())
  const isInitialized = ref(false)

  const productIds = computed(() => new Set(entryMap.value.keys()))
  const count = computed(() => entryMap.value.size)

  const init = async () => {
    if (!authStore.isAuthenticated || isInitialized.value) return
    try {
      const res = await userService.getWishlist()
      const items: { id: number; product: { id: number } }[] = res.data || []
      const map = new Map<number, number>()
      items.forEach(i => map.set(i.product.id, i.id))
      entryMap.value      = map
      isInitialized.value = true
    } catch { /* silently fail */ }
  }

  const has = (productId: number) => entryMap.value.has(productId)

  const toggle = async (productId: number): Promise<'added' | 'removed'> => {
    const entryId = entryMap.value.get(productId)
    if (entryId !== undefined) {
      entryMap.value.delete(productId)
      await userService.removeFromWishlist(entryId)
      return 'removed'
    } else {
      const res = await userService.addToWishlist(productId)
      const newEntryId: number = res.data?.id ?? res.id
      entryMap.value.set(productId, newEntryId)
      return 'added'
    }
  }

  const removeByProductId = (productId: number) => {
    entryMap.value.delete(productId)
  }

  return { entryMap, productIds, count, isInitialized, init, has, toggle, removeByProductId }
})
