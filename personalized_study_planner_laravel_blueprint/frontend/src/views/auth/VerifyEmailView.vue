<script setup lang="ts>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(false)
const verified = ref(false)
const error = ref<string | null>(null)

onMounted(async () => {
  const token = route.query.token as string
  if (!token) {
    error.value = 'Invalid verification link'
    return
  }
  
  await verifyEmail(token)
})

async function verifyEmail(token: string) {
  loading.value = true
  try {
    await authStore.verifyEmail(token)
    verified.value = true
    toastStore.success('Email verified!', 'Your email has been verified. You can now sign in.')
    setTimeout(() => router.push('/login'), 3000)
  } catch (error: any) {
    error.value = error.response?.data?.error?.message || 'Verification failed. The link may be invalid or expired.'
  } finally {
    loading.value = false
  }
}

function resendEmail() {
  router.push({ name: 'login', query: { resend: 'true' } })
}
</script>

<template>
  <AuthLayout>
    <div class="glass-card-lg rounded-2xl p-8 text-center">
      <div v-if="loading" class="space-y-4">
        <svg class="animate-spin h-12 w-12 mx-auto text-pink-600" viewBox="0 0 24 24" aria-hidden="true">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Verifying your email...</h1>
        <p class="text-gray-600 dark:text-gray-400">Please wait while we verify your email address</p>
      </div>
      
      <div v-else-if="verified" class="space-y-6">
        <div class="w-16 h-16 mx-auto bg-green-100/80 dark:bg-green-900/30 rounded-full flex items-center justify-center text-green-600 dark:text-green-400">
          <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 1.414l-4 4a1 1 0 01-1.414 1.414z" clip-rule="evenodd"/></svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Email Verified!</h1>
        <p class="text-gray-600 dark:text-gray-400">Your email has been verified. Redirecting to login...</p>
        <RouterLink to="/login" class="btn-primary inline-block">Go to Login</RouterLink>
      </div>
      
      <div v-else class="space-y-6">
        <div class="w-16 h-16 mx-auto bg-red-100/80 dark:bg-red-900/30 rounded-full flex items-center justify-center text-red-600 dark:text-red-400">
          <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 011.414-1.414L10 8.586 8.707 7.293a1 1 0 00-1.414-1.414L7.293 8.586 6 7.293a1 1 0 00-1.414 1.414L8.586 10 6 11.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414 1.414L11.414 10l1.293-1.293a1 1 0 101.414-1.414L11.414 10l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd"/></svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Verification Failed</h1>
        <p class="text-gray-600 dark:text-gray-400">{{ error || 'The verification link is invalid or has expired.' }}</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <button @click="resendEmail" class="btn-secondary">Resend Verification Email</button>
          <RouterLink to="/login" class="btn-primary">Go to Login</RouterLink>
        </div>
      </div>
    </AuthLayout>
  </template>