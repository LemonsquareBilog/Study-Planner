import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { api } from '@/services/api';
import type { User } from '@/types';

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null);
  const isAuthenticated = ref(false);
  const loading = ref(false);
  const error = ref<string | null>(null);
  let initialized = false;

  const userRole = computed(() => user.value?.role);
  const isStudent = computed(() => user.value?.role === 'student');
  const isAdmin = computed(() => user.value?.role === 'admin');

  async function initialize() {
    if (initialized) return;
    initialized = true;
    api.loadToken();
    if (api.token) {
      try {
        loading.value = true;
        const userData = await api.me();
        user.value = userData;
        isAuthenticated.value = true;
      } catch (err: any) {
        // Only clear token on explicit 401 Unauthorized, not on network/CORS errors
        if (err.response?.status === 401) {
          api.clearToken();
        }
      } finally {
        loading.value = false;
      }
    }
  }

  async function login(email: string, password: string) {
    loading.value = true;
    error.value = null;
    try {
      const data = await api.login(email, password);
      user.value = data.user;
      isAuthenticated.value = true;
      return data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Login failed';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function register(name: string, email: string, password: string, password_confirmation: string) {
    loading.value = true;
    error.value = null;
    try {
      const data = await api.register(name, email, password, password_confirmation);
      user.value = data.user;
      isAuthenticated.value = true;
      return data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Registration failed';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function logout() {
    try {
      await api.logout();
    } finally {
      user.value = null;
      isAuthenticated.value = false;
    }
  }

  async function updateProfile(data: { name: string; course?: string; year_level?: number }) {
    loading.value = true;
    try {
      const updatedUser = await api.updateProfile(data);
      user.value = updatedUser;
      return updatedUser;
    } finally {
      loading.value = false;
    }
  }

  async function updatePassword(data: { current_password: string; password: string; password_confirmation: string }) {
    loading.value = true;
    try {
      return await api.updatePassword(data);
    } finally {
      loading.value = false;
    }
  }

  return {
    user,
    isAuthenticated,
    loading,
    error,
    userRole,
    isStudent,
    isAdmin,
    initialize,
    login,
    register,
    logout,
    updateProfile,
    updatePassword,
  };
});
