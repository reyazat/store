import { ref, reactive, computed } from 'vue'
import { productService } from '../services/productService'
import type { AdminProductDetail, AdminProductFormData, AdminProductCategory, AdminProductManufacturer, AdminProductImage, AdminOption } from '../types/product'

function slugify(text: string): string {
  return text
    .toLowerCase()
    .trim()
    .replace(/[\s_]+/g, '-')
    .replace(/[^\w\-]+/g, '')
    .replace(/\-\-+/g, '-')
    .replace(/^-+/, '')
    .replace(/-+$/, '')
}

function makeDefaultForm(): AdminProductFormData {
  return {
    name: '',
    slug: '',
    base_sku: '',
    short_description: '',
    description: '',
    main_image: '',
    main_image_file: null,
    manufacturer_id: null,
    tax_class_id: null,
    weight: '',
    length: '',
    width: '',
    height: '',
    date_available: '',
    meta_title: '',
    meta_description: '',
    sorted: 0,
    status: 1,
    category_ids: [],
    discount_percent: '',
    discount_start_at: '',
    discount_end_at: '',
    images: [],
    related_product_ids: [],
    options: [],
    variants: [],
  }
}

export type ProductDetailFetcher = (slug: string) => Promise<AdminProductDetail>

/**
 * @param onSuccess   callback after save
 * @param detailFetcher  optional override for fetching product detail when editing.
 *   Defaults to the admin endpoint (/admin/products/{slug}).
 *   Store panel passes its own store-scoped fetcher (/store/products/{slug})
 *   so store owners are not blocked by admin middleware.
 */
export function useProductForm(
  onSuccess: (product: AdminProductDetail) => void,
  detailFetcher?: ProductDetailFetcher,
) {
  const isOpen      = ref(false)
  const isSaving    = ref(false)
  const editingSlug = ref<string | null>(null)
  const errors      = ref<Record<string, string>>({})
  const serverError = ref<string | null>(null)

  const form = reactive<AdminProductFormData>(makeDefaultForm())

  const isEditing = computed(() => editingSlug.value !== null)

  const categories       = ref<AdminProductCategory[]>([])
  const manufacturers    = ref<AdminProductManufacturer[]>([])
  const taxClasses       = ref<Array<{ id: number; name: string; rate: number }>>([])
  const availableOptions = ref<AdminOption[]>([])

  const mainImagePreview  = ref<string | null>(null)
  const relatedProducts   = ref<Array<{ id: number; name: string; slug: string; main_image: string | null }>>([])
  const relatedSearch     = ref('')
  const relatedResults    = ref<Array<{ id: number; name: string; slug: string; main_image: string | null }>>([])
  let relatedDebounce: ReturnType<typeof setTimeout> | null = null

  async function loadLookups() {
    const [cats, mans, taxes, opts] = await Promise.all([
      productService.getCategories(),
      productService.getManufacturers(),
      productService.getTaxClasses(),
      productService.getAvailableOptions(),
    ])
    categories.value       = cats.data
    manufacturers.value    = mans.data
    taxClasses.value       = taxes
    availableOptions.value = opts
  }

  async function openCreate() {
    Object.assign(form, makeDefaultForm())
    editingSlug.value    = null
    mainImagePreview.value = null
    relatedProducts.value  = []
    relatedSearch.value    = ''
    relatedResults.value   = []
    errors.value         = {}
    serverError.value    = null
    isOpen.value         = true
    await loadLookups()
  }

  async function openEdit(slug: string) {
    errors.value      = {}
    serverError.value = null
    isOpen.value      = true
    await loadLookups()

    try {
      const fetch   = detailFetcher ?? productService.getAdminProductDetail.bind(productService)
      const product = await fetch(slug)
      editingSlug.value = product.slug

      Object.assign(form, {
        name:              product.name,
        slug:              product.slug,
        base_sku:         product.base_sku,
        short_description: product.short_description ?? '',
        description:      product.description ?? '',
        main_image:       product.main_image ?? '',
        main_image_file:  null,
        manufacturer_id:  product.manufacturer_id ?? null,
        tax_class_id:     product.tax_class_id,
        weight:           product.weight,
        length:           product.length,
        width:            product.width,
        height:           product.height,
        date_available:   product.date_available ?? '',
        meta_title:       product.meta_title ?? '',
        meta_description: product.meta_description ?? '',
        sorted:           product.sorted,
        status:           product.status,
        category_ids:     product.categories.map(c => c.id),
        discount_percent: product.discount_percent ?? '',
        discount_start_at: product.discount_start_at
          ? product.discount_start_at.substring(0, 10)
          : '',
        discount_end_at: product.discount_end_at
          ? product.discount_end_at.substring(0, 10)
          : '',
        images:              (product.images ?? []).map(img => ({ ...img })),
        related_product_ids: (product.related_products ?? []).map(r => r.id),
        options: (product.options ?? []).map(opt => ({
          option_id: opt.id,
          required:  opt.required ?? false,
        })),
        variants: (product.variants ?? []).map(v => ({
          id:           v.id,
          sku:          v.sku,
          barcode:      v.barcode ?? '',
          weight:       v.weight ?? '',
          length:       v.length ?? '',
          width:        v.width ?? '',
          height:       v.height ?? '',
          status:       v.status,
          price:        v.price ?? '',
          stock:        v.stock ?? 0,
          option_values: v.option_values.map(ov => ({
            option_id:       ov.option_id,
            option_value_id: ov.option_value_id,
          })),
        })),
      })

      mainImagePreview.value = product.main_image ?? null
      relatedProducts.value  = product.related_products ?? []
    } catch {
      serverError.value = 'خطا در بارگذاری اطلاعات محصول'
    }
  }

  function close() {
    isOpen.value         = false
    relatedSearch.value  = ''
    relatedResults.value = []
    if (relatedDebounce) {
      clearTimeout(relatedDebounce)
      relatedDebounce = null
    }
  }

  function autoSlug() {
    if (!isEditing.value) {
      form.slug = slugify(form.name)
    }
  }

  function onMainImageChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0]
    if (!file) return
    form.main_image_file = file
    mainImagePreview.value = URL.createObjectURL(file)
  }

  function removeMainImage() {
    if (mainImagePreview.value && mainImagePreview.value.startsWith('blob:')) {
      URL.revokeObjectURL(mainImagePreview.value)
    }
    form.main_image      = ''
    form.main_image_file = null
    mainImagePreview.value = null
  }

  function addGalleryImages(event: Event) {
    const files = Array.from((event.target as HTMLInputElement).files ?? [])
    files.forEach(file => {
      const img: AdminProductImage = {
        file_name: file.name,
        sorted:    form.images.length,
        _preview:  URL.createObjectURL(file),
        _file:     file,
      }
      form.images.push(img)
    })
    ;(event.target as HTMLInputElement).value = ''
  }

  function removeGalleryImage(index: number) {
    const img = form.images[index]
    if (img._preview && img._preview.startsWith('blob:')) {
      URL.revokeObjectURL(img._preview)
    }
    if (img.id) {
      form.images[index] = { ...img, _toDelete: true }
    } else {
      form.images.splice(index, 1)
    }
  }

  function toggleCategory(id: number) {
    const idx = form.category_ids.indexOf(id)
    if (idx === -1) form.category_ids.push(id)
    else form.category_ids.splice(idx, 1)
  }

  function removeRelated(id: number) {
    form.related_product_ids = form.related_product_ids.filter(rid => rid !== id)
    relatedProducts.value    = relatedProducts.value.filter(r => r.id !== id)
  }

  function addRelated(product: { id: number; name: string; slug: string; main_image: string | null }) {
    if (form.related_product_ids.includes(product.id)) return
    form.related_product_ids.push(product.id)
    relatedProducts.value.push(product)
    relatedSearch.value  = ''
    relatedResults.value = []
  }

  async function searchRelated() {
    if (relatedDebounce) clearTimeout(relatedDebounce)
    relatedDebounce = setTimeout(async () => {
      relatedResults.value = await productService.searchRelated(
        relatedSearch.value,
        editingSlug.value,
      )
    }, 300)
  }

  function validate(): boolean {
    const e: Record<string, string> = {}
    if (!form.name.trim())     e.name     = 'نام محصول الزامی است'
    if (!form.slug.trim())     e.slug     = 'Slug الزامی است'
    if (!form.base_sku.trim()) e.base_sku = 'SKU الزامی است'
    if (!form.tax_class_id)    e.tax_class_id = 'کلاس مالیاتی الزامی است'
    if (!mainImagePreview.value && !form.main_image) e.main_image = 'تصویر اصلی الزامی است'
    errors.value = e
    return Object.keys(e).length === 0
  }

  async function submit() {
    if (!validate()) return
    isSaving.value    = true
    serverError.value = null
    try {
      let result: AdminProductDetail
      if (isEditing.value && editingSlug.value) {
        result = await productService.update(editingSlug.value, form as AdminProductFormData)
      } else {
        result = await productService.create(form as AdminProductFormData)
      }
      onSuccess(result)
      close()
    } catch (err: unknown) {
      const axiosErr = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
      if (axiosErr.response?.data?.errors) {
        const mapped: Record<string, string> = {}
        Object.entries(axiosErr.response.data.errors).forEach(([key, msgs]) => {
          mapped[key] = msgs[0]
        })
        errors.value = mapped
      } else {
        serverError.value = axiosErr.response?.data?.message ?? 'خطا در ذخیره محصول'
      }
    } finally {
      isSaving.value = false
    }
  }

  return {
    isOpen, isSaving, isEditing, editingSlug,
    form, errors, serverError,
    categories, manufacturers, taxClasses, availableOptions,
    mainImagePreview, relatedProducts, relatedSearch, relatedResults,
    openCreate, openEdit, close,
    autoSlug, onMainImageChange, removeMainImage,
    addGalleryImages, removeGalleryImage,
    toggleCategory, addRelated, removeRelated, searchRelated,
    submit,
  }
}
