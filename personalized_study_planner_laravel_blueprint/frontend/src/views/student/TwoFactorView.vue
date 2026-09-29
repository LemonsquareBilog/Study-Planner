<script setup lang="ts>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { useForm } from 'vee-validate'
import * as zod from 'zod'
import { 
  ArrowLeftIcon, CheckCircleIcon, ExclamationTriangleIcon,
  KeyIcon, ShieldCheckIcon, EyeIcon, EyeSlashIcon
} from '@heroicons/vue/24/outline'

const authStore = useAuthStore()
const toastStore = useToastStore()

const showSetup = ref(false)
const verifying = ref(false)
const loading = ref(false)
const qrCode = ref<string>('')
const secret = ref<string>('')
const recoveryCodes = ref<string[]>([])
const verified = ref(false)

const verifySchema = zod.object({
  code: zod.string().length(6, 'Code must be 6 digits'),
})

const verifyForm = useForm({
  validationSchema: zod.object({
    code: zod.string().length(6, 'Code must be 6 digits'),
  }),
  initialValues: {
    code: '',
  },
})

async function fetch2FAStatus() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get('/auth/me/2fa/qr')
    qrCode.value = response.data.data.qr_code_svg
    secret.value = response.data.data.secret
    recoveryCodes.value = response.data.data.recovery_codes || []
    verified.value = true
    showSetup.value = false
  } catch (error: any) {
    if (error.response?.status === 422) {
      // 2FA not set up yet
      return
    }
    toastStore.error('Failed to load 2FA status', error.message)
  }
}

async function enable2FA() {
  loading.value = true
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.post('/auth/me/2fa/enable')
    qrCode.value = response.data.data.qr_code_svg
    secret.value = response.data.data.secret
    recoveryCodes.value = response.data.data.recovery_codes || []
    showSetup.value = true
    toastStore.success('2FA setup initiated', 'Scan the QR code with your authenticator app')
  } catch (error: any) {
    toastStore.error('Failed to enable 2FA', error.message)
  } finally {
    loading.value = false
  }
}

async function verify2FA() {
  verifying.value = true
  try {
    await verifyForm.validate()
    const api = (await import('@/utils/api')).default
    await api.post('/auth/me/2fa/verify', { code: verifyForm.values.code })
    verified.value = true
    showSetup.value = false
    toastStore.success('2FA enabled!', 'Your account is now protected with two-factor authentication')
  } catch (error: any) {
    toastStore.error('Invalid code', 'Please check your authenticator app and try again')
  } finally {
    verifying.value = false
  }
}

async function disable2FA() {
  const password = prompt('Enter your password to confirm:')
  if (!password) return
  
  loading.value = true
  try {
    const api = (await import('@/utils/api')).default
    await api.post('/auth/me/2fa/disable', { password })
    toastStore.success('2FA disabled!')
    // Reset state
    qrCode.value = ''
    secret.value = ''
    recoveryCodes.value = []
    verified.value = false
    showSetup.value = false
  } catch (error: any) {
    toastStore.error('Failed to disable 2FA', error.message)
  } finally {
    loading.value = false
  }
}

async function fetch2FAQR() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get('/auth/me/2fa/qr')
    qrCode.value = response.data.data.qr_code_svg
    secret.value = response.data.data.secret
    recoveryCodes.value = response.data.data.recovery_codes || []
  } catch {
    // silent
  }
}

function copySecret() {
  navigator.clipboard.writeText(secret.value)
  toastStore.success('Secret copied!')
}

function copyRecoveryCodes() {
  navigator.clipboard.writeText(recoveryCodes.value.join('\n'))
  toastStore.success('Recovery codes copied!')
}

onMounted(async () => {
  await fetch2FAStatus()
})
</script>

<template>
  <DefaultLayout>
    <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex items-center gap-4 mb-8">
        <RouterLink to="/settings/profile" class="p-2 rounded-xl text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 hover:bg-pink-50/50 dark:hover:bg-pink-900/20 transition-colors">
          <ArrowLeftIcon class="w-6 h-6" aria-hidden="true" />
        </RouterLink>
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Two-Factor Authentication</h1>
          <p class="text-gray-600 dark:text-gray-400">Add an extra layer of security to your account</p>
        </div>
      </div>

      <div v-if="!verified && !showSetup" class="glass-card-lg rounded-2xl p-8 text-center">
        <div class="w-20 h-20 mx-auto mb-6 bg-pink-100/80 dark:bg-pink-900/30 rounded-full flex items-center justify-center">
          <ShieldCheckIcon class="w-10 h-10 text-pink-600 dark:text-pink-400" aria-hidden="true" />
        </div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Two-Factor Authentication</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-8 max-w-md mx-auto">
          Add an extra layer of security to your account. With 2FA enabled, you'll need to enter a code from your authenticator app each time you sign in.
        </p>
        <button @click="enable2FA" :disabled="loading" class="btn-primary w-full">
          <span v-if="loading" class="flex items-center justify-center gap-2">
            <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
            Enabling...
          </span>
          <span v-else>Enable Two-Factor Authentication</span>
        </button>
      </div>

      <!-- Setup 2FA -->
      <div v-else-if="showSetup && !verified" class="space-y-8">
        <div class="glass-card-lg rounded-2xl p-8">
          <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Set Up Two-Factor Authentication</h2>
            <p class="text-gray-600 dark:text-gray-400">Scan the QR code with your authenticator app (Google Authenticator, Authy, etc.)</p>
          </div>

          <div class="flex flex-col items-center gap-6">
            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl inline-block">
              <div v-if="qrCode" class="whitespace-pre-wrap" v-html="qrCode"></div>
              <div v-else class="w-64 h-64 bg-gray-100 dark:bg-gray-800 rounded-xl flex items-center justify-center text-gray-400">
                Generating QR code...
              </div>
            </div>
            
            <div class="text-center">
              <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Can't scan? Enter this secret key manually:</p>
              <div class="flex items-center gap-2 justify-center mb-4">
                <code class="px-4 py-2 bg-gray-100 dark:bg-gray-800 rounded-lg font-mono text-sm text-gray-900 dark:text-white select-all" @click="copySecret">{{ secret }}</code>
                <button @click="copySecret" class="text-sm text-pink-600 dark:text-pink-400 hover:underline">Copy</button>
              </div>
            </div>
          </div>

          <div class="glass-card rounded-xl p-4 bg-amber-50/50 dark:bg-amber-900/30 border border-amber-200/50 dark:border-amber-800/50 mb-6">
            <h4 class="font-medium text-amber-800 dark:text-amber-200 mb-2 flex items-center gap-2">
              <ExclamationTriangleIcon class="w-5 h-5" aria-hidden="true" />
              Important: Save Recovery Codes
            </h4>
            <p class="text-sm text-amber-800 dark:text-amber-200 mb-4">Save these codes in a safe place. They can be used to access your account if you lose access to your authenticator app.</p>
            <div class="grid grid-cols-2 gap-2 text-sm font-mono bg-gray-100 dark:bg-gray-800 rounded-lg p-4">
              <span v-for="(code, index) in recoveryCodes" :key="index" class="px-2 py-1 bg-white dark:bg-gray-700 rounded">{{ code }}</span>
            </div>
            <div class="flex gap-2 mt-4">
              <button @click="copyRecoveryCodes" class="btn-secondary text-sm flex-1">Copy All Codes</button>
              <button @click="verifyForm.code = ''; verified = false" class="btn-secondary text-sm flex-1">I'll Save These Later</button>
            </div>
          </div>

          <div class="mt-6">
            <form @submit.prevent="verify2FA" class="space-y-4" novalidate>
              <h4 class="font-medium text-gray-900 dark:text-white mb-4">Verify Your Setup</h4>
              <div>
                <label for="code" class="input-label">Enter 6-digit code from authenticator app</label>
                <input
                  id="code"
                  type="text"
                  v-model="verifyForm.values.code"
                  maxlength="6"
                  autocomplete="one-time-code"
                  inputmode="numeric"
                  class="input-field text-center text-2xl tracking-widest"
                  placeholder="000000"
                  :class="{ 'input-error': verifyForm.errors.code }"
                  aria-describedby="2fa-hint"
                />
                <p id="2fa-hint" class="input-hint">Enter the 6-digit code from your authenticator app</p>
                <p v-if="verifyForm.errors.code" class="input-error-message">{{ verifyForm.errors.code }}</p>
              </div>

              <button type="submit" :disabled="verifying" class="btn-primary w-full">
                <span v-if="verifying" class="flex items-center justify-center gap-2">
                  <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                  Verifying...
                </span>
                <span v-else>Verify & Enable 2FA</span>
              </button>

              <p class="text-center text-sm text-gray-500 dark:text-gray-400">
                Didn't receive a code? <button @click="fetch2FAQR" class="text-pink-600 dark:text-pink-400 hover:underline">Regenerate QR code</button>
              </p>
            </form>
          </div>
        </div>
      </div>

      <div v-else class="glass-card-lg rounded-2xl p-8">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Two-Factor Authentication Enabled</h2>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Your account is protected with two-factor authentication</p>
          </div>
          <span class="badge badge-green">Enabled</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <div class="glass-card rounded-xl p-6">
            <h3 class="font-medium text-gray-900 dark:text-white mb-4 flex items-center gap-2">
              <ShieldCheckIcon class="w-5 h-5 text-green-500" aria-hidden="true" />
              Recovery Codes
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">You have <span class="font-medium">{{ recoveryCodes.length }}</span> recovery codes remaining</p>
            <div class="grid grid-cols-2 gap-2 text-sm font-mono bg-gray-100 dark:bg-gray-800 rounded-lg p-4">
              <span v-for="(code, index) in recoveryCodes" :key="index" class="px-2 py-1 bg-white dark:bg-gray-700 rounded">{{ code }}</span>
            </div>
            <div class="flex gap-2 mt-4">
              <button @click="copyRecoveryCodes" class="btn-secondary text-sm flex-1">Copy All Codes</button>
              <button @click="fetch2FAQR" class="btn-secondary text-sm flex-1">Regenerate QR Code</button>
            </div>
          </div>

          <div class="glass-card rounded-xl p-6 bg-red-50/50 dark:bg-red-900/20 border border-red-200/50 dark:border-red-800/50">
            <h3 class="font-medium text-red-800 dark:text-red-200 mb-4 flex items-center gap-2">
              <ExclamationTriangleIcon class="w-5 h-5" aria-hidden="true" />
              Disable Two-Factor Authentication
            </h3>
            <p class="text-sm text-red-700 dark:text-red-300 mb-4">Disabling 2FA will remove the extra layer of security from your account. You'll only need your password to sign in.</p>
            <button @click="disable2FA" :disabled="loading" class="btn-danger">
              <span v-if="loading" class="flex items-center justify-center gap-2">
                <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                Disabling...
              </span>
              <span v-else>Disable 2FA</span>
            </button>
          </div>
        </div>
      </div>
    </DefaultLayout>
  </template>

  <script>
  import { onMounted } from 'vue'

  onMounted(async () => {
    await fetch2FAStatus()
  })
  </script>