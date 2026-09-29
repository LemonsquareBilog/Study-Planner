<template>
  <div class="space-y-8">
    <div v-if="loading" class="flex justify-center py-12" role="status" aria-label="Loading quiz">
      <svg class="animate-spin h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
      </svg>
    </div>

    <div v-else-if="!quiz" class="text-center py-12 bg-brand-navbar-cards-alt border border-brand-borders rounded-xl">
      <h3 class="text-lg font-medium text-text-primary">Quiz not found</h3>
      <router-link to="/quizzes" class="mt-2 text-secondary hover:underline transition-colors">Back to quizzes</router-link>
    </div>

    <div v-else-if="!quizStarted && !quizSubmitted">
      <div class="bg-brand-navbar-cards-alt border border-brand-borders rounded-xl p-8 text-center">
        <div class="flex items-center justify-center mb-4">
          <div class="w-16 h-16 rounded-full bg-status-info-light flex items-center justify-center" aria-hidden="true">
            <svg class="h-8 w-8 text-status-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
          </div>
        </div>
        <h1 class="text-2xl font-bold text-text-primary">{{ quiz.title }}</h1>
        <p class="mt-2 text-text-secondary">{{ quiz.description }}</p>
        <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
          <div class="bg-brand-navbar-cards rounded-lg p-4">
            <p class="text-2xl font-bold text-text-primary">{{ quiz.questions_count || 0 }}</p>
            <p class="text-sm text-text-secondary">Questions</p>
          </div>
          <div class="bg-brand-navbar-cards rounded-lg p-4">
            <p class="text-2xl font-bold text-text-primary">{{ quiz.passing_score }}%</p>
            <p class="text-sm text-text-secondary">Passing Score</p>
          </div>
          <div class="bg-brand-navbar-cards rounded-lg p-4">
            <p class="text-2xl font-bold text-text-primary">{{ quiz.topic?.name }}</p>
            <p class="text-sm text-text-secondary">Topic</p>
          </div>
          <div class="bg-brand-navbar-cards rounded-lg p-4">
            <p class="text-2xl font-bold text-text-primary">{{ quiz.topic?.subject?.name }}</p>
            <p class="text-sm text-text-secondary">Subject</p>
          </div>
        </div>
        <div class="mt-8">
          <button @click="startQuiz" class="btn-primary px-8 py-3 text-lg" aria-label="Start quiz">
            Start Quiz
          </button>
        </div>
      </div>
    </div>

    <div v-else-if="quizStarted && !quizSubmitted">
      <form @submit.prevent="submitQuiz" class="space-y-6">
        <div class="bg-brand-navbar-cards-alt border border-brand-borders rounded-xl p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-text-primary">{{ quiz.title }}</h2>
            <div class="text-sm text-text-secondary">Question {{ currentQuestionIndex + 1 }} of {{ quiz.questions?.length || 0 }}</div>
          </div>
          <div class="progress-bar mb-6" role="progressbar" :aria-valuenow="((currentQuestionIndex + 1) / (quiz.questions?.length || 1)) * 100" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-fill" :style="{ width: `${((currentQuestionIndex + 1) / (quiz.questions?.length || 1)) * 100}%` }"></div>
          </div>

          <div v-for="(question, index) in quiz.questions" :key="question.id" v-show="index === currentQuestionIndex" class="space-y-4">
            <p class="text-lg font-medium text-text-primary">{{ question.question }}</p>
            <div class="space-y-3" role="radiogroup" :aria-label="`Question ${index + 1} options`">
              <label v-for="option in ['A', 'B', 'C', 'D']" :key="option" class="flex items-center space-x-3 p-4 border-2 rounded-lg cursor-pointer hover:bg-brand-navbar-cards transition-colors" :class="{ 'border-primary bg-primary-light': answers[question.id] === option }">
                <input type="radio" :value="option" v-model="answers[question.id]" :name="`question-${question.id}`" class="h-4 w-4 text-primary border-brand-borders focus:ring-primary/50" :aria-label="`Option ${option}`" />
                <span class="font-medium text-text-primary">{{ option }}.</span>
                <span class="text-text-secondary">{{ question[`option_${option.toLowerCase()}`] }}</span>
              </label>
            </div>
          </div>

          <div class="flex justify-between pt-4 border-t border-brand-borders">
            <button type="button" @click="prevQuestion" :disabled="currentQuestionIndex === 0" class="btn-secondary" aria-label="Previous question">Previous</button>
            <div class="flex items-center space-x-2">
              <button type="button" @click="nextQuestion" v-if="currentQuestionIndex < (quiz.questions?.length || 1) - 1" class="btn-primary" aria-label="Next question">Next</button>
              <button type="submit" v-else class="btn-primary bg-status-success hover:bg-status-success-hover" aria-label="Submit quiz">Submit Quiz</button>
            </div>
          </div>
        </div>
      </form>
    </div>

    <div v-else-if="quizSubmitted && attempt">
      <div class="bg-brand-navbar-cards-alt border border-brand-borders rounded-xl p-8">
        <div class="text-center mb-8">
          <div class="inline-flex items-center justify-center w-24 h-24 rounded-full mb-4" :class="attempt.percentage >= (quiz.passing_score || 70) ? 'bg-status-success-light' : 'bg-status-urgent-light'">
            <svg class="h-12 w-12" :class="attempt.percentage >= (quiz.passing_score || 70) ? 'text-status-success' : 'text-status-urgent'" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path v-if="attempt.percentage >= (quiz.passing_score || 70)" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <h1 class="text-2xl font-bold text-text-primary">{{ attempt.percentage >= (quiz.passing_score || 70) ? 'Quiz Passed!' : 'Quiz Not Passed' }}</h1>
          <p class="mt-2 text-text-secondary">You scored {{ attempt.percentage }}% ({{ attempt.score }} out of {{ attempt.total_questions }} correct)</p>
          <p class="mt-1 text-sm text-text-secondary">Passing score: {{ quiz.passing_score }}%</p>
        </div>

        <div class="space-y-4" role="list" aria-label="Quiz results">
          <div v-for="(answer, index) in attempt.answers" :key="answer.id" class="p-4 rounded-lg" :class="answer.is_correct ? 'bg-status-success-light border border-status-success/30' : 'bg-status-urgent-light border border-status-urgent/30'">
            <div class="flex items-center justify-between mb-2">
              <span class="font-medium text-text-primary">Question {{ index + 1 }}</span>
              <span class="px-2 py-1 text-xs font-medium rounded-full" :class="answer.is_correct ? 'bg-status-success-light text-status-success' : 'bg-status-urgent-light text-status-urgent'">{{ answer.is_correct ? 'Correct' : 'Incorrect' }}</span>
            </div>
            <p class="text-sm text-text-secondary mb-2">{{ quiz.questions?.find(q => q.id === answer.question_id)?.question }}</p>
            <div class="text-sm">
              <p v-if="answer.selected_answer"><span class="font-medium text-text-primary">Your answer:</span> {{ answer.selected_answer }}</p>
              <p v-else><span class="font-medium text-text-primary">Your answer:</span> <span class="text-text-secondary">Not answered</span></p>
              <p v-if="!answer.is_correct" class="text-text-secondary">Review this question and try the quiz again to reinforce the concept.</p>
            </div>
          </div>
        </div>

        <div class="mt-8 flex justify-center space-x-4">
          <router-link to="/quizzes" class="btn-secondary">Back to Quizzes</router-link>
          <button @click="retakeQuiz" class="btn-primary">Retake Quiz</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useStudentStore } from '@/stores/student';
import type { Quiz, Question, QuizAttempt } from '@/types';

const route = useRoute();
const router = useRouter();
const studentStore = useStudentStore();

const quiz = ref<Quiz | null>(null);
const loading = ref(true);
const quizStarted = ref(false);
const quizSubmitted = ref(false);
const currentQuestionIndex = ref(0);
const answers = ref<Record<number, string>>({});
const attempt = ref<QuizAttempt | null>(null);
const startedAt = ref(new Date().toISOString());

onMounted(async () => {
  await loadQuiz();
});

async function loadQuiz() {
  loading.value = true;
  try {
    quiz.value = await studentStore.fetchQuiz(Number(route.params.id));
  } catch (err) {
    console.error('Failed to load quiz:', err);
  } finally {
    loading.value = false;
  }
}

function startQuiz() {
  quizStarted.value = true;
  startedAt.value = new Date().toISOString();
  answers.value = {};
  currentQuestionIndex.value = 0;
}

function nextQuestion() {
  if (currentQuestionIndex.value < (quiz.value?.questions?.length || 1) - 1) {
    currentQuestionIndex.value++;
  }
}

function prevQuestion() {
  if (currentQuestionIndex.value > 0) {
    currentQuestionIndex.value--;
  }
}

async function submitQuiz() {
  if (Object.keys(answers.value).length === 0) return;

  try {
    attempt.value = await studentStore.submitQuiz(quiz.value!.id, answers.value, startedAt.value);
    quizSubmitted.value = true;
  } catch (err) {
    console.error('Failed to submit quiz:', err);
  }
}

function retakeQuiz() {
  quizStarted.value = false;
  quizSubmitted.value = false;
  currentQuestionIndex.value = 0;
  answers.value = {};
  attempt.value = null;
}
</script>