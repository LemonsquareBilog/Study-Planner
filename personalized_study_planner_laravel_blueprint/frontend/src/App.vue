<script setup lang="ts">
import { RouterView, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useThemeStore } from '@/stores/theme'
import { useToastStore } from '@/stores/toast'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import ToastContainer from '@/components/ui/ToastContainer.vue'
import { onMounted, watch } from 'vue'

const router = useRouter()
const authStore = useAuthStore()
const themeStore = useThemeStore()
const toastStore = useToastStore()

// Initialize auth on app load
onMounted(async () => {
  await authStore.initialize()
})

// Track route changes for analytics/page views
watch(
  () => router.currentRoute.value,
  (to, from) => {
    // Scroll to top on route change
    window.scrollTo({ top: 0, behavior: 'smooth' })
    
    // Close mobile menu if open
    // (handled in layout components)
  }
)

const getLayout = () => {
  const publicRoutes = ['login', 'register', 'forgot-password', 'reset-password', 'verify-email']
  const isPublic = publicRoutes.includes(router.currentRoute.value.name as string)
  return isPublic ? AuthLayout : DefaultLayout
}
</script>

<template>
  <div class="min-h-screen">
    <!-- Theme transition class for smooth dark mode switching -->
    <div :class="['transition-colors duration-300', themeStore.isDark ? 'dark' : '']">
      <component :is="getLayout()">
        <RouterView v-slot="{ Component }">
          <transition name="page" mode="out-in">
            <component :is="Component" />
          </transition>
        </RouterView>
      </component>
      
      <!-- Global Toast Container -->
      <ToastContainer />
    </div>
  </div>
</template>

<style>
/* Page transitions */
.page-enter-active,
.page-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.page-enter-from,
.page-leave-to {
  opacity: 0;
  transform: translateY(10px);
}

/* Smooth theme transitions */
* {
  transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease, fill 0.3s ease, stroke 0.3s ease;
}

/* Prevent flash of wrong theme on load */
[v-cloak] {
  display: none;
}
</style>