<script setup lang="ts>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { RouterLink } from 'vue-router'
import { 
  ArrowLeftIcon, CheckCircleIcon, BookOpenIcon
} from '@heroicons/vue/24/outline'
import { useSearch, createSuggestionExtractor } from '@/composables/useSearch'
import SearchInput from '@/components/ui/SearchInput.vue'

const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(true)
const quizzes = ref<import('@/types').Quiz[]>([])

const searchComposable = useSearch({
  data: quizzes,
  searchFields: ['title', 'description', 'topic.name'],
  debounceMs: 150,
  minQueryLength: 1,
  fuzzyThreshold: 0.35,
  getSuggestions: createSuggestionExtractor(['title', 'description', 'topic.name'], 8)
})

const filteredQuizzes = computed(() => searchComposable.results.value)

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

function getDifficultyClass(difficulty: number): string {
  if (difficulty <= 2) return 'badge-success'
  if (difficulty === 3) return 'badge-warning'
  return 'badge-urgent'
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

onMounted(async () => {
  await fetchQuizzes()
})
</script>

<template>
  <DefaultLayout>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
          <RouterLink to="/dashboard" class="p-2 rounded-xl text-text-muted hover:text-text-primary dark:hover:text-white hover:bg-surface/50 dark:hover:bg-surface-alt/30 transition-colors">
            <ArrowLeftIcon class="w-6 h-6" aria-hidden="true" />
          </RouterLink>
          <div>
            <h1 class="text-3xl font-bold text-text-primary dark:text-white">Quizzes</h1>
            <p class="text-text-secondary dark:text-text-muted">Test your knowledge and track progress</p>
          </div>
        </div>
      </div>

      <!-- Search -->
      <div class="mb-6">
        <label for="quiz-search" class="sr-only">Search quizzes</label>
        <SearchInput
          id="quiz-search"
          v-model="searchComposable.query"
          :suggestions="searchComposable.suggestions"
          :loading="searchComposable.isSearching"
          placeholder="Search quizzes by title, description, topic..."
          aria-label="Search quizzes"
        />
        <p v-if="searchComposable.query && !searchComposable.hasResults" class="mt-2 text-sm text-text-muted">
          No quizzes found matching "{{ searchComposable.query }}"
        </p>
      </div>

      <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="i in 6" :key="i" class="glass-card-lg rounded-2xl p-6 animate-pulse">
          <div class="h-6 bg-surface-border/50 dark:bg-surface-border/30 rounded w-3/4 mb-4"></div>
          <div class="h-4 bg-surface-border/50 dark:bg-surface-border/30 rounded w-1/2"></div>
        </div>
      </div>

      <div v-else-if="filteredQuizzes.length === 0" class="text-center py-16">
        <CheckCircleIcon class="w-16 h-16 mx-auto text-text-muted mb-4" aria-hidden="true" />
        <h3 class="text-xl font-semibold text-text-primary dark:text-white mb-2">No quizzes available</h3>
        <p class="text-text-secondary dark:text-text-muted mb-6">
          {{ searchComposable.query ? 'Try adjusting your search' : 'No quizzes available for your subjects yet' }}
        </p>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <RouterLink 
          v-for="quiz in filteredQuizzes" 
          :key="quiz.id" 
          :to="`/quizzes/${quiz.id}`"
          class="glass-card-lg rounded-2xl p-6 hover:shadow-glass-lg hover:-translate-y-1 transition-all duration-300"
        >
          <div class="flex items-start justify-between mb-4">
            <div class="w-12 h-12 rounded-xl bg-primary-light/50 dark:bg-primary-dark/30 flex items-center justify-center text-primary dark:text-primary-light">
              <CheckCircleIcon class="w-6 h-6" aria-hidden="true" />
            </div>
            <span class="badge badge-gray">{{ quiz.topic?.name }}</span>
          </div>
          
          <h3 class="text-xl font-bold text-text-primary dark:text-white mb-2 truncate">{{ quiz.title }}</h3>
          <p class="text-text-secondary dark:text-text-muted text-sm mb-4 line-clamp-2">{{ quiz.description }}</p>
          
          <div class="flex items-center gap-4 text-sm text-text-secondary dark:text-text-muted mb-4">
            <span class="flex items-center gap-1">
              <BookOpenIcon class="w-4 h-4" aria-hidden="true" />
              {{ quiz.total_questions }} questions
            </span>
            <span class="badge" :class="getDifficultyClass(quiz.difficulty)">
              Difficulty: {{ getDifficultyLabel(quiz.difficulty) }}
            </span>
          </div>

          <div class="flex items-center justify-between pt-4 border-t border-surface-border/30 dark:border-surface-border/20">
            <div v-if="quiz.last_attempt" class="flex items-center gap-2 text-sm">
              <span class="text-text-secondary dark:text-text-muted">Last attempt:</span>
              <span class="font-medium" :class="quiz.last_attempt.percentage >= 70 ? 'text-status-success' : quiz.last_attempt.percentage >= 50 ? 'text-status-warning' : 'text-status-urgent'">
                {{ quiz.last_attempt.percentage }}%
              </span>
              <span class="text-text-muted">{{ formatRelativeTime(quiz.last_attempt.completed_at) }}</span>
            </div>
            <div v-else class="text-sm text-text-muted">
              Not attempted yet
            </div>
          </div>
        </RouterLink>
      </div>
    </div>
  </DefaultLayout>
</template>