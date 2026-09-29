<script setup lang="ts>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { RouterLink } from 'vue-router'
import { 
  ArrowLeftIcon, CheckCircleIcon, ClockIcon, 
  LightBulbIcon, ArrowRightIcon, DocumentTextIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(true)
const attempts = ref<import('@/types').QuizAttempt[]>([])

async function fetchAttempts() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get('/student/quiz-attempts')
    attempts.value = response.data.data || []
  } catch (error: any) {
    toastStore.error('Failed to load attempts', error.message)
  } finally {
    loading.value = false
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

function getScoreColor(percentage: number): string {
  if (percentage >= 80) return 'text-green-600 dark:text-green-400'
  if (percentage >= 60) return 'text-yellow-600 dark:text-yellow-400'
  return 'text-red-600 dark:text-red-400'
}
</script>

<template>
  <DefaultLayout>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
          <RouterLink to="/quizzes" class="p-2 rounded-xl text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 hover:bg-pink-50/50 dark:hover:bg-pink-900/20 transition-colors">
            <ArrowLeftIcon class="w-6 h-6" aria-hidden="true" />
          </RouterLink>
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Quiz Attempts</h1>
            <p class="text-gray-600 dark:text-gray-400">Your quiz history and results</p>
          </div>
        </div>
      </div>

      <div v-if="loading" class="animate-pulse space-y-4">
        <div v-for="i in 5" :key="i" class="glass-card-lg rounded-2xl p-6">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-gray-200/50 dark:bg-gray-700/50"></div>
            <div class="flex-1">
              <div class="h-5 bg-gray-200/50 dark:bg-gray-700/50 rounded w-3/4 mb-2"></div>
              <div class="h-4 bg-gray-200/50 dark:bg-gray-700/50 rounded w-1/2"></div>
            </div>
          </div>
        </div>
      </div>

      <div v-else-if="attempts.length === 0" class="text-center py-16">
        <CheckCircleIcon class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" aria-hidden="true" />
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No quiz attempts yet</h3>
        <p class="text-gray-600 dark:text-gray-400 mb-6">Take your first quiz to see your results here</p>
        <RouterLink to="/quizzes" class="btn-primary">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Browse Quizzes
        </RouterLink>
      </div>

      <div v-else class="space-y-6">
        <RouterLink 
          v-for="attempt in attempts" 
          :key="attempt.id" 
          :to="`/quiz-attempts/${attempt.id}`"
          class="glass-card-lg rounded-2xl p-6 hover:shadow-glass-lg hover:-translate-y-1 transition-all duration-300"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
              <div class="w-14 h-14 rounded-xl bg-pink-100/80 dark:bg-pink-900/30 flex items-center justify-center text-pink-600 dark:text-pink-400">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-.798z"/></svg>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900 dark:text-white truncate">{{ attempt.quiz?.title }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ attempt.quiz?.topic?.name }}</p>
              </div>
            </div>
            
            <div class="flex items-center gap-6">
              <div class="text-center">
                <p class="text-2xl font-bold" :class="attempt.percentage >= 70 ? 'text-green-600 dark:text-green-400' : attempt.percentage >= 50 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400'">{{ attempt.percentage }}%</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ attempt.score }}/{{ attempt.total_questions }}</p>
              </div>
              <div class="text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Completed</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ formatRelativeTime(attempt.completed_at) }}</p>
              </div>
              <div class="text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Duration</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ Math.round((new Date(attempt.completed_at).getTime() - new Date(attempt.started_at).getTime()) / 60000) }} min</p>
              </div>
              <ArrowRightIcon class="w-5 h-5 text-gray-400" aria-hidden="true" />
            </div>
          </RouterLink>
        </div>
      </div>
    </DefaultLayout>
  </template>

  <script>
  import { onMounted } from 'vue'

  onMounted(async () => {
    await fetchAttempts()
  })
  </script>