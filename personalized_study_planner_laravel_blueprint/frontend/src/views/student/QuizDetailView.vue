<script setup lang="ts>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { useForm } from 'vee-validate'
import * as zod from 'zod'
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
const quiz = ref<import('@/types').Quiz | null>(null)
const questions = ref<import('@/types').Question[]>([])
const showQuiz = ref(false)
const currentQuestionIndex = ref(0)
const userAnswers = ref<Record<number, string>>({})
const quizCompleted = ref(false)
const quizResult = ref<import('@/types').QuizAttempt | null>(null)
const timeRemaining = ref(0)
const timerInterval = ref<number | null>(null)

const quizSchema = zod.object({
  answers: zod.record(zod.string()),
})

const quizForm = useForm({
  validationSchema: zod.object({
    answers: zod.record(zod.string()),
  }),
  initialValues: {
    answers: {},
  },
)

async function fetchQuiz() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get(`/student/quizzes/${route.params.id}`)
    quiz.value = response.data.data
    questions.value = response.data.data.questions || []
  } catch (error: any) {
    toastStore.error('Failed to load quiz', error.message)
    router.push('/quizzes')
  } finally {
    loading.value = false
  }
}

async function startQuiz() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.post(`/student/quizzes/${quiz.value?.id}/start`)
    showQuiz.value = true
    currentQuestionIndex.value = 0
    userAnswers.value = {}
    if (quiz.value?.time_limit) {
      timeRemaining.value = quiz.value.time_limit * 60
      startTimer()
    }
  } catch (error: any) {
    toastStore.error('Failed to start quiz', error.message)
  }
}

async function submitQuiz() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.post(`/student/quizzes/${quiz.value?.id}/submit`, {
      answers: userAnswers.value,
      started_at: new Date(Date.now() - (quiz.value?.time_limit || 0) * 60 * 1000).toISOString(),
    })
    quizResult.value = response.data.data
    quizCompleted.value = true
    showQuiz.value = false
    if (timerInterval.value) clearInterval(timerInterval.value)
  } catch (error: any) {
    toastStore.error('Failed to submit quiz', error.message)
  }
}

function nextQuestion() {
  if (currentQuestionIndex.value < questions.value.length - 1) {
    currentQuestionIndex.value++
  }
}

function prevQuestion() {
  if (currentQuestionIndex.value > 0) {
    currentQuestionIndex.value--
  }
}

function selectAnswer(questionId: number, answer: string) {
  userAnswers.value[questionId] = answer
}

function startTimer() {
  if (timerInterval.value) clearInterval(timerInterval.value)
  timerInterval.value = window.setInterval(() => {
    if (timeRemaining.value > 0) {
      timeRemaining.value--
    } else {
      clearInterval(timerInterval.value!)
      submitQuiz()
    }
  }, 1000)
}

function formatTime(seconds: number): string {
  const mins = Math.floor(seconds / 60)
  const secs = seconds % 60
  return `${mins}:${secs.toString().padStart(2, '0')}`
}

function getDifficultyLabel(difficulty: number): string {
  const labels = ['', 'Very Easy', 'Easy', 'Moderate', 'Hard', 'Very Hard']
  return labels[difficulty] || 'Unknown'
}

const currentQuestion = computed(() => questions.value[currentQuestionIndex.value] || null)

onMounted(async () => {
  await fetchQuiz()
})

onUnmounted(() => {
  if (timerInterval.value) clearInterval(timerInterval.value)
})
</script>

<template>
  <DefaultLayout>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
      <div v-if="loading" class="animate-pulse space-y-8">
        <div class="h-8 bg-gray-200/50 dark:bg-gray-700/50 rounded w-1/4"></div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <div class="h-64 bg-gray-200/50 dark:bg-gray-700/50 rounded-xl"></div>
          <div class="h-64 bg-gray-200/50 dark:bg-gray-700/50 rounded-xl"></div>
        </div>
      </div>

      <div v-else class="space-y-8">
        <!-- Quiz Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
          <div class="flex items-center gap-4">
            <RouterLink to="/quizzes" class="p-2 rounded-xl text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 hover:bg-pink-50/50 dark:hover:bg-pink-900/20 transition-colors">
              <ArrowLeftIcon class="w-6 h-6" aria-hidden="true" />
            </RouterLink>
            <div>
              <div class="flex items-center gap-3 mb-2">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium" :class="quiz?.difficulty <= 2 ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : quiz?.difficulty === 3 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'">
                  {{ getDifficultyLabel(quiz?.difficulty) }}
                </span>
                <span v-if="quiz?.topic" class="badge badge-gray">{{ quiz.topic?.name }}</span>
              </div>
              <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ quiz?.title }}</h1>
            </div>
          </div>

          <!-- Timer -->
          <div v-if="showQuiz && quiz?.time_limit" class="fixed top-4 right-4 z-50 glass-card-lg rounded-xl p-4 animate-slide-in">
            <div class="flex items-center gap-3">
              <ExclamationTriangleIcon class="w-6 h-6 text-orange-500" aria-hidden="true" />
              <div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">Time Remaining</p>
                <p class="text-2xl font-mono font-bold text-pink-600 dark:text-pink-400">{{ formatTime(timeRemaining) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Quiz Content -->
        <div v-if="!showQuiz && !quizCompleted" class="glass-card-lg rounded-2xl p-8 text-center">
          <div class="max-w-2xl mx-auto">
            <CheckCircleIcon class="w-16 h-16 mx-auto text-pink-600 dark:text-pink-400 mb-6" aria-hidden="true" />
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">{{ quiz?.title }}</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-4">{{ quiz?.description }}</p>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
              <div class="glass-card rounded-xl p-4 text-center">
                <BookOpenIcon class="w-8 h-8 mx-auto text-pink-600 dark:text-pink-400 mb-2" aria-hidden="true" />
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ quiz?.total_questions }} Questions</p>
              </div>
              <div class="glass-card rounded-xl p-4 text-center">
                <ClockIcon class="w-8 h-8 mx-auto text-pink-600 dark:text-pink-400 mb-2" aria-hidden="true" />
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ quiz?.time_limit }} Minutes</p>
              </div>
              <div class="glass-card rounded-xl p-4 text-center">
                <LightBulbIcon class="w-8 h-8 mx-auto text-pink-600 dark:text-pink-400 mb-2" aria-hidden="true" />
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ getDifficultyLabel(quiz?.difficulty) }}</p>
              </div>
            </div>

            <p v-if="quiz?.time_limit" class="text-pink-600 dark:text-pink-400 text-sm mb-6 flex items-center justify-center gap-2">
              <ExclamationTriangleIcon class="w-5 h-5" aria-hidden="true" />
              This quiz has a time limit of {{ quiz?.time_limit }} minutes.
            </p>

            <RouterLink :to="`/quizzes/${quiz?.id}/take`" class="btn-primary w-full py-4 text-lg">
              <PlayIcon class="w-6 h-6 mr-2" aria-hidden="true" />
              Start Quiz
            </RouterLink>
            
            <RouterLink to="/quizzes" class="btn-secondary w-full py-4 mt-4">
              <ArrowLeftIcon class="w-5 h-5 mr-2" aria-hidden="true" />
              Back to Quizzes
            </RouterLink>
          </div>

          <!-- Quiz Taking -->
          <div v-else-if="showQuiz" class="space-y-6">
            <div class="glass-card-lg rounded-2xl p-6">
              <!-- Progress Bar -->
              <div class="mb-6">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Question {{ currentQuestionIndex + 1 }} of {{ questions.length }}</span>
                  <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ userAnswers[currentQuestion?.id] ? 'Answered' : 'Not answered' }}
                  </span>
                </div>
                <div class="progress-bar">
                  <div class="progress-fill" :style="{ width: ((currentQuestionIndex + 1) / questions.length) * 100 + '%' }"></div>
                </div>
              </div>

              <!-- Question -->
              <div v-if="currentQuestion" class="mb-8">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">{{ currentQuestion.question_text }}</h3>
                <div class="space-y-3">
                  <label v-for="(option, key) in currentQuestion.options" :key="key" class="flex items-center gap-3 p-4 glass-card rounded-xl cursor-pointer hover:shadow-glass-lg transition-all duration-200"
                    :class="{ 'ring-2 ring-pink-500': userAnswers[currentQuestion.id] === key }"
                    @click="selectAnswer(currentQuestion.id, key)"
                  >
                    <input
                      type="radio"
                      :name="`question-${currentQuestion.id}`"
                      :value="key"
                      :checked="userAnswers[currentQuestion.id] === key"
                      @change="selectAnswer(currentQuestion.id, key)"
                      class="w-5 h-5 text-pink-600 border-pink-300 focus:ring-pink-500"
                    />
                    <span class="text-gray-900 dark:text-white flex-1">{{ option }}</span>
                  </label>
                </div>
              </div>
            </div>

            <!-- Navigation -->
            <div class="flex items-center justify-between pt-6 border-t border-pink-200/50 dark:border-pink-800/50">
              <button 
                @click="prevQuestion" 
                :disabled="currentQuestionIndex === 0"
                class="btn-secondary"
              >
                <ArrowLeftIcon class="w-5 h-5 mr-2" aria-hidden="true" />
                Previous
              </button>
              
              <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">
                  Question {{ currentQuestionIndex + 1 }} of {{ questions.length }}
                </span>
              </div>
              
              <button 
                v-if="currentQuestionIndex < questions.length - 1"
                @click="nextQuestion" 
                class="btn-primary"
              >
                Next
                <ArrowRightIcon class="ml-2 w-5 h-5" aria-hidden="true" />
              </button>
              <button 
                v-else
                @click="submitQuiz" 
                class="btn-primary"
                :disabled="Object.keys(userAnswers).length < questions.length"
              >
                Submit Quiz
                <CheckCircleIcon class="ml-2 w-5 h-5" aria-hidden="true" />
              </button>
            </div>
          </div>

          <!-- Quiz Results -->
          <div v-else-if="quizCompleted && quizResult" class="space-y-6">
            <div class="glass-card-lg rounded-2xl p-8 text-center">
              <div class="w-20 h-20 mx-auto mb-6 rounded-full flex items-center justify-center" :class="quizResult.percentage >= 70 ? 'bg-green-100/80 dark:bg-green-900/30 text-green-600' : quizResult.percentage >= 50 ? 'bg-yellow-100/80 dark:bg-yellow-900/30 text-yellow-600' : 'bg-red-100/80 dark:bg-red-900/30 text-red-600'">
                <CheckCircleIcon class="w-10 h-10" aria-hidden="true" />
              </div>
              <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ quizResult.percentage }}%</h2>
              <p class="text-gray-600 dark:text-gray-400 mb-6">{{ quizResult.score }} out of {{ quizResult.total_questions }} correct</p>
              
              <div class="grid grid-cols-3 gap-4 mb-8">
                <div class="glass-card rounded-xl p-4 text-center">
                  <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ quizResult.score }}</p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Correct</p>
                </div>
                <div class="glass-card rounded-xl p-4 text-center">
                  <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ quizResult.total_questions - quizResult.score }}</p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Incorrect</p>
                </div>
                <div class="glass-card rounded-xl p-4 text-center">
                  <p class="text-2xl font-bold text-pink-600 dark:text-pink-400">{{ quizResult.percentage }}%</p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Score</p>
                </div>
              </div>

              <!-- Answer Review -->
              <div class="space-y-4">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Answer Review</h3>
                <div v-for="(answer, index) in quizResult.answers" :key="answer.question_id" class="glass-card rounded-xl p-4">
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

              <div class="flex gap-4 pt-6">
                <RouterLink to="/quizzes" class="btn-secondary flex-1">
                  <ArrowLeftIcon class="w-5 h-5 mr-2" aria-hidden="true" />
                  Back to Quizzes
                </RouterLink>
                <RouterLink :to="`/quizzes/${quiz?.id}/take`" class="btn-primary flex-1">
                  <ArrowRightIcon class="w-5 h-5 ml-2" aria-hidden="true" />
                  Retry Quiz
                </RouterLink>
              </div>
            </div>
          </div>
        </div>
      </div>
    </DefaultLayout>
  </template>

  <script>
  import { onMounted, onUnmounted } from 'vue'

  onMounted(async () => {
    await fetchQuiz()
  })

  onUnmounted(() => {
    if (timerInterval.value) clearInterval(timerInterval.value)
  })
  </script>