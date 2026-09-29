<script setup lang="ts>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { RouterLink } from 'vue-router'
import { 
  PlusIcon, MagnifyingGlassIcon, PencilIcon, TrashIcon, 
  ArrowPathIcon, BookOpenIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(true)
const subjects = ref<import('@/types').Subject[]>([])
const searchQuery = ref('')
const showDeleteModal = ref(false)
const subjectToDelete = ref<import('@/types').Subject | null>(null)

const subjectsData = computed(() => 
  subjects.value.filter(s => 
    s.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    s.description?.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
)

async function fetchSubjects() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get('/student/subjects')
    subjects.value = response.data.data || []
  } catch (error: any) {
    toastStore.error('Failed to load subjects', error.message)
  } finally {
    loading.value = false
  }
}

function confirmDelete(subject: import('@/types').Subject) {
  subjectToDelete.value = subject
  showDeleteModal.value = true
}

async function deleteSubject() {
  if (!subjectToDelete.value) return
  
  try {
    const api = (await import('@/utils/api')).default
    await api.delete(`/student/subjects/${subjectToDelete.value.id}`)
    toastStore.success('Subject deleted!')
    subjects.value = subjects.value.filter(s => s.id !== subjectToDelete.value?.id)
    showDeleteModal.value = false
    subjectToDelete.value = null
  } catch (error: any) {
    toastStore.error('Failed to delete subject', error.message)
  }
}

function restoreSubject(subject: import('@/types').Subject) {
  // Implementation for restore
}

function getColorBadge(color: string): string {
  const colors: Record<string, string> = {
    blue: 'bg-blue-500',
    indigo: 'bg-indigo-500',
    purple: 'bg-purple-500',
    pink: 'bg-pink-500',
    red: 'bg-red-500',
    orange: 'bg-orange-500',
    amber: 'bg-amber-500',
    yellow: 'bg-yellow-500',
    green: 'bg-green-500',
    teal: 'bg-teal-500',
    cyan: 'bg-cyan-500',
    gray: 'bg-gray-500',
  }
  return colors[color] || 'bg-gray-500'
}
</script>

<template>
  <DefaultLayout>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Subjects</h1>
          <p class="text-gray-600 dark:text-gray-400">Manage your study subjects</p>
        </div>
        <RouterLink to="/subjects/create" class="btn-primary flex-shrink-0">
          <PlusIcon class="w-5 h-5 mr-2" aria-hidden="true" />
          Add Subject
        </RouterLink>
      </div>

      <!-- Search -->
      <div class="mb-6">
        <label for="search" class="sr-only">Search subjects</label>
        <div class="relative max-w-md">
          <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" aria-hidden="true" />
          <input
            id="search"
            type="search"
            v-model="searchQuery"
            placeholder="Search subjects..."
            class="input-field pl-10 pr-4 w-full"
          />
        </div>
      </div>

      <!-- Subjects Grid -->
      <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="i in 6" :key="i" class="glass-card-lg rounded-2xl p-6 animate-pulse">
          <div class="h-6 bg-gray-200/50 dark:bg-gray-700/50 rounded w-3/4 mb-4"></div>
          <div class="h-4 bg-gray-200/50 dark:bg-gray-700/50 rounded w-1/2"></div>
          <div class="h-4 bg-gray-200/50 dark:bg-gray-700/50 rounded w-1/3"></div>
        </div>
      </div>

      <div v-else-if="subjectsData.length === 0" class="text-center py-16">
        <BookOpenIcon class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" aria-hidden="true" />
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No subjects yet</h3>
        <p class="text-gray-600 dark:text-gray-400 mb-6">Create your first subject to start organizing your studies</p>
        <RouterLink to="/subjects/create" class="btn-primary">Create Your First Subject</RouterLink>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <RouterLink 
          v-for="subject in subjectsData" 
          :key="subject.id" 
          :to="`/subjects/${subject.id}`"
          class="glass-card-lg rounded-2xl p-6 hover:shadow-glass-lg hover:-translate-y-1 transition-all duration-300"
        >
          <div class="flex items-start justify-between mb-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center" :class="getColorBadge(subject.color)">
              <BookOpenIcon class="w-6 h-6 text-white" aria-hidden="true" />
            </div>
            <div class="flex items-center gap-2">
              <RouterLink 
                :to="`/subjects/${subject.id}/edit`" 
                class="p-2 rounded-lg text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 hover:bg-pink-50/50 dark:hover:bg-pink-900/20 transition-colors"
                @click.stop
              >
                <PencilIcon class="w-5 h-5" aria-hidden="true" />
              </RouterLink>
              <button 
                @click="confirmDelete(subject)"
                class="p-2 rounded-lg text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50/50 dark:hover:bg-red-900/20 transition-colors"
                @click.stop
              >
                <TrashIcon class="w-5 h-5" aria-hidden="true" />
              </button>
            </div>
          </div>
          
          <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 truncate">{{ subject.name }}</h3>
          <p v-if="subject.description" class="text-gray-600 dark:text-gray-400 text-sm mb-4 line-clamp-2">{{ subject.description }}</p>
          
          <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
            <span class="flex items-center gap-1">
              <BookOpenIcon class="w-4 h-4" aria-hidden="true" />
              {{ subject.topics_count }} topics
            </span>
            <span class="flex items-center gap-1">
              <CheckCircleIcon class="w-4 h-4" aria-hidden="true" />
              {{ subject.tasks_count }} tasks
            </span>
            <span class="flex items-center gap-1" :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium', getColorBadge(subject.color) + ' text-white']">
              <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: subject.color_hex }"></span>
              {{ subject.color }}
            </span>
          </div>
        </RouterLink>
      </div>
    </div>

    <!-- Delete Modal -->
    <Transition name="slide-down">
      <div v-show="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="glass-card-lg rounded-2xl p-6 max-w-md w-full animate-scale-in">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Delete Subject</h3>
          <p class="text-gray-600 dark:text-gray-400 mb-6">Are you sure you want to delete "{{ subjectToDelete?.name }}"? This action cannot be undone.</p>
          <div class="flex gap-3 justify-end">
            <button @click="showDeleteModal = false" class="btn-secondary">Cancel</button>
            <button @click="deleteSubject" class="btn-danger">Delete</button>
          </div>
        </div>
      </div>
    </Transition>
  </DefaultLayout>
</template>

<script setup lang="ts>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { RouterLink } from 'vue-router'
import { 
  PlusIcon, MagnifyingGlassIcon, PencilIcon, TrashIcon, 
  BookOpenIcon, CheckCircleIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(true)
const subjects = ref<import('@/types').Subject[]>([])
const searchQuery = ref('')
const showDeleteModal = ref(false)
const subjectToDelete = ref<import('@/types').Subject | null>(null)

const subjectsData = computed(() => 
  subjects.value.filter(s => 
    s.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    s.description?.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
)

async function fetchSubjects() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get('/student/subjects')
    subjects.value = response.data.data || []
  } catch (error: any) {
    toastStore.error('Failed to load subjects', error.message)
  } finally {
    loading.value = false
  }
}

function confirmDelete(subject: import('@/types').Subject) {
  subjectToDelete.value = subject
  showDeleteModal.value = true
}

async function deleteSubject() {
  if (!subjectToDelete.value) return
  
  try {
    const api = (await import('@/utils/api')).default
    await api.delete(`/student/subjects/${subjectToDelete.value.id}`)
    toastStore.success('Subject deleted!')
    subjects.value = subjects.value.filter(s => s.id !== subjectToDelete.value?.id)
    showDeleteModal.value = false
    subjectToDelete.value = null
  } catch (error: any) {
    toastStore.error('Failed to delete subject', error.message)
  }
}

function restoreSubject(subject: import('@/types').Subject) {
  // Implementation for restore
}

function getColorBadge(color: string): string {
  const colors: Record<string, string> = {
    blue: 'bg-blue-500',
    indigo: 'bg-indigo-500',
    purple: 'bg-purple-500',
    pink: 'bg-pink-500',
    red: 'bg-red-500',
    orange: 'bg-orange-500',
    amber: 'bg-amber-500',
    yellow: 'bg-yellow-500',
    green: 'bg-green-500',
    teal: 'bg-teal-500',
    cyan: 'bg-cyan-500',
    gray: 'bg-gray-500',
  }
  return colors[color] || 'bg-gray-500'
}

onMounted(async () => {
  await fetchSubjects()
})
</script>