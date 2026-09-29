import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export interface Toast {
  id: string
  type: 'success' | 'error' | 'warning' | 'info'
  title: string
  message?: string
  duration?: number
  persistent?: boolean
  action?: {
    label: string
    onClick: () => void
  }
}

export const useToastStore = defineStore('toast', () => {
  const toasts = ref<Toast[]>([])
  const maxToasts = 5

  const visibleToasts = computed(() => toasts.value.slice(-maxToasts))

  function generateId() {
    return Math.random().toString(36).substring(2, 9)
  }

  function add(toast: Omit<Toast, 'id'>) {
    const newToast: Toast = {
      ...toast,
      id: generateId(),
      duration: toast.duration ?? (toast.type === 'error' ? 8000 : 5000),
    }
    
    toasts.value.push(newToast)
    
    // Auto remove after duration
    if (!newToast.persistent && newToast.duration && newToast.duration > 0) {
      setTimeout(() => {
        remove(newToast.id)
      }, newToast.duration)
    }
    
    return newToast.id
  }

  function remove(id: string) {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index !== -1) {
      toasts.value.splice(index, 1)
    }
  }

  function clear() {
    toasts.value = []
  }

  // Convenience methods
  function success(title: string, message?: string, options?: Partial<Toast>) {
    return add({ type: 'success', title, message, ...options })
  }

  function error(title: string, message?: string, options?: Partial<Toast>) {
    return add({ type: 'error', title, message, duration: 8000, ...options })
  }

  function warning(title: string, message?: string, options?: Partial<Toast>) {
    return add({ type: 'warning', title, message, ...options })
  }

  function info(title: string, message?: string, options?: Partial<Toast>) {
    return add({ type: 'info', title, message, ...options })
  }

  return {
    toasts,
    visibleToasts,
    add,
    remove,
    clear,
    success,
    error,
    warning,
    info,
  }
})