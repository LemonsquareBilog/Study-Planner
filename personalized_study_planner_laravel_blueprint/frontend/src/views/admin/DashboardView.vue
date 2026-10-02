<script setup lang="ts>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { RouterLink } from 'vue-router'
import { 
  UsersIcon, BookOpenIcon, Cog6ToothIcon, ChartBarIcon,
  PlusIcon, ArrowRightIcon, MagnifyingGlassIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(true)
const stats = ref<import('@/types').AdminDashboardData | null>(null)
const recentRegistrations = ref<import('@/types').User[]>([])

async function fetchDashboard() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get('/admin/dashboard')
    stats.value = response.data.data
    recentRegistrations.value = response.data.data?.recent_registrations || []
  } catch (error: any) {
    toastStore.error('Failed to load dashboard', error.message)
  } finally {
    loading.value = false
  }
}

const statCards = computed(() => {
  if (!stats.value) return []
  return [
    { label: 'Total Users', value: stats.value.users.total, icon: UsersIcon, color: 'text-blue-500', bg: 'bg-blue-100/50 dark:bg-blue-900/30' },
    { label: 'Students', value: stats.value.users.students, icon: UsersIcon, color: 'text-green-500', bg: 'bg-green-100/50 dark:bg-green-900/30' },
    { label: 'Admins', value: stats.value.users.admins, icon: UsersIcon, color: 'text-purple-500', bg: 'bg-purple-100/50 dark:bg-purple-900/30' },
    { label: 'Active Users', value: stats.value.users.active, icon: UsersIcon, color: 'text-emerald-500', bg: 'bg-emerald-100/50 dark:bg-emerald-900/30' },
    { label: 'Resources', value: stats.value.resources.total, icon: BookOpenIcon, color: 'text-orange-500', bg: 'bg-orange-100/50 dark:bg-orange-900/30' },
    { label: 'Active Resources', value: stats.value.resources.active, icon: BookOpenIcon, color: 'text-teal-500', bg: 'bg-teal-100/50 dark:bg-teal-900/30' },
    { label: 'Total Tasks', value: stats.value.tasks.total, icon: ChartBarIcon, color: 'text-pink-500', bg: 'bg-pink-100/50 dark:bg-pink-900/30' },
    { label: 'Completed Tasks', value: stats.value.tasks.completed, icon: ChartBarIcon, color: 'text-green-500', bg: 'bg-green-100/50 dark:bg-green-900/30' },
  ]
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
        <div>
          <h1 class="text-3xl font-bold text-text-primary dark:text-white">Admin Dashboard</h1>
          <p class="text-text-secondary dark:text-gray-400">System overview and key metrics</p>
        </div>
        <div class="flex gap-3 ml-auto">
          <RouterLink to="/admin/analytics" class="btn-secondary">
            <MagnifyingGlassIcon class="w-5 h-5 mr-2" aria-hidden="true" />
            View Analytics
          </RouterLink>
        </div>
      </div>

      <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div v-for="i in 8" :key="i" class="glass-card-lg rounded-2xl p-6 animate-pulse">
          <div class="h-6 bg-surface-border/50 dark:bg-surface-border/30 rounded w-3/4 mb-4"></div>
          <div class="h-12 bg-surface-border/50 dark:bg-surface-border/30 rounded w-full"></div>
        </div>
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div v-for="stat in statCards" :key="stat.label" class="glass-card-lg rounded-2xl p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-text-secondary dark:text-text-muted">{{ stat.label }}</p>
              <p class="text-3xl font-bold text-text-primary dark:text-white mt-1">{{ stat.value }}</p>
            </div>
            <div :class="['p-3 rounded-xl', stat.bg]">
              <component :is="stat.icon" :class="['w-6 h-6', stat.color]" aria-hidden="true" />
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Quick Navigation -->
        <div class="glass-card-lg rounded-2xl p-6">
          <h2 class="text-lg font-semibold text-text-primary dark:text-white mb-6 flex items-center gap-2">
            <ChartBarIcon class="w-5 h-5 text-primary dark:text-primary-light" aria-hidden="true" />
            Quick Navigation
          </h2>
          <div class="grid grid-cols-2 gap-4">
            <RouterLink to="/admin/users" class="flex items-center gap-3 p-4 glass-card rounded-xl hover:shadow-glass-lg hover:-translate-y-1 transition-all duration-300 group">
              <div class="p-3 rounded-xl bg-blue-100/50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                <UsersIcon class="w-6 h-6" aria-hidden="true" />
              </div>
              <div class="flex-1 min-w-0">
                <h3 class="font-medium text-text-primary dark:text-white">Users</h3>
                <p class="text-sm text-text-secondary dark:text-text-muted">Manage user accounts</p>
              </div>
              <ArrowRightIcon class="w-5 h-5 text-text-muted group-hover:text-primary transition-colors" aria-hidden="true" />
            </RouterLink>
            <RouterLink to="/admin/resources" class="flex items-center gap-3 p-4 glass-card rounded-xl hover:shadow-glass-lg hover:-translate-y-1 transition-all duration-300 group">
              <div class="p-3 rounded-xl bg-green-100/50 dark:bg-green-900/30 text-green-600 dark:text-green-400">
                <BookOpenIcon class="w-6 h-6" aria-hidden="true" />
              </div>
              <div class="flex-1 min-w-0">
                <h3 class="font-medium text-text-primary dark:text-white">Resources</h3>
                <p class="text-sm text-text-secondary dark:text-text-muted">Manage learning resources</p>
              </div>
              <ArrowRightIcon class="w-5 h-5 text-text-muted group-hover:text-primary transition-colors" aria-hidden="true" />
            </RouterLink>
            <RouterLink to="/admin/settings" class="flex items-center gap-3 p-4 glass-card rounded-xl hover:shadow-glass-lg hover:-translate-y-1 transition-all duration-300 group">
              <div class="p-3 rounded-xl bg-purple-100/50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400">
                <Cog6ToothIcon class="w-6 h-6" aria-hidden="true" />
              </div>
              <div class="flex-1 min-w-0">
                <h3 class="font-medium text-text-primary dark:text-white">Settings</h3>
                <p class="text-sm text-text-secondary dark:text-text-muted">System configuration</p>
              </div>
              <ArrowRightIcon class="w-5 h-5 text-text-muted group-hover:text-primary transition-colors" aria-hidden="true" />
            </RouterLink>
            <RouterLink to="/admin/analytics" class="flex items-center gap-3 p-4 glass-card rounded-xl hover:shadow-glass-lg hover:-translate-y-1 transition-all duration-300 group">
              <div class="p-3 rounded-xl bg-orange-100/50 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400">
                <ChartBarIcon class="w-6 h-6" aria-hidden="true" />
              </div>
              <div class="flex-1 min-w-0">
                <h3 class="font-medium text-text-primary dark:text-white">Analytics</h3>
                <p class="text-sm text-text-secondary dark:text-text-muted">System analytics & insights</p>
              </div>
              <ArrowRightIcon class="w-5 h-5 text-text-muted group-hover:text-primary transition-colors" aria-hidden="true" />
            </RouterLink>
          </div>
        </div>

        <!-- Recent Registrations -->
        <div class="glass-card-lg rounded-2xl overflow-hidden">
          <div class="px-6 py-4 border-b border-surface-border/50 dark:border-surface-border/30 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-text-primary dark:text-white flex items-center gap-2">
              <UsersIcon class="w-5 h-5 text-primary dark:text-primary-light" aria-hidden="true" />
              Recent Registrations
            </h2>
            <RouterLink to="/admin/users" class="text-sm text-primary hover:text-primary-hover underline">View all</RouterLink>
          </div>
          <div class="divide-y divide-surface-border/30 dark:divide-surface-border/20">
            <div v-if="recentRegistrations.length === 0" class="px-6 py-12 text-center">
              <UsersIcon class="w-12 h-12 mx-auto text-text-muted mb-3" aria-hidden="true" />
              <p class="text-text-secondary dark:text-text-muted">No recent registrations</p>
            </div>
            <div v-else>
              <div v-for="user in recentRegistrations.slice(0, 5)" :key="user.id" class="px-6 py-4 hover:bg-surface/50 dark:hover:bg-surface-alt/30 transition-colors flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="avatar avatar-sm">{{ user.name.charAt(0).toUpperCase() }}</div>
                  <div>
                    <p class="font-medium text-text-primary dark:text-white truncate">{{ user.name }}</p>
                    <p class="text-sm text-text-secondary dark:text-text-muted">{{ user.email }}</p>
                  </div>
                </div>
                <div class="flex items-center gap-3 text-sm">
                  <span class="badge" :class="user.role === 'admin' ? 'badge-purple' : 'badge-blue'">{{ user.role }}</span>
                  <span class="text-text-muted">{{ formatRelativeTime(user.created_at) }}</span>
                </div>
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
    await fetchDashboard()
  })
  </script>