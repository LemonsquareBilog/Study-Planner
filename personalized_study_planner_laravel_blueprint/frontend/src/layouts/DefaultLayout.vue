<template>
  <div class="min-h-screen flex flex-col">
    <!-- Header -->
    <header class="fixed top-0 left-0 right-0 z-50 glass-card border-b border-pink-200/50 dark:border-pink-800/50">
      <nav class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8" aria-label="Main navigation">
        <div class="flex h-16 items-center justify-between">
          <!-- Logo & Desktop Nav -->
          <div class="flex flex-1 items-center justify-between">
            <div class="flex items-center gap-8">
              <!-- Logo -->
              <RouterLink to="/" class="flex items-center gap-2" aria-label="Study Planner Home">
                <svg class="w-8 h-8 text-pink-600" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4v10.55c-.59.34-1.27.55-2 .55-2.21 0-4-1.79-4-4S9.79 3 12 3z"/>
                </svg>
                <span class="text-xl font-bold text-gray-900 dark:text-white hidden sm:block">StudyPlanner</span>
              </RouterLink>

              <!-- Desktop Navigation -->
              <div class="hidden md:flex items-center gap-1">
                <template v-for="item in filteredNavigation" :key="item.name">
                  <RouterLink
                    :to="item.href"
                    class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 rounded-xl
                           hover:text-pink-600 dark:hover:text-pink-400 hover:bg-pink-50/50 dark:hover:bg-pink-900/20
                           transition-all duration-200"
                    :aria-current="route.path === item.href ? 'page' : undefined"
                  >
                    <component :is="item.icon" class="w-5 h-5" aria-hidden="true" />
                    <span>{{ item.name }}</span>
                  </RouterLink>
                </template>
              </div>
            </div>

            <!-- Search (Desktop) -->
            <div class="hidden lg:block w-72">
              <div class="relative">
                <label for="global-search" class="sr-only">Search subjects, tasks, quizzes</label>
                <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" aria-hidden="true" />
                <input
                  id="global-search"
                  type="search"
                  v-model="searchQuery"
                  @keydown.enter="handleSearch"
                  placeholder="Search subjects, tasks, quizzes..."
                  class="input-field pl-10 pr-4 w-full"
                  aria-label="Search subjects, tasks, quizzes"
                />
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-3">
              <!-- Theme Toggle -->
              <button
                @click="toggleTheme"
                :aria-label="themeStore.isDark ? 'Switch to light mode' : 'Switch to dark mode'"
                class="p-2 rounded-xl text-gray-600 dark:text-gray-300 hover:bg-pink-100/50 dark:hover:bg-pink-900/20 transition-colors"
              >
                <SunIcon v-if="themeStore.isDark" class="w-5 h-5" aria-hidden="true" />
                <MoonIcon v-else class="w-5 h-5" aria-hidden="true" />
              </button>

              <!-- Notifications Bell -->
              <div class="relative">
                <button
                  @click="isNotificationsOpen = !isNotificationsOpen"
                  :aria-label="unreadCount > 0 ? `${unreadCount} unread notifications` : 'Notifications'"
                  :aria-expanded="isNotificationsOpen"
                  class="relative p-2 rounded-xl text-gray-600 dark:text-gray-300 hover:bg-pink-100/50 dark:hover:bg-pink-900/20 transition-colors"
                >
                  <BellIcon class="w-5 h-5" aria-hidden="true" />
                  <span v-if="unreadCount > 0" class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center text-xs font-semibold text-white bg-pink-500 rounded-full animate-pulse">
                    {{ unreadCount > 9 ? '9+' : unreadCount }}
                  </span>
                </button>

                <!-- Notifications Dropdown -->
                <Transition name="slide-down">
                  <div v-show="isNotificationsOpen" class="notifications-dropdown absolute right-0 mt-2 w-80 glass-card-lg rounded-xl shadow-lg ring-1 ring-pink-200/50 dark:ring-pink-800/50 py-2 animate-scale-in z-50">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-pink-200/50 dark:border-pink-800/50">
                      <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Notifications</h3>
                      <button
                        v-if="unreadCount > 0"
                        @click="markAllRead"
                        class="text-xs text-pink-600 dark:text-pink-400 hover:underline"
                      >
                        Mark all read
                      </button>
                    </div>
                    
                    <div class="max-h-96 overflow-y-auto">
                      <div v-if="recentNotifications.length === 0" class="px-4 py-8 text-center">
                        <BellIcon class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-2" aria-hidden="true" />
                        <p class="text-sm text-gray-500 dark:text-gray-400">No notifications yet</p>
                      </div>
                      
                      <div v-else class="divide-y divide-pink-100/50 dark:divide-pink-800/50">
                        <div 
                          v-for="notification in recentNotifications" 
                          :key="notification.id"
                          @click="markRead(notification.id)"
                          :class="['px-4 py-3 cursor-pointer transition-colors', !notification.read_at ? 'bg-pink-50/50 dark:bg-pink-900/20' : '']"
                        >
                          <div class="flex items-start gap-3">
                            <div class="flex-1 min-w-0">
                              <p class="text-sm font-medium text-gray-900 dark:text-white">{{ notification.title }}</p>
                              <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5 line-clamp-2">{{ notification.message }}</p>
                              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ formatRelativeTime(notification.created_at) }}</p>
                            </div>
                            <div v-if="!notification.read_at" class="flex-shrink-0 w-2 h-2 mt-1.5 bg-pink-500 rounded-full" aria-hidden="true" />
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="px-4 py-3 border-t border-pink-200/50 dark:border-pink-800/50">
                      <RouterLink to="/notifications" class="text-sm font-medium text-pink-600 dark:text-pink-400 hover:underline block text-center">
                        View all notifications
                      </RouterLink>
                    </div>
                  </div>
                </Transition>
              </div>

              <!-- User Menu -->
              <div class="relative">
                <button
                  @click="isUserMenuOpen = !isUserMenuOpen"
                  :aria-expanded="isUserMenuOpen"
                  :aria-label="User menu"
                  class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-pink-100/50 dark:hover:bg-pink-900/20 transition-colors"
                >
                  <div class="avatar avatar-md">{{ userInitials }}</div>
                  <span class="hidden sm:block text-sm font-medium text-gray-700 dark:text-gray-300">{{ authStore.user?.name }}</span>
                </button>

                <Transition name="slide-down">
                  <div v-show="isUserMenuOpen" class="absolute right-0 mt-2 w-56 glass-card-lg rounded-xl shadow-lg ring-1 ring-pink-200/50 dark:ring-pink-800/50 py-1 animate-scale-in z-50">
                    <div class="px-4 py-3 border-b border-pink-200/50 dark:border-pink-800/50">
                      <p class="text-sm font-medium text-gray-900 dark:text-white">{{ authStore.user?.name }}</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ authStore.user?.email }}</p>
                      <span class="badge badge-pink mt-1">{{ authStore.user?.role }}</span>
                    </div>
                    
                    <div class="py-1">
                      <RouterLink to="/settings/profile" class="dropdown-item" @click="isUserMenuOpen = false">
                        <UserCircleIcon class="w-5 h-5" aria-hidden="true" />
                        Profile
                      </RouterLink>
                      <RouterLink to="/settings/preferences" class="dropdown-item" @click="isUserMenuOpen = false">
                        <LightBulbIcon class="w-5 h-5" aria-hidden="true" />
                        Study Preferences
                      </RouterLink>
                      <RouterLink to="/settings/password" class="dropdown-item" @click="isUserMenuOpen = false">
                        <Cog6ToothIcon class="w-5 h-5" aria-hidden="true" />
                        Change Password
                      </RouterLink>
                      <RouterLink to="/settings/2fa" class="dropdown-item" @click="isUserMenuOpen = false">
                        <ArrowPathIcon class="w-5 h-5" aria-hidden="true" />
                        Two-Factor Auth
                      </RouterLink>
                      
                      <div v-if="authStore.isAdmin" class="dropdown-divider" />
                      
                      <RouterLink v-if="authStore.isAdmin" to="/admin" class="dropdown-item" @click="isUserMenuOpen = false">
                        <ChartBarIcon class="w-5 h-5" aria-hidden="true" />
                        Admin Dashboard
                      </RouterLink>
                    </div>

                    <div class="border-t border-pink-200/50 dark:border-pink-800/50 py-1">
                      <button @click="logout" class="dropdown-item text-red-600 dark:text-red-400">
                        <ArrowRightOnRectangleIcon class="w-5 h-5" aria-hidden="true" />
                        Sign out
                      </button>
                    </div>
                  </div>
                </Transition>
              </div>

              <!-- Mobile Menu Button -->
              <button
                @click="isMobileMenuOpen = !isMobileMenuOpen"
                :aria-expanded="isMobileMenuOpen"
                :aria-controls="mobile-menu"
                :aria-label="Toggle navigation menu"
                class="md:hidden p-2 rounded-xl text-gray-600 dark:text-gray-300 hover:bg-pink-100/50 dark:hover:bg-pink-900/20 transition-colors"
              >
                <MenuIcon v-if="!isMobileMenuOpen" class="w-6 h-6" aria-hidden="true" />
                <XIcon v-else class="w-6 h-6" aria-hidden="true" />
              </button>
            </div>
          </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" v-show="isMobileMenuOpen" class="md:hidden animate-slide-down">
          <div class="px-4 py-4 space-y-2 border-t border-pink-200/50 dark:border-pink-800/50">
            <div class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Navigation</div>
            <template v-for="item in filteredNavigation" :key="item.name">
              <RouterLink
                :to="item.href"
                @click="isMobileMenuOpen = false"
                class="flex items-center gap-3 px-3 py-3 text-base font-medium text-gray-700 dark:text-gray-300 rounded-xl
                       hover:text-pink-600 dark:hover:text-pink-400 hover:bg-pink-50/50 dark:hover:bg-pink-900/20
                       transition-colors"
              >
                <component :is="item.icon" class="w-5 h-5" aria-hidden="true" />
                <span>{{ item.name }}</span>
              </RouterLink>
            </template>
            
            <div class="pt-4 border-t border-pink-200/50 dark:border-pink-800/50 space-y-2">
              <RouterLink to="/login" @click="isMobileMenuOpen = false" class="block px-3 py-3 text-base font-medium text-gray-700 dark:text-gray-300 rounded-xl hover:text-pink-600 dark:hover:text-pink-400 hover:bg-pink-50/50 dark:hover:bg-pink-900/20 transition-colors">Sign in</RouterLink>
              <RouterLink to="/register" @click="isMobileMenuOpen = false" class="btn-primary text-center block">Get Started</RouterLink>
            </div>
          </div>
        </div>
      </nav>
    </header>

    <!-- Main Content -->
    <main id="main-content" class="flex-1 pt-16">
      <slot />
    </main>

    <!-- Footer -->
    <footer class="border-t border-pink-200/50 dark:border-pink-800/50 bg-white/50 dark:bg-gray-900/50 backdrop-blur">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
          <div class="md:col-span-2">
            <RouterLink to="/" class="flex items-center gap-2 mb-4" aria-label="Study Planner Home">
              <svg class="w-8 h-8 text-pink-600" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4v10.55c-.59.34-1.27.55-2 .55-2.21 0-4-1.79-4-4S9.79 3 12 3z"/>
              </svg>
              <span class="text-xl font-bold text-gray-900 dark:text-white">StudyPlanner</span>
            </RouterLink>
            <p class="text-gray-600 dark:text-gray-400 max-w-sm">AI-powered study planning that adapts to your learning style.</p>
          </div>
          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Product</h3>
            <ul class="space-y-2">
              <li><RouterLink to="/features" class="text-sm text-gray-600 dark:text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 transition-colors">Features</RouterLink></li>
              <li><RouterLink to="/pricing" class="text-sm text-gray-600 dark:text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 transition-colors">Pricing</RouterLink></li>
              <li><RouterLink to="/integrations" class="text-sm text-gray-600 dark:text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 transition-colors">Integrations</RouterLink></li>
              <li><RouterLink to="/changelog" class="text-sm text-gray-600 dark:text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 transition-colors">Changelog</RouterLink></li>
            </ul>
          </div>
          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Resources</h3>
            <ul class="space-y-2">
              <li><RouterLink to="/blog" class="text-sm text-gray-600 dark:text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 transition-colors">Blog</RouterLink></li>
              <li><RouterLink to="/docs" class="text-sm text-gray-600 dark:text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 transition-colors">Documentation</RouterLink></li>
              <li><RouterLink to="/community" class="text-sm text-gray-600 dark:text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 transition-colors">Community</RouterLink></li>
              <li><RouterLink to="/support" class="text-sm text-gray-600 dark:text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 transition-colors">Support</RouterLink></li>
            </ul>
          </div>
          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Legal</h3>
            <ul class="space-y-2">
              <li><RouterLink to="/privacy" class="text-sm text-gray-600 dark:text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 transition-colors">Privacy Policy</RouterLink></li>
              <li><RouterLink to="/terms" class="text-sm text-gray-600 dark:text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 transition-colors">Terms of Service</RouterLink></li>
              <li><RouterLink to="/cookies" class="text-sm text-gray-600 dark:text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 transition-colors">Cookie Policy</RouterLink></li>
            </ul>
          </div>
        </div>
        <div class="mt-12 pt-8 border-t border-pink-200/50 dark:border-pink-800/50 text-center">
          <p class="text-sm text-gray-500 dark:text-gray-500">© {{ new Date().getFullYear() }} StudyPlanner. All rights reserved.</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useThemeStore } from '@/stores/theme'
import { useToastStore } from '@/stores/toast'
import { 
  MenuIcon, XIcon, BellIcon, MoonIcon, SunIcon, 
  UserCircleIcon, Cog6ToothIcon, ArrowRightOnRectangleIcon,
  MagnifyingGlassIcon, BookOpenIcon, CalendarDaysIcon,
  CheckCircleIcon, LightBulbIcon, ChartBarIcon,
  ArrowPathIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const themeStore = useThemeStore()
const toastStore = useToastStore()

const isMobileMenuOpen = ref(false)
const isUserMenuOpen = ref(false)
const isNotificationsOpen = ref(false)
const searchQuery = ref('')

const unreadCount = ref(0)
const recentNotifications = ref<import('@/types').Notification[]>([])

const navigation = [
  { name: 'Dashboard', href: '/dashboard', icon: BookOpenIcon, roles: ['student'] },
  { name: 'Study Plan', href: '/study-plan', icon: CalendarDaysIcon, roles: ['student'] },
  { name: 'Tasks', href: '/tasks', icon: CheckCircleIcon, roles: ['student'] },
  { name: 'Subjects', href: '/subjects', icon: LightBulbIcon, roles: ['student'] },
  { name: 'Quizzes', href: '/quizzes', icon: ChartBarIcon, roles: ['student'] },
  { name: 'Progress', href: '/progress', icon: ChartBarIcon, roles: ['student'] },
  { name: 'Recommendations', href: '/recommendations', icon: LightBulbIcon, roles: ['student'] },
  { name: 'Admin Dashboard', href: '/admin', icon: ChartBarIcon, roles: ['admin'] },
  { name: 'Resources', href: '/admin/resources', icon: BookOpenIcon, roles: ['admin'] },
  { name: 'Users', href: '/admin/users', icon: UserCircleIcon, roles: ['admin'] },
  { name: 'Analytics', href: '/admin/analytics', icon: ChartBarIcon, roles: ['admin'] },
  { name: 'Settings', href: '/admin/settings', icon: Cog6ToothIcon, roles: ['admin'] },
]

const filteredNavigation = computed(() => 
  navigation.filter(item => !item.roles || item.roles.includes(authStore.user?.role || ''))
)

const userInitials = computed(() => {
  if (!authStore.user) return 'U'
  return authStore.user.name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
})

async function fetchNotifications() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get('/student/notifications', { 
      params: { unread: true, per_page: 5 } 
    })
    recentNotifications.value = response.data.data || []
    unreadCount.value = response.data.unread_count || 0
  } catch {
    // Silently fail
  }
}

async function markAllRead() {
  try {
    const api = (await import('@/utils/api')).default
    await api.post('/student/notifications/read-all')
    unreadCount.value = 0
    recentNotifications.value = recentNotifications.value.map(n => ({ ...n, read_at: new Date().toISOString() }))
    toastStore.success('All notifications marked as read')
  } catch {
    toastStore.error('Failed to mark notifications as read')
  }
}

async function markRead(notificationId: string) {
  try {
    const api = (await import('@/utils/api')).default
    await api.post(`/student/notifications/${notificationId}/read`)
    unreadCount.value = Math.max(0, unreadCount.value - 1)
    const notification = recentNotifications.value.find(n => n.id === notificationId)
    if (notification) notification.read_at = new Date().toISOString()
  } catch {
    toastStore.error('Failed to mark notification as read')
  }
}

function toggleTheme() {
  themeStore.toggleTheme()
}

function logout() {
  authStore.logout()
  toastStore.success('Logged out successfully')
}

function handleSearch() {
  if (searchQuery.value.trim()) {
    router.push({ name: 'subjects', query: { search: searchQuery.value.trim() } })
    searchQuery.value = ''
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

// Close dropdowns when clicking outside
function handleClickOutside(event: MouseEvent) {
  const target = event.target as HTMLElement
  if (!target.closest('.user-menu') && !target.closest('[aria-label="User menu"]')) {
    isUserMenuOpen.value = false
  }
  if (!target.closest('.notifications-dropdown') && !target.closest('[aria-label="Notifications"]')) {
    isNotificationsOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  fetchNotifications()
  const interval = setInterval(fetchNotifications, 30000)
  return () => clearInterval(interval)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>