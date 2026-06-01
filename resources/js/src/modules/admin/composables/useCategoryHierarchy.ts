import { ref, computed, type Ref } from 'vue'
import { categoryService, type AdminCategory } from '../services/categoryService'

export type SortMode = 'none' | 'root' | 'children'
export type SortStatus = 'idle' | 'saving' | 'saved' | 'error'

export interface CategoryNode extends AdminCategory {
  level: number
  children: CategoryNode[]
}

interface UseCategoryHierarchyOptions {
  categories: Ref<AdminCategory[]>
  onReorder: () => void
}

export function useCategoryHierarchy({ categories, onReorder }: UseCategoryHierarchyOptions) {
  // Sort mode state
  const sortMode = ref<SortMode>('none')
  const sortStatus = ref<SortStatus>('idle')
  const sortTargetParentId = ref<number | null>(null) // null means sorting root categories
  const sortTargetParentName = ref<string>('')

  // Drag state
  const dragList = ref<AdminCategory[]>([])
  const dragSrcId = ref<number | null>(null)
  const dragOverId = ref<number | null>(null)

  // Build hierarchical tree from flat list
  const categoryTree = computed<CategoryNode[]>(() => {
    const catMap = new Map<number, CategoryNode>()
    const roots: CategoryNode[] = []

    // First pass: create nodes
    categories.value.forEach(cat => {
      catMap.set(cat.id, { ...cat, level: 0, children: [] })
    })

    // Second pass: build tree
    categories.value.forEach(cat => {
      const node = catMap.get(cat.id)!
      if (cat.parent_id === null) {
        roots.push(node)
      } else {
        const parent = catMap.get(cat.parent_id)
        if (parent) {
          node.level = parent.level + 1
          parent.children.push(node)
        }
      }
    })

    // Sort each level by sorted field
    const sortByOrder = (a: CategoryNode, b: CategoryNode) => a.sorted - b.sorted
    roots.sort(sortByOrder)
    roots.forEach(root => {
      root.children.sort(sortByOrder)
    })

    return roots
  })

  // Flatten tree for display (with visual indicators)
  const flattenedTree = computed<CategoryNode[]>(() => {
    const result: CategoryNode[] = []
    const traverse = (nodes: CategoryNode[]) => {
      nodes.forEach(node => {
        result.push(node)
        if (node.children.length > 0) {
          traverse(node.children)
        }
      })
    }
    traverse(categoryTree.value)
    return result
  })

  // Get only root categories for root sorting
  const rootCategories = computed(() =>
    categories.value.filter(c => c.parent_id === null).sort((a, b) => a.sorted - b.sorted)
  )

  // Get children of specific parent for children sorting
  function getChildrenCategories(parentId: number): AdminCategory[] {
    return categories.value
      .filter(c => c.parent_id === parentId)
      .sort((a, b) => a.sorted - b.sorted)
  }

  // Enter sort mode for root categories
  function enterRootSortMode(): void {
    dragList.value = [...rootCategories.value]
    sortMode.value = 'root'
    sortTargetParentId.value = null
    sortTargetParentName.value = 'دسته‌های اصلی'
    sortStatus.value = 'idle'
  }

  // Enter sort mode for children of a specific parent
  function enterChildrenSortMode(parent: AdminCategory): void {
    dragList.value = getChildrenCategories(parent.id)
    sortMode.value = 'children'
    sortTargetParentId.value = parent.id
    sortTargetParentName.value = parent.name
    sortStatus.value = 'idle'
  }

  // Exit sort mode
  function exitSortMode(): void {
    sortMode.value = 'none'
    sortTargetParentId.value = null
    sortTargetParentName.value = ''
    dragList.value = []
    dragOverId.value = null
    dragSrcId.value = null
    sortStatus.value = 'idle'
  }

  // Check if any changes were made
  const isDirty = computed(() => {
    if (sortMode.value === 'none') return false

    let original: AdminCategory[] = []
    if (sortMode.value === 'root') {
      original = rootCategories.value
    } else if (sortMode.value === 'children' && sortTargetParentId.value !== null) {
      original = getChildrenCategories(sortTargetParentId.value)
    }

    if (dragList.value.length !== original.length) return false
    return dragList.value.some((item, i) => item.id !== original[i]?.id)
  })

  // Drag handlers
  function onDragStart(id: number): void {
    dragSrcId.value = id
  }

  function onDragOver(id: number): void {
    if (dragSrcId.value === null || dragSrcId.value === id) return

    const from = dragList.value.findIndex(c => c.id === dragSrcId.value)
    const to = dragList.value.findIndex(c => c.id === id)
    if (from === -1 || to === -1 || from === to) return

    dragOverId.value = id

    const updated = [...dragList.value]
    const [moved] = updated.splice(from, 1)
    updated.splice(to, 0, moved)
    dragList.value = updated
  }

  function onDragEnd(): void {
    dragOverId.value = null
    dragSrcId.value = null
  }

  // Save order
  async function saveOrder(): Promise<void> {
    sortStatus.value = 'saving'
    try {
      const ids = dragList.value.map(c => c.id)
      await categoryService.reorder(ids)
      sortStatus.value = 'saved'
      onReorder()
      setTimeout(() => {
        if (sortStatus.value === 'saved') {
          exitSortMode()
        }
      }, 1500)
    } catch {
      sortStatus.value = 'error'
    }
  }

  // Check if a category can have its children sorted (has children)
  function canSortChildren(category: AdminCategory): boolean {
    return category.children_count > 0
  }

  return {
    // State
    sortMode,
    sortStatus,
    sortTargetParentId,
    sortTargetParentName,
    dragList,
    dragSrcId,
    dragOverId,
    isDirty,

    // Computed
    categoryTree,
    flattenedTree,
    rootCategories,

    // Methods
    enterRootSortMode,
    enterChildrenSortMode,
    exitSortMode,
    onDragStart,
    onDragOver,
    onDragEnd,
    saveOrder,
    canSortChildren,
    getChildrenCategories,
  }
}
