import { ref, computed } from 'vue'
import { categoryService, type AdminCategory } from '../services/categoryService'

export type SortStatus = 'idle' | 'saving' | 'saved' | 'error'

export function useCategorySort(getItems: () => AdminCategory[], onReordered: () => void) {
  const sortMode   = ref(false)
  const sortStatus = ref<SortStatus>('idle')

  const dragList   = ref<AdminCategory[]>([])
  const dragSrcId  = ref<number | null>(null)
  const dragOverId = ref<number | null>(null)

  const isDirty = computed(() => {
    if (!sortMode.value) return false
    const original = getItems()
    return dragList.value.some((item, i) => item.id !== original[i]?.id)
  })

  function enterSortMode(): void {
    dragList.value = [...getItems()]
    sortMode.value = true
    sortStatus.value = 'idle'
  }

  function exitSortMode(): void {
    sortMode.value   = false
    dragOverId.value = null
    dragSrcId.value  = null
    sortStatus.value = 'idle'
  }

  function onDragStart(id: number): void {
    dragSrcId.value = id
  }

  function onDragOver(id: number): void {
    if (dragSrcId.value === null || dragSrcId.value === id) return

    const from = dragList.value.findIndex((c) => c.id === dragSrcId.value)
    const to   = dragList.value.findIndex((c) => c.id === id)
    if (from === -1 || to === -1 || from === to) return

    dragOverId.value = id

    const updated = [...dragList.value]
    const [moved] = updated.splice(from, 1)
    updated.splice(to, 0, moved)
    dragList.value = updated
  }

  function onDragEnd(): void {
    dragOverId.value = null
    dragSrcId.value  = null
  }

  async function saveOrder(): Promise<void> {
    sortStatus.value = 'saving'
    try {
      await categoryService.reorder(dragList.value.map((c) => c.id))
      sortMode.value   = false
      sortStatus.value = 'saved'
      onReordered()
      setTimeout(() => { sortStatus.value = 'idle' }, 2000)
    } catch {
      sortStatus.value = 'error'
    }
  }

  return {
    sortMode,
    sortStatus,
    dragList,
    dragSrcId,
    dragOverId,
    isDirty,
    enterSortMode,
    exitSortMode,
    onDragStart,
    onDragOver,
    onDragEnd,
    saveOrder,
  }
}
