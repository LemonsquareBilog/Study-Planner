<script setup lang="ts>
import { useRouter } from 'vue-router'
import { 
  ArrowLeftIcon, HomeIcon, SearchIcon, ExclamationCircleIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()

function goHome() {
  router.push('/')
}

function goBack() {
  router.back()
}
</script>

<template>
  <div class="min-h-screen flex flex-col items-center justify-center px-4 bg-gradient-to-br from-pink-50 via-white to-pink-100 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
    <div class="text-center max-w-md mx-auto px-4">
      <!-- Error Illustration -->
      <div class="mb-8">
        <div class="w-32 h-32 mx-auto mb-6 rounded-full bg-pink-100/80 dark:bg-pink-900/30 flex items-center justify-center">
          <ExclamationCircleIcon class="w-16 h-16 text-pink-500" aria-hidden="true" />
        </div>
        
        <h1 class="text-6xl font-bold text-gray-900 dark:text-white mb-2">404</h1>
        <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-300 mb-4">Page Not Found</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-8 max-w-sm mx-auto">
          Sorry, we couldn't find the page you're looking for. It might have been moved or doesn't exist.
        </p>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <button 
            @click="goHome" 
            class="btn-primary flex-1 sm:flex-none"
          >
            <HomeIcon class="w-5 h-5 mr-2" aria-hidden="true" />
            Go Home
          </button>
          <button 
            @click="goBack" 
            class="btn-secondary flex-1 sm:flex-none"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Go Back
          </button>
        </div>

        <!-- Helpful Links -->
        <div class="mt-12 pt-8 border-t border-pink-200/50 dark:border-pink-800/50">
          <p class="text-gray-600 dark:text-gray-400 mb-4">Or visit one of these popular pages:</p>
          <div class="flex flex-wrap gap-3 justify-center">
            <RouterLink to="/features" class="text-pink-600 dark:text-pink-400 hover:underline text-sm">Features</RouterLink>
            <span class="text-gray-300 dark:text-gray-600">·</span>
            <RouterLink to="/pricing" class="text-pink-600 dark:text-pink-400 hover:underline text-sm">Pricing</RouterLink>
            <span class="text-gray-300 dark:text-gray-600">·</span>
            <RouterLink to="/docs" class="text-pink-600 dark:text-pink-400 hover:underline text-sm">Documentation</RouterLink>
            <span class="text-gray-300 dark:text-gray-600">·</span>
            <RouterLink to="/contact" class="text-pink-600 dark:text-pink-400 hover:underline text-sm">Contact Support</RouterLink>
          </div>
        </div>

        <!-- Search Alternative -->
        <div class="mt-8">
          <p class="text-gray-600 dark:text-gray-400 mb-4">Can't find what you're looking for?</p>
          <form class="flex gap-2 max-w-md mx-auto" @submit.prevent>
            <label for="search-404" class="sr-only">Search</label>
            <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" aria-hidden="true" />
            <input
              type="search"
              id="search-404"
              placeholder="Search the site..."
              class="input-field pl-10 pr-4 flex-1"
            />
            <button type="submit" class="btn-primary whitespace-nowrap">Search</button>
          </form>
        </div>
      </div>
    </div>
  </template>