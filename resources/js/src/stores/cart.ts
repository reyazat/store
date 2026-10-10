import { defineStore } from 'pinia'
import { ref, computed, watch } from 'vue'
import type { CartItem } from '@shared/types'
import { cartService } from '@modules/cart/services'
import { useAuthStore } from './auth'

const GUEST_CART_KEY = 'guest_cart'

interface AppliedCoupon {
  id: number
  code: string
  name: string | null
  type: 'percentage' | 'fixed'
  value: number
}

export const useCartStore = defineStore('cart', () => {
  const authStore = useAuthStore()
  const items = ref<CartItem[]>([])
  const isDrawerOpen = ref(false)
  const isLoading = ref(false)

  // Coupon state — only relevant for authenticated users (server-side cart)
  const appliedCoupon   = ref<AppliedCoupon | null>(null)
  const couponDiscount  = ref<number>(0)
  const subtotal        = computed(() =>
    items.value.reduce((sum, item) => sum + (item.price * item.quantity), 0)
  )

  const itemsCount = computed(() =>
    items.value.reduce((sum, item) => sum + item.quantity, 0)
  )

  const total = computed(() =>
    Math.max(0, subtotal.value - couponDiscount.value)
  )

  // Load guest cart from localStorage
  const loadGuestCart = () => {
    try {
      const saved = localStorage.getItem(GUEST_CART_KEY)
      if (saved) {
        items.value = JSON.parse(saved)
      }
    } catch (error) {
      console.error('Failed to load guest cart:', error)
    }
  }

  // Save guest cart to localStorage
  const saveGuestCart = () => {
    try {
      localStorage.setItem(GUEST_CART_KEY, JSON.stringify(items.value))
    } catch (error) {
      console.error('Failed to save guest cart:', error)
    }
  }

  // Clear guest cart from localStorage
  const clearGuestCart = () => {
    localStorage.removeItem(GUEST_CART_KEY)
  }

  // Watch items for guest users
  watch(items, () => {
    if (!authStore.isAuthenticated) {
      saveGuestCart()
    }
  }, { deep: true })

  const toggleDrawer = () => {
    isDrawerOpen.value = !isDrawerOpen.value
  }

  const openDrawer = () => {
    isDrawerOpen.value = true
  }

  const closeDrawer = () => {
    isDrawerOpen.value = false
  }

  /** Hydrate cart state from a server CartResource response object. */
  const hydrateFromServer = (data: any) => {
    items.value = (data.items ?? []).map((item: any) => ({
      id:        item.id,
      productId: item.listing?.product?.id || item.product?.id,
      listingId: item.listing_id,
      variantId: item.variant_id || undefined,
      name:      item.listing?.product?.name || item.product?.name,
      price:     item.price,
      image:     item.listing?.product?.image || item.product?.image,
      quantity:  item.quantity,
    }))
    appliedCoupon.value  = data.coupon ?? null
    couponDiscount.value = parseFloat(data.coupon_discount ?? 0)
  }

  // Fetch cart from server (authenticated buyers only)
  const fetchCart = async () => {
    if (!authStore.isAuthenticated) {
      loadGuestCart()
      return
    }

    // Super admin and store owners cannot use customer cart
    if (authStore.isSuperAdmin || authStore.isStoreOwner) {
      return
    }

    isLoading.value = true
    try {
      const response = await cartService.getCart()
      if (response.success) {
        hydrateFromServer(response.data)
      }
    } catch (error) {
      console.error('Failed to fetch cart:', error)
      // Fallback to guest cart
      loadGuestCart()
    } finally {
      isLoading.value = false
    }
  }

  // Merge guest cart with user cart after login
  const mergeGuestCart = async () => {
    const guestItems = [...items.value]
    if (guestItems.length === 0) return

    try {
      // Fetch user cart first
      await fetchCart()

      // Add guest items to server
      for (const item of guestItems) {
        try {
          if (item.listingId) {
            await cartService.addItem(item.listingId, item.quantity, item.variantId)
          }
        } catch (error) {
          console.error('Failed to merge item:', error)
        }
      }

      // Refresh cart from server
      await fetchCart()

      // Clear guest cart
      clearGuestCart()
    } catch (error) {
      console.error('Failed to merge cart:', error)
    }
  }

  const addItem = async (item: CartItem) => {
    // Find existing item by productId AND variantId (if exists)
    const existing = items.value.find(i =>
      i.productId === item.productId &&
      (item.variantId ? i.variantId === item.variantId : !i.variantId)
    )

    if (existing) {
      existing.quantity += item.quantity
    } else {
      items.value.push(item)
    }
    openDrawer()

    // For authenticated users, sync with server
    if (authStore.isAuthenticated && item.listingId) {
      try {
        await cartService.addItem(item.listingId, item.quantity, item.variantId)
      } catch (error) {
        console.error('Failed to add item to server:', error)
      }
    }
  }

  const updateQuantity = async (itemId: number, quantity: number) => {
    if (quantity <= 0) {
      removeItem(itemId)
      return
    }

    // Match by unique cart item id only (not productId to avoid conflicts with variants)
    const item = items.value.find(i => i.id === itemId)
    if (item) {
      item.quantity = quantity
    }

    // For authenticated users, sync with server
    if (authStore.isAuthenticated) {
      try {
        await cartService.updateItem(itemId, quantity)
      } catch (error) {
        console.error('Failed to update item on server:', error)
      }
    }
  }

  const removeItem = async (itemId: number) => {
    // Remove by unique cart item id only (not productId to avoid conflicts with variants)
    items.value = items.value.filter(i => i.id !== itemId)

    // For authenticated users, sync with server
    if (authStore.isAuthenticated) {
      try {
        await cartService.removeItem(itemId)
      } catch (error) {
        console.error('Failed to remove item from server:', error)
      }
    }
  }

  const clearCart = async () => {
    items.value = []
    appliedCoupon.value  = null
    couponDiscount.value = 0

    // For authenticated users, sync with server
    if (authStore.isAuthenticated) {
      try {
        await cartService.clearCart()
      } catch (error) {
        console.error('Failed to clear cart on server:', error)
      }
    } else {
      clearGuestCart()
    }
  }

  /** Apply a coupon code (authenticated users only). Returns error message or null on success. */
  const applyCoupon = async (code: string): Promise<string | null> => {
    if (!authStore.isAuthenticated) return 'برای استفاده از کوپن باید وارد حساب خود شوید.'
    try {
      const response = await cartService.applyCoupon(code)
      if (response.success) {
        hydrateFromServer(response.data)
        return null
      }
      return response.message ?? 'خطا در اعمال کوپن.'
    } catch (e: any) {
      return e?.response?.data?.message ?? 'خطا در اعمال کوپن.'
    }
  }

  /** Remove the applied coupon (authenticated users only). */
  const removeCoupon = async (): Promise<void> => {
    if (!authStore.isAuthenticated) return
    try {
      const response = await cartService.removeCoupon()
      if (response.success) {
        hydrateFromServer(response.data)
      }
    } catch (error) {
      console.error('Failed to remove coupon:', error)
    }
  }

  // Initialize cart
  const initialize = async () => {
    if (authStore.isAuthenticated) {
      await fetchCart()
    } else {
      loadGuestCart()
    }
  }

  return {
    items,
    isDrawerOpen,
    isLoading,
    itemsCount,
    subtotal,
    total,
    appliedCoupon,
    couponDiscount,
    toggleDrawer,
    openDrawer,
    closeDrawer,
    fetchCart,
    addItem,
    updateQuantity,
    removeItem,
    clearCart,
    mergeGuestCart,
    initialize,
    applyCoupon,
    removeCoupon,
  }
})
