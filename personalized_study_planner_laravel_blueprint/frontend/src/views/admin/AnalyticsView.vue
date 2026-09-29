<script setup lang="ts>
import { ref, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { RouterLink } from 'vue-router'
import { 
  ArrowLeftIcon, ChartBarIcon, UsersIcon, BookOpenIcon,
  ClockIcon, CheckCircleIcon, LightBulbIcon
} from '@heroicons/vue/24/outline'
import { Chart, registerables } from 'chart.js'
import 'chartjs-adapter-date-fns'

Chart.register(...registerables)

const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(true)
const analytics = ref<import('@/types').AdminAnalyticsData | null>(null)

const usersChart = ref<HTMLCanvasElement | null>(null)
const resourcesChart = ref<HTMLCanvasElement | null>(null)
const quizChart = ref<HTMLCanvasElement | null>(null)
const studyChart = ref<HTMLCanvasElement | null>(null)

let usersChartInstance: Chart | null = null
let resourcesChartInstance: Chart | null = null
let quizChartInstance: Chart | null = null
let studyChartInstance: Chart | null = null

const metrics = ref([
  { label: 'Total Users', value: '0', icon: UsersIcon, color: 'text-blue-500', bg: 'bg-blue-100/50 dark:bg-blue-900/30' },
  { label: 'Total Resources', value: '0', icon: BookOpenIcon, color: 'text-green-500', bg: 'bg-green-100/50 dark:bg-green-900/30' },
  { label: 'Quiz Attempts', value: '0', icon: LightBulbIcon, color: 'text-purple-500', bg: 'bg-purple-100/50 dark:bg-purple-900/30' },
  { label: 'Avg Study Time', value: '0h', icon: ClockIcon, color: 'text-orange-500', bg: 'bg-orange-100/50 dark:bg-orange-900/30' },
])

async function fetchAnalytics() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get('/admin/analytics')
    analytics.value = response.data.data
    updateMetrics()
    renderCharts()
  } catch (error: any) {
    toastStore.error('Failed to load analytics', error.message)
  } finally {
    loading.value = false
  }
}

function updateMetrics() {
  if (!analytics.value) return
  metrics.value = [
    { label: 'Total Users', value: analytics.value.users.total.toString(), icon: UsersIcon, color: 'text-blue-500', bg: 'bg-blue-100/50 dark:bg-blue-900/30' },
    { label: 'Total Resources', value: analytics.value.resources.total.toString(), icon: BookOpenIcon, color: 'text-green-500', bg: 'bg-green-100/50 dark:bg-green-900/30' },
    { label: 'Quiz Attempts', value: analytics.value.quizzes.total_attempts.toString(), icon: LightBulbIcon, color: 'text-purple-500', bg: 'bg-purple-100/50 dark:bg-purple-900/30' },
    { label: 'Avg Study Time', value: Math.round(analytics.value.tasks.completion_rate) + 'h', icon: ClockIcon, color: 'text-orange-500', bg: 'bg-orange-100/50 dark:bg-orange-900/30' },
  ]
}

function renderCharts() {
  if (!analytics.value) return

  // Users over time chart
  if (usersChartInstance) usersChartInstance.destroy()
  usersChartInstance = new Chart(usersChart.value!, {
    type: 'line',
    data: {
      labels: analytics.value.users_over_time.map(u => u.date),
      datasets: [{
        label: 'Users',
        data: analytics.value.users_over_time.map(u => u.count),
        borderColor: '#ec4899',
        backgroundColor: 'rgba(236, 72, 153, 0.1)',
        fill: true,
        tension: 0.4,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true }
      }
    }
  })

  // Resources by type chart
  if (resourcesChartInstance) resourcesChartInstance.destroy()
  resourcesChartInstance = new Chart(resourcesChart.value!, {
    type: 'doughnut',
    data: {
      labels: analytics.value.resources_by_type.map(r => r.type),
      datasets: [{
        data: analytics.value.resources_by_type.map(r => r.count),
        backgroundColor: [
          '#ec4899', '#8b5cf6', '#3b82f6', '#14b8a6', '#f59e0b', '#ef4444'
        ],
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom' } }
    }
  })

  // Quiz performance chart
  if (quizChartInstance) quizChartInstance.destroy()
  quizChartInstance = new Chart(quizChart.value!, {
    type: 'bar',
    data: {
      labels: analytics.value.quiz_performance.map(q => q.topic),
      datasets: [{
        label: 'Average Score',
        data: analytics.value.quiz_performance.map(q => q.avg_score),
        backgroundColor: 'rgba(236, 72, 153, 0.8)',
        borderColor: '#ec4899',
        borderWidth: 1,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      indexAxis: 'y',
      plugins: { legend: { display: false } },
      scales: {
        x: { beginAtZero: true, max: 100 }
      }
    }
  })

  // Study hours chart
  if (studyChartInstance) studyChartInstance.destroy()
  studyChartInstance = new Chart(studyChart.value!, {
    type: 'line',
    data: {
      labels: analytics.value.study_hours.map(s => s.date),
      datasets: [{
        label: 'Minutes',
        data: analytics.value.study_hours.map(s => s.minutes),
        borderColor: '#3b82f6',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        fill: true,
        tension: 0.4,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true }
      }
    }
  })
}

function destroyCharts() {
  usersChartInstance?.destroy()
  resourcesChartInstance?.destroy()
  quizChartInstance?.destroy()
  studyChartInstance?.destroy()
}

async function fetchAnalytics() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get('/admin/analytics')
    analytics.value = response.data.data
    updateMetrics()
    renderCharts()
  } catch (error: any) {
    toastStore.error('Failed to load analytics', error.message)
  } finally {
    loading.value = false
  }
}

function updateMetrics() {
  if (!analytics.value) return
  metrics.value = [
    { label: 'Total Users', value: analytics.value.users.total.toString(), icon: UsersIcon, color: 'text-blue-500', bg: 'bg-blue-100/50 dark:bg-blue-900/30' },
    { label: 'Total Resources', value: analytics.value.resources.total.toString(), icon: BookOpenIcon, color: 'text-green-500', bg: 'bg-green-100/50 dark:bg-green-900/30' },
    { label: 'Quiz Attempts', value: analytics.value.quizzes.total_attempts.toString(), icon: LightBulbIcon, color: 'text-purple-500', bg: 'bg-purple-100/50 dark:bg-purple-900/30' },
    { label: 'Avg Study Time', value: Math.round(analytics.value.tasks.completion_rate) + 'h', icon: ClockIcon, color: 'text-orange-500', bg: 'bg-orange-100/50 dark:bg-orange-900/30' },
  ]
}
</script>