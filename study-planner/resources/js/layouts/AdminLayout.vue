<template>
  <div class="min-h-screen bg-brand-background">
    <header class="bg-brand-navbar-cards shadow-sm border-b border-brand-borders sticky top-0 z-40">
      <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" aria-label="Admin navigation">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <router-link to="/admin/resources" class="flex items-center space-x-2" aria-label="Admin Dashboard">
              <svg class="h-8 w-8 text-status-info" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
              <span class="text-xl font-bold text-text-primary">Admin Panel</span>
            </router-link>
          </div>

          <div class="flex items-center space-x-4">
            <nav class="hidden md:flex items-center space-x-1" aria-label="Admin navigation">
              <router-link to="/admin/resources" class="nav-link-admin">Resources</router-link>
              <router-link to="/admin/subjects" class="nav-link-admin">Subjects</router-link>
              <router-link to="/admin/users" class="nav-link-admin">Users</router-link>
              <router-link to="/admin/settings" class="nav-link-admin">Settings</router-link>
            </nav>

            <div class="flex items-center space-x-3">
              <span class="hidden sm:block text-sm font-medium text-text-secondary">{{ authStore.user?.name }}</span>
              <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden btn-secondary-admin px-3" :aria-expanded="mobileMenuOpen" aria-label="Toggle navigation menu">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
              </button>
              <router-link to="/dashboard" class="btn-secondary-admin text-sm" aria-label="Switch to student view">
                Student View
              </router-link>
              <button @click="logout" class="btn-secondary-admin text-sm" aria-label="Log out">
                Log Out
              </button>
            </div>
          </div>
        </div>
        <nav v-if="mobileMenuOpen" class="md:hidden border-t border-brand-borders py-3 space-y-1" aria-label="Mobile admin navigation">
          <router-link to="/admin/resources" class="nav-link-admin block" @click="mobileMenuOpen = false">Resources</router-link>
          <router-link to="/admin/subjects" class="nav-link-admin block" @click="mobileMenuOpen = false">Subjects</router-link>
          <router-link to="/admin/users" class="nav-link-admin block" @click="mobileMenuOpen = false">Users</router-link>
          <router-link to="/admin/settings" class="nav-link-admin block" @click="mobileMenuOpen = false">Settings</router-link>
        </nav>
      </nav>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" role="main">
      <router-view />
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();
const mobileMenuOpen = ref(false);

async function logout() {
  await authStore.logout();
  router.push('/login');
}
</script>

<style scoped>
/* Admin Navigation Links */
.nav-link-admin {
  padding: 0.5rem 0.75rem;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #7A5C66;
  transition: color 200ms ease, background-color 200ms ease;
}
.nav-link-admin:hover {
  color: #3F7D8C;
  background-color: #E8F4F6;
}
.nav-link-admin:focus {
  outline: none;
  box-shadow: 0 0 0 2px #FFF5F7, 0 0 0 4px rgba(63, 125, 140, 0.5);
}
.nav-link-admin.router-link-exact-active {
  color: #3F7D8C;
  background-color: #E8F4F6;
}

/* Secondary Button for Admin Layout */
.btn-secondary-admin {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.5rem 1rem;
  border: 1px solid #F3D2DA;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 0.375rem;
  color: #7A5C66;
  background-color: #FFF9FA;
  transition: background-color 200ms ease;
}
.btn-secondary-admin:hover {
  background-color: #FFF5F7;
}
.btn-secondary-admin:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(63, 125, 140, 0.5);
}
</style>