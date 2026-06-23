import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

/**
 * Composable that derives store-panel capability flags from the authenticated user's
 * Spatie permissions and organization role.
 *
 * Permission matrix (from CreateDefaultOrganizationRoles):
 *
 * Permission         | owner | admin | editor | user | member
 * -------------------|-------|-------|--------|------|-------
 * products.view      |   ✓   |   ✓   |   ✓    |  ✓   |   ✓
 * products.manage    |   ✓   |   ✓   |   ✓    |  ✗   |   ✗
 * orders.view        |   ✓   |   ✓   |   ✓    |  ✓   |   ✗
 * orders.manage      |   ✓   |   ✓   |   ✗    |  ✗   |   ✗
 * reviews.manage     |   ✓   |   ✓   |   ✓    |  ✓   |   ✗
 * coupons.manage     |   ✓   |   ✓   |   ✓    |  ✓   |   ✗
 * users.view         |   ✓   |   ✓   |   ✓    |  ✓   |   ✓
 * users.manage       |   ✓   |   ✓   |   ✗    |  ✗   |   ✗
 * content.manage     |   ✓   |   ✓   |   ✓    |  ✗   |   ✗
 * settings.view      |   ✓   |   ✓   |   ✗    |  ✓   |   ✗
 * settings.manage    |   ✓   |   ✓   |   ✗    |  ✗   |   ✗
 */
export function useStorePermissions() {
  const authStore = useAuthStore()

  // ─── Role shortcuts ──────────────────────────────────────────────────────

  const isOwner  = computed(() => authStore.hasRole('owner'))
  const isAdmin  = computed(() => authStore.hasRole('admin'))
  const isEditor = computed(() => authStore.hasRole('editor'))

  /** Human-readable label for the current user's role in this store */
  const currentRole = computed<string>(() => {
    if (isOwner.value)  return 'مالک'
    if (isAdmin.value)  return 'مدیر'
    if (isEditor.value) return 'ویرایشگر'
    if (authStore.hasRole('user'))   return 'کاربر'
    if (authStore.hasRole('member')) return 'عضو'
    return ''
  })

  const currentRoleKey = computed<string>(() => {
    if (isOwner.value)  return 'owner'
    if (isAdmin.value)  return 'admin'
    if (isEditor.value) return 'editor'
    if (authStore.hasRole('user'))   return 'user'
    if (authStore.hasRole('member')) return 'member'
    return ''
  })

  // ─── Capability flags ─────────────────────────────────────────────────────

  /** Can add / edit / delete products */
  const canManageProducts = computed(() =>
    authStore.hasPermission('products.manage')
  )

  /** Can view orders list and order detail */
  const canViewOrders = computed(() =>
    authStore.hasPermission('orders.view')
  )

  /** Can update order status, add notes */
  const canManageOrders = computed(() =>
    authStore.hasPermission('orders.manage')
  )

  /** Can view team member list */
  const canViewMembers = computed(() =>
    authStore.hasPermission('users.view')
  )

  /** Can add / remove / change roles of store members */
  const canManageMembers = computed(() =>
    authStore.hasPermission('users.manage')
  )

  /** Can send / cancel / resend email invitations */
  const canInviteMembers = computed(() =>
    authStore.hasPermission('users.invite')
  )

  /** Can manage coupons (create, update, delete) */
  const canManageCoupons = computed(() =>
    authStore.hasPermission('coupons.manage')
  )

  /** Can moderate product reviews (approve, reject, reply) */
  const canManageReviews = computed(() =>
    authStore.hasPermission('reviews.manage')
  )

  /** Can view store settings / profile */
  const canViewSettings = computed(() =>
    authStore.hasPermission('settings.view')
  )

  /** Can update store profile & settings */
  const canManageSettings = computed(() =>
    authStore.hasPermission('settings.manage')
  )

  return {
    isOwner,
    isAdmin,
    isEditor,
    currentRole,
    currentRoleKey,
    canManageProducts,
    canViewOrders,
    canManageOrders,
    canViewMembers,
    canManageMembers,
    canInviteMembers,
    canManageCoupons,
    canManageReviews,
    canViewSettings,
    canManageSettings,
  }
}
