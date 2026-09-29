<script setup lang="ts>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { RouterLink } from 'vue-router'
import { 
  ArrowLeftIcon, UserCircleIcon, ShieldCheckIcon, 
  CalendarIcon, MailIcon, LockClosedIcon, BellIcon,
  PencilIcon, TrashIcon, CheckCircleIcon, ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(true)
const user = ref<import('@/types').User | null>(null)

async function fetchUser() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get(`/admin/users/${route.params.id}`)
    user.value = response.data.data
  } catch (error: any) {
    toastStore.error('Failed to load user', error.message)
    router.push('/admin/users')
  } finally {
    loading.value = false
  }
}

async function toggleUserStatus() {
  if (!user.value) return
  try {
    const api = (await import('@/utils/api')).default
    await api.patch(`/admin/users/${user.value.id}`, { is_active: !user.value.is_active })
    toastStore.success(`User ${user.value.is_active ? 'deactivated' : 'activated'}!`)
    await fetchUser()
  } catch (error: any) {
    toastStore.error('Failed to update user status', error.message)
  }
}

async function deleteUser() {
  if (!user.value) return
  if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) return
  
  try {
    const api = (await import('@/utils/api')).default
    await api.delete(`/admin/users/${user.value.id}`)
    toastStore.success('User deleted!')
    router.push('/admin/users')
  } catch (error: any) {
    toastStore.error('Failed to delete user', error.message)
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
</script>

<template>
  <DefaultLayout>
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-8">
      <div v-if="loading" class="animate-pulse space-y-6">
        <div class="h-8 bg-gray-200/50 dark:bg-gray-700/50 rounded w-1/4"></div>
        <div class="glass-card-lg rounded-2xl p-6">
          <div class="h-6 bg-gray-200/50 dark:bg-gray-700/50 rounded w-3/4 mb-4"></div>
          <div class="h-4 bg-gray-200/50 dark:bg-gray-700/50 rounded w-1/2"></div>
        </div>
      </div>

      <div v-else class="space-y-8">
        <div class="flex items-center gap-4 mb-8">
          <RouterLink to="/admin/users" class="p-2 rounded-xl text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 hover:bg-pink-50/50 dark:hover:bg-pink-900/20 transition-colors">
            <ArrowLeftIcon class="w-6 h-6" aria-hidden="true" />
          </RouterLink>
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ user?.name }}</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ user?.email }}</p>
          </div>
          <div class="ml-auto flex items-center gap-3">
            <button @click="toggleUserStatus" class="btn-secondary">
              {{ user?.is_active ? 'Deactivate' : 'Activate' }}
            </button>
            <button @click="deleteUser" class="btn-danger">
              <TrashIcon class="w-5 h-5 mr-2" aria-hidden="true" />
              Delete
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- User Info -->
          <div class="lg:col-span-2 space-y-6">
            <div class="glass-card-lg rounded-2xl p-6">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <UserCircleIcon class="w-5 h-5 text-pink-600 dark:text-pink-400" aria-hidden="true" />
                Profile Information
              </h2>
              <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</label>
                    <p class="text-gray-900 dark:text-white mt-1">{{ user?.name }}</p>
                  </div>
                  <div>
                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</label>
                    <p class="text-gray-900 dark:text-white mt-1">{{ user?.email }}</p>
                  </div>
                  <div>
                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Role</label>
                    <p class="mt-1"><span class="badge" :class="user?.role === 'admin' ? 'badge-purple' : 'badge-blue'">{{ user?.role }}</span></p>
                  </div>
                  <div>
                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</label>
                    <p class="mt-1"><span :class="['badge', user?.is_active ? 'badge-green' : 'badge-gray']">{{ user?.is_active ? 'Active' : 'Inactive' }}</span></p>
                  </div>
                  <div>
                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Course</label>
                    <p class="text-gray-900 dark:text-white mt-1">{{ user?.course || 'Not specified' }}</p>
                  </div>
                  <div>
                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Year Level</label>
                    <p class="text-gray-900 dark:text-white mt-1">{{ user?.year_level || 'Not specified' }}</p>
                  </div>
                </div>
              </div>
            </div>

            <div class="glass-card-lg rounded-2xl p-6">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <CalendarIcon class="w-5 h-5 text-pink-600 dark:text-pink-400" aria-hidden="true" />
                Account Activity
              </h2>
              <div class="space-y-4">
                <div class="flex items-center gap-3 p-4 glass-card rounded-xl">
                  <div class="w-10 h-10 rounded-full bg-pink-100/80 dark:bg-pink-900/30 flex items-center justify-center text-pink-600 dark:text-pink-400">
                    <UserCircleIcon class="w-5 h-5" aria-hidden="true" />
                  </div>
                  <div>
                    <p class="font-medium text-gray-900 dark:text-white">Account Created</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatRelativeTime(user?.created_at) }}</p>
                  </div>
                </div>
                <div class="flex items-center gap-3 p-4 glass-card rounded-xl">
                  <div class="w-10 h-10 rounded-full bg-pink-100/80 dark:bg-pink-900/30 flex items-center justify-center text-pink-600 dark:text-pink-400">
                    <MailIcon class="w-5 h-5" aria-hidden="true" />
                  </div>
                  <div>
                    <p class="font-medium text-gray-900 dark:text-white">Email Verified</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ user?.email_verified_at ? formatRelativeTime(user.email_verified_at) : 'Not verified' }}</p>
                  </div>
                </div>
                <div class="flex items-center gap-3 p-4 glass-card rounded-xl">
                  <div class="w-10 h-10 rounded-full bg-purple-100/80 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 dark:text-purple-400">
                    <ShieldCheckIcon class="w-5 h-5" aria-hidden="true" />
                  </div>
                  <div>
                    <p class="font-medium text-gray-900 dark:text-white">Two-Factor Auth</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ user?.two_factor_confirmed_at ? 'Enabled' : 'Not enabled' }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="glass-card-lg rounded-2xl p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Quick Actions</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <button @click="toggleUserStatus" class="btn-secondary w-full">
                <span class="flex items-center justify-center gap-2">
                  <UserCircleIcon class="w-5 h-5" aria-hidden="true" />
                  {{ user?.is_active ? 'Deactivate User' : 'Activate User' }}
                </span>
              </button>
              <button @click="deleteUser" class="btn-danger w-full">
                <span class="flex items-center justify-center gap-2">
                  <TrashIcon class="w-5 h-5" aria-hidden="true" />
                  Delete User
                </span>
              </button>
            </div>
          </div>
        </div>

        <!-- Side Panel: Quick Stats -->
        <div class="space-y-6">
          <div class="glass-card-lg rounded-2xl p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Quick Stats</h2>
            <div class="grid grid-cols-2 gap-4">
              <StatCard label="Subjects" value="{{ userStats?.subjects || 0 }}" icon="BookOpenIcon" color="text-blue-500" bg="bg-blue-100/50 dark:bg-blue-900/30" />
              <StatCard label="Tasks" value="{{ userStats?.tasks || 0 }}" icon="CheckCircleIcon" color="text-green-500" bg="bg-green-100/50 dark:bg-green-900/30" />
              <StatCard label="Quizzes Taken" value="{{ userStats?.quizAttempts || 0 }}" icon="CheckCircleIcon" color: "text-purple-500" bg: "bg-purple-100/50 dark:bg-purple-900/30" />
            </div>
          </div>
        </div>
      </div>
    </DefaultLayout>
  </template>

  <script>
  import { onMounted } from 'vue'

  onMounted(async () => {
    await fetchUser()
  })
  </script>