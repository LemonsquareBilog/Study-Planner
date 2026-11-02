import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { api } from '@/services/api';
import { useStudentStore } from '@/stores/student';
import { authErrorMessage } from '@/services/authError';
import type { User } from '@/types';

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null);
  const isAuthenticated = ref(false);
  const loading = ref(false);
  const error = ref<string | null>(null);
  let initialized = false;
  const sessionRevision = ref(0);

  function beginSessionChange() {
    sessionRevision.value++;
    useStudentStore().clearDashboard();
  }

  const userRole = computed(() => user.value?.role);
  const isStudent = computed(() => user.value?.role === 'student');
  const isAdmin = computed(() => user.value?.role === 'admin');

  let initialization: Promise<void> | null = null;
  async function initialize() {
    if (initialization) return initialization;
    if (initialized) return;
    initialization = (async () => {
      loading.value = true;
      try {
        await api.loadToken();
        if (api.token) {
          user.value = await api.me();
          isAuthenticated.value = true;
        }
        initialized = true;
        error.value = null;
      } catch (err: any) {
        initialized = err.response?.status === 401 || err.response?.status === 403;
        if (initialized) {
          beginSessionChange();
          await api.clearToken();
          user.value = null;
          isAuthenticated.value = false;
        }
        error.value = authErrorMessage(err);
      } finally {
        loading.value = false;
      }
    })().finally(() => { initialization = null; });
    return initialization;
  }

  async function login(email: string, password: string) {
    beginSessionChange();
    loading.value = true;
    error.value = null;
    try {
      const data = await api.login(email, password);
      user.value = data.user;
      isAuthenticated.value = true;
      return data;
    } catch (err: any) {
      error.value = authErrorMessage(err);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function register(name: string, email: string, password: string, password_confirmation: string) {
    beginSessionChange();
    loading.value = true;
    error.value = null;
    try {
      const data = await api.register(name, email, password, password_confirmation);
      user.value = data.user;
      isAuthenticated.value = true;
      return data;
    } catch (err: any) {
      error.value = authErrorMessage(err);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function logout() {
    beginSessionChange();
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
    sessionRevision,
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
