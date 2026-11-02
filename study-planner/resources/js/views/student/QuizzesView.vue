<template>
  <div class="space-y-8">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-text-primary">Quizzes</h1>
        <p class="mt-1 text-sm text-text-secondary">Test your knowledge with practice quizzes.</p>
      </div>
    </div>

    <div class="flex gap-2 border-b border-brand-borders" role="tablist" aria-label="Quiz views">
      <button role="tab" :aria-selected="activeTab === 'available'" @click="selectTab('available')" :class="tabClass('available')">Available Quizzes</button>
      <button role="tab" :aria-selected="activeTab === 'history'" @click="selectTab('history')" :class="tabClass('history')">Completed Quizzes <span class="ml-1 text-xs">({{ historyTotal }})</span></button>
    </div>

    <form v-if="activeTab === 'available'" @submit.prevent="loadPage(1)">
      <input v-model="search" type="search" class="input-field-glass" aria-label="Search quizzes" placeholder="Search quizzes, then press Enter" @change="loadPage(1)" />
    </form>
    <div v-if="loadError" role="alert" class="glass-card p-4">
      <p>{{ loadError }}</p><button class="btn-secondary mt-2" @click="loadPage(currentPage)">Retry</button>
    </div>
    <div v-else-if="loading" class="flex justify-center py-12" role="status" aria-label="Loading quizzes">
      <svg class="animate-spin h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
      </svg>
    </div>

    <div v-else-if="activeTab === 'history' && selectedAttempt" class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl font-semibold text-text-primary">{{ selectedAttempt.quiz?.title }}</h2>
          <p class="text-sm text-text-secondary">{{ selectedAttempt.quiz?.topic?.subject?.name }} · {{ selectedAttempt.quiz?.topic?.name }} · {{ formatDate(selectedAttempt.completed_at) }}</p>
        </div>
        <button @click="selectedAttempt = null" class="btn-secondary">Back to history</button>
      </div>

      <section class="glass-card p-5" aria-label="Score breakdown">
        <p class="text-2xl font-bold text-text-primary">{{ selectedAttempt.score }} / {{ selectedAttempt.total_questions }} correct</p>
        <p class="mt-1 text-sm text-text-secondary">{{ selectedAttempt.percentage }}% · {{ selectedAttempt.percentage >= (selectedAttempt.quiz?.passing_score || 70) ? 'Passed' : 'Needs review' }}</p>
      </section>

      <section v-if="Number(selectedAttempt.percentage) < (selectedAttempt.quiz?.passing_score || 70)" class="bg-primary-light border border-primary/30 rounded-lg p-4">
        <h3 class="font-semibold text-text-primary">Area to review</h3>
        <p class="mt-1 text-sm text-text-secondary">{{ selectedAttempt.quiz?.topic?.name }} · {{ selectedAttempt.quiz?.topic?.subject?.name }}</p>
        <router-link to="/recommendations" class="mt-2 inline-block text-sm font-medium text-secondary hover:text-secondary-hover">View recommended resources</router-link>
      </section>

      <section class="space-y-4" aria-label="Question review">
        <article v-for="(answer, index) in selectedAttempt.answers" :key="answer.id" class="rounded-lg border p-4" :class="answer.is_correct ? 'border-status-success/30 bg-status-success-light/30' : 'border-status-urgent/30 bg-status-urgent-light/20'">
          <div class="flex items-center justify-between gap-3">
            <h3 class="font-medium text-text-primary">Question {{ index + 1 }}</h3>
            <span class="text-xs font-semibold" :class="answer.is_correct ? 'text-status-success' : 'text-status-urgent'">{{ answer.is_correct ? 'Correct' : 'Incorrect' }}</span>
          </div>
          <p class="mt-2 text-sm text-text-primary">{{ answer.question?.question }}</p>
          <p class="mt-2 text-sm text-text-secondary"><span class="font-semibold text-text-primary">User's Answer:</span> {{ formatAnswer(answer, answer.selected_answer) }}</p>
          <p class="text-sm text-text-secondary"><span class="font-semibold text-text-primary">Correct Answer:</span> {{ formatAnswer(answer, answer.question?.correct_answer || null) }}</p>
        </article>
      </section>
    </div>

    <div v-else-if="activeTab === 'history' && quizAttempts.length === 0" class="text-center py-12 bg-brand-navbar-cards-alt border border-brand-borders rounded-xl">
      <h3 class="text-sm font-medium text-text-primary">No completed quizzes yet</h3>
      <p class="mt-1 text-sm text-text-secondary">Your submitted quiz results will appear here.</p>
    </div>

    <div v-else-if="activeTab === 'history'" class="space-y-3">
      <article v-for="attempt in quizAttempts" :key="attempt.id" class="flex flex-col gap-4 rounded-lg border border-brand-borders bg-brand-navbar-cards-alt p-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="font-semibold text-text-primary">{{ attempt.quiz?.title }}</h2>
          <p class="text-sm text-text-secondary">{{ attempt.quiz?.topic?.subject?.name }} · {{ attempt.quiz?.topic?.name }}</p>
          <p class="text-xs text-text-secondary">Completed {{ formatDate(attempt.completed_at) }}</p>
        </div>
        <div class="flex items-center gap-4">
          <span class="text-sm font-medium text-text-primary">{{ attempt.score }}/{{ attempt.total_questions }} · {{ attempt.percentage }}%</span>
          <button @click="reviewAttempt(attempt.id)" :disabled="loadingAttempt" class="btn-secondary text-sm">{{ loadingAttempt ? 'Loading...' : 'Review' }}</button>
        </div>
      </article>
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
    <nav v-if="!loading && !selectedAttempt && lastPage > 1" aria-label="Quiz pagination" class="flex items-center justify-between gap-4">
      <button class="btn-secondary" :disabled="currentPage <= 1" @click="loadPage(currentPage - 1)">Previous</button>
      <p role="status">Page {{ currentPage }} of {{ lastPage }}</p>
      <button class="btn-secondary" :disabled="currentPage >= lastPage" @click="loadPage(currentPage + 1)">Next</button>
    </nav>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useStudentStore } from '@/stores/student';
import type { Quiz } from '@/types';
import type { QuizAnswer, QuizAttempt } from '@/types';

const studentStore = useStudentStore();

const quizzes = ref<Quiz[]>([]);
const loading = ref(true);
const loadingAttempt = ref(false);
const activeTab = ref<'available' | 'history'>('available');
const selectedAttempt = ref<QuizAttempt | null>(null);
const quizAttempts = ref<QuizAttempt[]>([]);

const currentPage = ref(1);
const lastPage = ref(1);
const historyTotal = ref(0);
const search = ref('');
const loadError = ref('');
let requestVersion = 0;

onMounted(() => loadPage(1));

async function loadPage(page: number) {
  const version = ++requestVersion;
  const tab = activeTab.value;
  loading.value = true;
  loadError.value = '';
  try {
    const response = tab === 'available'
      ? await studentStore.fetchQuizzes({ page, search: search.value })
      : await studentStore.fetchQuizHistory({ page });
    if (version !== requestVersion) return;
    currentPage.value = response.current_page;
    lastPage.value = response.last_page;
    if (tab === 'available') quizzes.value = response.data as Quiz[];
    else {
      quizAttempts.value = response.data as QuizAttempt[];
      historyTotal.value = response.total;
    }
  } catch {
    if (version === requestVersion) loadError.value = 'Could not load quizzes. Please try again.';
  } finally {
    if (version === requestVersion) loading.value = false;
  }
}

function selectTab(tab: 'available' | 'history') {
  activeTab.value = tab;
  selectedAttempt.value = null;
  void loadPage(1);
}

function tabClass(tab: 'available' | 'history') {
  return [
    'border-b-2 px-3 py-3 text-sm font-medium transition-colors',
    activeTab.value === tab ? 'border-primary text-primary' : 'border-transparent text-text-secondary hover:text-text-primary',
  ];
}

async function reviewAttempt(id: number) {
  loadingAttempt.value = true;
  try {
    selectedAttempt.value = await studentStore.fetchQuizAttempt(id);
  } catch (err) {
    loadError.value = 'Could not load this result. Please try again.';
  } finally {
    loadingAttempt.value = false;
  }
}

function formatDate(dateString: string) {
  return new Date(dateString).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function formatAnswer(answer: QuizAnswer, letter: string | null) {
  if (!letter) return 'Not answered';
  const optionKey = `option_${letter.toLowerCase()}` as 'option_a' | 'option_b' | 'option_c' | 'option_d';
  return `${letter}. ${answer.question?.[optionKey] || ''}`.trim();
}

function difficultyClass(difficulty: number | undefined) {
  const classes = ['', 'bg-status-success-light text-status-success', 'bg-status-success-light text-status-success', 'bg-status-warning-light text-status-warning', 'bg-status-warning-light text-status-warning', 'bg-status-urgent-light text-status-urgent'];
  return `px-2 py-1 text-xs font-medium rounded-full ${classes[difficulty || 3] || classes[3]}`;
}
</script>