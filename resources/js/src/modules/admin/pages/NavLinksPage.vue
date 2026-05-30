<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Plus, Pencil, Trash2, X, Save } from 'lucide-vue-next'
import { navLinkService } from '../services/navLinkService'
import type { NavLink, NavLinkFormData, NavLinkGroup } from '../types/navLink'

const links = ref<NavLink[]>([])
const isLoading = ref(true)
const isModalOpen = ref(false)
const isSaving = ref(false)
const isDeleting = ref<number | null>(null)
const editingLink = ref<NavLink | null>(null)
const activeGroup = ref<NavLinkGroup>('header')
const actionError = ref<string | null>(null)

function showError(msg: string) {
  actionError.value = msg
  setTimeout(() => (actionError.value = null), 4000)
}

const groupLabels: Record<NavLinkGroup, string> = {
  header:          'منوی اصلی (هدر)',
  footer_quick:    'دسترسی سریع (فوتر)',
  footer_service:  'خدمات مشتریان (فوتر)',
}

const groupOptions: { value: NavLinkGroup; label: string }[] = [
  { value: 'header',         label: 'منوی اصلی (هدر)' },
  { value: 'footer_quick',   label: 'دسترسی سریع (فوتر)' },
  { value: 'footer_service', label: 'خدمات مشتریان (فوتر)' },
]

const defaultForm = (): NavLinkFormData => ({
  label:      '',
  url:        '',
  group:      activeGroup.value,
  target:     '_self',
  sort_order: 0,
  is_active:  true,
})

const form = ref<NavLinkFormData>(defaultForm())

const filteredLinks = computed(() =>
  links.value.filter(l => l.group === activeGroup.value)
    .sort((a, b) => a.sort_order - b.sort_order)
)

const loadLinks = async () => {
  isLoading.value = true
  try {
    links.value = await navLinkService.getAll()
  } catch {
    // keep empty
  } finally {
    isLoading.value = false
  }
}

const openCreate = () => {
  editingLink.value = null
  form.value = defaultForm()
  isModalOpen.value = true
}

const openEdit = (link: NavLink) => {
  editingLink.value = link
  form.value = {
    label:      link.label,
    url:        link.url,
    group:      link.group,
    target:     link.target,
    sort_order: link.sort_order,
    is_active:  link.is_active,
  }
  isModalOpen.value = true
}

const closeModal = () => {
  isModalOpen.value = false
  editingLink.value = null
}

const save = async () => {
  isSaving.value = true
  try {
    if (editingLink.value) {
      const updated = await navLinkService.update(editingLink.value.id, form.value)
      const index = links.value.findIndex(l => l.id === editingLink.value!.id)
      if (index !== -1) links.value[index] = updated
    } else {
      const created = await navLinkService.create(form.value)
      links.value.push(created)
    }
    closeModal()
  } catch {
    showError('خطا در ذخیره لینک')
  } finally {
    isSaving.value = false
  }
}

const remove = async (link: NavLink) => {
  if (!confirm(`آیا از حذف لینک "${link.label}" مطمئن هستید؟`)) return
  isDeleting.value = link.id
  try {
    await navLinkService.delete(link.id)
    links.value = links.value.filter(l => l.id !== link.id)
  } catch {
    showError('خطا در حذف لینک')
  } finally {
    isDeleting.value = null
  }
}

const toggleActive = async (link: NavLink) => {
  try {
    const updated = await navLinkService.update(link.id, { is_active: !link.is_active })
    const index = links.value.findIndex(l => l.id === link.id)
    if (index !== -1) links.value[index] = updated
  } catch {
    showError('خطا در تغییر وضعیت لینک')
  }
}

onMounted(loadLinks)
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-secondary-800">مدیریت لینک‌ها</h1>
      <button
        type="button"
        @click="openCreate"
        class="flex items-center gap-2 px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition"
      >
        <Plus class="w-5 h-5" />
        لینک جدید
      </button>
    </div>

    <!-- Error Alert -->
    <Transition name="fade">
      <div v-if="actionError" class="flex items-center gap-2 mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
        {{ actionError }}
      </div>
    </Transition>

    <!-- Group Tabs -->
    <div class="flex gap-2 mb-4 flex-wrap">
      <button
        v-for="opt in groupOptions"
        :key="opt.value"
        type="button"
        @click="activeGroup = opt.value"
        :class="[
          'px-4 py-2 rounded-lg text-sm transition',
          activeGroup === opt.value
            ? 'bg-primary-500 text-white'
            : 'bg-white text-secondary-600 hover:bg-secondary-50 border border-secondary-200'
        ]"
      >
        {{ opt.label }}
      </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div v-if="isLoading" class="p-8 text-center">
        <div class="animate-spin w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full mx-auto"></div>
      </div>

      <table v-else class="w-full">
        <thead class="bg-secondary-50">
          <tr>
            <th class="px-4 py-3 text-right text-xs font-medium text-secondary-500">ترتیب</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-secondary-500">عنوان</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-secondary-500">آدرس</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-secondary-500">گروه</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-secondary-500">وضعیت</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-secondary-500">عملیات</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-secondary-100">
          <tr v-if="filteredLinks.length === 0">
            <td colspan="6" class="px-4 py-8 text-center text-secondary-400 text-sm">
              لینکی در این گروه وجود ندارد
            </td>
          </tr>
          <tr v-for="link in filteredLinks" :key="link.id" class="hover:bg-secondary-50">
            <td class="px-4 py-3 text-sm text-secondary-500 w-16">{{ link.sort_order }}</td>
            <td class="px-4 py-3 text-sm font-medium text-secondary-800">{{ link.label }}</td>
            <td class="px-4 py-3 text-sm text-secondary-500 font-mono" dir="ltr">{{ link.url }}</td>
            <td class="px-4 py-3 text-sm text-secondary-600">{{ groupLabels[link.group] }}</td>
            <td class="px-4 py-3">
              <button
                type="button"
                @click="toggleActive(link)"
                :class="[
                  'px-2 py-1 text-xs rounded-full transition',
                  link.is_active ? 'bg-green-100 text-green-700' : 'bg-secondary-100 text-secondary-500'
                ]"
              >
                {{ link.is_active ? 'فعال' : 'غیرفعال' }}
              </button>
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  @click="openEdit(link)"
                  class="p-1.5 hover:bg-secondary-100 rounded-lg transition"
                  title="ویرایش"
                >
                  <Pencil class="w-4 h-4 text-secondary-600" />
                </button>
                <button
                  type="button"
                  @click="remove(link)"
                  :disabled="isDeleting === link.id"
                  class="p-1.5 hover:bg-red-50 rounded-lg transition disabled:opacity-50"
                  title="حذف"
                >
                  <Trash2 class="w-4 h-4 text-red-500" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <div
        v-if="isModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="closeModal"
      >
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg" dir="rtl">
          <div class="flex items-center justify-between p-5 border-b border-secondary-100">
            <h2 class="text-lg font-bold text-secondary-800">
              {{ editingLink ? 'ویرایش لینک' : 'لینک جدید' }}
            </h2>
            <button type="button" @click="closeModal" class="p-1 hover:bg-secondary-100 rounded-lg">
              <X class="w-5 h-5 text-secondary-500" />
            </button>
          </div>

          <form @submit.prevent="save" class="p-5 space-y-4">
            <div>
              <label for="link-label" class="block text-sm font-medium text-secondary-700 mb-1">عنوان لینک</label>
              <input
                id="link-label"
                v-model="form.label"
                type="text"
                required
                class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
              />
            </div>

            <div>
              <label for="link-url" class="block text-sm font-medium text-secondary-700 mb-1">آدرس URL</label>
              <input
                id="link-url"
                v-model="form.url"
                type="text"
                required
                dir="ltr"
                class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
              />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label for="link-group" class="block text-sm font-medium text-secondary-700 mb-1">گروه</label>
                <select
                  id="link-group"
                  v-model="form.group"
                  class="w-full h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                >
                  <option v-for="opt in groupOptions" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </option>
                </select>
              </div>

              <div>
                <label for="link-target" class="block text-sm font-medium text-secondary-700 mb-1">باز شدن در</label>
                <select
                  id="link-target"
                  v-model="form.target"
                  class="w-full h-10 px-3 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                >
                  <option value="_self">همین صفحه</option>
                  <option value="_blank">تب جدید</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label for="link-order" class="block text-sm font-medium text-secondary-700 mb-1">ترتیب</label>
                <input
                  id="link-order"
                  v-model.number="form.sort_order"
                  type="number"
                  min="0"
                  class="w-full h-10 px-4 rounded-lg border border-secondary-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                />
              </div>

              <div class="flex items-end pb-1">
                <label class="flex items-center gap-2 cursor-pointer">
                  <input
                    v-model="form.is_active"
                    type="checkbox"
                    class="w-4 h-4 rounded border-secondary-300 text-primary-600"
                  />
                  <span class="text-sm text-secondary-700">فعال</span>
                </label>
              </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
              <button
                type="button"
                @click="closeModal"
                class="px-4 py-2 text-secondary-600 hover:bg-secondary-100 rounded-lg transition"
              >
                انصراف
              </button>
              <button
                type="submit"
                :disabled="isSaving"
                class="flex items-center gap-2 px-5 py-2 bg-primary-500 hover:bg-primary-600 disabled:opacity-60 text-white rounded-lg transition"
              >
                <Save class="w-4 h-4" />
                {{ isSaving ? 'در حال ذخیره...' : 'ذخیره' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
