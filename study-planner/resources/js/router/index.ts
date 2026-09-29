import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/auth/LoginView.vue'),
      meta: { guest: true },
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('@/views/auth/RegisterView.vue'),
      meta: { guest: true },
    },
    {
      path: '/',
      component: () => import('@/layouts/StudentLayout.vue'),
      meta: { requiresAuth: true, role: 'student' },
      children: [
        { path: '', redirect: '/dashboard' },
        { path: 'dashboard', name: 'dashboard', component: () => import('@/views/student/DashboardView.vue') },
        { path: 'subjects', name: 'subjects', component: () => import('@/views/student/SubjectsView.vue') },
        { path: 'subjects/:id', name: 'subject-detail', component: () => import('@/views/student/SubjectDetailView.vue'), props: true },
        { path: 'tasks', name: 'tasks', component: () => import('@/views/student/TasksView.vue') },
        { path: 'study-plan', name: 'study-plan', component: () => import('@/views/student/StudyPlanView.vue') },
        { path: 'quizzes', name: 'quizzes', component: () => import('@/views/student/QuizzesView.vue') },
        { path: 'quizzes/:id', name: 'quiz-detail', component: () => import('@/views/student/QuizDetailView.vue'), props: true },
        { path: 'recommendations', name: 'recommendations', component: () => import('@/views/student/RecommendationsView.vue') },
        { path: 'progress', name: 'progress', component: () => import('@/views/student/ProgressView.vue') },
        { path: 'notifications', name: 'notifications', component: () => import('@/views/student/NotificationsView.vue') },
      ],
    },
    {
      path: '/admin',
      component: () => import('@/layouts/AdminLayout.vue'),
      meta: { requiresAuth: true, role: 'admin' },
      children: [
        { path: '', redirect: '/admin/resources' },
        { path: 'resources', name: 'admin-resources', component: () => import('@/views/admin/ResourcesView.vue') },
        { path: 'subjects', name: 'admin-subjects', component: () => import('@/views/admin/SubjectsView.vue') },
        { path: 'users', name: 'admin-users', component: () => import('@/views/admin/UsersView.vue') },
        { path: 'settings', name: 'admin-settings', component: () => import('@/views/admin/SettingsView.vue') },
      ],
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/',
    },
  ],
});

router.beforeEach(async (to, from) => {
  const authStore = useAuthStore();
  
  if (!authStore.isAuthenticated && !authStore.loading) {
    await authStore.initialize();
  }

  if (to.meta.guest && authStore.isAuthenticated) {
    return authStore.isAdmin ? '/admin' : '/dashboard';
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return '/login';
  }

  if (to.meta.role && authStore.userRole !== to.meta.role) {
    return authStore.isAdmin ? '/admin' : '/dashboard';
  }

  return true;
});

export default router;