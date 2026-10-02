<script setup lang="ts>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { RouterLink } from 'vue-router'
import { 
  UsersIcon, ArrowLeftIcon, PencilIcon, 
  TrashIcon, CheckCircleIcon, ArrowRightIcon, ShieldCheckIcon
} from '@heroicons/vue/24/outline'
import { useSearch, createSuggestionExtractor } from '@/composables/useSearch'
import SearchInput from '@/components/ui/SearchInput.vue'

const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(true)
const users = ref<import('@/types').User[]>([])
const roleFilter = ref('')
const statusFilter = ref('')

const searchComposable = useSearch({
  data: users,
  searchFields: ['name', 'email'],
  debounceMs: 150,
  minQueryLength: 1,
  fuzzyThreshold: 0.35,
  getSuggestions: createSuggestionExtractor(['name', 'email'], 8)
})

const filteredUsers = computed(() => 
  searchComposable.results.value.filter(u => {
    const matchesRole = !roleFilter.value || u.role === roleFilter.value
    const matchesStatus = !statusFilter.value || (statusFilter.value === 'active' ? u.is_active : !u.is_active)
    return matchesRole && matchesStatus
  })
)

async function fetchUsers() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get('/admin/users')
    users.value = response.data.data || []
  } catch (error: any) {
    toastStore.error('Failed to load users', error.message)
  } finally {
    loading.value = false
  }
}

async function deleteUser(user: import('@/types').User) {
  if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) return
  
  try {
    const api = (await import('@/utils/api')).default
    await api.delete(`/admin/users/${user.id}`)
    toastStore.success('User deleted!')
    await fetchUsers()
  } catch (error: any) {
    toastStore.error('Failed to delete user', error.message)
  }
}

async function toggleUserStatus(user: import('@/types').User) {
  try {
    const api = (await import('@/utils/api')).default
    await api.patch(`/admin/users/${user.id}`, { is_active: !user.is_active })
    toastStore.success(`User ${user.is_active ? 'deactivated' : 'activated'}!`)
    await fetchUsers()
  } catch (error: any) {
    toastStore.error('Failed to update user status', error.message)
  }
}

async function toggleUserRole(user: import('@/types').User) {
  try {
    const api = (await import('@/utils/api')).default
    const newRole = user.role === 'admin' ? 'student' : 'admin'
    await api.patch(`/admin/users/${user.id}`, { role: newRole })
    toastStore.success(`User role changed to ${newRole}!`)
    await fetchUsers()
  } catch (error: any) {
    toastStore.error('Failed to change user role', error.message)
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
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
          <RouterLink to="/admin" class="p-2 rounded-xl text-text-muted hover:text-text-primary dark:hover:text-white hover:bg-surface/50 dark:hover:bg-surface-alt/30 transition-colors">
            <ArrowLeftIcon class="w-6 h-6" aria-hidden="true" />
          </RouterLink>
          <div>
            <h1 class="text-3xl font-bold text-text-primary dark:text-white">Users</h1>
            <p class="text-text-secondary dark:text-text-muted">Manage user accounts</p>
          </div>
        </div>
        
        <div class="flex gap-3 ml-auto">
          <select v-model="roleFilter" class="select-field input-field w-full sm:w-40">
            <option value="">All Roles</option>
            <option value="student">Students</option>
            <option value="admin">Admins</option>
          </select>
          <select v-model="statusFilter" class="select-field input-field w-full sm:w-40">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>

      <!-- Search -->
      <div class="mb-6">
        <label for="user-search" class="sr-only">Search users</label>
        <SearchInput
          id="user-search"
          v-model="searchComposable.query"
          :suggestions="searchComposable.suggestions"
          :loading="searchComposable.isSearching"
          placeholder="Search users by name or email..."
          aria-label="Search users"
        />
        <p v-if="searchComposable.query && !searchComposable.hasResults" class="mt-2 text-sm text-text-muted">
          No users found matching "{{ searchComposable.query }}"
        </p>
      </div>

      <!-- Users Table -->
      <div v-if="loading" class="animate-pulse space-y-4">
        <div v-for="i in 5" :key="i" class="glass-card-lg rounded-2xl p-6">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-surface-border/50 dark:bg-surface-border/30"></div>
            <div class="flex-1">
              <div class="h-5 bg-surface-border/50 dark:bg-surface-border/30 rounded w-3/4 mb-2"></div>
              <div class="h-4 bg-surface-border/50 dark:bg-surface-border/30 rounded w-1/2"></div>
            </div>
          </div>
        </div>
      </div>

      <div v-else-if="filteredUsers.length === 0" class="text-center py-16">
        <UsersIcon class="w-16 h-16 mx-auto text-text-muted mb-4" aria-hidden="true" />
        <h3 class="text-xl font-semibold text-text-primary dark:text-white mb-2">No users found</h3>
        <p class="text-text-secondary dark:text-text-muted mb-6">
          {{ searchComposable.query || roleFilter || statusFilter ? 'Try adjusting your filters' : 'No users registered yet' }}
        </p>
      </div>

      <div v-else class="glass-card-lg rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="bg-surface/50 dark:bg-surface-alt/30">
                <th class="px-6 py-3 text-left text-xs font-semibold text-text-secondary dark:text-text-muted uppercase tracking-wider">User</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-text-secondary dark:text-text-muted uppercase tracking-wider">Role</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-text-secondary dark:text-text-muted uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-text-secondary dark:text-text-muted uppercase tracking-wider">Registered</th>
                <th class="px-6 py-3 text-right text-xs font-semibold text-text-secondary dark:text-text-muted uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-surface-border/30 dark:divide-surface-border/20">
              <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-surface/50 dark:hover:bg-surface-alt/30 transition-colors">
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="avatar avatar-md">{{ user.name.charAt(0).toUpperCase() }}</div>
                    <div>
                      <p class="font-medium text-text-primary dark:text-white truncate">{{ user.name }}</p>
                      <p class="text-sm text-text-secondary dark:text-text-muted truncate">{{ user.email }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span class="badge" :class="user.role === 'admin' ? 'badge-purple' : 'badge-blue'">{{ user.role }}</span>
                </td>
                <td class="px-6 py-4">
                  <button 
                    @click="toggleUserStatus(user)"
                    :class="['badge', user.is_active ? 'badge-success' : 'badge-gray']"
                    class="cursor-pointer"
                  >
                    {{ user.is_active ? 'Active' : 'Inactive' }}
                  </button>
                </td>
                <td class="px-6 py-4 text-sm text-text-secondary dark:text-text-muted">
                  {{ formatRelativeTime(user.created_at) }}
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <RouterLink :to="`/admin/users/${user.id}`" class="p-2 rounded-lg text-text-muted hover:text-text-primary dark:hover:text-white hover:bg-surface/50 dark:hover:bg-surface-alt/30 transition-colors" title="View Details">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542 7-4.477 0-8.268-2.943-9.542 7-4.477 0-8.268-2.943-9.542 7-4.477 0-8.268-2.943-9.542 7z"/></svg>
                    </RouterLink>
                    <button @click="toggleUserRole(user)" class="p-2 rounded-lg text-text-muted hover:text-text-primary dark:hover:text-white hover:bg-surface/50 dark:hover:bg-surface-alt/30 transition-colors" title="Toggle Role">
                      <ShieldCheckIcon class="w-5 h-5" aria-hidden="true" />
                    </button>
                    <button @click="toggleUserStatus(user)" class="p-2 rounded-lg text-text-muted hover:text-status-success dark:hover:text-status-success-light hover:bg-status-success/10 dark:hover:bg-status-success/20 transition-colors" :title="user.is_active ? 'Deactivate' : 'Activate'">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" :class="user.is_active ? 'text-status-success' : 'text-text-muted'" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </button>
                    <button @click="deleteUser(user)" class="p-2 rounded-lg text-text-muted hover:text-status-urgent dark:hover:text-status-urgent-light hover:bg-status-urgent/10 dark:hover:bg-status-urgent/20 transition-colors" title="Delete">
                      <TrashIcon class="w-5 h-5" aria-hidden="true" />
                    </button>
                  </div>
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
  await fetchUsers()
})
</script>