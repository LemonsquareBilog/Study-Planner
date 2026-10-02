<script setup lang="ts>
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { RouterLink } from 'vue-router'
import { 
  ArrowLeftIcon, MagnifyingGlassIcon, FunnelIcon,
  ArrowRightIcon, ExclamationTriangleIcon, InformationCircleIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'
import { useSearch, createSuggestionExtractor } from '@/composables/useSearch'
import SearchInput from '@/components/ui/SearchInput.vue'

const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(true)
const logs = ref<import('@/types').AuditLog[]>([])
const levelFilter = ref('')
const dateFrom = ref('')
const dateTo = ref('')

const searchComposable = useSearch({
  data: logs,
  searchFields: ['description', 'event_type'],
  debounceMs: 150,
  minQueryLength: 1,
  fuzzyThreshold: 0.35,
  getSuggestions: createSuggestionExtractor(['description', 'event_type'], 8)
})

const filteredLogs = computed(() => 
  searchComposable.results.value.filter(l => {
    const matchesLevel = !levelFilter.value || l.event_type.includes(levelFilter.value)
    const matchesDateFrom = !dateFrom.value || new Date(l.created_at) >= new Date(dateFrom.value)
    const matchesDateTo = !dateTo.value || new Date(l.created_at) <= new Date(dateTo.value)
    return matchesLevel && matchesDateFrom && matchesDateTo
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
    'user.login': 'bg-status-success/15 dark:bg-status-success/20 text-status-success dark:text-status-success-light',
    'user.logout': 'bg-surface-border/50 dark:bg-surface-border/30 text-text-secondary dark:text-text-muted',
    'user.registered': 'bg-primary-light/50 dark:bg-primary-dark/30 text-primary dark:text-primary-light',
    'user.login_failed': 'bg-status-urgent/15 dark:bg-status-urgent/20 text-status-urgent dark:text-status-urgent-light',
    'user.password_reset': 'bg-status-warning/15 dark:bg-status-warning/20 text-status-warning dark:text-status-warning-light',
    'resource.created': 'bg-status-success/15 dark:bg-status-success/20 text-status-success dark:text-status-success-light',
    'resource.updated': 'bg-primary-light/50 dark:bg-primary-dark/30 text-primary dark:text-primary-light',
    'resource.deleted': 'bg-status-urgent/15 dark:bg-status-urgent/20 text-status-urgent dark:text-status-urgent-light',
    'system.health_check_failed': 'bg-status-urgent/15 dark:bg-status-urgent/20 text-status-urgent dark:text-status-urgent-light',
  }
  return colors[level] || 'bg-surface-border/50 dark:bg-surface-border/30 text-text-secondary dark:text-text-muted'
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

function clearFilters() {
  searchComposable.clear()
  levelFilter.value = ''
  dateFrom.value = ''
  dateTo.value = ''
}

function hasActiveFilters(): boolean {
  return !!searchComposable.query || !!levelFilter.value || !!dateFrom.value || !!dateTo.value
}
</script>

<template>
  <DefaultLayout>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
          <RouterLink to="/admin" class="p-2 rounded-xl text-text-muted hover:text-text-primary dark:hover:text-white hover:bg-surface/50 dark:hover:bg-surface-alt/30 transition-colors">
            <ArrowLeftIcon class="w-6 h-6" aria-hidden="true" />
          </RouterLink>
          <div>
            <h1 class="text-3xl font-bold text-text-primary dark:text-white">System Logs</h1>
            <p class="text-text-secondary dark:text-text-muted">Monitor system activity and audit trail</p>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="glass-card-lg rounded-2xl p-4 mb-6">
        <div class="flex flex-col sm:flex-row gap-4">
          <div class="relative flex-1 min-w-[200px]">
            <SearchInput
              id="log-search"
              v-model="searchComposable.query"
              :suggestions="searchComposable.suggestions"
              :loading="searchComposable.isSearching"
              placeholder="Search logs by description or event type..."
              aria-label="Search logs"
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
            v-if="hasActiveFilters()"
            @click="clearFilters"
            class="btn-secondary whitespace-nowrap"
          >
            <XMarkIcon class="w-5 h-5 mr-2" aria-hidden="true" />
            Clear Filters
          </button>
        </div>
      </div>

      <!-- Logs Table -->
      <div v-if="loading" class="animate-pulse space-y-4">
        <div v-for="i in 5" :key="i" class="glass-card-lg rounded-2xl p-6">
          <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-surface-border/50 dark:bg-surface-border/30"></div>
            <div class="flex-1">
              <div class="h-5 bg-surface-border/50 dark:bg-surface-border/30 rounded w-3/4 mb-2"></div>
              <div class="h-4 bg-surface-border/50 dark:bg-surface-border/30 rounded w-1/2"></div>
            </div>
          </div>
        </div>
      </div>

      <div v-else-if="filteredLogs.length === 0" class="text-center py-16">
        <ExclamationTriangleIcon class="w-16 h-16 mx-auto text-text-muted mb-4" aria-hidden="true" />
        <h3 class="text-xl font-semibold text-text-primary dark:text-white mb-2">No logs found</h3>
        <p class="text-text-secondary dark:text-text-muted">
          {{ hasActiveFilters() ? 'Try adjusting your filters' : 'No logs recorded yet' }}
        </p>
      </div>

      <div v-else class="glass-card-lg rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="bg-surface/50 dark:bg-surface-alt/30">
                <th class="px-6 py-3 text-left text-xs font-semibold text-text-secondary dark:text-text-muted uppercase tracking-wider">Time</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-text-secondary dark:text-text-muted uppercase tracking-wider">Event</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-text-secondary dark:text-text-muted uppercase tracking-wider">User</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-text-secondary dark:text-text-muted uppercase tracking-wider">Description</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-text-secondary dark:text-text-muted uppercase tracking-wider">IP</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-surface-border/30 dark:divide-surface-border/20">
              <tr v-for="log in filteredLogs" :key="log.id" class="hover:bg-surface/50 dark:hover:bg-surface-alt/30 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary dark:text-text-muted">
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
                    <span class="text-sm font-medium text-text-primary dark:text-white">{{ log.user.name }}</span>
                    <span class="text-xs text-text-secondary dark:text-text-muted">{{ log.user.email }}</span>
                  </div>
                  <span v-else class="text-sm text-text-secondary dark:text-text-muted">System</span>
                </td>
                <td class="px-6 py-4">
                  <p class="text-sm text-text-primary dark:text-white max-w-md truncate">{{ log.description }}</p>
                  <p v-if="log.payload && Object.keys(log.payload).length > 0" class="text-xs text-text-muted font-mono mt-1">
                    {{ JSON.stringify(log.payload).substring(0, 100) }}{{ JSON.stringify(log.payload).length > 100 ? '...' : '' }}
                  </p>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary dark:text-text-muted">
                  {{ log.ip || 'N/A' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
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