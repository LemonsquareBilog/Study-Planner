<script setup lang="ts>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { useForm } from 'vee-validate'
import * as zod from 'zod'

const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(false)

const forgotPasswordSchema = zod.object({
  email: zod.string().email('Please enter a valid email address'),
})

const { handleSubmit, errors, resetForm } = useForm({
  validationSchema: forgotPasswordSchema,
  initialValues: {
    email: '',
  },
})

const onSubmit = handleSubmit(async (values) => {
  loading.value = true
  
  try {
    await authStore.forgotPassword(values.email)
    toastStore.success('Email sent!', 'Check your inbox for password reset instructions.')
    router.push('/login')
  } catch (error: any) {
    const details = error.response?.data?.error?.details
    if (details) {
      Object.entries(details).forEach(([field, messages]) => {
        setFieldError(field, messages[0])
      })
    } else {
      toastStore.error('Failed to send reset email', error.message)
    }
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <AuthLayout>
    <div class="glass-card-lg rounded-2xl p-8">
      <div class="text-center mb-8">
        <RouterLink to="/" class="inline-flex items-center gap-2 mb-6" aria-label="Study Planner Home">
          <svg class="w-10 h-10 text-pink-600" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4v10.55c-.59.34-1.27.55-2 .55-2.21 0-4-1.79-4-4S9.79 3 12 3z"/>
          </svg>
          <span class="text-2xl font-bold text-gray-900 dark:text-white">StudyPlanner</span>
        </RouterLink>
        
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Forgot Password</h1>
        <p class="text-gray-600 dark:text-gray-400">Enter your email and we'll send you reset instructions</p>
      </div>

      <form @submit.prevent="onSubmit" class="space-y-6" novalidate>
        <div>
          <label for="email" class="input-label">Email</label>
          <input
            id="email"
            type="email"
            v-model="form.email"
            autocomplete="email"
            class="input-field"
            :class="{ 'input-error': errors.email }"
            placeholder="you@example.com"
            aria-describedby="email-error"
          />
          <p v-if="errors.email" id="email-error" class="input-error-message">{{ errors.email }}</p>
        </div>

        <button type="submit" :disabled="loading" class="btn-primary w-full">
          <span v-if="loading" class="flex items-center justify-center gap-2">
            <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            Sending...
          </span>
          <span v-else>Send Reset Link</span>
        </button>
      </form>

      <p class="mt-8 text-center text-sm text-gray-600 dark:text-gray-400">
        Remember your password? <RouterLink to="/login" class="text-pink-600 dark:text-pink-400 font-medium hover:underline">Sign in</RouterLink>
      </p>
    </form>
  </AuthLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { useForm } from 'vee-validate'
import * as zod from 'zod'

const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(false)

const forgotPasswordSchema = zod.object({
  email: zod.string().email('Please enter a valid email address'),
})

const { handleSubmit, errors, setFieldError, resetForm } = useForm({
  validationSchema: forgotPasswordSchema,
  initialValues: {
    email: '',
  },
})

const onSubmit = handleSubmit(async (values) => {
  loading.value = true
  
  try {
    await authStore.forgotPassword(values.email)
    toastStore.success('Email sent!', 'Check your inbox for password reset instructions.')
    router.push('/login')
  } catch (error: any) {
    const details = error.response?.data?.error?.details
    if (details) {
      Object.entries(details).forEach(([field, messages]) => {
        setFieldError(field, messages[0])
      })
    } else {
      toastStore.error('Failed to send reset email', error.message)
    }
  } finally {
    loading.value = false
  }
})
</script>