<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { RouterLink } from 'vue-router'
import { 
  BookOpenIcon, CalendarDaysIcon, CheckCircleIcon, 
  ChartBarIcon, LightBulbIcon, BellIcon, ArrowRightIcon,
  ClockIcon, FireIcon, TrophyIcon
} from '@heroicons/vue/24/outline'

const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(true)
const dashboardData = ref<any>(null)
const onboardingData = ref<any>(null)

const stats = computed(() => {
  if (!dashboardData.value) return []
  const progress = dashboardData.value.progress
  return [
    { label: 'Tasks Completed', value: progress.tasks?.completed || 0, icon: CheckCircleIcon, color: 'text-status-success', bg: 'bg-status-success/15 dark:bg-status-success/20' },
    { label: 'Study Hours', value: Math.round((progress.study_minutes?.total || 0) / 60), icon: ClockIcon, color: 'text-primary', bg: 'bg-primary-light/50 dark:bg-primary-dark/30' },
    { label: 'Quizzes Taken', value: progress.quizzes?.total_attempts || 0, icon: TrophyIcon, color: 'text-secondary', bg: 'bg-secondary-light/50 dark:bg-secondary-dark/30' },
    { label: 'Current Streak', value: progress.streak?.current || 0, icon: FireIcon, color: 'text-status-warning', bg: 'bg-status-warning/15 dark:bg-status-warning/20' },
  ]
})

const quickActions = [
  { name: 'Create Task', href: '/tasks/create', icon: CheckCircleIcon, color: 'bg-primary', description: 'Add a new assignment' },
  { name: 'Generate Plan', href: '/study-plan/generate', icon: CalendarDaysIcon, color: 'bg-primary', description: 'Generate study schedule' },
  { name: 'Take Quiz', href: '/quizzes', icon: TrophyIcon, color: 'bg-primary', description: 'Test your knowledge' },
  { name: 'View Progress', href: '/progress', icon: ChartBarIcon, color: 'bg-primary', description: 'View analytics' },
]

async function fetchDashboard() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get('/student/dashboard')
    dashboardData.value = response.data.data
    onboardingData.value = response.data.data.onboarding
    loading.value = false
  } catch (error: any) {
    toastStore.error('Failed to load dashboard', error.message)
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

function getPriorityColor(priority: string): string {
  switch (priority) {
    case 'urgent': return 'bg-status-urgent'
    case 'high': return 'bg-status-warning'
    case 'medium': return 'bg-status-info'
    case 'low': return 'bg-status-success'
    default: return 'bg-text-muted'
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

function getOnboardingRoute(action: string | null): string {
  switch (action) {
    case 'create_study_preferences': return '/settings/preferences'
    case 'create_first_subject': return '/subjects/create'
    case 'create_first_task': return '/tasks/create'
    case 'generate_study_plan': return '/study-plan/generate'
    default: return '/onboarding'
  }
}

function getOnboardingButtonText(action: string | null): string {
  switch (action) {
    case 'create_study_preferences': return 'Set Up Preferences'
    case 'create_first_subject': return 'Create Subject'
    case 'create_first_task': return 'Create Task'
    case 'generate_study_plan': return 'Generate Plan'
    default: return 'Continue Setup'
  }
}

function getNextStepMessage(action: string | null): string {
  switch (action) {
    case 'create_study_preferences': return 'Set up your study preferences to get personalized schedules.'
    case 'create_first_subject': return 'Add your first subject to start organizing your studies.'
    case 'create_first_task': return 'Create your first task to begin tracking your work.'
    case 'generate_study_plan': return 'Generate your first AI-powered study plan.'
    default: return 'Complete your setup to unlock all features.'
  }
}

function getStatusBadgeClass(status: string): string {
  switch (status) {
    case 'pending': return 'badge-warning'
    case 'in_progress': return 'badge-info'
    case 'completed': return 'badge-success'
    case 'cancelled': return 'badge-gray'
    default: return 'badge-gray'
  }
}

function formatTime(timeString: string): string {
  const [hours, minutes] = timeString.split(':')
  const hour = parseInt(hours)
  const ampm = hour >= 12 ? 'PM' : 'AM'
  const hour12 = hour % 12 || 12
  return `${hour12}:${minutes} ${ampm}`
}

onMounted(() => {
  fetchDashboard()
})
</script>

<template>
  <DefaultLayout>
    <template #default>
      <!-- Onboarding Banner -->
      <div v-if="onboardingData && !onboardingData.is_complete" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 mt-4">
        <div class="glass-card-lg rounded-2xl p-6 border-l-4 border-primary">
          <div class="flex items-start justify-between gap-4">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-light/80 dark:bg-primary-dark/30 text-primary-dark dark:text-primary-light">
                  Setup Progress: {{ onboardingData.completion_percentage }}%
                </span>
              </div>
              <p class="text-text-secondary dark:text-gray-300 mb-2">
                {{ getNextStepMessage(onboardingData.next_action) }}
              </p>
              <div class="h-2 bg-primary-light/50 dark:bg-primary-dark/30 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-primary to-primary-hover rounded-full transition-all duration-500" :style="{ width: onboardingData.completion_percentage + '%' }" />
              </div>
              <div class="flex gap-4 mt-4 text-sm text-text-muted">
                <span>Subjects: {{ onboardingData.counts?.subjects || 0 }}</span>
                <span>Tasks: {{ onboardingData.counts?.tasks || 0 }}</span>
                <span>Sessions: {{ onboardingData.counts?.study_sessions || 0 }}</span>
              </div>
            </div>
            <RouterLink 
              :to="getOnboardingRoute(onboardingData.next_action)"
              class="btn-primary whitespace-nowrap flex-shrink-0"
            >
              {{ getOnboardingButtonText(onboardingData.next_action) }}
              <ArrowRightIcon class="ml-2 w-5 h-5" aria-hidden="true" />
            </RouterLink>
          </div>
        </div>
      </div>

      <!-- Welcome Header -->
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
          <div>
            <h1 class="text-3xl font-bold text-text-primary dark:text-white">
              Welcome back, {{ authStore.user?.name?.split(' ')[0] }}! 👋
            </h1>
            <p class="text-text-secondary dark:text-gray-400 mt-1">Here's what's happening with your studies today.</p>
          </div>
          
          <div class="flex flex-wrap gap-3">
            <RouterLink to="/study-plan/generate" class="btn-primary">
              <CalendarDaysIcon class="w-5 h-5 mr-2" aria-hidden="true" />
              Generate Plan
            </RouterLink>
            <RouterLink to="/tasks/create" class="btn-secondary">
              <CheckCircleIcon class="w-5 h-5 mr-2" aria-hidden="true" />
              New Task
            </RouterLink>
          </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div v-for="stat in stats" :key="stat.label" class="glass-card-lg rounded-2xl p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-text-secondary dark:text-gray-400">{{ stat.label }}</p>
                <p class="text-3xl font-bold text-text-primary dark:text-white mt-1">{{ stat.value }}</p>
              </div>
              <div :class="['p-3 rounded-xl', stat.bg]">
                <component :is="stat.icon" :class="['w-6 h-6', stat.color]" aria-hidden="true" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pb-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Left Column: Upcoming Tasks & Sessions -->
          <div class="lg:col-span-2 space-y-8">
            <!-- Upcoming Tasks -->
            <div class="glass-card-lg rounded-2xl overflow-hidden">
              <div class="flex items-center justify-between px-6 py-4 border-b border-surface-border/50 dark:border-surface-border/30">
                <h2 class="text-lg font-semibold text-text-primary dark:text-white flex items-center gap-2">
                  <CheckCircleIcon class="w-5 h-5 text-primary dark:text-primary-light" aria-hidden="true" />
                  Upcoming Tasks
                </h2>
                <RouterLink to="/tasks" class="text-sm text-primary hover:text-primary-hover underline">View all</RouterLink>
              </div>
              <div class="divide-y divide-surface-border/30 dark:divide-surface-border/20">
                <div v-if="dashboardData?.upcoming_tasks?.length === 0" class="px-6 py-12 text-center">
                  <CheckCircleIcon class="w-12 h-12 mx-auto text-text-muted mb-3" aria-hidden="true" />
                  <p class="text-text-secondary dark:text-gray-400">No upcoming tasks. Great job!</p>
                  <RouterLink to="/tasks/create" class="btn-primary mt-4 inline-block">Create Task</RouterLink>
                </div>
                <div v-else class="divide-y divide-surface-border/30 dark:divide-surface-border/20">
                  <div 
                    v-for="task in dashboardData.upcoming_tasks.slice(0, 5)" 
                    :key="task.id"
                    class="px-6 py-4 hover:bg-surface/50 dark:hover:bg-surface-alt/30 transition-colors"
                  >
                    <RouterLink :to="`/tasks/${task.id}`" class="flex items-center justify-between gap-4 w-full">
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                          <h3 class="font-medium text-text-primary dark:text-white truncate">{{ task.title }}</h3>
                          <span :class="['badge', 'badge-' + task.priority]">{{ task.priority }}</span>
                          <span v-if="task.subject" class="badge badge-gray">{{ task.subject.name }}</span>
                        </div>
                        <p class="text-sm text-text-secondary dark:text-gray-400 flex items-center gap-1">
                          <CalendarDaysIcon class="w-4 h-4" aria-hidden="true" />
                          Due {{ formatRelativeTime(task.deadline) }}
                        </p>
                      </div>
                      <div class="flex items-center gap-2">
                        <span :class="['badge', getStatusBadgeClass(task.status)]">{{ getStatusLabel(task.status) }}</span>
                        <ArrowRightIcon class="w-4 h-4 text-text-muted" aria-hidden="true" />
                      </div>
                    </RouterLink>
                  </div>
                </div>
              </div>
            </div>

            <!-- Upcoming Study Sessions -->
            <div class="glass-card-lg rounded-2xl overflow-hidden">
              <div class="flex items-center justify-between px-6 py-4 border-b border-surface-border/50 dark:border-surface-border/30">
                <h2 class="text-lg font-semibold text-text-primary dark:text-white flex items-center gap-2">
                  <CalendarDaysIcon class="w-5 h-5 text-primary dark:text-primary-light" aria-hidden="true" />
                  Upcoming Study Sessions
                </h2>
                <RouterLink to="/study-plan" class="text-sm text-primary hover:text-primary-hover underline">View all</RouterLink>
              </div>
              <div class="divide-y divide-surface-border/30 dark:divide-surface-border/20">
                <div v-if="dashboardData?.upcoming_sessions?.length === 0" class="px-6 py-12 text-center">
                  <CalendarDaysIcon class="w-12 h-12 mx-auto text-text-muted mb-3" aria-hidden="true" />
                  <p class="text-text-secondary dark:text-gray-400">No upcoming study sessions</p>
                  <RouterLink to="/study-plan/generate" class="btn-primary mt-4 inline-block">Generate Plan</RouterLink>
                </div>
                <div v-else>
                  <div 
                    v-for="session in dashboardData.upcoming_sessions.slice(0, 5)" 
                    :key="session.id"
                    class="px-6 py-4 hover:bg-surface/50 dark:hover:bg-surface-alt/30 transition-colors"
                  >
                    <RouterLink :to="`/study-sessions/${session.id}`" class="flex items-center justify-between gap-4 w-full">
                      <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-xl bg-primary-light/80 dark:bg-primary-dark/30 flex items-center justify-center text-primary dark:text-primary-light">
                          <ClockIcon class="w-7 h-7" aria-hidden="true" />
                        </div>
                        <div>
                          <h3 class="font-medium text-text-primary dark:text-white">{{ session.subject?.name || 'Study Session' }}</h3>
                          <p class="text-sm text-text-secondary dark:text-gray-400">{{ session.topic?.name || 'General review' }}</p>
                        </div>
                      </div>
                      <div class="text-right">
                        <p class="font-medium text-text-primary dark:text-white">{{ formatTime(session.start_time) }} - {{ formatTime(session.end_time) }}</p>
                        <p class="text-sm text-text-secondary dark:text-gray-400">{{ formatRelativeTime(session.scheduled_date) }}</p>
                        <span class="badge badge-primary mt-1">{{ session.status }}</span>
                      </div>
                    </RouterLink>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column: Quick Actions & Recommendations -->
          <div class="space-y-8">
            <!-- Quick Actions -->
            <div class="glass-card-lg rounded-2xl p-6">
              <h2 class="text-lg font-semibold text-text-primary dark:text-white mb-6 flex items-center gap-2">
                <LightBulbIcon class="w-5 h-5 text-primary dark:text-primary-light" aria-hidden="true" />
                Quick Actions
              </h2>
              <div class="grid grid-cols-2 gap-4">
                <RouterLink 
                  v-for="action in quickActions" 
                  :key="action.name" 
                  :to="action.href"
                  class="flex items-center gap-3 p-4 glass-card rounded-xl hover:shadow-glass-lg hover:-translate-y-1 transition-all duration-300 group"
                >
                  <div :class="['p-3 rounded-xl', action.color]">
                    <component :is="action.icon" class="w-6 h-6 text-white" aria-hidden="true" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <h3 class="font-medium text-text-primary dark:text-white">{{ action.name }}</h3>
                    <p class="text-sm text-text-secondary dark:text-gray-400">{{ action.description }}</p>
                  </div>
                  <ArrowRightIcon class="w-5 h-5 text-text-muted group-hover:text-primary transition-colors" aria-hidden="true" />
                </RouterLink>
              </div>
            </div>

            <!-- Recommendations -->
            <div class="glass-card-lg rounded-2xl overflow-hidden">
              <div class="px-6 py-4 border-b border-surface-border/50 dark:border-surface-border/30">
                <h2 class="text-lg font-semibold text-text-primary dark:text-white flex items-center gap-2">
                  <LightBulbIcon class="w-5 h-5 text-primary dark:text-primary-light" aria-hidden="true" />
                  Recommended Resources
                </h2>
              </div>
              <div v-if="dashboardData?.recommendations?.length === 0" class="px-6 py-12 text-center">
                <LightBulbIcon class="w-12 h-12 mx-auto text-text-muted mb-3" aria-hidden="true" />
                <p class="text-text-secondary dark:text-gray-400 mb-2">No recommendations yet</p>
                <p class="text-sm text-text-muted mb-4">Take quizzes to get personalized recommendations</p>
                <RouterLink to="/quizzes" class="btn-secondary inline-block">Take a Quiz</RouterLink>
              </div>
              <div v-else class="divide-y divide-surface-border/30 dark:divide-surface-border/20">
                <RouterLink 
                  v-for="rec in dashboardData.recommendations.slice(0, 3)" 
                  :key="rec.id" 
                  :to="`/recommendations`"
                  class="px-6 py-4 hover:bg-surface/50 dark:hover:bg-surface-alt/30 transition-colors flex items-center justify-between gap-4"
                >
                  <div class="flex items-center gap-3 flex-1 min-w-0">
                    <div class="w-12 h-12 rounded-xl bg-primary-light/80 dark:bg-primary-dark/30 flex items-center justify-center text-primary dark:text-primary-light">
                      <BookOpenIcon class="w-6 h-6" aria-hidden="true" />
                    </div>
                    <div class="min-w-0">
                      <h3 class="font-medium text-text-primary dark:text-white truncate">{{ rec.title }}</h3>
                      <p class="text-sm text-text-secondary dark:text-gray-400">{{ rec.resource_type }}</p>
                    </div>
                  </div>
                  <span class="badge badge-primary">{{ rec.recommendation_reason }}</span>
                </RouterLink>
                <div class="px-6 py-3 text-center">
                  <RouterLink to="/recommendations" class="text-sm text-primary hover:text-primary-hover underline">View all recommendations</RouterLink>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </DefaultLayout>
</template>