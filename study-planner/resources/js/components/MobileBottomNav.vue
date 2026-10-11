<template>
  <nav 
    v-if="isMobile"
    class="mobile-bottom-nav"
    role="navigation"
    aria-label="Main navigation"
  >
    <div class="nav-background" aria-hidden="true"></div>
    <div class="nav-content">
      <button
        v-for="item in navItems"
        :key="item.route"
        :class="[
          'nav-item',
          { 'nav-item--active': isActive(item.route) }
        ]"
        @click="navigate(item.route)"
        :aria-label="item.label"
        :aria-current="isActive(item.route) ? 'page' : undefined"
      >
        <span class="nav-icon" aria-hidden="true">
          <component :is="item.icon" class="h-6 w-6" />
        </span>
        <span class="nav-label">{{ item.label }}</span>
        <span 
          v-if="item.badge && item.badge > 0" 
          class="nav-badge"
          :aria-label="`${item.badge} unread ${item.label.toLowerCase()}`"
        >
          {{ item.badge > 9 ? '9+' : item.badge }}
        </span>
      </button>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { computed, onMounted, onBeforeUnmount, ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useStudentStore } from '@/stores/student';
import {
  HomeIcon,
  CheckCircleIcon,
  CalendarIcon,
  UserCircleIcon,
  BellIcon,
  BookOpenIcon,
  ChartBarIcon,
  Cog6ToothIcon,
} from '@heroicons/vue/24/outline';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const studentStore = useStudentStore();

const isMobile = ref(false);

function checkMobile() {
  isMobile.value = window.innerWidth < 768;
}

onMounted(() => {
  checkMobile();
  window.addEventListener('resize', checkMobile);
});

onBeforeUnmount(() => {
  window.removeEventListener('resize', checkMobile);
});

const unreadNotifications = computed(() => studentStore.unreadNotificationsCount || 0);

const navItems = computed(() => [
  { route: '/dashboard', label: 'Home', icon: HomeIcon, badge: 0 },
  { route: '/tasks', label: 'Tasks', icon: CheckCircleIcon, badge: 0 },
  { route: '/study-plan', label: 'Study', icon: CalendarIcon, badge: 0 },
  { route: '/notifications', label: 'Alerts', icon: BellIcon, badge: unreadNotifications.value },
  { route: '/progress', label: 'Progress', icon: ChartBarIcon, badge: 0 },
]);

function isActive(routePath: string): boolean {
  return route.path.startsWith(routePath);
}

function navigate(routePath: string): void {
  router.push(routePath);
}
</script>

<style scoped>
.mobile-bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 50;
  display: none;
  padding-bottom: env(safe-area-inset-bottom);
}

@media (max-width: 1023px) {
  .mobile-bottom-nav {
    display: block;
  }
}

.nav-background {
  position: absolute;
  inset: 0;
  background: rgba(255, 249, 250, 0.85);
  backdrop-filter: blur(24px);
  -webkit-backdrop-filter: blur(24px);
  border-top: 1px solid rgba(243, 210, 218, 0.5);
  box-shadow: 0 -8px 32px rgba(179, 63, 98, 0.12);
  border-radius: 24px 24px 0 0;
  margin: 0 16px 16px 16px;
  box-sizing: border-box;
}

.nav-content {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-around;
  padding: 0.5rem;
  height: 72px;
  max-width: 480px;
  margin: 0 auto;
}

.nav-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.25rem;
  padding: 0.5rem 0.75rem;
  border-radius: 1.5rem;
  transition: all 200ms ease-out;
  flex: 1;
  min-width: 0;
  color: #7A5C66;
  background: transparent;
  border: none;
  cursor: pointer;
}

.nav-item:focus-visible {
  outline: none;
  box-shadow: 0 0 0 2px rgba(179, 63, 98, 0.5), 0 0 0 4px white;
}

.nav-item--active {
  color: #B33F62;
  background: linear-gradient(135deg, rgba(179, 63, 98, 0.12) 0%, rgba(179, 63, 98, 0.08) 100%);
  box-shadow: 
    0 4px 16px rgba(179, 63, 98, 0.2),
    inset 0 1px 0 rgba(255, 255, 255, 0.3);
}

.nav-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  transition: transform 150ms ease-out;
}

.nav-item--active .nav-icon {
  transform: translateY(-1px);
}

.nav-label {
  font-size: 0.625rem;
  font-weight: 500;
  letter-spacing: 0.05em;
  line-height: 1;
}

.nav-badge {
  position: absolute;
  top: -0.25rem;
  right: -0.25rem;
  min-width: 16px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 0.375rem;
  background: #C6485F;
  color: white;
  font-size: 9px;
  font-weight: 700;
  border-radius: 9999px;
  border: 2px solid white;
  box-shadow: 0 2px 8px rgba(198, 72, 95, 0.4);
  transform: scale(1);
  transition: transform 150ms ease-out;
}

.nav-item--active .nav-badge {
  background: #C6485F;
  box-shadow: 0 2px 8px rgba(198, 72, 95, 0.5);
}

/* Animation for nav item appearance */
@keyframes navItemSlideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.nav-item {
  animation: navItemSlideUp 0.4s ease-out backwards;
}

.nav-item:nth-child(1) { animation-delay: 50ms; }
.nav-item:nth-child(2) { animation-delay: 100ms; }
.nav-item:nth-child(3) { animation-delay: 150ms; }
.nav-item:nth-child(4) { animation-delay: 200ms; }
.nav-item:nth-child(5) { animation-delay: 250ms; }

/* Pressed state */
.nav-item:active:not(.nav-item--active) {
  transform: scale(0.96);
  background: rgba(179, 63, 98, 0.08);
}

/* Safe area handling for notched devices */
@supports (padding-bottom: env(safe-area-inset-bottom)) {
  .mobile-bottom-nav {
    padding-bottom: env(safe-area-inset-bottom);
  }
}
</style>