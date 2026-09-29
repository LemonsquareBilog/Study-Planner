<script setup lang="ts">
import { ref, computed } from 'vue'
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
const passwordStrength = ref(0)

const registerSchema = zod.object({
  name: zod.string().min(2, 'Name must be at least 2 characters').max(100),
  email: zod.string().email('Please enter a valid email address'),
  password: zod.string()
    .min(8, 'Password must be at least 8 characters')
    .regex(/[A-Z]/, 'Password must contain at least one uppercase letter')
    .regex(/[a-z]/, 'Password must contain at least one lowercase letter')
    .regex(/[0-9]/, 'Password must contain at least one number')
    .regex(/[^A-Za-z0-9]/, 'Password must contain at least one symbol'),
  password_confirmation: zod.string(),
  course: zod.string().max(150).optional(),
  year_level: zod.number().min(1).max(8).optional(),
}).refine((data) => data.password === data.password_confirmation, {
  message: 'Passwords do not match',
  path: ['password_confirmation'],
})

const { handleSubmit, errors, setFieldError, resetForm, form } = useForm({
  validationSchema: registerSchema,
  initialValues: {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    course: '',
    year_level: undefined,
  },
})

const onSubmit = handleSubmit(async (values) => {
  loading.value = true
  
  try {
    await authStore.register({
      name: values.name,
      email: values.email,
      password: values.password,
      password_confirmation: values.password_confirmation,
      course: values.course || undefined,
      year_level: values.year_level,
    })
    
    toastStore.success('Account created!', 'Welcome to StudyPlanner. Let\'s get you set up.')
    router.push('/onboarding')
  } catch (error: any) {
    const details = error.response?.data?.error?.details
    if (details) {
      Object.entries(details).forEach(([field, messages]) => {
        setFieldError(field, messages[0])
      })
    } else if (error.response?.status === 422) {
      toastStore.error('Registration failed', error.response.data.error?.message || 'Registration failed')
    } else {
      toastStore.error('Registration failed', 'An unexpected error occurred')
    }
  } finally {
    loading.value = false
  }
})

function calculatePasswordStrength(password: string): number {
  let strength = 0
  if (password.length >= 8) strength += 25
  if (/[A-Z]/.test(password)) strength += 25
  if (/[a-z]/.test(password)) strength += 25
  if (/[0-9]/.test(password)) strength += 25
  if (/[^A-Za-z0-9]/.test(password)) strength += 25
  return Math.min(strength, 100)
}

const passwordStrengthColor = computed(() => {
  if (passwordStrength.value < 30) return 'bg-red-500'
  if (passwordStrength.value < 60) return 'bg-yellow-500'
  if (passwordStrength.value < 90) return 'bg-blue-500'
  return 'bg-green-500'
})

const passwordStrengthLabel = computed(() => {
  if (passwordStrength.value < 30) return 'Weak'
  if (passwordStrength.value < 60) return 'Fair'
  if (passwordStrength.value < 90) return 'Good'
  return 'Strong'
})
</script>

<template>
  <AuthLayout>
    <div class="glass-card-lg rounded-2xl p-8">
      <!-- Header -->
      <div class="text-center mb-8">
        <RouterLink to="/" class="inline-flex items-center gap-2 mb-6" aria-label="Study Planner Home">
          <svg class="w-10 h-10 text-primary" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4v10.55c-.59.34-1.27.55-2 .55-2.21 0-4-1.79-4-4S9.79 3 12 3z"/>
          </svg>
          <span class="text-2xl font-bold text-text-primary dark:text-white">StudyPlanner</span>
        </RouterLink>
        
        <h1 class="text-2xl font-bold text-text-primary dark:text-white mb-2">Create your account</h1>
        <p class="text-text-secondary dark:text-gray-400">Start your personalized learning journey today</p>
      </div>

      <form @submit.prevent="onSubmit" class="space-y-6" novalidate>
        <div>
          <label for="name" class="input-label">Full Name</label>
          <input
            id="name"
            type="text"
            v-model="form.name"
            autocomplete="name"
            class="input-field"
            :class="{ 'input-error': errors.name }"
            placeholder="John Doe"
            aria-describedby="name-error"
          />
          <p v-if="errors.name" id="name-error" class="input-error-message">{{ errors.name }}</p>
        </div>

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

        <div>
          <label for="password" class="input-label">Password</label>
          <div class="relative">
            <input
              id="password"
              :type="showPassword ? 'text' : 'password'"
              v-model="form.password"
              @input="passwordStrength.value = calculatePasswordStrength(form.password)"
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
          
          <!-- Password Strength Meter -->
          <div class="mt-2">
            <div class="flex items-center justify-between mb-1">
              <span class="text-xs text-text-muted">Password strength</span>
              <span class="text-xs font-medium" :class="passwordStrength.value < 30 ? 'text-status-urgent' : passwordStrength.value < 60 ? 'text-status-warning' : passwordStrength.value < 90 ? 'text-status-info' : 'text-status-success'">
                {{ passwordStrengthLabel }} ({{ passwordStrength }}%)
              </span>
            </div>
            <div class="h-2 bg-surface-border/50 dark:bg-surface-border/30 rounded-full overflow-hidden">
              <div 
                :class="passwordStrengthColor"
                class="h-full rounded-full transition-all duration-300 ease-out"
                :style="{ width: passwordStrength.value + '%' }"
              />
            </div>
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
          <p v-if="errors.password_confirmation" id="confirm-error" class="input-error-message">{{ errors.password_confirmation }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="course" class="input-label">Course / Major (optional)</label>
            <input
              id="course"
              type="text"
              v-model="form.course"
              class="input-field"
              :class="{ 'input-error': errors.course }"
              placeholder="Computer Science"
              aria-describedby="course-error"
            />
            <p v-if="errors.course" id="course-error" class="input-error-message">{{ errors.course }}</p>
          </div>

          <div>
            <label for="year_level" class="input-label">Year Level (optional)</label>
            <select
              id="year_level"
              v-model="form.year_level"
              class="select-field input-field"
              :class="{ 'input-error': errors.year_level }"
              aria-describedby="year-error"
            >
              <option value="">Select year</option>
              <option value="1">1st Year</option>
              <option value="2">2nd Year</option>
              <option value="3">3rd Year</option>
              <option value="4">4th Year</option>
              <option value="5">5th Year</option>
              <option value="6">6th Year</option>
              <option value="7">7th Year</option>
              <option value="8">8th Year</option>
            </select>
            <p v-if="errors.year_level" id="year-error" class="input-error-message">{{ errors.year_level }}</p>
          </div>
        </div>

        <div class="flex items-start gap-3">
          <input
            id="terms"
            type="checkbox"
            class="checkbox mt-1"
            required
          />
          <label for="terms" class="text-sm text-text-secondary dark:text-gray-400">
            I agree to the <RouterLink to="/terms" class="text-primary hover:text-primary-hover underline">Terms of Service</RouterLink> and <RouterLink to="/privacy" class="text-primary hover:text-primary-hover underline">Privacy Policy</RouterLink>
          </label>
        </div>

        <button type="submit" :disabled="loading" class="btn-primary w-full">
          <span v-if="loading" class="flex items-center justify-center gap-2">
            <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            Creating account...
          </span>
          <span v-else>Create Account</span>
        </button>
      </form>

      <p class="mt-8 text-center text-sm text-text-secondary dark:text-gray-400">
        Already have an account? <RouterLink to="/login" class="text-primary hover:text-primary-hover font-medium underline">Sign in</RouterLink>
      </p>
    </form>
  </AuthLayout>
</template>