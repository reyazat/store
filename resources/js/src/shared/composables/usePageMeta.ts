import { watch, isRef, onUnmounted } from 'vue'
import type { Ref } from 'vue'

export interface PageMetaOptions {
  title: Ref<string | null | undefined>
  description?: Ref<string | null | undefined>
  siteName?: Ref<string | undefined> | string
}

function setMetaTag(name: string, content: string): void {
  let el = document.querySelector<HTMLMetaElement>(`meta[name="${name}"]`)
  if (!el) {
    el = document.createElement('meta')
    el.setAttribute('name', name)
    document.head.appendChild(el)
  }
  el.setAttribute('content', content)
}

function removeMetaTag(name: string): void {
  document.querySelector(`meta[name="${name}"]`)?.remove()
}

export function usePageMeta({ title, description, siteName }: PageMetaOptions): void {
  const defaultTitle = document.title

  const resolveSiteName = (): string | undefined =>
    isRef(siteName) ? siteName.value : siteName

  const apply = () => {
    const resolvedTitle = title.value?.trim() || null
    const resolvedDesc  = description?.value?.trim() || null
    const resolvedSite  = resolveSiteName()

    if (resolvedTitle) {
      document.title = resolvedSite ? `${resolvedTitle} - ${resolvedSite}` : resolvedTitle
    }

    if (resolvedDesc) {
      setMetaTag('description', resolvedDesc)
    } else {
      removeMetaTag('description')
    }
  }

  const stopTitle    = watch(title, apply, { immediate: true })
  const stopDesc     = description ? watch(description, apply) : null
  const stopSiteName = isRef(siteName) ? watch(siteName, apply) : null

  onUnmounted(() => {
    stopTitle()
    stopDesc?.()
    stopSiteName?.()
    document.title = defaultTitle
    removeMetaTag('description')
  })
}
