<template>
  <div class="min-h-screen flex items-center justify-center bg-brand-background py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
    <!-- Aurora decorative blobs -->
    <div class="absolute -top-20 -right-20 w-72 h-72 bg-white/30 rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>
    <div class="absolute -bottom-20 -left-20 w-96 h-96 bg-brand-borders/40 rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>
    
    <div class="relative w-full max-w-[440px] mx-auto">
      <!-- Glass Card -->
      <div class="glass-login-card p-8 sm:p-10">
        <!-- Icon & Headline -->
        <div class="text-center mb-8">
          <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-primary-light mb-4 shadow-sm" aria-hidden="true">
            <svg class="w-8 h-8 text-primary" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4v10.55c-.59.34-1.27.55-2 .55-2.21 0-4-1.79-4-4S9.79 3 12 3z"/>
            </svg>
          </div>
          <h1 class="text-2xl sm:text-[28px] font-bold text-text-primary mb-1">Study Planner</h1>
          <p class="text-sm text-text-secondary">
            Personalized Learning & Study System
          </p>
        </div>


        <!-- Login Form -->
        <form class="space-y-5" @submit.prevent="handleSubmit" novalidate>
          <div class="space-y-4">
            <div>
              <label for="email" class="input-label">Email</label>
              <input
                id="email"
                name="email"
                type="email"
                autocomplete="email"
                required
                v-model="form.email"
                :class="['input-field-glass', { 'input-error': errors.email }]"
                placeholder="Email address"
                aria-describedby="email-error"
              />
              <p v-if="errors.email" id="email-error" class="input-error-message">{{ errors.email }}</p>
            </div>
            <div>
              <label for="password" class="input-label">Password</label>
              <div class="relative">
                <input
                  id="password"
                  name="password"
                  :type="showPassword ? 'text' : 'password'"
                  autocomplete="current-password"
                  required
                  v-model="form.password"
                  :class="['input-field-glass', { 'input-error': errors.password }]"
                  placeholder="Password"
                  aria-describedby="password-error"
                  class="pr-12"
                />
                <button
                  type="button"
                  @click="showPassword = !showPassword"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-text-secondary hover:text-text-primary transition-colors"
                  :aria-label="showPassword ? 'Hide password' : 'Show password'"
                  aria-pressed="showPassword"
                >
                  <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                  </svg>
                </button>
              </div>
              <p v-if="errors.password" id="password-error" class="input-error-message">{{ errors.password }}</p>
            </div>
          </div>

          <div v-if="error" class="text-status-urgent text-sm text-center" role="alert">{{ error }}</div>

          <div>
            <button type="submit" :disabled="loading" class="btn-primary-glass w-full">
              <span v-if="loading" class="flex items-center justify-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                </svg>
                Signing in...
              </span>
              <span v-else>Sign in</span>
            </button>
          </div>
        </form>




        <div class="text-center mt-6">
          <p class="text-xs text-text-secondary">
            Or <router-link to="/register" class="text-primary hover:text-primary-hover font-medium transition-colors">create a new student account</router-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { authErrorMessage } from '@/services/authError';

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();

const form = ref({ email: '', password: '' });
const errors = ref<Record<string, string>>({});
const error = ref<string | null>(null);
const loading = ref(false);
const showPassword = ref(false);




async function handleSubmit() {
  errors.value = {};
  error.value = null;
  loading.value = true;

  try {
    await authStore.login(form.value.email, form.value.password);
    const redirect = (route.query.redirect as string) || (authStore.isAdmin ? '/admin' : '/dashboard');
    router.push(redirect);
  } catch (err: any) {
    if (err.response?.data?.errors) {
      errors.value = Object.fromEntries(Object.entries(err.response.data.errors).map(([key, value]) => [key, Array.isArray(value) ? value.join(' ') : String(value)]));
    } else {
      error.value = authErrorMessage(err);
    }
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
/* No scoped styles needed - using global classes from app.css */
</style>
