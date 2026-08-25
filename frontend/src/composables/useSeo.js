import { onBeforeUnmount, watchEffect } from 'vue'

const DEFAULT_TITLE = 'Connextion — Find talent. Find opportunities. Anywhere.'

function setMeta(selector, attrs) {
  let el = document.head.querySelector(selector)
  if (!el) {
    el = document.createElement(attrs.rel ? 'link' : 'meta')
    document.head.appendChild(el)
  }
  Object.entries(attrs).forEach(([key, value]) => el.setAttribute(key, value))
  return el
}

/**
 * Sets the page title, description and canonical URL. Location and search
 * pages are indexable in their own right, so each needs its own canonical.
 */
export function useSeo(source) {
  watchEffect(() => {
    const { title, description, canonical } = typeof source === 'function' ? source() : source

    document.title = title ? `${title} | Connextion` : DEFAULT_TITLE

    if (description) {
      setMeta('meta[name="description"]', { name: 'description', content: description })
      setMeta('meta[property="og:description"]', { property: 'og:description', content: description })
    }

    setMeta('meta[property="og:title"]', { property: 'og:title', content: title || DEFAULT_TITLE })

    // Canonical always points at the clean path, never at the filtered query
    // string, so filter permutations do not compete with each other.
    const href = canonical
      ? new URL(canonical, window.location.origin).href
      : window.location.origin + window.location.pathname

    setMeta('link[rel="canonical"]', { rel: 'canonical', href })
  })

  onBeforeUnmount(() => {
    document.title = DEFAULT_TITLE
  })
}
