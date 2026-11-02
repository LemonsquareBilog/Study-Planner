<template>
  <div class="space-y-8">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-text-primary">Dashboard</h1>
        <p class="mt-1 text-sm text-text-secondary">Welcome back, <strong class="font-bold text-text-primary">{{ authStore.user?.name }}</strong>! Here's your study overview.</p>
      </div>
    </div>

    <p v-if="refreshing && !loading" role="status" class="text-sm text-text-secondary">Updating dashboard…</p>
    <div v-if="loadError" role="alert" class="glass-card p-4">
      <p>{{ loadError }}</p>
      <button type="button" class="btn-secondary mt-2" @click="loadDashboard">Retry</button>
    </div>
    <div v-if="loading" class="flex justify-center py-12" role="status" aria-label="Loading dashboard">
      <svg class="animate-spin h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
      </svg>
    </div>

    <template v-if="!loading && (!loadError || hasData)">
    <section class="glass-card p-6" aria-labelledby="level-status-heading">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-wide text-primary">Level Status</p>
          <h2 id="level-status-heading" class="mt-1 text-xl font-bold text-text-primary">{{ levelStatus.label }}</h2>
          <p class="mt-1 text-sm text-text-secondary">Based on your quiz performance and completed tasks.</p>
        </div>
        <div class="text-left sm:text-right">
          <p class="text-3xl font-bold text-primary">{{ levelStatus.progress }}%</p>
          <p class="text-xs text-text-secondary">current progress</p>
        </div>
      </div>
      <div class="mt-4 h-2 overflow-hidden rounded-full bg-brand-borders" role="progressbar" :aria-valuenow="levelStatus.progress" aria-valuemin="0" aria-valuemax="100" :aria-label="`${levelStatus.label} progress`">
        <div class="h-full rounded-full bg-primary transition-all" :style="{ width: `${levelStatus.progress}%` }"></div>
      </div>
      <div class="mt-3 flex flex-wrap gap-3 text-xs text-text-secondary">
        <span>{{ progress?.quizzes?.average_percentage || 0 }}% average quiz score</span>
        <span>{{ progress?.tasks?.completion_rate || 0 }}% task completion</span>
      </div>
    </section>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <StatCard title="Tasks Completed" :value="progress?.tasks?.completed || 0" :total="progress?.tasks?.total || 0" :label="`of ${progress?.tasks?.total || 0} total`" icon="check-circle" color="green" class="glass-stat-card" />
      <StatCard title="Study Minutes" :value="progress?.study_sessions?.minutes_completed || 0" label="completed" icon="clock" color="blue" class="glass-stat-card" />
      <StatCard title="Quiz Attempts" :value="progress?.quizzes?.attempts || 0" :label="`average ${progress?.quizzes?.average_percentage || 0}%`" icon="academic-cap" color="purple" class="glass-stat-card" />
      <StatCard title="Unread Alerts" :value="unreadNotifications" label="notifications" icon="bell" color="yellow" class="glass-stat-card" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <section class="glass-card p-6" aria-labelledby="upcoming-tasks-heading">
        <div class="flex items-center justify-between mb-4">
          <h2 id="upcoming-tasks-heading" class="text-lg font-semibold text-text-primary">Upcoming Tasks</h2>
          <router-link to="/tasks" class="text-sm text-secondary hover:text-secondary-hover font-medium transition-colors">View all</router-link>
        </div>
        <ul v-if="upcomingTasks.length === 0" class="text-center py-8 text-text-secondary" role="list">
          <li>No upcoming tasks. <router-link to="/tasks" class="text-secondary hover:underline transition-colors">Create one</router-link> to get started!</li>
        </ul>
        <ul v-else class="space-y-3" role="list">
          <li v-for="task in upcomingTasks.slice(0, 5)" :key="task.id" class="flex items-center justify-between p-3 glass-card">
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-text-primary truncate">{{ task.title }}</p>
              <p class="text-xs text-text-secondary">{{ task.subject?.name }} · Due {{ formatDate(task.deadline) }}</p>
            </div>
            <span :class="priorityClass(task.priority)" class="px-2 py-1 text-xs font-medium rounded-full">{{ task.priority }}</span>
          </li>
        </ul>
      </section>

      <section class="glass-card p-6" aria-labelledby="upcoming-sessions-heading">
        <div class="flex items-center justify-between mb-4">
          <h2 id="upcoming-sessions-heading" class="text-lg font-semibold text-text-primary">Upcoming Study Sessions</h2>
          <router-link to="/study-plan" class="text-sm text-secondary hover:text-secondary-hover font-medium transition-colors">View all</router-link>
        </div>
        <ul v-if="upcomingSessions.length === 0" class="text-center py-8 text-text-secondary" role="list">
          <li>No study sessions scheduled. <router-link to="/study-plan" class="text-secondary hover:underline transition-colors">Generate a plan</router-link> to get started!</li>
        </ul>
        <ul v-else class="space-y-3" role="list">
          <li v-for="session in upcomingSessions.slice(0, 5)" :key="session.id" class="flex items-center justify-between p-3 glass-card">
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-text-primary">{{ session.subject?.name }}</p>
              <p class="text-xs text-text-secondary">{{ session.topic?.name || 'General' }} · {{ formatSessionTime(session) }}</p>
            </div>
            <span class="px-2 py-1 text-xs font-medium bg-primary-light text-primary rounded-full">{{ session.duration_minutes }} min</span>
          </li>
        </ul>
      </section>
    </div>

    <section v-if="recommendations.length > 0" class="glass-card p-6" aria-labelledby="recommendations-heading">
      <div class="flex items-center justify-between mb-4">
        <h2 id="recommendations-heading" class="text-lg font-semibold text-text-primary">Recommended Resources</h2>
        <router-link to="/recommendations" class="text-sm text-secondary hover:text-secondary-hover font-medium transition-colors">View all</router-link>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <ResourceCard v-for="resource in recommendations.slice(0, 3)" :key="resource.id" :resource="resource" @view="recordView" />
      </div>
    </section>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useStudentStore } from '@/stores/student';
import StatCard from '@/components/StatCard.vue';
import ResourceCard from '@/components/ResourceCard.vue';

const authStore = useAuthStore();
const studentStore = useStudentStore();

const ownerId = authStore.user?.id;
const sessionRevision = authStore.sessionRevision;
const hasData = ref(!!ownerId && studentStore.dashboardUserId === ownerId && studentStore.dashboardSessionRevision === sessionRevision && !!studentStore.progress);
const loading = ref(!hasData.value);
const refreshing = ref(false);
const loadError = ref('');
const progress = ref(hasData.value ? studentStore.progress : null);
const upcomingTasks = ref(hasData.value ? studentStore.upcomingTasks : []);
const upcomingSessions = ref(hasData.value ? studentStore.upcomingSessionsList : []);
const unreadNotifications = ref(hasData.value ? studentStore.unreadNotificationsCount : 0);
const recommendations = ref(hasData.value ? studentStore.recommendations : []);
const levelStatus = computed(() => {
  const quizScore = Number(progress.value?.quizzes?.average_percentage || 0);
  const taskRate = Number(progress.value?.tasks?.completion_rate || 0);
  const currentProgress = Math.round((quizScore + taskRate) / 2);
  return {
    progress: currentProgress,
    label: currentProgress >= 80 ? 'Advanced Learner' : currentProgress >= 60 ? 'Developing Learner' : 'Getting Started',
  };
});

watch(() => authStore.sessionRevision, () => {
  hasData.value = false;
  progress.value = null;
  upcomingTasks.value = [];
  upcomingSessions.value = [];
  unreadNotifications.value = 0;
  recommendations.value = [];
  loadError.value = '';
  loading.value = true;
});

onMounted(loadDashboard);

async function loadDashboard() {
  refreshing.value = true;
  loading.value = !hasData.value;
  loadError.value = '';
  try {
    await studentStore.fetchDashboard();
    if (authStore.user?.id !== ownerId || authStore.sessionRevision !== sessionRevision || studentStore.dashboardUserId !== ownerId || studentStore.dashboardSessionRevision !== sessionRevision) return;
    progress.value = studentStore.progress;
    upcomingTasks.value = studentStore.upcomingTasks;
    upcomingSessions.value = studentStore.upcomingSessionsList;
    unreadNotifications.value = studentStore.unreadNotificationsCount;
    recommendations.value = studentStore.recommendations;
    hasData.value = true;
  } catch {
    if (authStore.user?.id === ownerId && authStore.sessionRevision === sessionRevision) {
      loadError.value = hasData.value ? 'Could not refresh the dashboard. Showing the last loaded information.' : 'Could not load the dashboard. Please try again.';
    }
  } finally {
    loading.value = false;
    refreshing.value = false;
  }
}

function formatDate(dateString: string) {
  return new Date(dateString).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function formatSessionTime(session: any) {
  const date = new Date(session.scheduled_date);
  const time = session.start_time || session.startTime;
  return `${date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} at ${time.slice(0, 5)}`;
}

function priorityClass(priority: string) {
  const classes: Record<string, string> = {
    urgent: 'bg-status-urgent-light text-status-urgent',
    high: 'bg-status-warning-light text-status-warning',
    medium: 'bg-status-warning-light text-status-warning',
    low: 'bg-status-success-light text-status-success',
  };
  return classes[priority] || 'bg-brand-borders text-text-secondary';
}

async function recordView(resourceId: number, topicId?: number) {
  try {
    await studentStore.recordView(resourceId, topicId);
  } catch (err) {
    console.error('Failed to record view:', err);
  }
}
</script>