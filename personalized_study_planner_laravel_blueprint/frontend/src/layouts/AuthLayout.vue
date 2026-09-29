<script setup lang="ts">
import { useThemeStore } from '@/stores/theme'
import { MoonIcon, SunIcon } from '@heroicons/vue/24/outline'

const themeStore = useThemeStore()

function toggleTheme() {
  themeStore.toggleTheme()
}
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <!-- Header -->
    <header class="fixed top-0 left-0 right-0 z-50 glass-card border-b border-pink-200/50 dark:border-pink-800/50">
      <nav class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8" aria-label="Authentication navigation">
        <div class="flex h-16 items-center justify-between">
          <RouterLink to="/" class="flex items-center gap-2" aria-label="Study Planner Home">
            <svg class="w-8 h-8 text-pink-600" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4v10.55c-.59.34-1.27.55-2 .55-2.21 0-4-1.79-4-4S9.79 3 12 3z"/>
            </svg>
            <span class="text-xl font-bold text-gray-900 dark:text-white">StudyPlanner</span>
          </RouterLink>

          <button
            @click="toggleTheme"
            :aria-label="themeStore.isDark ? 'Switch to light mode' : 'Switch to dark mode'"
            class="p-2 rounded-xl text-gray-600 dark:text-gray-300 hover:bg-pink-100/50 dark:hover:bg-pink-900/20 transition-colors"
          >
            <SunIcon v-if="themeStore.isDark" class="w-5 h-5" aria-hidden="true" />
            <MoonIcon v-else class="w-5 h-5" aria-hidden="true" />
          </button>
        </div>
      </nav>
    </header>

    <!-- Main Content -->
    <main id="main-content" class="flex-1 flex items-center justify-center pt-16 px-4 py-12">
      <div class="w-full max-w-md">
        <slot />
      </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-pink-200/50 dark:border-pink-800/50 bg-white/50 dark:bg-gray-900/50 backdrop-blur">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 text-center">
        <p class="text-sm text-gray-500 dark:text-gray-500">© {{ new Date().getFullYear() }} StudyPlanner. All rights reserved.</p>
      </div>
    </footer>
  </div>
</template>