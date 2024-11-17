// useTheme.js
import { ref, watch } from 'vue'

export function useTheme() {
  const theme = ref(localStorage.getItem('theme') || 'light')

  const toggleTheme = () => {
    theme.value = theme.value === 'light' ? 'dark' : 'light'
  }

  watch(theme, (newTheme) => {
    document.body.className = newTheme
    localStorage.setItem('theme', newTheme)
  }, { immediate: true })

  return { theme, toggleTheme }
}