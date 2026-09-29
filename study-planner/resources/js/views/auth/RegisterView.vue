<template>
  <div class="min-h-screen flex items-center justify-center bg-brand-background py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
    <!-- Aurora decorative blobs -->
    <div class="absolute -top-20 -right-20 w-72 h-72 bg-white/30 rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>
    <div class="absolute -bottom-20 -left-20 w-96 h-96 bg-brand-borders/40 rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>
    
    <div class="relative w-full max-w-[420px] mx-auto">
      <!-- Glass Card -->
      <div class="glass-login-card p-10">
        <!-- Icon & Headline -->
        <div class="text-center mb-10">
          <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-primary-light mb-6" aria-hidden="true">
            <svg class="w-8 h-8 text-primary" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4v10.55c-.59.34-1.27.55-2 .55-2.21 0-4-1.79-4-4S9.79 3 12 3z"/>
            </svg>
          </div>
          <h1 class="text-[28px] font-bold text-text-primary mb-2">Create your account</h1>
          <p class="text-text-secondary">
            Already have an account? <router-link to="/login" class="text-primary hover:text-primary-hover font-medium transition-colors">Sign in</router-link>
          </p>
        </div>

        <!-- Register Form -->
        <form class="space-y-6" @submit.prevent="handleSubmit" novalidate>
          <div class="space-y-4">
            <div>
              <label for="name" class="input-label">Full name</label>
              <input
                id="name"
                name="name"
                type="text"
                autocomplete="name"
                required
                v-model="form.name"
                :class="['input-field-glass', { 'input-error': errors.name }]"
                placeholder="Full name"
                aria-describedby="name-error"
              />
              <p v-if="errors.name" id="name-error" class="input-error-message">{{ errors.name }}</p>
            </div>
            <div>
              <label for="email" class="input-label">Email address</label>
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
              <input
                id="password"
                name="password"
                type="password"
                autocomplete="new-password"
                required
                v-model="form.password"
                :class="['input-field-glass', { 'input-error': errors.password }]"
                placeholder="Password (min 8 characters)"
                aria-describedby="password-error"
              />
              <p v-if="errors.password" id="password-error" class="input-error-message">{{ errors.password }}</p>
            </div>
            <div>
              <label for="password_confirmation" class="input-label">Confirm password</label>
              <input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                autocomplete="new-password"
                required
                v-model="form.password_confirmation"
                :class="['input-field-glass', { 'input-error': errors.password_confirmation }]"
                placeholder="Confirm password"
                aria-describedby="password-confirmation-error"
              />
              <p v-if="errors.password_confirmation" id="password-confirmation-error" class="input-error-message">{{ errors.password_confirmation }}</p>
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
                Creating account...
              </span>
              <span v-else>Create account</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const router = useRouter();

const form = ref({ name: '', email: '', password: '', password_confirmation: '' });
const errors = ref<Record<string, string>>({});
const error = ref<string | null>(null);
const loading = ref(false);

async function handleSubmit() {
  errors.value = {};
  error.value = null;
  loading.value = true;

  try {
    await authStore.register(form.value.name, form.value.email, form.value.password, form.value.password_confirmation);
    router.push('/dashboard');
  } catch (err: any) {
    if (err.response?.data?.errors) {
      errors.value = err.response.data.errors;
    } else {
      error.value = err.response?.data?.message || 'Registration failed. Please try again.';
    }
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
/* Glass Login Card */
.glass-login-card {
  background: rgba(255, 255, 255, 0.35);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.5);
  border-radius: 20px;
  box-shadow: 0 8px 32px rgba(179, 63, 98, 0.15);
  padding: 40px;
  max-width: 420px;
  margin: 0 auto;
}

/* Glass Input Fields */
.input-field-glass {
  width: 100%;
  padding: 14px 16px;
  color: #33222A;
  border-radius: 10px;
  transition: all 200ms ease;
  background: rgba(255, 255, 255, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.6);
  font-size: 1rem;
}
.input-field-glass::placeholder {
  color: #7A5C66;
}
.input-field-glass:focus {
  outline: none;
  border-color: #B33F62;
  box-shadow: 0 0 0 2px rgba(179, 63, 98, 0.3);
  background: rgba(255, 255, 255, 0.7);
}

/* Glass Primary Button */
.btn-primary-glass {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 14px 16px;
  color: white;
  font-weight: 600;
  border-radius: 10px;
  transition: all 200ms ease;
  background: #B33F62;
  box-shadow: 0 4px 12px rgba(179, 63, 98, 0.3);
  border: none;
  cursor: pointer;
  font-size: 1rem;
  width: 100%;
}
.btn-primary-glass:hover:not(:disabled) {
  background: #96334F;
  box-shadow: 0 6px 16px rgba(179, 63, 98, 0.4);
}
.btn-primary-glass:focus {
  box-shadow: 0 0 0 2px #FFF5F7, 0 0 0 4px rgba(179, 63, 98, 0.5);
}
.btn-primary-glass:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Input label */
.input-label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: #7A5C66;
  margin-bottom: 0.5rem;
}

/* Error message */
.input-error-message {
  margin-top: 0.375rem;
  font-size: 0.875rem;
  color: #C6485F;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

/* Responsive */
@media (max-width: 480px) {
  .glass-login-card {
    padding: 24px;
    border-radius: 16px;
  }
}
</style>