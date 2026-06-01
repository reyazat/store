import { computed, reactive, ref, watch } from 'vue'
import { categoryService, type AdminCategoryDetail, type CategoryFormData } from '../services/categoryService'

const COMMON_ICONS = ['📱', '💻', '🏠', '👕', '💄', '⚽', '📚', '🧸', '🔧', '🎮', '🍕', '🚗', '✈️', '🎵', '📷', '🌿']

const PERSIAN_MAP: Record<string, string> = {
  'ا': 'a', 'آ': 'a', 'ب': 'b', 'پ': 'p', 'ت': 't', 'ث': 's', 'ج': 'j',
  'چ': 'ch', 'ح': 'h', 'خ': 'kh', 'د': 'd', 'ذ': 'z', 'ر': 'r', 'ز': 'z',
  'ژ': 'zh', 'س': 's', 'ش': 'sh', 'ص': 's', 'ض': 'z', 'ط': 't', 'ظ': 'z',
  'ع': 'a', 'غ': 'gh', 'ف': 'f', 'ق': 'q', 'ک': 'k', 'گ': 'g', 'ل': 'l',
  'م': 'm', 'ن': 'n', 'و': 'v', 'ه': 'h', 'ی': 'y', 'ئ': 'y', 'ء': '',
  'ة': 'h', 'ؤ': 'v', 'إ': 'a', 'أ': 'a',
  '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4',
  '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9',
}

function slugify(text: string): string {
  const transliterated = text
    .split('')
    .map((ch) => PERSIAN_MAP[ch] ?? ch)
    .join('')

  return transliterated
    .toLowerCase()
    .trim()
    .replace(/[\s_]+/g, '-')
    .replace(/[^\w\-]+/g, '')
    .replace(/-{2,}/g, '-')
    .replace(/^-+|-+$/g, '')
}

function makeEmptyForm(): CategoryFormData {
  return {
    parent_id:        null,
    name:             '',
    slug:             '',
    description:      '',
    image:            null,
    image_file:       null,
    icon:             '',
    meta_title:       '',
    meta_description: '',
    status:           1,
    sorted:           0,
  }
}

export function useCategoryForm() {
  const isOpen      = ref(false)
  const isEditing   = ref(false)
  const isSubmitting = ref(false)
  const editingSlug  = ref<string | null>(null)
  const editingId    = ref<number | null>(null)
  const editingChildrenIds = ref<number[]>([])
  const editingParent = ref<{ id: number; name: string; slug: string } | null>(null)
  const imagePreview = ref<string | null>(null)
  const errors       = reactive<Record<string, string>>({})
  const parentOptions = ref<Array<{ id: number; name: string; slug: string; parent_id: number | null; level: number }>>([])

  const form = reactive<CategoryFormData>(makeEmptyForm())

  let autoSlugEnabled       = true
  let slugSetByProgram       = false

  watch(() => form.name, (val) => {
    if (!isEditing.value && autoSlugEnabled) {
      slugSetByProgram = true
      form.slug = slugify(val)
    }
  })

  watch(() => form.slug, () => {
    if (slugSetByProgram) {
      slugSetByProgram = false
      return
    }
    autoSlugEnabled = false
  })

  function resetErrors(): void {
    Object.keys(errors).forEach((k) => delete errors[k])
  }

  function resetForm(): void {
    const empty = makeEmptyForm()
    Object.assign(form, empty)
    imagePreview.value = null
    autoSlugEnabled    = true
    slugSetByProgram   = false
    resetErrors()
  }

  async function loadParentOptions(): Promise<void> {
    try {
      parentOptions.value = await categoryService.getParentOptions()
    } catch {
      parentOptions.value = []
    }
  }

  async function openCreate(): Promise<void> {
    resetForm()
    isEditing.value  = false
    editingSlug.value = null
    await loadParentOptions()
    isOpen.value = true
  }

  async function openEdit(slug: string): Promise<void> {
    resetForm()
    isEditing.value   = true
    editingSlug.value = slug

    const detail: AdminCategoryDetail = await categoryService.getById(slug)
    editingId.value = detail.id
    editingChildrenIds.value = detail.children.map(c => c.id)
    editingParent.value = detail.parent

    await loadParentOptions()

    Object.assign(form, {
      parent_id:        detail.parent_id,
      name:             detail.name,
      slug:             detail.slug,
      description:      detail.description ?? '',
      image:            detail.image,
      image_file:       null,
      icon:             detail.icon ?? '',
      meta_title:       detail.meta_title ?? '',
      meta_description: detail.meta_description ?? '',
      status:           detail.status,
      sorted:           detail.sorted,
    })
    imagePreview.value = detail.image ?? null
    autoSlugEnabled    = false
    isOpen.value       = true
  }

  const availableParentOptions = computed(() => {
    // When creating (editingId is null), show all categories
    // When editing, filter out self and children to prevent circular reference
    if (!isEditing.value || editingId.value === null) {
      return parentOptions.value
    }
    return parentOptions.value.filter(p =>
      p.id !== editingId.value &&
      !editingChildrenIds.value.includes(p.id)
    )
  })

  function close(): void {
    isOpen.value = false
  }

  function onImageChange(event: Event): void {
    const input = event.target as HTMLInputElement
    const file  = input.files?.[0] ?? null
    if (!file) return

    form.image_file = file
    form.image      = null

    const reader = new FileReader()
    reader.onload = (e) => { imagePreview.value = e.target?.result as string }
    reader.readAsDataURL(file)
  }

  function clearImage(): void {
    form.image_file    = null
    form.image         = null
    imagePreview.value = null
  }

  function mapErrors(serverErrors: Record<string, string[]>): void {
    resetErrors()
    Object.entries(serverErrors).forEach(([field, messages]) => {
      errors[field] = messages[0]
    })
  }

  async function submit(): Promise<AdminCategoryDetail | null> {
    resetErrors()
    isSubmitting.value = true

    try {
      const result = isEditing.value && editingSlug.value
        ? await categoryService.update(editingSlug.value, form)
        : await categoryService.create(form)

      isOpen.value = false
      return result
    } catch (err: unknown) {
      const axiosErr = err as { response?: { data?: { errors?: Record<string, string[]> } } }
      if (axiosErr.response?.data?.errors) {
        mapErrors(axiosErr.response.data.errors)
      }
      return null
    } finally {
      isSubmitting.value = false
    }
  }

  return {
    isOpen,
    isEditing,
    isSubmitting,
    editingId,
    editingParent,
    form,
    errors,
    imagePreview,
    parentOptions,
    availableParentOptions,
    commonIcons: COMMON_ICONS,
    openCreate,
    openEdit,
    close,
    onImageChange,
    clearImage,
    submit,
  }
}
