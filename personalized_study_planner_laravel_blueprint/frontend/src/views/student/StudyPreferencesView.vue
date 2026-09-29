<script setup lang="ts>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { useForm } from 'vee-validate'
import * as zod from 'zod'
import { 
  ClockIcon, CheckCircleIcon, ArrowRightIcon, 
  ArrowLeftIcon, BookOpenIcon, LightBulbIcon
} from '@heroicons/vue/24/outline'

const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(false)
const preferences = ref<import('@/types').StudyPreference | null>(null)

const preferencesSchema = zod.object({
  max_daily_minutes: zod.number().min(15).max(720),
  session_duration_minutes: zod.number().min(10).max(180),
  break_duration_minutes: zod.number().min(1).max(60),
  preferred_start_time: zod.string().regex(/^([01]\d|2[0-3]):([0-5]\d)$/, 'Invalid time format (HH:MM)'),
  preferred_end_time: zod.string().regex(/^([01]\d|2[0-3]):([0-5]\d)$/, 'Invalid time format (HH:MM)'),
})

const preferencesForm = useForm({
  validationSchema: zod.object({
    max_daily_minutes: zod.number().min(15).max(720),
    session_duration_minutes: zod.number().min(10).max(180),
    break_duration_minutes: zod.number().min(1).max(60),
    preferred_start_time: zod.string().regex(/^([01]\d|2[0-3]):([0-5]\d)$/, 'Invalid time format (HH:MM)'),
    preferred_end_time: zod.string().regex(/^([01]\d|2[0-3]):([0-5]\d)$/, 'Invalid time format (HH:MM)'),
  }),
  initialValues: {
    max_daily_minutes: 180,
    session_duration_minutes: 50,
    break_duration_minutes: 10,
    preferred_start_time: '18:00',
    preferred_end_time: '22:00',
  },
)

async function fetchPreferences() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get('/student/study-preference')
    preferences.value = response.data.data
    if (preferences.value) {
      preferencesForm.setValues({
        max_daily_minutes: preferences.value.max_daily_minutes,
        session_duration_minutes: preferences.value.session_duration_minutes,
        break_duration_minutes: preferences.value.break_duration_minutes,
        preferred_start_time: preferences.value.preferred_start_time,
        preferred_end_time: preferences.value.preferred_end_time,
      })
    }
  } catch (error: any) {
    toastStore.error('Failed to load preferences', error.message)
  } finally {
    loading.value = false
  }
}

async function submitPreferences() {
  loading.value = true
  try {
    await preferencesForm.validate()
    const api = (await import('@/utils/api')).default
    await api.post('/student/study-preference', preferencesForm.values)
    toastStore.success('Study preferences saved!')
  } catch (error: any) {
    toastStore.error('Failed to save preferences', error.message)
  } finally {
    loading.value = false
  }
}

async function createDefaults() {
  loading.value = true
  try {
    const api = (await import('@/utils/api')).default
    await api.post('/student/study-preference/defaults')
    toastStore.success('Default preferences created!')
    await fetchPreferences()
  } catch (error: any) {
    toastStore.error('Failed to create defaults', error.message)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <DefaultLayout>
    <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex items-center gap-4 mb-8">
        <RouterLink to="/dashboard" class="p-2 rounded-xl text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 hover:bg-pink-50/50 dark:hover:bg-pink-900/20 transition-colors">
          <ArrowLeftIcon class="w-6 h-6" aria-hidden="true" />
        </RouterLink>
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Study Preferences</h1>
          <p class="text-gray-600 dark:text-gray-400">Configure your study schedule and preferences</p>
        </div>
      </div>

      <div v-if="loading" class="animate-pulse space-y-6">
        <div class="glass-card-lg rounded-2xl p-6">
          <div class="h-6 bg-gray-200/50 dark:bg-gray-700/50 rounded w-3/4 mb-4"></div>
          <div class="h-4 bg-gray-200/50 dark:bg-gray-700/50 rounded w-1/2"></div>
        </div>
        <div class="glass-card-lg rounded-2xl p-6">
          <div class="h-6 bg-gray-200/50 dark:bg-gray-700/50 rounded w-1/2 mb-4"></div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div v-for="i in 3" :key="i" class="h-10 bg-gray-200/50 dark:bg-gray-700/50 rounded"></div>
          </div>
          <div class="h-4 bg-gray-200/50 dark:bg-gray-700/50 rounded w-full mt-4"></div>
          <div class="h-4 bg-gray-200/50 dark:bg-gray-700/50 rounded w-full mt-4"></div>
        </div>
      </div>

      <div v-else class="space-y-8">
        <div class="glass-card-lg rounded-2xl p-8">
          <div class="text-center mb-8">
            <ClockIcon class="w-12 h-12 mx-auto text-pink-600 dark:text-pink-400 mb-4" aria-hidden="true" />
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Study Preferences</h2>
            <p class="text-gray-600 dark:text-gray-400">Set your preferred study hours and session preferences</p>
          </div>

          <form @submit.prevent="submitPreferences" class="space-y-6" novalidate>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="max_daily_minutes" class="input-label">Max Daily Study Time (minutes)</label>
                <input
                  id="max_daily_minutes"
                  type="number"
                  v-model="preferencesForm.values.max_daily_minutes"
                  min="15"
                  max="720"
                  class="input-field"
                  :class="{ 'input-error': preferencesForm.errors.max_daily_minutes }"
                />
                <p v-if="preferencesForm.errors.max_daily_minutes" class="input-error-message">{{ preferencesForm.errors.max_daily_minutes }}</p>
              </div>

              <div>
                <label for="session_duration_minutes" class="input-label">Session Duration (minutes)</label>
                <input
                  id="session_duration_minutes"
                  type="number"
                  v-model="preferencesForm.values.session_duration_minutes"
                  min="10"
                  max="180"
                  class="input-field"
                  :class="{ 'input-error': preferencesForm.errors.session_duration_minutes }"
                />
                <p v-if="preferencesForm.errors.session_duration_minutes" class="input-error-message">{{ preferencesForm.errors.session_duration_minutes }}</p>
              </div>

              <div>
                <label for="break_duration_minutes" class="input-label">Break Duration (minutes)</label>
                <input
                  id="break_duration_minutes"
                  type="number"
                  v-model="preferencesForm.values.break_duration_minutes"
                  min="1"
                  max="60"
                  class="input-field"
                  :class="{ 'input-error': preferencesForm.errors.break_duration_minutes }"
                />
                <p v-if="preferencesForm.errors.break_duration_minutes" class="input-error-message">{{ preferencesForm.errors.break_duration_minutes }}</p>
              </div>

              <div>
                <label for="preferred_start_time" class="input-label">Preferred Start Time</label>
                <input
                  id="preferred_start_time"
                  type="time"
                  v-model="preferencesForm.values.preferred_start_time"
                  class="input-field"
                  :class="{ 'input-error': preferencesForm.errors.preferred_start_time }"
                />
                <p v-if="preferencesForm.errors.preferred_start_time" class="input-error-message">{{ preferencesForm.errors.preferred_start_time }}</p>
              </div>

              <div class="md:col-span-2">
                <label for="preferred_end_time" class="input-label">Preferred End Time</label>
                <input
                  id="preferred_end_time"
                  type="time"
                  v-model="preferencesForm.values.preferred_end_time"
                  class="input-field"
                  :class="{ 'input-error': preferencesForm.errors.preferred_end_time }"
                />
                <p v-if="preferencesForm.errors.preferred_end_time" class="input-error-message">{{ preferencesForm.errors.preferred_end_time }}</p>
              </div>
            </div>

            <div class="flex gap-4 pt-4">
              <button type="button" @click="createDefaults" class="btn-secondary flex-1" :disabled="loading || preferences">
                Create Defaults
              </button>
              <button type="submit" :disabled="loading" class="btn-primary flex-1">
                <span v-if="loading" class="flex items-center justify-center gap-2">
                  <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                  Saving...
                </span>
                <span v-else>Save Preferences</span>
              </button>
            </div>
          </form>
        </div>

        <!-- Tips -->
        <div class="glass-card rounded-2xl p-6 bg-pink-50/50 dark:bg-pink-900/20">
          <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
            <LightBulbIcon class="w-5 h-5 text-pink-600 dark:text-pink-400" aria-hidden="true" />
            Tips for Effective Studying
          </h3>
          <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
            <li class="flex items-center gap-2"><CheckCircleIcon class="w-4 h-4 text-green-500" aria-hidden="true" /> Set consistent study hours that match your natural rhythm</li>
            <li class="flex items-center gap-2"><CheckCircleIcon class="w-4 h-4 text-green-500" aria-hidden="true" /> Keep sessions between 25-50 minutes with short breaks</li>
            <li class="flex items-center gap-2"><CheckCircleIcon class="w-4 h-4 text-green-500" aria-hidden="true" /> Schedule harder subjects during your peak focus hours</li>
            <li class="flex items-center gap-2"><CheckCircleIcon class="w-4 h-4 text-green-500" aria-hidden="true" /> Review material at increasing intervals for better retention</li>
          </ul>
        </div>
      </div>
    </DefaultLayout>
  </template>

  <script>
  import { onMounted } from 'vue'

  onMounted(async () => {
    await fetchPreferences()
  })
  </script>