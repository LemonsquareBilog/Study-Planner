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
const show2FA = ref(false)
const loading = ref(false)

const loginSchema = zod.object({
  email: zod.string().email('Please enter a valid email address'),
  password: zod.string().min(1, 'Password is required'),
  two_factor_code: zod.string().length(6, 'Code must be 6 digits').optional(),
})

const { handleSubmit, errors, setFieldError, setValues, resetForm, form } = useForm({
  validationSchema: loginSchema,
  initialValues: {
    email: '',
    password: '',
    two_factor_code: '',
  },
})

const onSubmit = handleSubmit(async (values) => {
  loading.value = true
  
  try {
    const result = await authStore.login({
      email: values.email,
      password: values.password,
      two_factor_code: values.two_factor_code || undefined,
    })
    
    if (result.requiresTwoFactor) {
      show2FA.value = true
      toastStore.info('Two-factor authentication required', 'Enter the 6-digit code from your authenticator app')
      return
    }
    
    toastStore.success('Welcome back!', `Welcome, ${authStore.user?.name}`)
    
    const redirect = route.query.redirect as string || (authStore.isAdmin ? '/admin' : '/dashboard')
    router.push(redirect)
  } catch (error: any) {
    const details = error.response?.data?.error?.details
    if (details) {
      Object.entries(details).forEach(([field, messages]) => {
        setFieldError(field, messages[0])
      })
    } else if (error.response?.status === 422) {
      toastStore.error('Login failed', error.response.data.error?.message || 'Invalid credentials')
    } else {
      toastStore.error('Login failed', 'An unexpected error occurred')
    }
  } finally {
    loading.value = false
  }
})

function togglePasswordVisibility() {
  showPassword.value = !showPassword.value
}

function handleForgotPassword() {
  router.push({ name: 'forgot-password' })
}
</script>

<template>
  <AuthLayout>
    <template #default>
      <div class="relative w-full max-w-[420px] mx-auto">
        <!-- Aurora decorative blobs -->
        <div class="absolute -top-20 -right-20 w-72 h-72 bg-white/30 rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>
        <div class="absolute -bottom-20 -left-20 w-96 h-96 bg-[#F3D2DA]/40 rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>
        
        <!-- Glass Card -->
        <div class="relative glass-login-card p-10">
          <!-- Icon & Headline -->
          <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-primary-light mb-6" aria-hidden="true">
              <svg class="w-8 h-8 text-primary" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4v10.55c-.59.34-1.27.55-2 .55-2.21 0-4-1.79-4-4S9.79 3 12 3z"/>
              </svg>
            </div>
            <h1 class="text-[28px] font-bold text-text-primary mb-2">Sign in to your account</h1>
            <p class="text-text-secondary">
              Or <RouterLink to="/register" class="text-primary hover:text-primary-hover font-medium transition-colors">create a new account</RouterLink>
            </p>
          </div>

          <!-- 2FA Form -->
          <form v-if="show2FA" @submit.prevent="onSubmit" class="space-y-6" novalidate>
            <div>
              <label for="two_factor_code" class="input-label">Authentication Code</label>
              <div class="relative">
                <input
                  id="two_factor_code"
                  type="text"
                  v-model="form.two_factor_code"
                  maxlength="6"
                  autocomplete="one-time-code"
                  inputmode="numeric"
                  class="input-field-glass text-center text-2xl tracking-widest"
                  placeholder="000000"
                  :class="{ 'input-error': errors.two_factor_code }"
                  aria-describedby="2fa-hint"
                />
              </div>
              <p id="2fa-hint" class="input-hint">Enter the 6-digit code from your authenticator app</p>
              <p v-if="errors.two_factor_code" class="input-error-message">{{ errors.two_factor_code }}</p>
            </div>

            <div class="flex items-center justify-between">
              <button type="button" @click="show2FA = false; resetForm()" class="text-sm text-text-secondary hover:text-primary transition-colors">
                Use password instead
              </button>
            </div>

            <button type="submit" :disabled="loading" class="btn-primary-glass w-full">
              <span v-if="loading" class="flex items-center justify-center gap-2">
                <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                Verifying...
              </span>
              <span v-else>Verify Code</span>
            </button>

            <p class="text-center text-sm text-text-muted">
              Didn't receive a code? <a href="#" class="text-primary hover:text-primary-hover hover:underline">Resend</a>
            </p>
          </form>

          <!-- Regular Login Form -->
          <form v-else @submit.prevent="onSubmit" class="space-y-6" novalidate>
            <div>
              <label for="email" class="input-label">Email</label>
              <input
                id="email"
                type="email"
                v-model="form.email"
                autocomplete="email"
                class="input-field-glass"
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
                  autocomplete="current-password"
                  class="input-field-glass pr-12"
                  placeholder="••••••••"
                  :class="{ 'input-error': errors.password }"
                  aria-describedby="password-error"
                />
                <button
                  type="button"
                  @click="showPassword = !showPassword"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-text-muted hover:text-primary transition-colors"
                  :aria-label="showPassword ? 'Hide password' : 'Show password'"
                >
                  <EyeIcon v-if="!showPassword" class="w-5 h-5" aria-hidden="true" />
                  <EyeSlashIcon v-else class="w-5 h-5" aria-hidden="true" />
                </button>
              </div>
              <p v-if="errors.password" id="password-error" class="input-error-message">{{ errors.password }}</p>
            </div>

            <div class="flex items-center justify-between">
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" class="checkbox" />
                <span class="text-sm text-text-secondary">Remember me</span>
              </label>
              <button type="button" @click="handleForgotPassword" class="text-sm text-primary hover:text-primary-hover hover:underline">
                Forgot password?
              </button>
            </div>

            <button type="submit" :disabled="loading" class="btn-primary-glass w-full">
              <span v-if="loading" class="flex items-center justify-center gap-2">
                <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                Signing in...
              </span>
              <span v-else>Sign in</span>
            </button>
          </form>

          <!-- Divider -->
          <div class="relative my-8">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-white/30" />
            </div>
            <div class="relative flex justify-center text-sm">
              <span class="px-4 bg-white/30 backdrop-blur-sm text-text-secondary">Or continue with</span>
            </div>
          </div>

          <!-- Social Login -->
          <div class="grid grid-cols-2 gap-3">
            <button type="button" class="btn-ghost-glass" disabled>
              <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.29H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
              </svg>
              <span>Google</span>
            </button>
            <button type="button" class="btn-ghost-glass" disabled>
              <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.687-1.026 2.687-1.026.546 1.379.202 2.398.1 2.651.63.7 1.028 1.605 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0122 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"/>
              </svg>
              <span>GitHub</span>
            </button>
          </div>
        </div>
      </div>
    </template>
  </AuthLayout>
</template>

<style scoped>
/* Glass Login Card */
.glass-login-card {
  background: rgba(255, 255, 255, 0.35);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.5);
  border-radius: 20px;
  box-shadow: 0 8px 32px rgba(179, 63, 98, 0.15);
}

/* Glass Input Fields */
.input-field-glass {
  @apply w-full px-4 py-[14px] text-text-primary placeholder-text-secondary rounded-[10px] transition-all duration-200;
  background: rgba(255, 255, 255, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.6);
}
.input-field-glass:focus {
  @apply outline-none ring-2 ring-primary/30 border-primary;
  background: rgba(255, 255, 255, 0.7);
}

/* Glass Primary Button */
.btn-primary-glass {
  @apply inline-flex items-center justify-center px-4 py-[14px] text-white font-semibold rounded-[10px] transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed;
  background: #B33F62;
  box-shadow: 0 4px 12px rgba(179, 63, 98, 0.3);
}
.btn-primary-glass:hover:not(:disabled) {
  background: #96334F;
  box-shadow: 0 6px 16px rgba(179, 63, 98, 0.4);
}
.btn-primary-glass:focus {
  @apply ring-primary/50 ring-offset-white dark:ring-offset-gray-900;
}

/* Glass Ghost Button */
.btn-ghost-glass {
  @apply inline-flex items-center justify-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed;
  background: rgba(255, 255, 255, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.4);
  color: #33222A;
}
.btn-ghost-glass:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.5);
  border-color: rgba(255, 255, 255, 0.6);
}

/* Checkbox in glass context */
.glass-login-card .checkbox {
  @apply border-white/60 text-primary focus:ring-primary/50;
}

/* Responsive */
@media (max-width: 480px) {
  .glass-login-card {
    padding: 24px;
    border-radius: 16px;
  }
}
</style>