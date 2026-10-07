<template>
  <div class="space-y-8">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-text-primary">Admin Dashboard</h1>
        <p class="mt-1 text-sm text-text-secondary">Platform overview and key metrics.</p>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-12" role="status" aria-label="Loading dashboard">
      <svg class="animate-spin h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
      </svg>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <MetricCard
        v-for="metric in metrics"
        :key="metric.key"
        :label="metric.label"
        :value="metric.value"
        :icon="metric.icon"
        :color="metric.color"
        :trend="metric.trend"
        :trend-label="metric.trendLabel"
        :link="metric.link"
      />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <section class="glass-card p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-text-primary">Recent Users</h2>
          <router-link to="/admin/users" class="text-sm text-secondary hover:text-secondary-hover transition-colors">View all</router-link>
        </div>
        <div v-if="recentUsers.length === 0" class="text-center py-8 text-text-secondary">No recent users.</div>
        <div v-else class="divide-y divide-brand-borders/50">
          <div v-for="user in recentUsers" :key="user.id" class="py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-primary-light flex items-center justify-center">
                <span class="text-primary font-medium">{{ user.name.charAt(0).toUpperCase() }}</span>
              </div>
              <div>
                <p class="font-medium text-text-primary">{{ user.name }}</p>
                <p class="text-sm text-text-secondary">{{ user.email }}</p>
              </div>
            </div>
            <div class="text-right">
              <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full" :class="user.is_active ? 'bg-status-success-light text-status-success' : 'bg-brand-borders text-text-secondary'">
                {{ user.is_active ? 'Active' : 'Inactive' }}
              </span>
              <p class="mt-1 text-xs text-text-secondary">{{ formatDate(user.created_at) }}</p>
            </div>
          </div>
        </div>
      </section>

      <section class="glass-card p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-text-primary">Recent Quiz Attempts</h2>
          <router-link to="/admin/users" class="text-sm text-secondary hover:text-secondary-hover transition-colors">View all</router-link>
        </div>
        <div v-if="recentAttempts.length === 0" class="text-center py-8 text-text-secondary">No recent quiz attempts.</div>
        <div v-else class="divide-y divide-brand-borders/50">
          <div v-for="attempt in recentAttempts" :key="attempt.id" class="py-4 flex items-center justify-between">
            <div>
              <p class="font-medium text-text-primary">{{ attempt.quiz?.title || 'Unknown Quiz' }}</p>
              <p class="text-sm text-text-secondary">{{ attempt.user?.name || 'Unknown User' }}</p>
            </div>
            <div class="text-right">
              <span class="text-lg font-bold text-text-primary">{{ attempt.percentage }}%</span>
              <p class="text-xs text-text-secondary">{{ formatDate(attempt.completed_at) }}</p>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from 'vue';
import { useAdminStore } from '@/stores/admin';
import { useRouter } from 'vue-router';
import MetricCard from '@/components/ui/MetricCard.vue';

const adminStore = useAdminStore();
const router = useRouter();

const loading = ref(true);
const metrics = ref<Metric[]>([]);
const recentUsers = ref<any[]>([]);
const recentAttempts = ref<any[]>([]);

interface Metric {
  key: string;
  label: string;
  value: string | number;
  icon: string;
  color: string;
  trend?: string;
  trendLabel?: string;
  link?: string;
}

onMounted(async () => {
  try {
    const data = await adminStore.getAdminDashboard();
    if (data?.metrics) {
      metrics.value = [
        { key: 'students', label: 'Total Students', value: data.metrics.total_students, icon: 'users', color: 'primary', link: '/admin/users' },
        { key: 'activeStudents', label: 'Active Students', value: data.metrics.active_students, icon: 'user-check', color: 'success', trend: `${data.metrics.active_students} of ${data.metrics.total_students}`, trendLabel: 'Active' },
        { key: 'resources', label: 'Resources', value: data.metrics.total_resources, icon: 'book', color: 'info', link: '/admin/resources' },
        { key: 'subjects', label: 'Subjects', value: data.metrics.total_subjects, icon: 'layers', color: 'warning', link: '/admin/subjects' },
        { key: 'quizzes', label: 'Quizzes', value: data.metrics.total_quizzes, icon: 'question-circle', color: 'purple', link: '/admin/resources' },
        { key: 'attempts', label: 'Quiz Attempts', value: data.metrics.total_quiz_attempts, icon: 'clipboard-check', color: 'pink', trend: `${data.metrics.average_quiz_score}% avg`, trendLabel: 'Avg Score' },
      ];
    }
    recentUsers.value = data?.recent_users || [];
    recentAttempts.value = data?.recent_attempts || [];
  } catch (err) {
    console.error('Failed to load admin dashboard:', err);
  } finally {
    loading.value = false;
  }
});

function formatDate(dateString: string) {
  return new Date(dateString).toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>
