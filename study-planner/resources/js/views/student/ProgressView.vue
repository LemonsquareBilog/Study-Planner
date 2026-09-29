<template>
  <div class="space-y-8">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-text-primary">Progress</h1>
        <p class="mt-1 text-sm text-text-secondary">Track your learning progress and performance analytics.</p>
      </div>
      <button @click="fetchProgress" :disabled="loading" class="btn-secondary text-sm" aria-label="Refresh progress">
        <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        Refresh
      </button>
    </div>

    <div v-if="loading" class="flex justify-center py-12" role="status" aria-label="Loading progress">
      <svg class="animate-spin h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
      </svg>
    </div>

    <div v-else class="space-y-8">
      <section aria-labelledby="summary-heading">
        <h2 id="summary-heading" class="text-lg font-semibold text-text-primary mb-4">Overview</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <StatCard title="Tasks Completed" :value="progress.tasks?.completed || 0" :total="progress.tasks?.total || 0" :label="`of ${progress.tasks?.total || 0} total`" icon="check-circle" color="green" />
          <StatCard title="Completion Rate" :value="progress.tasks?.completion_rate || 0" label="%" icon="check-circle" color="blue" />
          <StatCard title="Study Minutes" :value="progress.study_sessions?.minutes_completed || 0" label="completed" icon="clock" color="purple" />
          <StatCard title="Avg Quiz Score" :value="progress.quizzes?.average_percentage || 0" :label="`% (${progress.quizzes?.attempts || 0} attempts)`" icon="academic-cap" color="yellow" />
        </div>
      </section>

      <section aria-labelledby="topic-performance-heading">
        <div class="flex items-center justify-between mb-4">
          <h2 id="topic-performance-heading" class="text-lg font-semibold text-text-primary">Topic Performance</h2>
        </div>
        <div v-if="topicPerformance.length === 0" class="text-center py-12 bg-brand-navbar-cards-alt border border-brand-borders rounded-xl">
          <svg class="mx-auto h-12 w-12 text-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-text-primary">No quiz data yet</h3>
          <p class="mt-1 text-sm text-text-secondary">Take quizzes to see your topic-level performance.</p>
          <router-link to="/quizzes" class="mt-4 btn-primary inline-block">Take a Quiz</router-link>
        </div>
        <div v-else class="bg-brand-navbar-cards-alt border border-brand-borders rounded-xl overflow-hidden">
          <table class="min-w-full divide-y divide-brand-borders" role="table">
            <thead class="bg-brand-navbar-cards">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">Topic</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">Subject</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">Avg Score</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">Attempts</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">Best / Worst</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-brand-borders" role="rowgroup">
              <tr v-for="topic in topicPerformance" :key="topic.topic_id" class="hover:bg-brand-navbar-cards" role="row">
                <td class="px-6 py-4 text-sm font-medium text-text-primary">{{ topic.topic_name }}</td>
                <td class="px-6 py-4 text-sm text-text-secondary">{{ topic.subject_name || 'N/A' }}</td>
                <td class="px-6 py-4 text-sm font-medium text-text-primary">{{ topic.avg_percentage.toFixed(1) }}%</td>
                <td class="px-6 py-4 text-sm text-text-secondary">{{ topic.attempt_count }}</td>
                <td class="px-6 py-4 text-sm text-text-secondary">{{ topic.best_score.toFixed(1) }}% / {{ topic.worst_score.toFixed(1) }}%</td>
                <td class="px-6 py-4">
                  <span :class="classificationClass(topic.classification)" class="px-2 py-1 text-xs font-medium rounded-full">{{ formatClassification(topic.classification) }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useStudentStore } from '@/stores/student';
import StatCard from '@/components/StatCard.vue';

const studentStore = useStudentStore();

const progress = ref({});
const topicPerformance = ref([]);
const loading = ref(true);

onMounted(async () => {
  await fetchProgress();
});

async function fetchProgress() {
  loading.value = true;
  try {
    const data = await studentStore.fetchProgress();
    progress.value = data.summary;
    topicPerformance.value = data.topic_performance;
  } catch (err) {
    console.error('Failed to load progress:', err);
  } finally {
    loading.value = false;
  }
}

function classificationClass(classification: string) {
  const classes = { weak: 'bg-status-urgent-light text-status-urgent', needs_review: 'bg-status-warning-light text-status-warning', strong: 'bg-status-success-light text-status-success' };
  return classes[classification as keyof typeof classes] || 'bg-brand-borders text-text-secondary';
}

function formatClassification(classification: string) {
  return classification.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
}
</script>