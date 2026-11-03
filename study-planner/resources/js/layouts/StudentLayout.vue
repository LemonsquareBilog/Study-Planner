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
      <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" aria-label="Main navigation">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <router-link to="/dashboard" class="flex items-center space-x-2" aria-label="Study Planner Home">
              <svg class="h-8 w-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span class="text-xl font-bold text-text-primary">Study Planner</span>
            </router-link>
          </div>

          <div class="flex items-center space-x-4">
            <nav class="panel-desktop-nav items-center nav-links-container" aria-label="Student navigation">
              <router-link to="/dashboard" class="nav-link">Dashboard</router-link>
              <router-link to="/subjects" class="nav-link">Subjects</router-link>
              <router-link to="/tasks" class="nav-link">Tasks</router-link>
              <router-link to="/study-plan" class="nav-link">Study Plan</router-link>
              <router-link to="/quizzes" class="nav-link">Quizzes</router-link>
              <router-link to="/recommendations" class="nav-link">Recommendations</router-link>
              <router-link to="/progress" class="nav-link">Progress</router-link>
              <router-link to="/notifications" class="nav-link relative">
                Notifications
                <span v-if="unreadCount > 0" class="absolute -top-1 -right-1 bg-status-urgent text-white text-xs w-5 h-5 rounded-full flex items-center justify-center" :aria-label="`${unreadCount} unread notifications`">{{ unreadCount }}</span>
              </router-link>
            </nav>

            <div class="flex items-center space-x-3">
              <span class="hidden sm:block text-sm font-medium text-text-secondary">{{ authStore.user?.name }}</span>
              <router-link v-if="authStore.isAdmin" to="/admin" class="btn-secondary text-sm text-secondary hidden sm:inline-flex" aria-label="Return to admin panel">
                Admin Panel
              </router-link>
              <button
                @click="mobileMenuOpen = !mobileMenuOpen"
                class="lg:hidden btn-secondary p-2 rounded-lg"
                :aria-expanded="mobileMenuOpen"
                aria-label="Toggle navigation menu"
                aria-controls="mobile-menu"
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
      </nav>
    </header>
        
        <!-- Mobile Navigation Drawer -->
        <nav
          id="mobile-menu"
          v-if="mobileMenuOpen"
          class="app-safe-drawer lg:hidden fixed inset-y-0 right-0 z-50 w-64 glass-header shadow-xl transform transition-transform duration-300 ease-in-out"
          aria-label="Mobile navigation"
        >
          <div class="flex flex-col h-full">
            <div class="p-4 border-b border-brand-borders/50">
              <h3 class="text-lg font-semibold text-text-primary">Navigation</h3>
            </div>
            <nav class="flex-1 py-4 space-y-1 px-3 overflow-y-auto" aria-label="Mobile student navigation">
              <router-link
                v-if="authStore.isAdmin"
                to="/admin"
                class="nav-link block font-semibold text-secondary px-3 py-3"
                @click="mobileMenuOpen = false"
              >
                Admin Panel
              </router-link>
              <router-link
                v-for="item in studentNav"
                :key="item.to"
                :to="item.to"
                class="nav-link block px-3 py-3"
                @click="mobileMenuOpen = false"
              >
                {{ item.label }}
              </router-link>
            </nav>
            <div class="p-4 border-t border-brand-borders/50 space-y-2">
              <router-link v-if="authStore.isAdmin" to="/admin" class="btn-secondary w-full justify-center" @click="mobileMenuOpen = false">
                Admin Panel
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

    <!-- Mobile Bottom Navigation -->
    <MobileBottomNav />

    <main class="app-main max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 relative pb-24 lg:pb-8" role="main">
      <div class="glass-card p-4 sm:p-6">
        <router-view />
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useStudentStore } from '@/stores/student';
import { useRouter } from 'vue-router';
import MobileBottomNav from '@/components/MobileBottomNav.vue';

const authStore = useAuthStore();
const studentStore = useStudentStore();
const router = useRouter();

const mobileMenuOpen = ref(false);
const studentNav = [
  { to: '/dashboard', label: 'Dashboard' },
  { to: '/subjects', label: 'Subjects' },
  { to: '/tasks', label: 'Tasks' },
  { to: '/study-plan', label: 'Study Plan' },
  { to: '/quizzes', label: 'Quizzes' },
  { to: '/recommendations', label: 'Recommendations' },
  { to: '/progress', label: 'Progress' },
  { to: '/notifications', label: 'Notifications' },
];

const unreadCount = computed(() => studentStore.unreadNotificationsCount || studentStore.unreadNotifications);

async function logout() {
  await authStore.logout();
  router.push('/login');
}
</script>
