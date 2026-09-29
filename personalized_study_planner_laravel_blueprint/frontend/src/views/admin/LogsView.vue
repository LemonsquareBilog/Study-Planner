<script setup lang="ts>
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { RouterLink } from 'vue-router'
import { 
  ArrowLeftIcon, MagnifyingGlassIcon, FunnelIcon,
  ArrowRightIcon, ExclamationTriangleIcon, InformationCircleIcon
} from '@heroicons/vue/24/outline'

const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(true)
const logs = ref<import('@/types').AuditLog[]>([])
const searchQuery = ref('')
const levelFilter = ref('')
const dateFrom = ref('')
const dateTo = ref('')

const filteredLogs = computed(() => 
  logs.value.filter(l => {
    const matchesSearch = l.description?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      l.event_type.toLowerCase().includes(searchQuery.value.toLowerCase())
    const matchesLevel = !levelFilter.value || l.event_type.includes(levelFilter.value)
    const matchesDateFrom = !dateFrom.value || new Date(l.created_at) >= new Date(dateFrom.value)
    const matchesDateTo = !dateTo.value || new Date(l.created_at) <= new Date(dateTo.value)
    return matchesSearch && matchesLevel && matchesDateFrom && matchesDateTo
  })
)

const levels = computed(() => 
  [...new Set(logs.value.map(l => l.event_type))].sort()
)

async function fetchLogs() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get('/admin/logs', { params: { per_page: 100 } })
    logs.value = response.data.data || []
  } catch (error: any) {
    toastStore.error('Failed to load logs', error.message)
  } finally {
    loading.value = false
  }
}

function getLevelColor(level: string): string {
  const colors: Record<string, string> = {
    'user.login': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    'user.logout': 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
    'user.registered': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    'user.login_failed': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    'user.password_reset': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    'resource.created': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    'resource.updated': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    'resource.deleted': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    'system.health_check_failed': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
  }
  return colors[level] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
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
          <RouterLink to="/admin" class="p-2 rounded-xl text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 hover:bg-pink-50/50 dark:hover:bg-pink-900/20 transition-colors">
            <ArrowLeftIcon class="w-6 h-6" aria-hidden="true" />
          </RouterLink>
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">System Logs</h1>
            <p class="text-gray-600 dark:text-gray-400">Monitor system activity and audit trail</p>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="glass-card-lg rounded-2xl p-4 mb-6">
        <div class="flex flex-col sm:flex-row gap-4">
          <div class="relative flex-1 min-w-[200px]">
            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" aria-hidden="true" />
            <input
              id="search"
              type="search"
              v-model="searchQuery"
              placeholder="Search logs..."
              class="input-field pl-10 pr-4 w-full"
            />
          </div>
          <select v-model="levelFilter" class="select-field input-field w-full sm:w-40">
            <option value="">All Levels</option>
            <option v-for="level in levels" :key="level" :value="level">{{ level }}</option>
          </select>
          <input
            type="date"
            v-model="dateFrom"
            class="input-field w-full sm:w-40"
            placeholder="From"
          />
          <input
            type="date"
            v-model="dateTo"
            class="input-field w-full sm:w-40"
            placeholder="To"
          />
          <button 
            v-if="searchQuery || levelFilter || dateFrom || dateTo"
            @click="searchQuery = ''; levelFilter = ''; dateFrom = ''; dateTo = ''"
            class="btn-secondary whitespace-nowrap"
          >
            Clear Filters
          </button>
        </div>
      </div>

      <!-- Logs Table -->
      <div v-if="loading" class="animate-pulse space-y-4">
        <div v-for="i in 5" :key="i" class="glass-card-lg rounded-2xl p-6">
          <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-gray-200/50 dark:bg-gray-700/50"></div>
            <div class="flex-1">
              <div class="h-5 bg-gray-200/50 dark:bg-gray-700/50 rounded w-3/4 mb-2"></div>
              <div class="h-4 bg-gray-200/50 dark:bg-gray-700/50 rounded w-1/2"></div>
            </div>
          </div>
        </div>
      </div>

      <div v-else-if="filteredLogs.length === 0" class="text-center py-16">
        <ExclamationTriangleIcon class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" aria-hidden="true" />
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No logs found</h3>
        <p class="text-gray-600 dark:text-gray-400">{{ searchQuery || levelFilter || dateFrom || dateTo ? 'Try adjusting your filters' : 'No logs recorded yet' }}</p>
      </div>

      <div v-else class="glass-card-lg rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="bg-pink-50/50 dark:bg-pink-900/30">
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Time</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Event</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">User</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Description</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">IP</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-pink-100/50 dark:divide-pink-800/50">
              <tr v-for="log in filteredLogs" :key="log.id" class="hover:bg-pink-50/30 dark:hover:bg-pink-900/10 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                  {{ formatRelativeTime(log.created_at) }}
                </td>
                <td class="px-6 py-4">
                  <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', getLevelColor(log.event_type)]">
                    {{ log.event_type }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div v-if="log.user" class="flex items-center gap-2">
                    <div class="avatar avatar-sm">{{ log.user.name.charAt(0).toUpperCase() }}</div>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ log.user.name }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ log.user.email }}</span>
                  </div>
                  <span v-else class="text-sm text-gray-500 dark:text-gray-400">System</span>
                </td>
                <td class="px-6 py-4">
                  <p class="text-sm text-gray-900 dark:text-white max-w-md truncate">{{ log.description }}</p>
                  <p v-if="log.payload && Object.keys(log.payload).length > 0" class="text-xs text-gray-500 dark:text-gray-400 font-mono mt-1">
                    {{ JSON.stringify(log.payload).substring(0, 100) }}{{ JSON.stringify(log.payload).length > 100 ? '...' : '' }}
                  </p>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                  {{ log.ip || 'N/A' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </DefaultLayout>
  </template>

  <script>
  import { onMounted } from 'vue'

  onMounted(async () => {
    await fetchLogs()
  })
  </script>