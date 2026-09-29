<script setup lang="ts>
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { RouterLink } from 'vue-router'
import { 
  ArrowLeftIcon, CheckCircleIcon, ClockIcon, 
  LightBulbIcon, ArrowRightIcon, DocumentTextIcon,
  ChartBarIcon
} from '@heroicons/vue/24/outline'

const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(true)
const progress = ref<import('@/types').ProgressReport | null>(null)

const statCards = computed(() => {
  if (!progress.value) return []
  const summary = progress.value.summary
  return [
    { label: 'Tasks Completed', value: summary.tasks?.completed || 0, icon: CheckCircleIcon, color: 'text-green-500', bg: 'bg-green-100/50 dark:bg-green-900/30' },
    { label: 'Study Hours', value: Math.round((summary.study_minutes || 0) / 60), icon: ClockIcon, color: 'text-blue-500', bg: 'bg-blue-100/50 dark:bg-blue-900/30' },
    { label: 'Quizzes Taken', value: summary.quizzes?.total_attempts || 0, icon: LightBulbIcon, color: 'text-purple-500', bg: 'bg-purple-100/50 dark:bg-purple-900/30' },
    { label: 'Avg Quiz Score', value: Math.round(summary.quizzes?.average_score || 0) + '%', icon: ChartBarIcon, color: 'text-purple-500', bg: 'bg-purple-100/50 dark:bg-purple-900/30' },
  ]
}

const topicPerformance = computed(() => {
  if (!progress.value) return []
  return progress.value.topic_performance || []
}

async function fetchProgress() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get('/student/progress')
    progress.value = response.data.data
  } catch (error: any) {
    toastStore.error('Failed to load progress', error.message)
  } finally {
    loading.value = false
  }
}

async function exportProgress() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get('/student/progress/export', { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `progress-report-${new Date().toISOString().split('T')[0]}.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    toastStore.success('Progress exported!')
  } catch (error: any) {
    toastStore.error('Failed to export progress', error.message)
  }
}

function getDifficultyColor(difficulty: number): string {
  if (difficulty <= 2) return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
  if (difficulty === 3) return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
  return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
}

function getClassificationColor(classification: string): string {
  switch (classification) {
    case 'weak': return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
    case 'needs_review': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
    case 'strong': return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
    default: return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
  }
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
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Progress</h1>
            <p class="text-gray-600 dark:text-gray-400">Track your learning progress and performance</p>
          </div>
        </div>
        <div class="flex gap-3 ml-auto">
          <button @click="exportProgress" class="btn-secondary" :disabled="loading">
            <DocumentTextIcon class="w-5 h-5 mr-2" aria-hidden="true" />
            Export CSV
          </button>
        </div>
      </div>

      <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div v-for="i in 4" :key="i" class="glass-card-lg rounded-2xl p-6 animate-pulse">
          <div class="h-6 bg-gray-200/50 dark:bg-gray-700/50 rounded w-3/4 mb-4"></div>
          <div class="h-12 bg-gray-200/50 dark:bg-gray-700/50 rounded w-full"></div>
        </div>
      </div>

      <div v-else class="space-y-8">
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div v-for="stat in statCards" :key="stat.label" class="glass-card-lg rounded-2xl p-6">
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

        <!-- Topic Performance -->
        <div class="glass-card-lg rounded-2xl overflow-hidden">
          <div class="px-6 py-4 border-b border-pink-200/50 dark:border-pink-800/50">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
              <ChartBarIcon class="w-5 h-5 text-pink-600 dark:text-pink-400" aria-hidden="true" />
              Topic Performance
            </h2>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="bg-pink-50/50 dark:bg-pink-900/30">
                  <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Topic</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Avg Score</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Attempts</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Best Score</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Classification</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-pink-100/50 dark:divide-pink-800/50">
                <tr v-if="topicPerformance.length === 0" class="text-center py-12">
                  <td colspan="5" class="px-6 py-12 text-center">
                    <LightBulbIcon class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" aria-hidden="true" />
                    <p class="text-gray-500 dark:text-gray-400">No quiz data available yet</p>
                    <RouterLink to="/quizzes" class="btn-primary mt-4 inline-block">Take a Quiz</RouterLink>
                  </td>
                </tr>
                <tr v-else v-for="topic in topicPerformance" :key="topic.topic_id" class="hover:bg-pink-50/30 dark:hover:bg-pink-900/10 transition-colors">
                  <td class="px-6 py-4">
                    <div class="font-medium text-gray-900 dark:text-white truncate max-w-xs">{{ topic.topic_name }}</div>
                  </td>
                  <td class="px-6 py-4">
                    <span class="font-medium text-gray-900 dark:text-white">{{ topic.avg_percentage }}%</span>
                  </td>
                  <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ topic.attempt_count }}</td>
                  <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ topic.best_score }}%</td>
                  <td class="px-6 py-4">
                    <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', getClassificationColor(topic.classification)]">
                      {{ topic.classification }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- CTA -->
          <div class="text-center py-8">
            <RouterLink to="/quizzes" class="btn-primary">
              <CheckCircleIcon class="w-5 h-5 mr-2" aria-hidden="true" />
              Take a Quiz to Improve
            </RouterLink>
          </div>
        </div>
      </div>
    </DefaultLayout>
  </template>

  <script>
  import { onMounted } from 'vue'

  onMounted(async () => {
    await fetchProgress()
  })
  </script>