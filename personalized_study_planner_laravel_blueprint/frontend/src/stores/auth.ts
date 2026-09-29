import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/utils/api'
import type { User, LoginCredentials, RegisterData, PasswordReset, TwoFactorData } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(null)
  const loading = ref(false)
  const twoFactorRequired = ref(false)
  const twoFactorUserId = ref<number | null>(null)

  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const isAdmin = computed(() => user.value?.role === 'admin')
  const isStudent = computed(() => user.value?.role === 'student')

  // Initialize auth from localStorage
  async function initialize() {
    const storedToken = localStorage.getItem('access_token')
    const storedUser = localStorage.getItem('user')
    
    if (storedToken && storedUser) {
      token.value = storedToken
      try {
        user.value = JSON.parse(storedUser)
        api.defaults.headers.common['Authorization'] = `Bearer ${storedToken}`
        
        // Verify token is still valid
        await fetchUser()
      } catch {
        await logout()
      }
    }
  }

  // Fetch current user
  async function fetchUser() {
    try {
      const response = await api.get('/auth/me')
      user.value = response.data.data
      localStorage.setItem('user', JSON.stringify(user.value))
    } catch (error) {
      throw error
    }
  }

  // Login
  async function login(credentials: LoginCredentials) {
    loading.value = true
    twoFactorRequired.value = false
    
    try {
      const response = await api.post('/auth/login', credentials)
      
      if (response.data.error?.code === 'TWO_FACTOR_REQUIRED') {
        twoFactorRequired.value = true
        twoFactorUserId.value = response.data.data?.user_id || null
        return { requiresTwoFactor: true }
      }
      
      token.value = response.data.data.token
      user.value = response.data.data.user
      
      localStorage.setItem('access_token', token.value!)
      localStorage.setItem('user', JSON.stringify(user.value))
      api.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
      
      return { success: true }
    } catch (error: any) {
      throw error
    } finally {
      loading.value = false
    }
  }

  // Verify 2FA
  async function verifyTwoFactor(data: TwoFactorData) {
    loading.value = true
    
    try {
      const response = await api.post('/auth/me/2fa/verify', data)
      
      token.value = response.data.data.token
      user.value = response.data.data.user
      
      localStorage.setItem('access_token', token.value!)
      localStorage.setItem('user', JSON.stringify(user.value))
      api.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
      
      twoFactorRequired.value = false
      twoFactorUserId.value = null
      
      return { success: true }
    } catch (error: any) {
      throw error
    } finally {
      loading.value = false
    }
  }

  // Register
  async function register(data: RegisterData) {
    loading.value = true
    
    try {
      const response = await api.post('/auth/register', data)
      
      token.value = response.data.data.token
      user.value = response.data.data.user
      
      localStorage.setItem('access_token', token.value!)
      localStorage.setItem('user', JSON.stringify(user.value))
      api.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
      
      return { success: true }
    } catch (error: any) {
      throw error
    } finally {
      loading.value = false
    }
  }

  // Logout
  async function logout() {
    try {
      if (token.value) {
        await api.post('/auth/logout')
      }
    } catch {
      // Ignore logout errors
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('access_token')
      localStorage.removeItem('user')
      delete api.defaults.headers.common['Authorization']
    }
  }

  // Update profile
  async function updateProfile(data: Partial<User>) {
    loading.value = true
    
    try {
      const response = await api.put('/auth/me', data)
      user.value = response.data.data
      localStorage.setItem('user', JSON.stringify(user.value))
      return { success: true }
    } catch (error: any) {
      throw error
    } finally {
      loading.value = false
    }
  }

  // Update password
  async function updatePassword(data: { current_password: string; password: string; password_confirmation: string }) {
    loading.value = true
    
    try {
      await api.put('/auth/me/password', data)
      return { success: true }
    } catch (error: any) {
      throw error
    } finally {
      loading.value = false
    }
  }

  // Forgot password
  async function forgotPassword(email: string) {
    loading.value = true
    
    try {
      await api.post('/auth/forgot-password', { email })
      return { success: true }
    } catch (error: any) {
      throw error
    } finally {
      loading.value = false
    }
  }

  // Reset password
  async function resetPassword(data: PasswordReset) {
    loading.value = true
    
    try {
      await api.post('/auth/reset-password', data)
      return { success: true }
    } catch (error: any) {
      throw error
    } finally {
      loading.value = false
    }
  }

  // Enable 2FA
  async function enable2FA() {
    loading.value = true
    
    try {
      const response = await api.post('/auth/me/2fa/enable')
      return response.data.data
    } catch (error: any) {
      throw error
    } finally {
      loading.value = false
    }
  }

  // Disable 2FA
  async function disable2FA(password: string) {
    loading.value = true
    
    try {
      await api.post('/auth/me/2fa/disable', { password })
      return { success: true }
    } catch (error: any) {
      throw error
    } finally {
      loading.value = false
    }
  }

  // Get 2FA QR code
  async function get2FAQR() {
    const response = await api.get('/auth/me/2fa/qr')
    return response.data.data
  }

  // Check if 2FA is enabled
  const hasTwoFactorEnabled = computed(() => user.value?.two_factor_confirmed_at !== null)

  return {
    user,
    token,
    loading,
    twoFactorRequired,
    twoFactorUserId,
    isAuthenticated,
    isAdmin,
    isStudent,
    hasTwoFactorEnabled,
    initialize,
    fetchUser,
    login,
    verifyTwoFactor,
    register,
    logout,
    updateProfile,
    updatePassword,
    forgotPassword,
    resetPassword,
    enable2FA,
    disable2FA,
    get2FAQR,
  }
})