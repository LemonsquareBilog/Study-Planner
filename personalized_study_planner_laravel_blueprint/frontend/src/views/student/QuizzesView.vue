<script setup lang="ts>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { RouterLink } from 'vue-router'
import { 
  ArrowLeftIcon, PencilIcon, TrashIcon, CheckCircleIcon, 
  MagnifyingGlassIcon, PlusIcon, BookOpenIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(true)
const quizzes = ref<import('@/types').Quiz[]>([])
const searchQuery = ref('')

const filteredQuizzes = computed(() => 
  quizzes.value.filter(q => 
    q.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    q.description?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    q.topic?.name.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
)

async function fetchQuizzes() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get('/student/quizzes')
    quizzes.value = response.data.data || []
  } catch (error: any) {
    toastStore.error('Failed to load quizzes', error.message)
  } finally {
    loading.value = false
  }
}

function getDifficultyLabel(difficulty: number): string {
  const labels = ['', 'Very Easy', 'Easy', 'Moderate', 'Hard', 'Very Hard']
  return labels[difficulty] || 'Unknown'
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
          <RouterLink to="/dashboard" class="p-2 rounded-xl text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 hover:bg-pink-50/50 dark:hover:bg-pink-900/20 transition-colors">
            <ArrowLeftIcon class="w-6 h-6" aria-hidden="true" />
          </RouterLink>
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Quizzes</h1>
            <p class="text-gray-600 dark:text-gray-400">Test your knowledge and track progress</p>
          </div>
        </div>

        <div class="flex gap-3 ml-auto">
          <input
            type="search"
            v-model="searchQuery"
            placeholder="Search quizzes..."
            class="input-field pl-10 pr-4 max-w-xs"
          />
        </div>
      </div>

      <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="i in 6" :key="i" class="glass-card-lg rounded-2xl p-6 animate-pulse">
          <div class="h-6 bg-gray-200/50 dark:bg-gray-700/50 rounded w-3/4 mb-4"></div>
          <div class="h-4 bg-gray-200/50 dark:bg-gray-700/50 rounded w-1/2"></div>
        </div>
      </div>

      <div v-else-if="filteredQuizzes.length === 0" class="text-center py-16">
        <CheckCircleIcon class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" aria-hidden="true" />
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No quizzes available</h3>
        <p class="text-gray-600 dark:text-gray-400 mb-6">{{ searchQuery ? 'Try adjusting your search' : 'No quizzes available for your subjects yet' }}</p>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <RouterLink 
          v-for="quiz in filteredQuizzes" 
          :key="quiz.id" 
          :to="`/quizzes/${quiz.id}`"
          class="glass-card-lg rounded-2xl p-6 hover:shadow-glass-lg hover:-translate-y-1 transition-all duration-300"
        >
          <div class="flex items-start justify-between mb-4">
            <div class="w-12 h-12 rounded-xl bg-pink-100/80 dark:bg-pink-900/30 flex items-center justify-center text-pink-600 dark:text-pink-400">
              <CheckCircleIcon class="w-6 h-6" aria-hidden="true" />
            </div>
            <span class="badge badge-gray">{{ quiz.topic?.name }}</span>
          </div>
          
          <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 truncate">{{ quiz.title }}</h3>
          <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 line-clamp-2">{{ quiz.description }}</p>
          
          <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400 mb-4">
            <span class="flex items-center gap-1">
              <BookOpenIcon class="w-4 h-4" aria-hidden="true" />
              {{ quiz.total_questions }} questions
            </span>
            <span class="flex items-center gap-1">
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium" :class="quiz.difficulty <= 2 ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : quiz.difficulty === 3 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'">
                Difficulty: {{ getDifficultyLabel(quiz.difficulty) }}
              </span>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-pink-200/50 dark:border-pink-800/50">
              <div v-if="quiz.last_attempt" class="flex items-center gap-2 text-sm">
                <span class="text-gray-500 dark:text-gray-400">Last attempt:</span>
                <span class="font-medium" :class="quiz.last_attempt.percentage >= 70 ? 'text-green-600 dark:text-green-400' : quiz.last_attempt.percentage >= 50 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400'">
                  {{ quiz.last_attempt.percentage }}%
                </span>
                <span class="text-gray-400 dark:text-gray-500">{{ formatRelativeTime(quiz.last_attempt.completed_at) }}</span>
              </div>
              <div v-else class="text-sm text-gray-500 dark:text-gray-400">
                Not attempted yet
              </div>
            </div>
          </RouterLink>
        </div>
      </div>
    </DefaultLayout>
  </template>

  <script>
  import { onMounted } from 'vue'

  onMounted(async () => {
    await fetchQuizzes()
  })
  </script>