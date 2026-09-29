<script setup lang="ts>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
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

const loading = ref(false)
const subjects = ref<import('@/types').Subject[]>([])
const topics = ref<import('@/types').Topic[]>([])
const showTaskForm = ref(false)
const editingTask = ref<import('@/types').Task | null>(null)
const deletingTask = ref<import('@/types').Task | null>(null)

const taskSchema = zod.object({
  subject_id: zod.number().min(1),
  topic_id: zod.number().optional(),
  title: zod.string().min(2).max(200),
  description: zod.string().optional(),
  task_type: zod.enum(['assignment', 'project', 'exam', 'quiz', 'reading', 'review', 'other']),
  priority: zod.enum(['low', 'medium', 'high', 'urgent']),
  deadline: zod.string().datetime(),
  estimated_minutes: zod.number().min(1).max(1440),
})

const taskForm = useForm({
  validationSchema: taskSchema,
  initialValues: {
    subject_id: 0,
    topic_id: undefined,
    title: '',
    description: '',
    task_type: 'assignment',
    priority: 'medium',
    deadline: '',
    estimated_minutes: 60,
  },
)

async function fetchTasks() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get('/student/tasks')
    tasks.value = response.data.data || []
  } catch {
    // silent
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

async function submitTask() {
  loading.value = true
  try {
    await taskForm.validate()
    const api = (await import('@/utils/api')).default
    
    if (editingTask.value) {
      await api.put(`/student/tasks/${editingTask.value.id}`, taskForm.values)
      toastStore.success('Task updated!')
    } else {
      await api.post('/student/tasks', taskForm.values)
      toastStore.success('Task created!')
    }
    showTaskForm.value = false
    editingTask.value = null
    taskForm.resetForm()
    await fetchTasks()
  } catch (error: any) {
    toastStore.error('Failed to save task', error.message)
  } finally {
    loading.value = false
  }
}

async function deleteTask(task: import('@/types').Task) {
  if (!confirm('Are you sure you want to delete this task? This action cannot be undone.')) return
  
  try {
    const api = (await import('@/utils/api')).default
    await api.delete(`/student/tasks/${task.id}`)
    toastStore.success('Task deleted!')
    await fetchTasks()
  } catch (error: any) {
    toastStore.error('Failed to delete task', error.message)
  }
}

async function completeTask(task: import('@/types').Task) {
  try {
    const api = (await import('@/utils/api')).default
    await api.post(`/student/tasks/${task.id}/complete`)
    toastStore.success('Task completed!')
    await fetchTasks()
  } catch (error: any) {
    toastStore.error('Failed to complete task', error.message)
  }
}

function editTask(task: import('@/types').Task) {
  editingTask.value = task
  taskForm.setValues({
    subject_id: task.subject_id,
    topic_id: task.topic_id || undefined,
    title: task.title,
    description: task.description || '',
    task_type: task.task_type,
    priority: task.priority,
    deadline: task.deadline ? new Date(task.deadline).toISOString().slice(0, 16) : '',
    estimated_minutes: task.estimated_minutes,
  })
  showTaskForm.value = true
}

function newTask() {
  editingTask.value = null
  taskForm.resetForm()
  showTaskForm.value = true
}

function cancelForm() {
  showTaskForm.value = false
  editingTask.value = null
  taskForm.resetForm()
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
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
          <RouterLink to="/tasks" class="p-2 rounded-xl text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 hover:bg-pink-50/50 dark:hover:bg-pink-900/20 transition-colors">
            <ArrowLeftIcon class="w-6 h-6" aria-hidden="true" />
          </RouterLink>
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Tasks</h1>
            <p class="text-gray-600 dark:text-gray-400">Manage your assignments and track progress</p>
          </div>
        </div>
        
        <button @click="newTask" class="btn-primary ml-auto flex-shrink-0">
          <PlusIcon class="w-5 h-5 mr-2" aria-hidden="true" />
          Add Task
        </button>
      </div>

      <!-- Filters -->
      <div class="glass-card-lg rounded-2xl p-4 mb-6">
        <div class="flex flex-col sm:flex-row gap-4">
          <div class="relative flex-1 min-w-[200px]">
            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" aria-hidden="true" />
            <input
              type="search"
              v-model="searchQuery"
              placeholder="Search tasks..."
              class="input-field pl-10 pr-4 w-full"
            />
          </div>
          <select v-model="statusFilter" class="select-field input-field w-full sm:w-40">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
          <select v-model="priorityFilter" class="select-field input-field w-full sm:w-40">
            <option value="">All Priority</option>
            <option value="urgent">Urgent</option>
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <option value="low">Low</option>
          </select>
          <select v-model="subjectFilter" class="select-field input-field w-full sm:w-48">
            <option value="">All Subjects</option>
            <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </div>
      </div>

      <!-- Task Form Modal -->
      <Transition name="slide-down">
        <div v-show="showTaskForm" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
          <div class="glass-card-lg rounded-2xl p-6 max-w-2xl w-full max-h-[90vh] overflow-y-auto animate-scale-in">
            <div class="flex items-center justify-between mb-6">
              <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                {{ editingTask ? 'Edit Task' : 'Create Task' }}
              </h2>
              <button @click="cancelForm" class="p-2 rounded-xl text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100/50 dark:hover:bg-gray-800/50 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>

            <form @submit.prevent="submitTask" class="space-y-6" novalidate>
              <div>
                <label for="subject_id" class="input-label">Subject</label>
                <select
                  id="subject_id"
                  v-model="taskForm.values.subject_id"
                  @change="fetchTopics"
                  class="select-field input-field"
                  :class="{ 'input-error': taskForm.errors.subject_id }"
                >
                  <option value="0">Select a subject</option>
                  <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
                <p v-if="taskForm.errors.subject_id" class="input-error-message">{{ taskForm.errors.subject_id }}</p>
              </div>

              <div v-if="topics.length > 0">
                <label for="topic_id" class="input-label">Topic (optional)</label>
                <select
                  id="topic_id"
                  v-model="taskForm.values.topic_id"
                  class="select-field input-field"
                >
                  <option value="0">Select a topic (optional)</option>
                  <option v-for="t in topics" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>
              </div>

              <div>
                <label for="title" class="input-label">Task Title</label>
                <input
                  id="title"
                  type="text"
                  v-model="taskForm.values.title"
                  class="input-field"
                  :class="{ 'input-error': taskForm.errors.title }"
                  placeholder="e.g., Complete Chapter 3 exercises"
                />
                <p v-if="taskForm.errors.title" class="input-error-message">{{ taskForm.errors.title }}</p>
              </div>

              <div>
                <label for="description" class="input-label">Description (optional)</label>
                <textarea
                  id="description"
                  v-model="taskForm.values.description"
                  rows="3"
                  class="input-field resize-y"
                  placeholder="Details about this task..."
                />
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label for="task_type" class="input-label">Task Type</label>
                  <select
                    id="task_type"
                    v-model="taskForm.values.task_type"
                    class="select-field input-field"
                  >
                    <option value="assignment">Assignment</option>
                    <option value="project">Project</option>
                    <option value="exam">Exam</option>
                    <option value="quiz">Quiz</option>
                    <option value="reading">Reading</option>
                    <option value="review">Review</option>
                    <option value="other">Other</option>
                  </select>
                </div>

                <div>
                  <label for="priority" class="input-label">Priority</label>
                  <select
                    id="priority"
                    v-model="taskForm.values.priority"
                    class="select-field input-field"
                  >
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                  </select>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label for="deadline" class="input-label">Deadline</label>
                  <input
                    id="deadline"
                    type="datetime-local"
                    v-model="taskForm.values.deadline"
                    class="input-field"
                    :class="{ 'input-error': taskForm.errors.deadline }"
                  />
                  <p v-if="taskForm.errors.deadline" class="input-error-message">{{ taskForm.errors.deadline }}</p>
                </div>

                <div>
                  <label for="estimated_minutes" class="input-label">Estimated Time (minutes)</label>
                  <input
                    id="estimated_minutes"
                    type="number"
                    v-model="taskForm.values.estimated_minutes"
                    min="1"
                    max="1440"
                    class="input-field"
                    :class="{ 'input-error': taskForm.errors.estimated_minutes }"
                  />
                  <p v-if="taskForm.errors.estimated_minutes" class="input-error-message">{{ taskForm.errors.estimated_minutes }}</p>
                </div>
              </div>

              <div class="flex gap-4 pt-4">
                <button type="button" @click="cancelForm" class="btn-secondary flex-1">
                  <ArrowLeftIcon class="mr-2 w-5 h-5" aria-hidden="true" />
                  Back
                </button>
                <button type="submit" :disabled="loading" class="btn-primary flex-1">
                  <span v-if="loading" class="flex items-center justify-center gap-2">
                    <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                    Saving...
                  </span>
                  <span v-else>{{ editingTask ? 'Update Task' : 'Create Task' }} <ArrowRightIcon class="ml-2 w-5 h-5" aria-hidden="true" /></span>
                </button>
              </div>
            </form>
          </div>
        </Transition>

        <!-- Task List -->
        <div v-if="filteredTasks.length === 0" class="text-center py-16">
          <CheckCircleIcon class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" aria-hidden="true" />
          <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No tasks found</h3>
          <p class="text-gray-600 dark:text-gray-400 mb-6">{{ searchQuery || statusFilter || priorityFilter ? 'Try adjusting your filters' : 'Create your first task to start tracking your work' }}</p>
          <button @click="newTask" class="btn-primary">
            <PlusIcon class="w-5 h-5 mr-2" aria-hidden="true" />
            Create Task
          </button>
        </div>

        <div v-else class="divide-y divide-pink-100/50 dark:divide-pink-800/50">
          <RouterLink 
            v-for="task in filteredTasks" 
            :key="task.id" 
            :to="`/tasks/${task.id}`"
            class="flex items-center justify-between px-6 py-4 hover:bg-pink-50/30 dark:hover:bg-pink-900/10 transition-colors"
          >
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1">
                <h3 class="font-medium text-gray-900 dark:text-white truncate">{{ task.title }}</h3>
                <span :class="['badge', 'badge-' + task.priority]">{{ task.priority }}</span>
                <span v-if="task.subject" class="badge badge-gray">{{ task.subject.name }}</span>
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
    </DefaultLayout>
  </template>

  <script>
  import { onMounted } from 'vue'

  onMounted(async () => {
    await fetchTasks()
    await fetchSubjects()
  })
  </script>