<template>
  <div class="space-y-8">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-text-primary">Quizzes</h1>
        <p class="mt-1 text-sm text-text-secondary">Test your knowledge with practice quizzes.</p>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-12" role="status" aria-label="Loading quizzes">
      <svg class="animate-spin h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
      </svg>
    </div>

    <div v-else-if="quizzes.length === 0" class="text-center py-12 bg-brand-navbar-cards-alt border border-brand-borders rounded-xl">
      <svg class="mx-auto h-12 w-12 text-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-text-primary">No quizzes available</h3>
      <p class="mt-1 text-sm text-text-secondary">Quizzes will appear here when your topics have associated quizzes.</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" role="list">
      <article v-for="quiz in quizzes" :key="quiz.id" class="bg-brand-navbar-cards-alt border border-brand-borders rounded-xl p-6 hover:shadow-md transition-shadow" role="listitem">
        <div class="flex items-start justify-between mb-4">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-lg bg-status-info-light flex items-center justify-center" aria-hidden="true">
              <svg class="h-6 w-6 text-status-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
              </svg>
            </div>
            <div>
              <h3 class="text-lg font-semibold text-text-primary">{{ quiz.title }}</h3>
              <p class="text-sm text-text-secondary">{{ quiz.topic?.name }} · {{ quiz.topic?.subject?.name }}</p>
            </div>
          </div>
          <span :class="difficultyClass(quiz.topic?.difficulty)" class="inline-flex shrink-0 items-center whitespace-nowrap px-2 py-1 text-xs font-medium rounded-full">Level {{ quiz.topic?.difficulty }}</span>
        </div>

        <p class="text-sm text-text-secondary mb-4 line-clamp-2">{{ quiz.description || 'No description' }}</p>

        <div class="flex items-center justify-between pt-4 border-t border-brand-borders">
          <div class="flex items-center space-x-4 text-sm text-text-secondary">
            <span>{{ quiz.questions_count || 0 }} questions</span>
            <span>Pass: {{ quiz.passing_score }}%</span>
          </div>
          <router-link :to="`/quizzes/${quiz.id}`" class="btn-primary text-sm py-1.5 px-3">Start Quiz</router-link>
        </div>
      </article>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useStudentStore } from '@/stores/student';
import type { Quiz } from '@/types';

const studentStore = useStudentStore();

const quizzes = ref<Quiz[]>([]);
const loading = ref(true);

onMounted(async () => {
  try {
    await studentStore.fetchQuizzes();
    quizzes.value = studentStore.quizzes;
  } catch (err) {
    console.error('Failed to load quizzes:', err);
  } finally {
    loading.value = false;
  }
});

function difficultyClass(difficulty: number | undefined) {
  const classes = ['', 'bg-status-success-light text-status-success', 'bg-status-success-light text-status-success', 'bg-status-warning-light text-status-warning', 'bg-status-warning-light text-status-warning', 'bg-status-urgent-light text-status-urgent'];
  return `px-2 py-1 text-xs font-medium rounded-full ${classes[difficulty || 3] || classes[3]}`;
}
</script>