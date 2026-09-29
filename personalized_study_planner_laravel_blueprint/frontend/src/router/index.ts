import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes: RouteRecordRaw[] = [
  // Public routes
  {
    path: '/',
    name: 'home',
    component: () => import('@/views/HomeView.vue'),
    meta: { layout: 'default' },
  },
  {
    path: '/features',
    name: 'features',
    component: () => import('@/views/FeaturesView.vue'),
    meta: { layout: 'default' },
  },
  {
    path: '/pricing',
    name: 'pricing',
    component: () => import('@/views/PricingView.vue'),
    meta: { layout: 'default' },
  },
  {
    path: '/about',
    name: 'about',
    component: () => import('@/views/AboutView.vue'),
    meta: { layout: 'default' },
  },
  {
    path: '/contact',
    name: 'contact',
    component: () => import('@/views/ContactView.vue'),
    meta: { layout: 'default' },
  },

  // Auth routes
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/auth/LoginView.vue'),
    meta: { layout: 'auth', guest: true },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('@/views/auth/RegisterView.vue'),
    meta: { layout: 'auth', guest: true },
  },
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: () => import('@/views/auth/ForgotPasswordView.vue'),
    meta: { layout: 'auth', guest: true },
  },
  {
    path: '/reset-password',
    name: 'reset-password',
    component: () => import('@/views/auth/ResetPasswordView.vue'),
    meta: { layout: 'auth', guest: true },
  },
  {
    path: '/verify-email',
    name: 'verify-email',
    component: () => import('@/views/auth/VerifyEmailView.vue'),
    meta: { layout: 'auth', requiresAuth: true },
  },

  // Student routes
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('@/views/student/DashboardView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'student' },
  },
  {
    path: '/onboarding',
    name: 'onboarding',
    component: () => import('@/views/student/OnboardingView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'student' },
  },

  // Study Preferences
  {
    path: '/settings/preferences',
    name: 'study-preferences',
    component: () => import('@/views/student/StudyPreferencesView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'student' },
  },
  {
    path: '/settings/profile',
    name: 'profile',
    component: () => import('@/views/student/ProfileView.vue'),
    meta: { layout: 'default', requiresAuth: true },
  },
  {
    path: '/settings/password',
    name: 'change-password',
    component: () => import('@/views/student/ChangePasswordView.vue'),
    meta: { layout: 'default', requiresAuth: true },
  },
  {
    path: '/settings/2fa',
    name: 'two-factor',
    component: () => import('@/views/student/TwoFactorView.vue'),
    meta: { layout: 'default', requiresAuth: true },
  },

  // Subjects & Topics
  {
    path: '/subjects',
    name: 'subjects',
    component: () => import('@/views/student/SubjectsView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'student' },
  },
  {
    path: '/subjects/create',
    name: 'subjects-create',
    component: () => import('@/views/student/SubjectFormView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'student' },
  },
  {
    path: '/subjects/:id',
    name: 'subject-detail',
    component: () => import('@/views/student/SubjectDetailView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'student' },
    props: true,
  },
  {
    path: '/subjects/:id/edit',
    name: 'subject-edit',
    component: () => import('@/views/student/SubjectFormView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'student' },
    props: true,
  },
  {
    path: '/subjects/:subjectId/topics',
    name: 'topics',
    component: () => import('@/views/student/TopicsView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'student' },
    props: true,
  },
  {
    path: '/subjects/:subjectId/topics/create',
    name: 'topic-create',
    component: () => import('@/views/student/TopicFormView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'student' },
    props: true,
  },
  {
    path: '/subjects/:subjectId/topics/:id/edit',
    name: 'topic-edit',
    component: () => import('@/views/student/TopicFormView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'student' },
    props: true,
  },

  // Tasks
  {
    path: '/tasks',
    name: 'tasks',
    component: () => import('@/views/student/TasksView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'student' },
  },
  {
    path: '/tasks/create',
    name: 'task-create',
    component: () => import('@/views/student/TaskFormView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'student' },
  },
  {
    path: '/tasks/:id',
    name: 'task-detail',
    component: () => import('@/views/student/TaskDetailView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'student' },
    props: true,
  },
  {
    path: '/tasks/:id/edit',
    name: 'task-edit',
    component: () => import('@/views/student/TaskFormView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'student' },
    props: true,
  },

  // Study Plans
  {
    path: '/study-plan',
    name: 'study-plan',
    component: () => import('@/views/student/StudyPlanView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'student' },
  },
  {
    path: '/study-plan/calendar',
    name: 'study-plan-calendar',
    component: () => import('@/views/student/StudyPlanCalendarView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'student' },
  },
  {
    path: '/study-plan/generate',
    name: 'study-plan-generate',
    component: () => import('@/views/student/StudyPlanGenerateView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'student' },
  },
  {
    path: '/study-sessions/:id',
    name: 'study-session-detail',
    component: () => import('@/views/student/StudySessionDetailView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'student' },
    props: true,
  },

  // Quizzes
  {
    path: '/quizzes',
    name: 'quizzes',
    component: () => import('@/views/student/QuizzesView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'student' },
  },
  {
    path: '/quizzes/:id',
    name: 'quiz-detail',
    component: () => import('@/views/student/QuizDetailView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'student' },
    props: true,
  },
  {
    path: '/quizzes/:id/take',
    name: 'quiz-take',
    component: () => import('@/views/student/QuizTakeView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'student' },
    props: true,
  },
  {
    path: '/quiz-attempts',
    name: 'quiz-attempts',
    component: () => import('@/views/student/QuizAttemptsView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'student' },
  },
  {
    path: '/quiz-attempts/:id',
    name: 'quiz-attempt-detail',
    component: () => import('@/views/student/QuizAttemptDetailView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'student' },
    props: true,
  },

  // Recommendations
  {
    path: '/recommendations',
    name: 'recommendations',
    component: () => import('@/views/student/RecommendationsView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'student' },
  },

  // Progress
  {
    path: '/progress',
    name: 'progress',
    component: () => import('@/views/student/ProgressView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'student' },
  },
  {
    path: '/progress/export',
    name: 'progress-export',
    component: () => import('@/views/student/ProgressExportView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'student' },
  },

  // Notifications
  {
    path: '/notifications',
    name: 'notifications',
    component: () => import('@/views/student/NotificationsView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'student' },
  },

  // Admin routes
  {
    path: '/admin',
    name: 'admin-dashboard',
    component: () => import('@/views/admin/DashboardView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'admin' },
  },
  {
    path: '/admin/analytics',
    name: 'admin-analytics',
    component: () => import('@/views/admin/AnalyticsView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'admin' },
  },
  {
    path: '/admin/logs',
    name: 'admin-logs',
    component: () => import('@/views/admin/LogsView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'admin' },
  },

  // Admin Resources
  {
    path: '/admin/resources',
    name: 'admin-resources',
    component: () => import('@/views/admin/ResourcesView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'admin' },
  },
  {
    path: '/admin/resources/create',
    name: 'admin-resource-create',
    component: () => import('@/views/admin/ResourceFormView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'admin' },
  },
  {
    path: '/admin/resources/:id/edit',
    name: 'admin-resource-edit',
    component: () => import('@/views/admin/ResourceFormView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'admin' },
    props: true,
  },

  // Admin Users
  {
    path: '/admin/users',
    name: 'admin-users',
    component: () => import('@/views/admin/UsersView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'admin' },
  },
  {
    path: '/admin/users/:id',
    name: 'admin-user-detail',
    component: () => import('@/views/admin/UserDetailView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'admin' },
    props: true,
  },

  // Admin Settings
  {
    path: '/admin/settings',
    name: 'admin-settings',
    component: () => import('@/views/admin/SettingsView.vue'),
    meta: { layout: 'default', requiresAuth: true, role: 'admin' },
  },

  // 404
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/views/NotFoundView.vue'),
    meta: { layout: 'default' },
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    }
    if (to.hash) {
      return { el: to.hash, behavior: 'smooth' }
    }
    return { top: 0 }
  },
})

// Navigation guards
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  
  // Initialize auth if not already done
  if (!authStore.initialized) {
    await authStore.initialize()
  }
  
  const isAuthenticated = authStore.isAuthenticated
  const userRole = authStore.user?.role
  
  // Guest only routes
  if (to.meta.guest && isAuthenticated) {
    return next({ name: userRole === 'admin' ? 'admin-dashboard' : 'dashboard' })
  }
  
  // Requires authentication
  if (to.meta.requiresAuth && !isAuthenticated) {
    return next({ name: 'login', query: { redirect: to.fullPath } })
  }
  
  // Role-based access
  if (to.meta.role && userRole !== to.meta.role) {
    if (userRole === 'admin') {
      return next({ name: 'admin-dashboard' })
    }
    return next({ name: 'dashboard' })
  }
  
  // Redirect from / to appropriate dashboard if authenticated
  if (to.name === 'home' && isAuthenticated) {
    return next({ name: userRole === 'admin' ? 'admin-dashboard' : 'dashboard' })
  }
  
  next()
})

export default router