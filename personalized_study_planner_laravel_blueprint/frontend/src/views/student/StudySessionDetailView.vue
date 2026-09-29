<script setup lang="ts>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { RouterLink } from 'vue-router'
import { 
  ArrowLeftIcon, CalendarDaysIcon, ClockIcon, 
  CheckCircleIcon, BookOpenIcon, LightBulbIcon,
  ArrowRightIcon, PlayIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(true)
const session = ref<import('@/types').StudySession | null>(null)

async function fetchSession() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get(`/student/study-sessions/${route.params.id}`)
    session.value = response.data.data
  } catch (error: any) {
    toastStore.error('Failed to load session', error.message)
    router.push('/study-plan')
  } finally {
    loading.value = false
  }
}

async function updateStatus(status: string) {
  try {
    const api = (await import('@/utils/api')).default
    await api.put(`/student/study-sessions/${route.params.id}/status`, { status })
    toastStore.success(`Session marked as ${status}`)
    await fetchSession()
  } catch (error: any) {
    toastStore.error('Failed to update session', error.message)
  }
}

function formatTime(timeString: string): string {
  const [hours, minutes] = timeString.split(':')
  const hour = parseInt(hours)
  const ampm = hour >= 12 ? 'PM' : 'AM'
  const hour12 = hour % 12 || 12
  return `${hour12}:${minutes} ${ampm}`
}

function formatRelativeTime(dateString: string): string {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)
  
  if (diffMins < 1) return 'Just now'
  if (diffMins < 60) return `${diffMins}m ago`
  if (diffHours < 24) return `${diffHours}h ago`
  if (diffDays < 7) return `${diffDays}d ago`
  return date.toLocaleDateString()
}

function getStatusBadgeClass(status: string): string {
  switch (status) {
    case 'scheduled': return 'badge-blue'
    case 'in_progress': return 'badge-yellow'
    case 'completed': return 'badge-green'
    case 'skipped': return 'badge-gray'
    default: return 'badge-gray'
  }
}

function getStatusLabel(status: string): string {
  switch (status) {
    case 'in_progress': return 'In Progress'
    case 'completed': return 'Completed'
    case 'scheduled': return 'Scheduled'
    case 'skipped': return 'Skipped'
    default: return status
  }
}
</script>

<template>
  <DefaultLayout>
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-8">
      <div v-if="loading" class="animate-pulse space-y-6">
        <div class="h-8 bg-gray-200/50 dark:bg-gray-700/50 rounded w-1/4"></div>
        <div class="glass-card-lg rounded-2xl p-6">
          <div class="h-6 bg-gray-200/50 dark:bg-gray-700/50 rounded w-3/4 mb-4"></div>
          <div class="h-4 bg-gray-200/50 dark:bg-gray-700/50 rounded w-1/2"></div>
        </div>
      </div>

      <div v-else class="space-y-6">
        <div class="flex items-center gap-4 mb-8">
          <RouterLink to="/study-plan" class="p-2 rounded-xl text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 hover:bg-pink-50/50 dark:hover:bg-pink-900/20 transition-colors">
            <ArrowLeftIcon class="w-6 h-6" aria-hidden="true" />
          </RouterLink>
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ session?.subject?.name || 'Study Session' }}</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ session?.topic?.name || 'General review' }}</p>
          </div>
        </div>

        <div class="glass-card-lg rounded-2xl p-8">
          <div class="flex items-center justify-between mb-8">
            <div>
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ session?.subject?.name || 'Study Session' }}</h1>
              <p class="text-gray-600 dark:text-gray-400">{{ session?.topic?.name || 'General review' }}</p>
            </div>
            <span :class="['badge', getStatusBadgeClass(session?.status)]">{{ getStatusLabel(session?.status) }}</span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="glass-card rounded-xl p-6 text-center">
              <CalendarDaysIcon class="w-8 h-8 mx-auto text-pink-600 dark:text-pink-400 mb-2" aria-hidden="true" />
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Date</p>
              <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ formatRelativeTime(session?.scheduled_date) }}</p>
            </div>
            <div class="glass-card rounded-xl p-6 text-center">
              <ClockIcon class="w-8 h-8 mx-auto text-pink-600 dark:text-pink-400 mb-2" aria-hidden="true" />
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Time</p>
              <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ formatTime(session?.start_time) }} - {{ formatTime(session?.end_time) }}</p>
            </div>
            <div class="glass-card rounded-xl p-6 text-center">
              <ClockIcon class="w-8 h-8 mx-auto text-pink-600 dark:text-pink-400 mb-2" aria-hidden="true" />
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Duration</p>
              <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ session?.duration_minutes }} minutes</p>
            </div>
          </div>

          <div v-if="session?.task" class="glass-card-lg rounded-2xl p-6 mb-8">
            <div class="flex items-center gap-4">
              <div class="w-14 h-14 rounded-xl bg-pink-100/80 dark:bg-pink-900/30 flex items-center justify-center text-pink-600 dark:text-pink-400">
                <CheckCircleIcon class="w-7 h-7" aria-hidden="true" />
              </div>
              <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Linked Task</h3>
                <p class="text-gray-600 dark:text-gray-400">{{ session?.task?.title }}</p>
              </div>
            </div>
          </div>

          <!-- Status Actions -->
          <div class="space-y-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Actions</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              <button 
                v-if="session?.status === 'scheduled'"
                @click="updateStatus('in_progress')"
                class="btn-primary flex items-center justify-center gap-2"
              >
                <PlayIcon class="w-5 h-5" aria-hidden="true" />
                Start Session
              </button>
              <button 
                v-if="session?.status === 'scheduled'"
                @click="updateStatus('skipped')"
                class="btn-secondary"
              >
                Skip Session
              </button>
              <button 
                v-if="session?.status === 'in_progress'"
                @click="updateStatus('completed')"
                class="btn-primary flex items-center justify-center gap-2"
              >
                <CheckCircleIcon class="w-5 h-5" aria-hidden="true" />
                Complete Session
              </button>
              <button 
                v-if="session?.status === 'in_progress'"
                @click="updateStatus('skipped')"
                class="btn-secondary"
              >
                Skip
              </button>
              <button 
                v-if="session?.status === 'skipped'"
                @click="updateStatus('scheduled')"
                class="btn-secondary"
              >
                <ArrowRightIcon class="w-5 h-5 mr-2" aria-hidden="true" />
                Reschedule
              </button>
              <button 
                v-if="session?.status === 'completed'"
                @click="updateStatus('scheduled')"
                class="btn-secondary"
                disabled
              >
                Reschedule (Completed)
              </button>
            </div>
          </div>

          <!-- Session Details -->
          <div class="glass-card-lg rounded-2xl p-6 mt-8">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Session Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
              <div>
                <p class="text-gray-500 dark:text-gray-400">Subject</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ session?.subject?.name }}</p>
              </div>
              <div>
                <p class="text-gray-500 dark:text-gray-400">Topic</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ session?.topic?.name || 'General review' }}</p>
              </div>
              <div>
                <p class="text-gray-500 dark:text-gray-400">Date</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ formatRelativeTime(session?.scheduled_date) }}</p>
              </div>
              <div>
                <p class="text-gray-500 dark:text-gray-400">Time</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ formatTime(session?.start_time) }} - {{ formatTime(session?.end_time) }}</p>
              </div>
              <div>
                <p class="text-gray-500 dark:text-gray-400">Duration</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ session?.duration_minutes }} minutes</p>
              </div>
              <div>
                <p class="text-gray-500 dark:text-gray-400">Status</p>
                <p class="font-medium text-gray-900 dark:text-white"><span :class="['badge', getStatusBadgeClass(session?.status)]">{{ getStatusLabel(session?.status) }}</span></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </DefaultLayout>
  </template>

  <script>
  import { onMounted } from 'vue'

  onMounted(async () => {
    await fetchSession()
  })
  </script>