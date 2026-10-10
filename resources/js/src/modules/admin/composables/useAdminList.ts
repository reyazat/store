import { ref, reactive, watch, onMounted } from 'vue'

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface AdminListOptions<F extends object> {
  defaultFilters: F
  fetchFn: (filters: F & { page: number }) => Promise<{ data: unknown[]; meta: PaginationMeta }>
  debounceMs?: number
}

export function useAdminList<T, F extends object>(options: AdminListOptions<F>) {
  const { defaultFilters, fetchFn, debounceMs = 350 } = options

  const items    = ref<T[]>([])
  const isLoading = ref(true)
  const errorMsg  = ref<string | null>(null)
  const page      = ref(1)

  const filters = reactive<F>({ ...defaultFilters } as F)

  const meta = ref<PaginationMeta>({
    current_page: 1,
    last_page:    1,
    per_page:     20,
    total:        0,
  })

  let debounceTimer: ReturnType<typeof setTimeout> | null = null

  const load = async () => {
    isLoading.value = true
    errorMsg.value  = null
    try {
      const result = await fetchFn({ ...(filters as F), page: page.value })
      items.value = result.data as T[]
      meta.value  = result.meta
    } catch {
      errorMsg.value = 'خطا در بارگذاری داده‌ها'
    } finally {
      isLoading.value = false
    }
  }

  const reload = () => {
    page.value = 1
    load()
  }

  const debouncedReload = () => {
    if (debounceTimer) clearTimeout(debounceTimer)
    debounceTimer = setTimeout(reload, debounceMs)
  }

  const goToPage = (p: number) => {
    if (p < 1 || p > meta.value.last_page) return
    page.value = p
    load()
  }

  watch(() => (filters as F & { per_page?: number }).per_page, () => reload())

  onMounted(load)

  return { items, isLoading, errorMsg, filters, meta, page, load, reload, debouncedReload, goToPage }
}
