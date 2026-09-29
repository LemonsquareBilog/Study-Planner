<script setup lang="ts>
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { RouterLink } from 'vue-router'
import { 
  ArrowLeftIcon, LightBulbIcon, BookOpenIcon, 
  ArrowRightIcon, DocumentTextIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(true)
const recommendations = ref<import('@/types').Resource[]>([])
const reason = ref<string | null>(null)

async function fetchRecommendations() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get('/student/recommendations')
    recommendations.value = response.data.data || []
    reason.value = response.data.reason || null
  } catch (error: any) {
    toastStore.error('Failed to load recommendations', error.message)
  } finally {
    loading.value = false
  }
}

async function recordView(resourceId: number, topicId?: number) {
  try {
    const api = (await import('@/utils/api')).default
    await api.post(`/student/recommendations/${resourceId}/view`, { topic_id: topicId })
  } catch {
    // silent
  }
}

function getTypeIcon(type: string) {
  const icons: Record<string, any> = {
    book: 'BookOpenIcon',
    video: 'VideoCameraIcon',
    article: 'DocumentTextIcon',
    website: 'GlobeAltIcon',
    course: 'AcademicCapIcon',
    other: 'CubeIcon',
  }
  return icons[type] || 'BookOpenIcon'
}

function getDifficultyColor(difficulty: number): string {
  if (difficulty <= 2) return 'text-green-600 dark:text-green-400'
  if (difficulty === 3) return 'text-yellow-600 dark:text-yellow-400'
  return 'text-red-600 dark:text-red-400'
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
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Recommendations</h1>
            <p class="text-gray-600 dark:text-gray-400">Personalized resources based on your quiz performance</p>
          </div>
        </div>
      </div>

      <!-- Reason Message -->
      <div v-if="reason" class="mb-8 glass-card-lg rounded-2xl p-6" :class="reason === 'no_quiz_data' ? 'border-l-4 border-blue-500 bg-blue-50/50 dark:bg-blue-900/30' : 'border-l-4 border-green-500 bg-green-50/50 dark:bg-green-900/30'">
        <div class="flex items-start gap-4">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center" :class="reason === 'no_quiz_data' ? 'bg-blue-100/80 dark:bg-blue-900/30 text-blue-600' : 'bg-green-100/80 dark:bg-green-900/30 text-green-600'">
            <LightBulbIcon v-if="reason === 'no_quiz_data'" class="w-5 h-5" aria-hidden="true" />
            <CheckCircleIcon v-else class="w-5 h-5" aria-hidden="true" />
          </div>
          <div>
            <p v-if="reason === 'no_quiz_data'" class="text-gray-700 dark:text-gray-300">
              Take your first quiz to get personalized resource recommendations!
            </p>
            <p v-else class="text-gray-700 dark:text-gray-300">
              Great job! All your topics are strong. No recommendations needed at this time.
            </p>
            <RouterLink v-if="reason === 'no_quiz_data'" to="/quizzes" class="btn-primary mt-4 inline-block">
              <CheckCircleIcon class="w-5 h-5 mr-2" aria-hidden="true" />
              Take a Quiz
            </RouterLink>
          </div>
        </div>
      </div>

      <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="i in 6" :key="i" class="glass-card-lg rounded-2xl p-6 animate-pulse">
          <div class="h-6 bg-gray-200/50 dark:bg-gray-700/50 rounded w-3/4 mb-4"></div>
          <div class="h-4 bg-gray-200/50 dark:bg-gray-700/50 rounded w-1/2"></div>
        </div>
      </div>

      <div v-else-if="recommendations.length === 0" class="text-center py-16">
        <LightBulbIcon class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" aria-hidden="true" />
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No recommendations available</h3>
        <p class="text-gray-600 dark:text-gray-400 mb-6">Start taking quizzes to unlock personalized recommendations</p>
        <RouterLink to="/quizzes" class="btn-primary">
          <CheckCircleIcon class="w-5 h-5 mr-2" aria-hidden="true" />
          Take a Quiz
        </RouterLink>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <RouterLink 
          v-for="resource in recommendations" 
          :key="resource.id" 
          :to="resource.url"
          target="_blank"
          rel="noopener noreferrer"
          class="glass-card-lg rounded-2xl p-6 hover:shadow-glass-lg hover:-translate-y-1 transition-all duration-300"
        >
          <div class="flex items-center gap-3 mb-3">
            <div class="w-12 h-12 rounded-xl bg-pink-100/80 dark:bg-pink-900/30 flex items-center justify-center text-pink-600 dark:text-pink-400">
              <DocumentTextIcon class="w-6 h-6" aria-hidden="true" />
            </div>
            <div class="min-w-0">
              <h3 class="font-medium text-gray-900 dark:text-white truncate">{{ resource.title }}</h3>
              <span class="text-xs text-gray-500 dark:text-gray-400 capitalize">{{ resource.resource_type }}</span>
            </div>
          </div>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">{{ resource.description }}</p>
          <div class="flex items-center justify-between text-sm">
            <span class="text-gray-500 dark:text-gray-400">Difficulty: {{ resource.difficulty }}/5</span>
            <span class="text-pink-600 dark:text-pink-400 hover:underline">View Resource <ArrowRightIcon class="w-4 h-4 ml-1" aria-hidden="true" /></span>
          </div>
          <button 
            @click.prevent="recordView(resource.id)"
            class="w-full mt-4 btn-primary"
          >
            Mark as Viewed
          </button>
        </RouterLink>
      </div>
    </DefaultLayout>
  </template>

  <script>
  import { onMounted } from 'vue'

  onMounted(async () => {
    await fetchRecommendations()
  })
  </script>