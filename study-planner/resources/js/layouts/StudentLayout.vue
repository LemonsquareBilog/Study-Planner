<template>
  <div class="min-h-screen bg-brand-background relative overflow-hidden">
    <!-- Aurora decorative blobs -->
    <div class="absolute -top-20 -right-20 w-72 h-72 bg-white/30 rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>
    <div class="absolute -bottom-20 -left-20 w-96 h-96 bg-brand-borders/40 rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>
    
    <header class="glass-header sticky top-0 z-40 relative">
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
            <nav class="hidden md:flex items-center space-x-1" aria-label="Student navigation">
              <router-link to="/dashboard" class="nav-link-student">Dashboard</router-link>
              <router-link to="/subjects" class="nav-link-student">Subjects</router-link>
              <router-link to="/tasks" class="nav-link-student">Tasks</router-link>
              <router-link to="/study-plan" class="nav-link-student">Study Plan</router-link>
              <router-link to="/quizzes" class="nav-link-student">Quizzes</router-link>
              <router-link to="/recommendations" class="nav-link-student">Recommendations</router-link>
              <router-link to="/progress" class="nav-link-student">Progress</router-link>
              <router-link to="/notifications" class="nav-link-student relative">
                Notifications
                <span v-if="unreadCount > 0" class="absolute -top-1 -right-1 bg-status-urgent text-white text-xs w-5 h-5 rounded-full flex items-center justify-center" :aria-label="`${unreadCount} unread notifications`">{{ unreadCount }}</span>
              </router-link>
            </nav>

            <div class="flex items-center space-x-3">
              <span class="hidden sm:block text-sm font-medium text-text-secondary">{{ authStore.user?.name }}</span>
              <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden btn-secondary-student px-3" :aria-expanded="mobileMenuOpen" aria-label="Toggle navigation menu">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
              </button>
              <button @click="logout" class="btn-secondary-student text-sm" aria-label="Log out">
                Log Out
              </button>
            </div>
          </div>
        </div>
        <nav v-if="mobileMenuOpen" class="md:hidden border-t border-brand-borders py-3 space-y-1" aria-label="Mobile student navigation">
          <router-link v-for="item in studentNav" :key="item.to" :to="item.to" class="nav-link-student block" @click="mobileMenuOpen = false">{{ item.label }}</router-link>
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
import { computed, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useStudentStore } from '@/stores/student';
import { useRouter } from 'vue-router';

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

<style scoped>
/* Student Navigation Links */
.nav-link-student {
  padding: 0.5rem 0.75rem;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #7A5C66;
  transition: color 200ms ease, background-color 200ms ease;
}
.nav-link-student:hover {
  color: #B33F62;
  background-color: #FFF5F7;
}
.nav-link-student:focus {
  outline: none;
  box-shadow: 0 0 0 2px #FFF5F7, 0 0 0 4px rgba(179, 63, 98, 0.5);
}
.nav-link-student.router-link-exact-active {
  color: #B33F62;
  background-color: #FFF5F7;
}

/* Secondary Button for Student Layout */
.btn-secondary-student {
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
.btn-secondary-student:hover {
  background-color: #FFF5F7;
}
.btn-secondary-student:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(63, 125, 140, 0.5);
}
</style>