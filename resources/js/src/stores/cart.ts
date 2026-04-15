import { defineStore } from 'pinia'
import { ref, computed, watch } from 'vue'
import type { CartItem } from '@shared/types'
import { cartService } from '@modules/cart/services'
import { useAuthStore } from './auth'

const GUEST_CART_KEY = 'guest_cart'

export const useCartStore = defineStore('cart', () => {
  const authStore = useAuthStore()
  const items = ref<CartItem[]>([])
  const isDrawerOpen = ref(false)
  const isLoading = ref(false)

  const itemsCount = computed(() => 
    items.value.reduce((sum, item) => sum + item.quantity, 0)
  )

  const total = computed(() => 
    items.value.reduce((sum, item) => sum + (item.price * item.quantity), 0)
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

  // Fetch cart from server (authenticated users only)
  const fetchCart = async () => {
    if (!authStore.isAuthenticated) {
      loadGuestCart()
      return
    }

    isLoading.value = true
    try {
      const response = await cartService.getCart()
      if (response.success) {
        items.value = response.data.items.map((item: any) => ({
          id: item.id,
          productId: item.listing?.product?.id || item.product?.id,
          listingId: item.listing_id,
          variantId: item.variant_id || undefined,
          name: item.listing?.product?.name || item.product?.name,
          price: item.price,
          image: item.listing?.product?.image || item.product?.image,
          quantity: item.quantity,
        }))
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
    
    const item = items.value.find(i => i.id === itemId || i.productId === itemId)
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
    items.value = items.value.filter(i => i.id !== itemId && i.productId !== itemId)
    
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
    total,
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
  }
})
