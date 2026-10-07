<template>
  <div class="space-y-8">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-text-primary">Recommendations</h1>
        <p class="mt-1 text-sm text-text-secondary">Personalized learning resources based on your quiz performance.</p>
      </div>
      <button @click="fetchRecommendations" :disabled="loading" class="btn-secondary text-sm" aria-label="Refresh recommendations">
        <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        Refresh
      </button>
    </div>

    <div v-if="loading" class="flex justify-center py-12" role="status" aria-label="Loading recommendations">
      <svg class="animate-spin h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
      </svg>
    </div>

    <div v-else class="space-y-6">
      <section v-if="weakAreas.length" class="space-y-4" aria-labelledby="weak-areas-heading">
        <div class="flex items-center justify-between">
          <h2 id="weak-areas-heading" class="text-lg font-semibold text-text-primary">Areas to strengthen</h2>
          <router-link to="/progress" class="text-sm font-medium text-secondary hover:text-secondary-hover">View progress</router-link>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <article v-for="area in weakAreas" :key="area.topic_id" class="glass-stat-card p-5">
            <div class="flex items-start justify-between gap-3">
              <div>
                <h3 class="font-semibold text-text-primary">{{ area.topic_name }}</h3>
                <p class="text-sm text-text-secondary">{{ area.subject_name || 'Subject' }}</p>
              </div>
              <span class="px-2 py-1 text-xs font-medium rounded-full" :class="area.classification === 'weak' ? 'bg-status-urgent-light text-status-urgent' : 'bg-status-warning-light text-status-warning'">{{ area.classification === 'weak' ? 'Weak area' : 'Needs review' }}</span>
            </div>
            <div class="mt-4 flex items-center justify-between text-sm">
              <span class="font-semibold text-text-primary">{{ Math.round(Number(area.avg_percentage)) }}% average</span>
              <span class="text-text-secondary">{{ area.attempt_count }} quiz attempt(s)</span>
            </div>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-brand-borders" role="progressbar" :aria-valuenow="Math.round(Number(area.avg_percentage))" aria-valuemin="0" aria-valuemax="100" :aria-label="`${area.topic_name} average score`">
              <div class="h-full rounded-full bg-primary" :style="{ width: `${Math.max(0, Math.min(100, Number(area.avg_percentage)))}%` }"></div>
            </div>
          </article>
        </div>
      </section>

      <div v-if="recommendations.length === 0" class="text-center py-12 bg-brand-navbar-cards-alt border border-brand-borders rounded-xl">
        <svg class="mx-auto h-12 w-12 text-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-text-primary">{{ noRecommendationHeading }}</h3>
        <p class="mt-1 text-sm text-text-secondary">{{ noRecommendationMessage }}</p>
        <router-link v-if="studentStore.topicPerformance.length === 0" to="/quizzes" class="mt-4 btn-primary inline-block">Take a Quiz</router-link>
      </div>

      <div v-else class="space-y-6">
      <div class="bg-primary-light border border-primary/30 rounded-xl p-4">
        <div class="flex items-center">
          <svg class="h-5 w-5 text-primary mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p class="text-primary text-sm">Recommendations are based on topics where your quiz scores are below the threshold. Resources marked as "Weak Area" target your lowest-scoring topics.</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" role="list">
        <ResourceCard v-for="resource in recommendations" :key="resource.id" :resource="resource" @view="recordView" role="listitem" />
      </div>
    </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useStudentStore } from '@/stores/student';
import ResourceCard from '@/components/ResourceCard.vue';

const studentStore = useStudentStore();

const recommendations = ref([]);
const loading = ref(true);
const weakAreas = computed(() => studentStore.topicPerformance.filter(topic => topic.classification !== 'strong'));
const noRecommendationHeading = computed(() => weakAreas.value.length
  ? 'No matching resources yet'
  : studentStore.topicPerformance.length
    ? 'Your topic performance is strong'
    : 'No recommendations yet');
const noRecommendationMessage = computed(() => weakAreas.value.length
  ? 'There are no active resources linked to the topics or subjects that need review yet.'
  : studentStore.topicPerformance.length
    ? 'Keep practicing. Recommendations will update if future quiz results identify an area to strengthen.'
    : 'Take quizzes to identify weak areas and get personalized resource recommendations.');

onMounted(async () => {
  await Promise.all([
    fetchRecommendations(),
    studentStore.fetchProgress().catch(err => console.error('Failed to load topic performance:', err)),
  ]);
});

async function fetchRecommendations() {
  loading.value = true;
  try {
    await studentStore.fetchRecommendations();
    recommendations.value = studentStore.recommendations;
  } catch (err) {
    console.error('Failed to load recommendations:', err);
  } finally {
    loading.value = false;
  }
}

async function recordView(resourceId: number, topicId?: number) {
  try {
    await studentStore.recordView(resourceId, topicId);
  } catch (err) {
    console.error('Failed to record view:', err);
  }
}
</script>