<template>
  <div class="min-h-screen bg-brand-background relative overflow-hidden">
    <!-- Aurora decorative blobs -->
    <div class="absolute -top-20 -right-20 w-72 h-72 bg-white/30 rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>
    <div class="absolute -bottom-20 -left-20 w-96 h-96 bg-brand-borders/40 rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>

    <header class="glass-header sticky top-0 z-40 relative">
      <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" aria-label="Admin navigation">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <router-link to="/admin/resources" class="flex items-center space-x-2" aria-label="Admin Dashboard">
              <svg class="h-8 w-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
              <span class="text-xl font-bold text-text-primary">Admin Panel</span>
            </router-link>
          </div>

          <div class="flex items-center space-x-4">
            <nav class="panel-desktop-nav items-center nav-links-container" aria-label="Admin navigation">
              <router-link to="/admin" class="nav-link">Dashboard</router-link>
              <router-link to="/admin/resources" class="nav-link">Resources</router-link>
              <router-link to="/admin/subjects" class="nav-link">Subjects</router-link>
              <router-link to="/admin/users" class="nav-link">Users</router-link>
              <router-link to="/admin/settings" class="nav-link">Settings</router-link>
            </nav>

            <div class="flex items-center space-x-3">
              <span class="hidden sm:block text-sm font-medium text-text-secondary">{{ authStore.user?.name }}</span>
              <button @click="mobileMenuOpen = !mobileMenuOpen" class="panel-menu-toggle btn-secondary px-3" :aria-expanded="mobileMenuOpen" aria-label="Toggle navigation menu">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
              </button>
              <router-link to="/dashboard" class="panel-desktop-action btn-secondary text-sm" aria-label="Switch to student view">
                Student View
              </router-link>
              <button @click="logout" class="btn-secondary text-sm" aria-label="Log out">
                Log Out
              </button>
            </div>
          </div>
        </div>
        <nav v-if="mobileMenuOpen" class="panel-mobile-nav border-t border-brand-borders py-3 space-y-1" aria-label="Mobile admin navigation">
          <router-link to="/admin" class="nav-link block" @click="mobileMenuOpen = false">Dashboard</router-link>
          <router-link to="/admin/resources" class="nav-link block" @click="mobileMenuOpen = false">Resources</router-link>
          <router-link to="/admin/subjects" class="nav-link block" @click="mobileMenuOpen = false">Subjects</router-link>
          <router-link to="/admin/users" class="nav-link block" @click="mobileMenuOpen = false">Users</router-link>
          <router-link to="/admin/settings" class="nav-link block" @click="mobileMenuOpen = false">Settings</router-link>
          <router-link to="/dashboard" class="nav-link block" @click="mobileMenuOpen = false">Student View</router-link>
        </nav>
      </nav>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 relative" role="main">
      <div class="glass-card p-6">
        <router-view />
      </div>
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