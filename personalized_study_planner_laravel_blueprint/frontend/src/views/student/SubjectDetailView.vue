<script setup lang="ts>
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { RouterLink } from 'vue-router'
import { 
  ArrowLeftIcon, BookOpenIcon, CheckCircleIcon, 
  ClockIcon, LightBulbIcon, ArrowRightIcon,
  ExclamationTriangleIcon, PencilIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(true)
const subject = ref<import('@/types').Subject | null>(null)
const topics = ref<import('@/types').Topic[]>([])
const tasks = ref<import('@/types').Task[]>([])
const resources = ref<import('@/types').Resource[]>([])
const activeTab = ref<'topics' | 'tasks' | 'resources'>('topics')

const stats = computed(() => {
  if (!subject.value) return []
  return [
    { label: 'Topics', value: subject.value?.topics_count || 0, icon: BookOpenIcon },
    { label: 'Tasks', value: subject.value?.tasks_count || 0, icon: CheckCircleIcon },
  ]
}

async function fetchSubject() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get(`/student/subjects/${route.params.id}`)
    subject.value = response.data.data
  } catch (error: any) {
    toastStore.error('Failed to load subject', error.message)
    router.push('/subjects')
  } finally {
    loading.value = false
  }
}

async function fetchTopics() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get(`/student/subjects/${route.params.id}/topics`)
    topics.value = response.data.data || []
  } catch {
    // silent
  }
}

async function fetchTasks() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get(`/student/tasks`, { params: { subject_id: route.params.id } })
    tasks.value = response.data.data || []
  } catch {
    // silent
  }
}

async function fetchResources() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get(`/student/subjects/${route.params.id}`)
    resources.value = response.data.data?.resources || []
  } catch {
    // silent
  }
}

async function deleteSubject() {
  if (!confirm('Are you sure you want to delete this subject? This action cannot be undone.')) return
  
  try {
    const api = (await import('@/utils/api')).default
    await api.delete(`/student/subjects/${route.params.id}`)
    toastStore.success('Subject deleted!')
    router.push('/subjects')
  } catch (error: any) {
    toastStore.error('Failed to delete subject', error.message)
  }
}

function getDifficultyLabel(difficulty: number): string {
  const labels = ['', 'Very Easy', 'Easy', 'Moderate', 'Hard', 'Very Hard']
  return labels[difficulty] || 'Unknown'
}

function getDifficultyColor(difficulty: number): string {
  if (difficulty <= 2) return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
  if (difficulty === 3) return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
  return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
}

function getColorBadge(color: string): string {
  const colors: Record<string, string> = {
    blue: 'bg-blue-500', indigo: 'bg-indigo-500', purple: 'bg-purple-500',
    pink: 'bg-pink-500', red: 'bg-red-500', orange: 'bg-orange-500',
    amber: 'bg-amber-500', yellow: 'bg-yellow-500', green: 'bg-green-500',
    teal: 'bg-teal-500', cyan: 'bg-cyan-500', gray: 'bg-gray-500',
  }
  return colors[color] || 'bg-gray-500'
}

function getDifficultyColor(difficulty: number): string {
  if (difficulty <= 2) return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
  if (difficulty === 3) return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
  return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
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
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
      <div v-if="loading" class="animate-pulse space-y-8">
        <div class="h-8 bg-gray-200/50 dark:bg-gray-700/50 rounded w-1/4"></div>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <div class="lg:col-span-2 h-64 bg-gray-200/50 dark:bg-gray-700/50 rounded-xl"></div>
          <div class="h-64 bg-gray-200/50 dark:bg-gray-700/50 rounded-xl"></div>
        </div>
      </div>

      <div v-else class="space-y-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
          <div class="flex items-center gap-4">
            <RouterLink to="/subjects" class="p-2 rounded-xl text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 hover:bg-pink-50/50 dark:hover:bg-pink-900/20 transition-colors">
              <ArrowLeftIcon class="w-6 h-6" aria-hidden="true" />
            </RouterLink>
            <div>
              <div class="flex items-center gap-3 mb-2">
                <div class="w-14 h-14 rounded-xl flex items-center justify-center" :class="getColorBadge(subject?.color)">
                  <BookOpenIcon class="w-7 h-7 text-white" aria-hidden="true" />
                </div>
                <div>
                  <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ subject?.name }}</h1>
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium" :class="getDifficultyColor(subject?.difficulty)">
                    {{ getDifficultyLabel(subject?.difficulty) }}
                  </span>
                </div>
              </div>
            </div>
            
            <div class="flex items-center gap-4 ml-auto">
              <RouterLink :to="`/subjects/${route.params.id}/edit`" class="btn-secondary">
                <PencilIcon class="w-5 h-5 mr-2" aria-hidden="true" />
                Edit
              </RouterLink>
              <button @click="deleteSubject" class="btn-danger">
                <TrashIcon class="w-5 h-5 mr-2" aria-hidden="true" />
                Delete
              </button>
            </div>
          </div>

          <!-- Stats -->
          <div class="grid grid-cols-2 gap-4 mb-8">
            <div v-for="stat in stats" :key="stat.label" class="glass-card-lg rounded-2xl p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ stat.label }}</p>
                  <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ stat.value }}</p>
                </div>
                <div :class="['p-3 rounded-xl', stat.bg]">
                  <component :is="stat.icon" :class="['w-6 h-6', stat.color]" aria-hidden="true" />
                </div>
              </div>
            </div>
          </div>

          <!-- Tabs -->
          <div class="border-b border-pink-200/50 dark:border-pink-800/50">
            <nav class="flex gap-8" aria-label="Subject sections">
              <button 
                @click="activeTab = 'topics'"
                :class="['px-4 py-3 text-sm font-medium border-b-2 -mb-px transition-colors', activeTab === 'topics' ? 'border-pink-500 text-pink-600 dark:text-pink-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300']"
              >
                Topics ({{ topics.length }})
              </button>
              <button 
                @click="activeTab = 'tasks'"
                :class="['px-4 py-3 text-sm font-medium border-b-2 -mb-px transition-colors', activeTab === 'tasks' ? 'border-pink-500 text-pink-600 dark:text-pink-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300']"
              >
                Tasks ({{ tasks.length }})
              </button>
              <button 
                @click="activeTab = 'resources'"
                :class="['px-4 py-3 text-sm font-medium border-b-2 -mb-px transition-colors', activeTab === 'resources' ? 'border-pink-500 text-pink-600 dark:text-pink-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300']"
              >
                Resources ({{ resources.length }})
              </button>
            </nav>
          </div>

          <!-- Topics Tab -->
          <div v-show="activeTab === 'topics'" class="space-y-4 mt-6">
            <div class="flex items-center justify-between">
              <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Topics</h2>
              <RouterLink :to="`/subjects/${route.params.id}/topics/create`" class="btn-primary">
                <PlusIcon class="w-5 h-5 mr-2" aria-hidden="true" />
                Add Topic
              </RouterLink>
            </div>
            
            <div v-if="topics.length === 0" class="text-center py-12">
              <BookOpenIcon class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" aria-hidden="true" />
              <p class="text-gray-500 dark:text-gray-400">No topics yet. Create your first topic to get started.</p>
              <RouterLink :to="`/subjects/${route.params.id}/topics/create`" class="btn-primary mt-4 inline-block">Create Topic</RouterLink>
            </div>
            
            <div v-else class="divide-y divide-pink-100/50 dark:divide-pink-800/50">
              <RouterLink 
                v-for="topic in topics" 
                :key="topic.id" 
                :to="`/subjects/${route.params.id}/topics/${topic.id}`"
                class="flex items-center justify-between px-6 py-4 hover:bg-pink-50/30 dark:hover:bg-pink-900/10 transition-colors"
              >
                <div class="flex items-center gap-4 flex-1 min-w-0">
                  <div class="w-12 h-12 rounded-xl bg-pink-100/80 dark:bg-pink-900/30 flex items-center justify-center text-pink-600 dark:text-pink-400">
                    <BookOpenIcon class="w-6 h-6" aria-hidden="true" />
                  </div>
                  <div class="min-w-0">
                    <h3 class="font-medium text-gray-900 dark:text-white truncate">{{ topic.name }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
                      <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium" :class="topic.difficulty <= 2 ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : topic.difficulty === 3 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'">
                        {{ getDifficultyLabel(topic.difficulty) }}
                      </span>
                      <span>{{ topic.quizzes_count }} quizzes</span>
                      <span>{{ topic.tasks_count }} tasks</span>
                    </p>
                  </div>
                </div>
                <ArrowRightIcon class="w-5 h-5 text-gray-400" aria-hidden="true" />
              </RouterLink>
            </div>
          </div>

          <!-- Tasks Tab -->
          <div v-show="activeTab === 'tasks'" class="space-y-4 mt-6">
            <div class="flex items-center justify-between">
              <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Tasks</h2>
              <RouterLink :to="`/tasks/create?subject_id=${route.params.id}`" class="btn-primary">
                <PlusIcon class="w-5 h-5 mr-2" aria-hidden="true" />
                Add Task
              </RouterLink>
            </div>
            
            <div v-if="tasks.length === 0" class="text-center py-12">
              <CheckCircleIcon class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" aria-hidden="true" />
              <p class="text-gray-500 dark:text-gray-400">No tasks yet. Create your first task to start tracking.</p>
              <RouterLink :to="`/tasks/create?subject_id=${route.params.id}`" class="btn-primary mt-4 inline-block">Create Task</RouterLink>
            </div>
            
            <div v-else class="divide-y divide-pink-100/50 dark:divide-pink-800/50">
              <RouterLink 
                v-for="task in tasks" 
                :key="task.id" 
                :to="`/tasks/${task.id}`"
                class="flex items-center justify-between px-6 py-4 hover:bg-pink-50/30 dark:hover:bg-pink-900/10 transition-colors"
              >
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 mb-1">
                    <h3 class="font-medium text-gray-900 dark:text-white truncate">{{ task.title }}</h3>
                    <span :class="['badge', 'badge-' + task.priority]">{{ task.priority }}</span>
                    <span v-if="task.topic" class="badge badge-gray">{{ task.topic.name }}</span>
                  </div>
                  <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1">
                    <CalendarDaysIcon class="w-4 h-4" aria-hidden="true" />
                    Due {{ formatRelativeTime(task.deadline) }}
                  </p>
                </div>
                <div class="flex items-center gap-2">
                  <span :class="['badge', getStatusBadgeClass(task.status)]">{{ getStatusLabel(task.status) }}</span>
                  <ArrowRightIcon class="w-4 h-4 text-gray-400" aria-hidden="true" />
                </div>
              </RouterLink>
            </div>
          </div>

          <!-- Resources Tab -->
          <div v-show="activeTab === 'resources'" class="space-y-4 mt-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Resources</h2>
            
            <div v-if="resources.length === 0" class="text-center py-12">
              <BookOpenIcon class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" aria-hidden="true" />
              <p class="text-gray-500 dark:text-gray-400">No resources for this subject yet.</p>
            </div>
            
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <RouterLink 
                v-for="resource in resources" 
                :key="resource.id" 
                :to="resource.url"
                target="_blank"
                rel="noopener noreferrer"
                class="glass-card-lg rounded-2xl p-6 hover:shadow-glass-lg hover:-translate-y-1 transition-all duration-300"
              >
                <div class="flex items-center gap-3 mb-3">
                  <div class="w-12 h-12 rounded-xl bg-pink-100/80 dark:bg-pink-900/30 flex items-center justify-center text-pink-600 dark:text-pink-400">
                    <BookOpenIcon class="w-6 h-6" aria-hidden="true" />
                  </div>
                  <div>
                    <h3 class="font-medium text-gray-900 dark:text-white truncate">{{ resource.title }}</h3>
                    <span class="text-xs text-gray-500 dark:text-gray-400 capitalize">{{ resource.resource_type }}</span>
                  </div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">{{ resource.description }}</p>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-500 dark:text-gray-400">Difficulty: {{ resource.difficulty }}/5</span>
                  <span class="text-pink-600 dark:text-pink-400 hover:underline">View Resource <ArrowRightIcon class="w-4 h-4 ml-1" aria-hidden="true" /></span>
                </div>
              </RouterLink>
            </div>
          </div>
        </div>
      </div>
    </DefaultLayout>
  </template>

  <script>
  import { onMounted } from 'vue'

  onMounted(async () => {
    await Promise.all([fetchSubject(), fetchTopics(), fetchTasks(), fetchResources()])
  })
  </script>