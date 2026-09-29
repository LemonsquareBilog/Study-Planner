<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { useForm } from 'vee-validate'
import * as zod from 'zod'
import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const toastStore = useToastStore()

const showPassword = ref(false)
const loading = ref(false)

const resetPasswordSchema = zod.object({
  password: zod.string()
    .min(8, 'Password must be at least 8 characters')
    .regex(/[A-Z]/, 'Password must contain at least one uppercase letter')
    .regex(/[a-z]/, 'Password must contain at least one lowercase letter')
    .regex(/[0-9]/, 'Password must contain at least one number')
    .regex(/[^A-Za-z0-9]/, 'Password must contain at least one symbol'),
  password_confirmation: zod.string(),
}).refine((data) => data.password === data.password_confirmation, {
  message: 'Passwords do not match',
  path: ['password_confirmation'],
})

const { handleSubmit, errors, setFieldError, resetForm, form } = useForm({
  validationSchema: resetPasswordSchema,
  initialValues: {
    password: '',
    password_confirmation: '',
  },
})

const onSubmit = handleSubmit(async (values) => {
  loading.value = true
  
  try {
    await authStore.resetPassword({
      token: route.query.token as string,
      email: route.query.email as string,
      password: values.password,
      password_confirmation: values.password_confirmation,
    })
    
    toastStore.success('Password reset!', 'Your password has been updated. You can now sign in.')
    router.push('/login')
  } catch (error: any) {
    const details = error.response?.data?.error?.details
    if (details) {
      Object.entries(details).forEach(([field, messages]) => {
        setFieldError(field, messages[0])
      })
    } else {
      toastStore.error('Reset failed', error.message)
    }
  } finally {
    loading.value = false
  }
})

function togglePasswordVisibility() {
  showPassword.value = !showPassword.value
}
</script>

<template>
  <AuthLayout>
    <div class="glass-card-lg rounded-2xl p-8">
      <div class="text-center mb-8">
        <RouterLink to="/" class="inline-flex items-center gap-2 mb-6" aria-label="Study Planner Home">
          <svg class="w-10 h-10 text-primary" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4v10.55c-.59.34-1.27.55-2 .55-2.21 0-4-1.79-4-4S9.79 3 12 3z"/>
          </svg>
          <span class="text-2xl font-bold text-text-primary dark:text-white">StudyPlanner</span>
        </RouterLink>
        
        <h1 class="text-2xl font-bold text-text-primary dark:text-white mb-2">Reset Password</h1>
        <p class="text-text-secondary dark:text-gray-400">Enter your new password below</p>
      </div>

      <form @submit.prevent="onSubmit" class="space-y-6" novalidate>
        <div>
          <label for="password" class="input-label">New Password</label>
          <div class="relative">
            <input
              id="password"
              :type="showPassword ? 'text' : 'password'"
              v-model="form.password"
              autocomplete="new-password"
              class="input-field pr-12"
              placeholder="Create a strong password"
              :class="{ 'input-error': errors.password }"
              aria-describedby="password-hint"
            />
            <button
              type="button"
              @click="showPassword = !showPassword"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-text-muted hover:text-primary dark:hover:text-primary-light"
              :aria-label="showPassword ? 'Hide password' : 'Show password'"
            >
              <EyeIcon v-if="!showPassword" class="w-5 h-5" aria-hidden="true" />
              <EyeSlashIcon v-else class="w-5 h-5" aria-hidden="true" />
            </button>
          </div>
          <p id="password-hint" class="input-hint">At least 8 characters with uppercase, lowercase, number, and symbol</p>
          <p v-if="errors.password" class="input-error-message">{{ errors.password }}</p>
        </div>

        <div>
          <label for="password_confirmation" class="input-label">Confirm Password</label>
          <input
            id="password_confirmation"
            :type="showPassword ? 'text' : 'password'"
            v-model="form.password_confirmation"
            autocomplete="new-password"
            class="input-field"
            :class="{ 'input-error': errors.password_confirmation }"
            placeholder="Confirm your password"
            aria-describedby="confirm-error"
          />
          <p v-if="errors.password_confirmation" class="input-error-message">{{ errors.password_confirmation }}</p>
        </div>

        <button type="submit" :disabled="loading" class="btn-primary w-full">
          <span v-if="loading" class="flex items-center justify-center gap-2">
            <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            Resetting...
          </span>
          <span v-else>Reset Password</span>
        </button>
      </form>

      <p class="mt-8 text-center text-sm text-text-secondary dark:text-gray-400">
        <RouterLink to="/login" class="text-primary hover:text-primary-hover font-medium underline">Back to Sign In</RouterLink>
      </p>
    </form>
  </AuthLayout>
</template>