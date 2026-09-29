<script setup lang="ts>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { RouterLink } from 'vue-router'
import { 
  ArrowLeftIcon, BellIcon, CheckCircleIcon, 
  ClockIcon, EyeIcon, EyeSlashIcon, TrashIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(true)
const notifications = ref<import('@/types').Notification[]>([])
const showUnreadOnly = ref(false)
const unreadCount = ref(0)

const filteredNotifications = computed(() => 
  showUnreadOnly.value 
    ? notifications.value.filter(n => !n.read_at)
    : notifications.value
)

async function fetchNotifications() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get('/student/notifications', { params: { per_page: 50 } })
    notifications.value = response.data.data || []
    unreadCount.value = response.data.unread_count || 0
  } catch (error: any) {
    toastStore.error('Failed to load notifications', error.message)
  } finally {
    loading.value = false
  }
}

async function markRead(notification: import('@/types').Notification) {
  try {
    const api = (await import('@/utils/api')).default
    await api.post(`/student/notifications/${notification.id}/read`)
    notification.read_at = new Date().toISOString()
    unreadCount.value = Math.max(0, unreadCount.value - 1)
  } catch (error: any) {
    toastStore.error('Failed to mark as read', error.message)
  }
}

async function markAllRead() {
  try {
    const api = (await import('@/utils/api')).default
    await api.post('/student/notifications/read-all')
    notifications.value = notifications.value.map(n => ({ ...n, read_at: new Date().toISOString() }))
    unreadCount.value = 0
    toastStore.success('All notifications marked as read')
  } catch (error: any) {
    toastStore.error('Failed to mark all as read', error.message)
  }
}

async function deleteNotification(notification: import('@/types').Notification) {
  if (!confirm('Are you sure you want to delete this notification?')) return
  
  try {
    const api = (await import('@/utils/api')).default
    await api.delete(`/student/notifications/${notification.id}`)
    toastStore.success('Notification deleted!')
    notifications.value = notifications.value.filter(n => n.id !== notification.id)
    if (!notification.read_at) unreadCount.value = Math.max(0, unreadCount.value - 1)
  } catch (error: any) {
    toastStore.error('Failed to delete notification', error.message)
  }
}

function getTypeIcon(type: string) {
  const icons: Record<string, any> = {
    study_session_reminder: 'ClockIcon',
    deadline_reminder: 'ExclamationTriangleIcon',
    quiz_completed: 'CheckCircleIcon',
    study_plan_generated: 'LightBulbIcon',
    task_deadline_approaching: 'ClockIcon',
    system_alert: 'ExclamationTriangleIcon',
    admin_user_registered: 'UserPlusIcon',
  }
  return icons[type] || 'BellIcon'
}

function getTypeColor(type: string): string {
  const colors: Record<string, string> = {
    study_session_reminder: 'text-blue-600 dark:text-blue-400 bg-blue-100/50 dark:bg-blue-900/30',
    deadline_reminder: 'text-orange-600 dark:text-orange-400 bg-orange-100/50 dark:bg-orange-900/30',
    quiz_completed: 'text-green-600 dark:text-green-400 bg-green-100/50 dark:bg-green-900/30',
    study_plan_generated: 'text-purple-600 dark:text-purple-400 bg-purple-100/50 dark:bg-purple-900/30',
    task_deadline_approaching: 'text-red-600 dark:text-red-400 bg-red-100/50 dark:bg-red-900/30',
    system_alert: 'text-red-600 dark:text-red-400 bg-red-100/50 dark:bg-red-900/30',
    admin_user_registered: 'text-purple-600 dark:text-purple-400 bg-purple-100/50 dark:bg-purple-900/30',
  }
  return colors[type] || 'text-gray-600 dark:text-gray-400 bg-gray-100/50 dark:bg-gray-900/30'
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
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
          <RouterLink to="/dashboard" class="p-2 rounded-xl text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 hover:bg-pink-50/50 dark:hover:bg-pink-900/20 transition-colors">
            <ArrowLeftIcon class="w-6 h-6" aria-hidden="true" />
          </RouterLink>
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Notifications</h1>
            <p class="text-gray-600 dark:text-gray-400">Stay updated with your study reminders and alerts</p>
          </div>
        </div>
        
        <div class="flex items-center gap-3 ml-auto">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="showUnreadOnly" class="checkbox" />
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Unread only</span>
          </label>
          <button v-if="unreadCount > 0" @click="markAllRead" class="btn-secondary text-sm">
            Mark all read ({{ unreadCount }})
          </button>
        </div>
      </div>

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

      <div v-else-if="notifications.length === 0" class="text-center py-16">
        <BellIcon class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" aria-hidden="true" />
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No notifications yet</h3>
        <p class="text-gray-600 dark:text-gray-400">You're all caught up!</p>
      </div>

      <div v-else class="space-y-4">
        <div v-for="notification in notifications" :key="notification.id" 
          class="glass-card-lg rounded-xl p-4 flex items-start gap-4 transition-colors"
          :class="!notification.read_at ? 'bg-pink-50/50 dark:bg-pink-900/20 border-l-4 border-pink-500' : ''"
        >
          <div class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center" :class="getTypeColor(notification.type)">
            <component :is="getTypeIcon(notification.type)" class="w-5 h-5" aria-hidden="true" />
          </div>
          
          <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-4 mb-1">
              <div>
                <h3 class="font-medium text-gray-900 dark:text-white">{{ notification.title }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">{{ notification.message }}</p>
              </div>
              <div class="flex items-center gap-2">
                <span v-if="!notification.read_at" class="badge badge-pink">Unread</span>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ formatRelativeTime(notification.created_at) }}</span>
              </div>
            </div>
            
            <div class="flex items-center gap-2 flex-shrink-0">
              <button 
                v-if="!notification.read_at"
                @click="markRead(notification)"
                class="p-2 rounded-lg text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 hover:bg-pink-50/50 dark:hover:bg-pink-900/20 transition-colors"
                aria-label="Mark as read"
              >
                <EyeIcon class="w-5 h-5" aria-hidden="true" />
              </button>
              <button 
                v-else
                @click="markRead(notification)"
                class="p-2 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100/50 dark:hover:bg-gray-800/50 transition-colors"
                aria-label="Mark as unread"
              >
                <EyeSlashIcon class="w-5 h-5" aria-hidden="true" />
              </button>
              <button 
                @click="deleteNotification(notification)"
                class="p-2 rounded-lg text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50/50 dark:hover:bg-red-900/20 transition-colors"
                aria-label="Delete notification"
              >
                <TrashIcon class="w-5 h-5" aria-hidden="true" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </DefaultLayout>
  </template>

  <script>
  import { onMounted } from 'vue'

  onMounted(async () => {
    await fetchNotifications()
    // Poll for new notifications every 30 seconds
    const interval = setInterval(fetchNotifications, 30000)
    return () => clearInterval(interval)
  })
  </script>