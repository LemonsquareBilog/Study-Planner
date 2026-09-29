<script setup lang="ts>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { useForm } from 'vee-validate'
import * as zod from 'zod'
import { RouterLink } from 'vue-router'
import { 
  ArrowLeftIcon, CalendarDaysIcon, CheckCircleIcon, 
  ExclamationTriangleIcon, PencilIcon, PlusIcon,
  ClockIcon, EyeIcon, EyeSlashIcon, TrashIcon,
  BookOpenIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(true)
const task = ref<import('@/types').Task | null>(null)
const subjects = ref<import('@/types').Subject[]>([])
const topics = ref<import('@/types').Topic[]>([])
const showDeleteModal = ref(false)

async function fetchTask() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get(`/student/tasks/${route.params.id}`)
    task.value = response.data.data
  } catch (error: any) {
    toastStore.error('Failed to load task', error.message)
    router.push('/tasks')
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

async function fetchTopics() {
  if (!taskForm.values.subject_id) return
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get(`/student/subjects/${taskForm.values.subject_id}/topics`)
    topics.value = response.data.data || []
  } catch {
    topics.value = []
  }
}

async function deleteTask() {
  if (!confirm('Are you sure you want to delete this task? This action cannot be undone.')) return
  
  try {
    const api = (await import('@/utils/api')).default
    await api.delete(`/student/tasks/${route.params.id}`)
    toastStore.success('Task deleted!')
    router.push('/tasks')
  } catch (error: any) {
    toastStore.error('Failed to delete task', error.message)
  }
}

async function completeTask() {
  try {
    const api = (await import('@/utils/api')).default
    await api.post(`/student/tasks/${route.params.id}/complete`)
    toastStore.success('Task completed!')
    await fetchTask()
  } catch (error: any) {
    toastStore.error('Failed to complete task', error.message)
  }
}

function getPriorityColor(priority: string): string {
  switch (priority) {
    case 'urgent': return 'bg-red-500'
    case 'high': return 'bg-orange-500'
    case 'medium': return 'bg-yellow-500'
    case 'low': return 'bg-green-500'
    default: return 'bg-gray-500'
  }
}

function getStatusBadgeClass(status: string): string {
  switch (status) {
    case 'pending': return 'badge-yellow'
    case 'in_progress': return 'badge-blue'
    case 'completed': return 'badge-green'
    case 'cancelled': return 'badge-gray'
    default: return 'badge-gray'
  }
}

function getStatusLabel(status: string): string {
  switch (status) {
    case 'in_progress': return 'In Progress'
    case 'completed': return 'Completed'
    case 'pending': return 'Pending'
    case 'cancelled': return 'Cancelled'
    default: return status
  }
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
          <RouterLink to="/tasks" class="p-2 rounded-xl text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 hover:bg-pink-50/50 dark:hover:bg-pink-900/20 transition-colors">
            <ArrowLeftIcon class="w-6 h-6" aria-hidden="true" />
          </RouterLink>
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ task?.title }}</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ task?.subject?.name }}</p>
          </div>
        </div>

        <div class="glass-card-lg rounded-2xl p-8">
          <div class="flex items-center justify-between mb-8">
            <div>
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ task?.title }}</h1>
              <p class="text-gray-600 dark:text-gray-400">{{ task?.description }}</p>
            </div>
            <span :class="['badge', 'badge-' + task?.priority]">{{ task?.priority }}</span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="glass-card rounded-xl p-6 text-center">
              <CalendarDaysIcon class="w-8 h-8 mx-auto text-pink-600 dark:text-pink-400 mb-2" aria-hidden="true" />
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Deadline</p>
              <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ formatRelativeTime(task?.deadline) }}</p>
            </div>
            <div class="glass-card rounded-xl p-6 text-center">
              <ClockIcon class="w-8 h-8 mx-auto text-pink-600 dark:text-pink-400 mb-2" aria-hidden="true" />
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Estimated Time</p>
              <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ task?.estimated_minutes }} minutes</p>
            </div>
            <div class="glass-card rounded-xl p-6 text-center">
              <CheckCircleIcon class="w-8 h-8 mx-auto text-pink-600 dark:text-pink-400 mb-2" aria-hidden="true" />
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Status</p>
              <p class="text-xl font-bold text-gray-900 dark:text-white mt-1"><span :class="['badge', getStatusBadgeClass(task?.status)]">{{ getStatusLabel(task?.status) }}</span></p>
            </div>
          </div>

          <div v-if="task?.subject" class="glass-card-lg rounded-2xl p-6 mb-8">
            <div class="flex items-center gap-4">
              <div class="w-14 h-14 rounded-xl bg-pink-100/80 dark:bg-pink-900/30 flex items-center justify-center text-pink-600 dark:text-pink-400">
                <BookOpenIcon class="w-7 h-7" aria-hidden="true" />
              </div>
              <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Subject</h3>
                <p class="text-gray-600 dark:text-gray-400">{{ task?.subject?.name }}</p>
              </div>
            </div>
          </div>

          <!-- Status Actions -->
          <div class="space-y-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Actions</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              <button 
                v-if="task?.status === 'pending'"
                @click="updateStatus('in_progress')"
                class="btn-primary flex items-center justify-center gap-2"
              >
                <PlayIcon class="w-5 h-5" aria-hidden="true" />
                Start Task
              </button>
              <button 
                v-if="task?.status === 'pending'"
                @click="confirmDelete"
                class="btn-danger"
              >
                <TrashIcon class="w-5 h-5 mr-2" aria-hidden="true" />
                Delete
              </button>
              <button 
                v-if="task?.status === 'in_progress'"
                @click="completeTask"
                class="btn-primary flex items-center justify-center gap-2"
              >
                <CheckCircleIcon class="w-5 h-5" aria-hidden="true" />
                Complete Task
              </button>
              <button 
                v-if="task?.status === 'in_progress'"
                @click="confirmDelete"
                class="btn-danger"
              >
                <TrashIcon class="w-5 h-5 mr-2" aria-hidden="true" />
                Delete
              </button>
              <button 
                v-if="task?.status === 'skipped'"
                @click="updateStatus('scheduled')"
                class="btn-secondary"
              >
                <ArrowRightIcon class="w-5 h-5 mr-2" aria-hidden="true" />
                Reschedule
              </button>
              <button 
                v-if="task?.status === 'completed'"
                @click="confirmDelete"
                class="btn-danger"
              >
                <TrashIcon class="w-5 h-5 mr-2" aria-hidden="true" />
                Delete
              </button>
            </div>
          </div>

          <!-- Task Details -->
          <div class="glass-card-lg rounded-2xl p-6 mt-8">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Task Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
              <div>
                <p class="text-gray-500 dark:text-gray-400">Subject</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ task?.subject?.name }}</p>
              </div>
              <div>
                <p class="text-gray-500 dark:text-gray-400">Topic</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ task?.topic?.name || 'None' }}</p>
              </div>
              <div>
                <p class="text-gray-500 dark:text-gray-400">Priority</p>
                <p class="font-medium text-gray-900 dark:text-white"><span :class="['badge', 'badge-' + task?.priority]">{{ task?.priority }}</span></p>
              </div>
              <div>
                <p class="text-gray-500 dark:text-gray-400">Task Type</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ task?.task_type }}</p>
              </div>
              <div>
                <p class="text-gray-500 dark:text-gray-400">Estimated Time</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ task?.estimated_minutes }} minutes</p>
              </div>
              <div>
                <p class="text-gray-500 dark:text-gray-400">Created</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ formatRelativeTime(task?.created_at) }}</p>
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
    await fetchTask()
    await fetchSubjects()
  })
  </script>