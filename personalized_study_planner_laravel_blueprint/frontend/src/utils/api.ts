import axios, { AxiosError, AxiosInstance, InternalAxiosRequestConfig } from 'axios'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'

// Create axios instance
const api: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api',
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
  withCredentials: true,
})

let isRefreshing = false
let failedQueue: Array<{
  resolve: (value: unknown) => void
  reject: (reason: unknown) => void
}> = []

function processQueue(error: Error | null, token: string | null = null) {
  failedQueue.forEach(({ resolve, reject }) => {
    if (error) {
      reject(error)
    } else {
      resolve(token)
    }
  })
  failedQueue = []
}

// Request interceptor
api.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const authStore = useAuthStore()
    
    if (authStore.token && config.headers) {
      config.headers.Authorization = `Bearer ${authStore.token}`
    }
    
    // Add request ID for tracking
    config.headers['X-Request-ID'] = crypto.randomUUID()
    
    return config
  },
  (error) => Promise.reject(error)
)

// Response interceptor
api.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    const originalRequest = error.config as InternalAxiosRequestConfig & { _retry?: boolean }
    const authStore = useAuthStore()
    const toastStore = useToastStore()
    
    // Handle 401 Unauthorized
    if (error.response?.status === 401 && !originalRequest._retry) {
      if (isRefreshing) {
        // Wait for token refresh
        return new Promise((resolve, reject) => {
          failedQueue.push({ resolve, reject })
        }).then((token) => {
          if (originalRequest.headers) {
            originalRequest.headers.Authorization = `Bearer ${token}`
          }
          return api(originalRequest)
        }).catch((err) => Promise.reject(err))
      }
      
      originalRequest._retry = true
      isRefreshing = true
      
      try {
        // Try to refresh token via login endpoint (Sanctum doesn't have refresh by default)
        // We'll just logout and redirect
        await authStore.logout()
        toastStore.error('Session expired', 'Please log in again')
        window.location.href = '/login'
        return Promise.reject(error)
      } catch {
        return Promise.reject(error)
      } finally {
        isRefreshing = false
        processQueue(null, null)
      }
    }
    
    // Handle 403 Forbidden
    if (error.response?.status === 403) {
      toastStore.error('Access denied', 'You do not have permission to perform this action')
    }
    
    // Handle 422 Validation Errors
    if (error.response?.status === 422) {
      const data = error.response.data as { error?: { details?: Record<string, string[]>; message?: string } }
      const details = data.error?.details || {}
      const message = data.error?.message || 'Validation failed'
      
      // Show first validation error in toast
      const firstError = Object.values(details)[0]?.[0]
      if (firstError) {
        toastStore.error('Validation error', firstError)
      } else {
        toastStore.error('Validation error', message)
      }
    }
    
    // Handle 429 Rate Limited
    if (error.response?.status === 429) {
      const retryAfter = error.response.headers['retry-after'] || 60
      toastStore.warning('Too many requests', `Please wait ${retryAfter} seconds before trying again`, {
        duration: retryAfter * 1000,
      })
    }
    
    // Handle 500 Server Errors
    if (error.response?.status && error.response.status >= 500) {
      toastStore.error('Server error', 'Something went wrong. Please try again later.')
    }
    
    // Network errors
    if (!error.response) {
      toastStore.error('Network error', 'Unable to connect to the server. Please check your connection.')
    }
    
    return Promise.reject(error)
  }
)

export default api