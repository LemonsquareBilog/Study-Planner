<script setup lang="ts>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { RouterLink } from 'vue-router'
import { 
  ArrowLeftIcon, CheckCircleIcon, ClockIcon, 
  LightBulbIcon, ArrowRightIcon, DocumentTextIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(true)
const attempt = ref<import('@/types').QuizAttempt | null>(null)

async function fetchAttempt() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get(`/student/quiz-attempts/${route.params.id}`)
    attempt.value = response.data.data
  } catch (error: any) {
    toastStore.error('Failed to load attempt', error.message)
    router.push('/quiz-attempts')
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
          <RouterLink to="/quiz-attempts" class="p-2 rounded-xl text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 hover:bg-pink-50/50 dark:hover:bg-pink-900/20 transition-colors">
            <ArrowLeftIcon class="w-6 h-6" aria-hidden="true" />
          </RouterLink>
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Quiz Results</h1>
            <p class="text-gray-600 dark:text-gray-400">Detailed breakdown of your quiz attempt</p>
          </div>
        </div>

        <div class="glass-card-lg rounded-2xl p-8 text-center">
          <div class="w-20 h-20 mx-auto mb-6 rounded-full flex items-center justify-center" :class="attempt?.percentage >= 70 ? 'bg-green-100/80 dark:bg-green-900/30 text-green-600' : attempt?.percentage >= 50 ? 'bg-yellow-100/80 dark:bg-yellow-900/30 text-yellow-600' : 'bg-red-100/80 dark:bg-red-900/30 text-red-600'">
            <CheckCircleIcon class="w-10 h-10" aria-hidden="true" />
          </div>
          <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ attempt?.percentage }}%</h2>
          <p class="text-gray-600 dark:text-gray-400 mb-6">{{ attempt?.score }} out of {{ attempt?.total_questions }} correct</p>
          
          <div class="grid grid-cols-3 gap-4 mb-8">
            <div class="glass-card rounded-xl p-4 text-center">
              <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ attempt?.score }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">Correct</p>
            </div>
            <div class="glass-card rounded-xl p-4 text-center">
              <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ attempt?.total_questions - attempt?.score }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">Incorrect</p>
            </div>
            <div class="glass-card rounded-xl p-4 text-center">
              <p class="text-2xl font-bold text-pink-600 dark:text-pink-400">{{ attempt?.percentage }}%</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">Score</p>
            </div>
          </div>

          <div class="flex gap-4 pt-6">
            <RouterLink to="/quizzes" class="btn-secondary flex-1">
              <ArrowLeftIcon class="w-5 h-5 mr-2" aria-hidden="true" />
              Back to Quizzes
            </RouterLink>
            <RouterLink :to="`/quizzes/${attempt?.quiz_id}/take`" class="btn-primary flex-1">
              <ArrowRightIcon class="w-5 h-5 ml-2" aria-hidden="true" />
              Retry Quiz
            </RouterLink>
          </div>
        </div>

        <!-- Answer Review -->
        <div class="space-y-4">
          <h3 class="text-xl font-bold text-gray-900 dark:text-white">Answer Review</h3>
          <div v-for="(answer, index) in attempt?.answers" :key="answer.question_id" class="glass-card rounded-xl p-4">
            <div class="flex items-start justify-between mb-3">
              <h4 class="font-medium text-gray-900 dark:text-white">{{ index + 1 }}. {{ answer.question?.question_text }}</h4>
              <span :class="['badge', answer.is_correct ? 'badge-green' : 'badge-red']">
                {{ answer.is_correct ? 'Correct' : 'Incorrect' }}
              </span>
            </div>
            <div class="space-y-2 text-sm">
              <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full flex items-center justify-center" :class="answer.selected_answer === answer.question?.correct_answer ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'">
                  {{ answer.selected_answer || 'No answer' }}
                </span>
                <span class="text-gray-600 dark:text-gray-400">Your answer</span>
              </div>
              <div v-if="!answer.is_correct" class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full flex items-center justify-center bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                  {{ answer.question?.correct_answer }}
                </span>
                <span class="text-gray-600 dark:text-gray-400">Correct answer</span>
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
    await fetchAttempt()
  })
  </script>