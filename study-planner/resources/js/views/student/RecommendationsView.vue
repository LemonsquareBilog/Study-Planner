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

    <div v-else-if="recommendations.length === 0" class="text-center py-12 bg-brand-navbar-cards-alt border border-brand-borders rounded-xl">
      <svg class="mx-auto h-12 w-12 text-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-text-primary">No recommendations yet</h3>
      <p class="mt-1 text-sm text-text-secondary">Take quizzes to identify weak areas and get personalized resource recommendations.</p>
      <router-link to="/quizzes" class="mt-4 btn-primary inline-block">Take a Quiz</router-link>
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
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useStudentStore } from '@/stores/student';
import ResourceCard from '@/components/ResourceCard.vue';

const studentStore = useStudentStore();

const recommendations = ref([]);
const loading = ref(true);

onMounted(async () => {
  await fetchRecommendations();
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

async function recordView(resourceId: number) {
  try {
    await studentStore.recordView(resourceId);
  } catch (err) {
    console.error('Failed to record view:', err);
  }
}
</script>