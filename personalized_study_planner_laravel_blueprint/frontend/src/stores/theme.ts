import { defineStore } from 'pinia'
import { ref, watch, computed } from 'vue'

export const useThemeStore = defineStore('theme', () => {
  const isDark = ref(false)
  const systemPrefersDark = ref(false)
  const initialized = ref(false)

  // Initialize theme from localStorage or system preference
  function initTheme() {
    if (initialized.value) return
    
    const stored = localStorage.getItem('theme')
    const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches
    
    systemPrefersDark.value = systemDark
    
    if (stored) {
      isDark.value = stored === 'dark'
    } else {
      isDark.value = systemDark
    }
    
    applyTheme()
    initialized.value = true
    
    // Listen for system preference changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
      systemPrefersDark.value = e.matches
      if (!localStorage.getItem('theme')) {
        isDark.value = e.matches
        applyTheme()
      }
    })
  }

  function applyTheme() {
    const html = document.documentElement
    if (isDark.value) {
      html.classList.add('dark')
    } else {
      html.classList.remove('dark')
    }
    
    // Update meta theme-color
    const metaThemeColor = document.querySelector('meta[name="theme-color"]')
    if (metaThemeColor) {
      metaThemeColor.setAttribute('content', isDark.value ? '#1a1a2e' : '#FFC0CB')
    }
    
    // Update CSS custom properties for brand colors
    const root = document.documentElement
    if (isDark.value) {
      root.style.setProperty('--color-brand-background', '#1a1a2e')
      root.style.setProperty('--color-brand-navbar-cards', '#1e1e2f')
      root.style.setProperty('--color-brand-navbar-cards-alt', '#252542')
      root.style.setProperty('--color-brand-borders', '#3d3d5c')
      root.style.setProperty('--color-brand-text-primary', '#f5f0f2')
      root.style.setProperty('--color-brand-text-secondary', '#c8b8c0')
    } else {
      root.style.setProperty('--color-brand-background', '#FFC0CB')
      root.style.setProperty('--color-brand-navbar-cards', '#FFF9FA')
      root.style.setProperty('--color-brand-navbar-cards-alt', '#FFF5F7')
      root.style.setProperty('--color-brand-borders', '#F3D2DA')
      root.style.setProperty('--color-brand-text-primary', '#33222A')
      root.style.setProperty('--color-brand-text-secondary', '#7A5C66')
    }
  }

  function toggleTheme() {
    isDark.value = !isDark.value
    localStorage.setItem('theme', isDark.value ? 'dark' : 'light')
    applyTheme()
  }

  function setTheme(theme: 'light' | 'dark' | 'system') {
    if (theme === 'system') {
      localStorage.removeItem('theme')
      isDark.value = systemPrefersDark.value
    } else {
      localStorage.setItem('theme', theme)
      isDark.value = theme === 'dark'
    }
    applyTheme()
  }

  const currentTheme = computed(() => {
    if (!localStorage.getItem('theme')) return 'system'
    return isDark.value ? 'dark' : 'light'
  })

  return {
    isDark,
    systemPrefersDark,
    initialized,
    currentTheme,
    initTheme,
    toggleTheme,
    setTheme,
    applyTheme,
  }
})