<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { useForm } from 'vee-validate'
import * as zod from 'zod'
import { RouterLink } from 'vue-router'
import { 
  CalendarDaysIcon, ArrowLeftIcon, PlusIcon, PencilIcon, 
  TrashIcon, CheckCircleIcon, ClockIcon, BookOpenIcon,
  LightBulbIcon, ArrowRightIcon
} from '@heroicons/vue/24/outline'
import type { StudySession, Subject } from '@/types'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(true)
const studyPlan = ref<StudySession[]>([])
const subjects = ref<Subject[]>([])
const showGenerateForm = ref(false)
const generating = ref(false)

const generateSchema = zod.object({
  from: zod.string().date(),
  to: zod.string().date(),
}).refine((data) => new Date(data.to) >= new Date(data.from), {
  message: 'End date must be after or equal to start date',
  path: ['to'],
})

const generateForm = useForm({
  validationSchema: generateSchema,
  initialValues: {
    from: new Date().toISOString().split('T')[0],
    to: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
  },
)

async function fetchStudyPlan() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get('/student/study-plan')
    studyPlan.value = response.data.data || []
  } catch (error: any) {
    toastStore.error('Failed to load study plan', error.message)
  } finally {
    loading.value = false
  }
}

async function fetchSubjects() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get('/student/subjects')
    subjects.value = response.data.data || []
  } catch {
    // silent
  }
}

async function generatePlan() {
  generating.value = true
  try {
    await generateForm.validate()
    const api = (await import('@/utils/api')).default
    const response = await api.post('/student/study-plan/generate', generateForm.values)
    toastStore.success(`${response.data.data.created_sessions} study sessions generated!`)
    showGenerateForm.value = false
    await fetchStudyPlan()
  } catch (error: any) {
    toastStore.error('Failed to generate plan', error.message)
  } finally {
    generating.value = false
  }
}

async function updateSessionStatus(session: StudySession, status: string) {
  try {
    const api = (await import('@/utils/api')).default
    await api.put(`/student/study-plan/${session.id}/status`, { status })
    toastStore.success(`Session marked as ${status}`)
    await fetchStudyPlan()
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

const groupedSessions = computed(() => {
  const groups: Record<string, StudySession[]> = {}
  studyPlan.value.forEach(session => {
    if (!groups[session.scheduled_date]) {
      groups[session.scheduled_date] = []
    }
    groups[session.scheduled_date].push(session)
  }
  return Object.entries(groups)
    .sort(([a], [b]) => new Date(a).getTime() - new Date(b).getTime())
    .map(([date, sessions]) => ({ date, sessions }))
}
</script>

<template>
  <DefaultLayout>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
          <RouterLink to="/dashboard" class="p-2 rounded-xl text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 hover:bg-pink-50/50 dark:hover:bg-pink-900/20 transition-colors">
            <ArrowLeftIcon class="w-6 h-6" aria-hidden="true" />
          </RouterLink>
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Study Plan</h1>
            <p class="text-gray-600 dark:text-gray-400">View and manage your study schedule</p>
          </div>
        </div>
        
        <div class="flex gap-3 ml-auto">
          <button @click="showGenerateForm = true" class="btn-primary">
            <PlusIcon class="w-5 h-5 mr-2" aria-hidden="true" />
            Generate Plan
          </button>
          <RouterLink to="/study-plan/calendar" class="btn-secondary">
            <CalendarDaysIcon class="w-5 h-5 mr-2" aria-hidden="true" />
            Calendar View
          </RouterLink>
        </div>
      </div>

      <!-- Generate Plan Modal -->
      <Transition name="slide-down">
        <div v-show="showGenerateForm" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
          <div class="glass-card-lg rounded-2xl p-6 max-w-md w-full animate-scale-in">
            <div class="flex items-center justify-between mb-6">
              <h2 class="text-xl font-bold text-gray-900 dark:text-white">Generate Study Plan</h2>
              <button @click="showGenerateForm = false" class="p-2 rounded-xl text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100/50 dark:hover:bg-gray-800/50 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>

            <form @submit.prevent="generatePlan" class="space-y-6" novalidate>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label for="from" class="input-label">Start Date</label>
                  <input
                    id="from"
                    type="date"
                    v-model="generateForm.values.from"
                    class="input-field"
                    :class="{ 'input-error': generateForm.errors.from }"
                    :min="new Date().toISOString().split('T')[0]"
                  />
                  <p v-if="generateForm.errors.from" class="input-error-message">{{ generateForm.errors.from }}</p>
                </div>

                <div>
                  <label for="to" class="input-label">End Date</label>
                  <input
                    id="to"
                    type="date"
                    v-model="generateForm.values.to"
                    class="input-field"
                    :class="{ 'input-error': generateForm.errors.to }"
                    :min="generateForm.values.from"
                  />
                  <p v-if="generateForm.errors.to" class="input-error-message">{{ generateForm.errors.to }}</p>
                </div>
              </div>

              <div class="glass-card rounded-xl p-4 bg-pink-50/50 dark:bg-pink-900/20">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  <strong>How it works:</strong> Based on your study preferences, tasks, and deadlines, our AI will generate an optimized study schedule that fits your available time and prioritizes urgent work.
                </p>
              </div>

              <div class="flex gap-4 pt-4">
                <button type="button" @click="showGenerateForm = false" class="btn-secondary flex-1">Cancel</button>
                <button type="submit" :disabled="generating" class="btn-primary flex-1">
                  <span v-if="generating" class="flex items-center justify-center gap-2">
                    <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                    Generating...
                  </span>
                  <span v-else>Generate Plan <ArrowRightIcon class="ml-2 w-5 h-5" aria-hidden="true" /></span>
                </button>
              </div>
            </form>
          </div>
        </Transition>

        <!-- Study Plan List -->
        <div v-if="loading" class="animate-pulse space-y-8">
          <div class="h-8 bg-gray-200/50 dark:bg-gray-700/50 rounded w-1/4"></div>
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="lg:col-span-2 h-64 bg-gray-200/50 dark:bg-gray-700/50 rounded-xl"></div>
            <div class="h-64 bg-gray-200/50 dark:bg-gray-700/50 rounded-xl"></div>
          </div>
        </div>

        <div v-else-if="studyPlan.length === 0" class="text-center py-16">
          <CalendarDaysIcon class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" aria-hidden="true" />
          <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No study plan yet</h3>
          <p class="text-gray-600 dark:text-gray-400 mb-6">Generate your first AI-powered study plan to get started</p>
          <button @click="showGenerateForm = true" class="btn-primary">Generate Plan</button>
        </div>

        <div v-else class="space-y-8">
          <div v-for="dateGroup in groupedSessions" :key="dateGroup.date" class="glass-card-lg rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-pink-200/50 dark:border-pink-800/50 bg-pink-50/50 dark:bg-pink-900/20">
              <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                  {{ formatRelativeTime(dateGroup.date) }}
                </h2>
                <span class="badge badge-pink">{{ dateGroup.sessions.length }} sessions</span>
              </div>
            </div>
            <div class="divide-y divide-pink-100/50 dark:divide-pink-800/50">
              <RouterLink 
                v-for="session in dateGroup.sessions" 
                :key="session.id" 
                :to="`/study-sessions/${session.id}`"
                class="flex items-center justify-between px-6 py-4 hover:bg-pink-50/30 dark:hover:bg-pink-900/10 transition-colors"
              >
                <div class="flex items-center gap-4">
                  <div class="w-14 h-14 rounded-xl bg-pink-100/80 dark:bg-pink-900/30 flex items-center justify-center text-pink-600 dark:text-pink-400">
                    <ClockIcon class="w-7 h-7" aria-hidden="true" />
                  </div>
                  <div>
                    <h3 class="font-medium text-gray-900 dark:text-white">{{ session.subject?.name || 'Study Session' }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ session.topic?.name || 'General review' }}</p>
                  </div>
                </div>
                <div class="flex items-center gap-4">
                  <div class="text-right">
                    <p class="font-medium text-gray-900 dark:text-white">{{ formatTime(session.start_time) }} - {{ formatTime(session.end_time) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ session.duration_minutes }} min</p>
                  </div>
                  <span :class="['badge', getStatusBadgeClass(session.status)]">{{ getStatusLabel(session.status) }}</span>
                  <ArrowRightIcon class="w-4 h-4 text-gray-400" aria-hidden="true" />
              </RouterLink>
            </div>
          </div>

          <!-- CTA for empty dates -->
          <div class="text-center py-8">
            <RouterLink to="/study-plan/generate" class="btn-primary">
              <PlusIcon class="w-5 h-5 mr-2" aria-hidden="true" />
              Generate More Sessions
            </RouterLink>
          </div>
        </div>
      </div>
    </DefaultLayout>
  </template>

  <script>
  import { ref, onMounted, computed } from 'vue'
  import { useRouter } from 'vue-router'
  import { useAuthStore } from '@/stores/auth'
  import { useToastStore } from '@/stores/toast'
  import { useForm } from 'vee-validate'
  import * as zod from 'zod'
  import { RouterLink } from 'vue-router'
  import { 
    CalendarDaysIcon, ArrowLeftIcon, PlusIcon, PencilIcon, 
    TrashIcon, CheckCircleIcon, ClockIcon, BookOpenIcon,
    LightBulbIcon, ArrowRightIcon
  } from '@heroicons/vue/24/outline'

  const router = useRouter()
  const route = useRoute()
  const authStore = useAuthStore()
  const toastStore = useToastStore()

  const loading = ref(true)
  const studyPlan = ref<StudySession[]>([])
  const subjects = ref<Subject[]>([])
  const showGenerateForm = ref(false)
  const generating = ref(false)

  const generateSchema = zod.object({
    from: zod.string().date(),
    to: zod.string().date(),
  }).refine((data) => new Date(data.to) >= new Date(data.from), {
    message: 'End date must be after or equal to start date',
    path: ['to'],
  })

  const generateForm = useForm({
    validationSchema: generateSchema,
    initialValues: {
      from: new Date().toISOString().split('T')[0],
      to: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    },
  )

  async function fetchStudyPlan() {
    try {
      const api = (await import('@/utils/api')).default
      const response = await api.get('/student/study-plan')
      studyPlan.value = response.data.data || []
    } catch (error: any) {
      toastStore.error('Failed to load study plan', error.message)
    } finally {
      loading.value = false
    }
  }

  async function fetchSubjects() {
    try {
      const api = (await import('@/utils/api')).default
      const response = await api.get('/student/subjects')
      subjects.value = response.data.data || []
    } catch {
      // silent
    }
  }

  async function generatePlan() {
    generating.value = true
    try {
      await generateForm.validate()
      const api = (await import('@/utils/api')).default
      const response = await api.post('/student/study-plan/generate', generateForm.values)
      toastStore.success(`${response.data.data.created_sessions} study sessions generated!`)
      showGenerateForm.value = false
      await fetchStudyPlan()
    } catch (error: any) {
      toastStore.error('Failed to generate plan', error.message)
    } finally {
      generating.value = false
    }
  }

  async function updateSessionStatus(session: StudySession, status: string) {
    try {
      const api = (await import('@/utils/api')).default
      await api.put(`/student/study-plan/${session.id}/status`, { status })
      toastStore.success(`Session marked as ${status}`)
      await fetchStudyPlan()
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

  const groupedSessions = computed(() => {
    const groups: Record<string, StudySession[]> = {}
    studyPlan.value.forEach(session => {
      if (!groups[session.scheduled_date]) {
        groups[session.scheduled_date] = []
      }
      groups[session.scheduled_date].push(session)
    }
    return Object.entries(groups)
      .sort(([a], [b]) => new Date(a).getTime() - new Date(b).getTime())
      .map(([date, sessions]) => ({ date, sessions }))
  })
</script>