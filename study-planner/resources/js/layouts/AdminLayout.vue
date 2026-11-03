<template>
  <div class="min-h-screen bg-brand-background relative overflow-hidden">
    <!-- Aurora decorative blobs -->
    <div class="absolute -top-20 -right-20 w-72 h-72 bg-white/30 rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>
    <div class="absolute -bottom-20 -left-20 w-96 h-96 bg-brand-borders/40 rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>
    
    <!-- Mobile navigation drawer overlay -->
    <div
      v-if="mobileMenuOpen"
      class="fixed inset-0 z-40 bg-black/50 lg:hidden"
      @click="mobileMenuOpen = false"
      aria-hidden="true"
    ></div>
    
    <header class="app-header glass-header sticky top-0 z-50">
      <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" aria-label="Admin navigation">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <router-link to="/admin" class="flex items-center space-x-2" aria-label="Admin Dashboard">
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
              <strong class="hidden sm:block text-sm font-bold text-text-primary">{{ authStore.user?.name }}</strong>
              <router-link to="/dashboard" class="hidden sm:btn-secondary text-sm" aria-label="Switch to student view">
                Student View
              </router-link>
              <button
                @click="mobileMenuOpen = !mobileMenuOpen"
                class="lg:hidden btn-secondary p-2 rounded-lg"
                :aria-expanded="mobileMenuOpen"
                aria-label="Toggle navigation menu"
                aria-controls="admin-mobile-menu"
              >
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                  <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
              <button @click="logout" class="btn-secondary text-sm hidden sm:inline-flex" aria-label="Log out">
                Log Out
              </button>
            </div>
          </div>
        </div>
        
        <!-- Mobile Navigation Drawer -->
        <nav
          id="admin-mobile-menu"
          v-if="mobileMenuOpen"
          class="app-safe-drawer lg:hidden fixed inset-y-0 right-0 z-50 w-64 glass-header shadow-xl transform transition-transform duration-300 ease-in-out"
          aria-label="Mobile admin navigation"
        >
          <div class="flex flex-col h-full">
            <div class="p-4 border-b border-brand-borders/50">
              <h3 class="text-lg font-semibold text-text-primary">Admin Navigation</h3>
            </div>
            <nav class="flex-1 py-4 space-y-1 px-3 overflow-y-auto" aria-label="Mobile admin navigation">
              <router-link to="/admin" class="nav-link block px-3 py-3" @click="mobileMenuOpen = false">Dashboard</router-link>
              <router-link to="/admin/resources" class="nav-link block px-3 py-3" @click="mobileMenuOpen = false">Resources</router-link>
              <router-link to="/admin/subjects" class="nav-link block px-3 py-3" @click="mobileMenuOpen = false">Subjects</router-link>
              <router-link to="/admin/users" class="nav-link block px-3 py-3" @click="mobileMenuOpen = false">Users</router-link>
              <router-link to="/admin/settings" class="nav-link block px-3 py-3" @click="mobileMenuOpen = false">Settings</router-link>
              <router-link to="/dashboard" class="nav-link block px-3 py-3 text-secondary" @click="mobileMenuOpen = false">Student View</router-link>
            </nav>
            <div class="p-4 border-t border-brand-borders/50 space-y-2">
              <router-link to="/dashboard" class="btn-secondary w-full justify-center text-secondary" @click="mobileMenuOpen = false">
                Student View
              </router-link>
              <button @click="mobileMenuOpen = false; logout()" class="btn-secondary w-full justify-center text-status-urgent">
                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Log Out
              </button>
            </div>
          </div>
        </nav>
      </nav>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 relative" role="main">
      <div class="glass-card p-4 sm:p-6">
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
