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
        <p v-if="quiz.submission_retry_safe === false" class="mt-4 text-sm text-text-secondary" role="status">
          If submission fails, check quiz history before retrying to avoid a duplicate result.
        </p>
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
      <form
        @submit.prevent="submitQuiz()"
        @contextmenu.prevent
        @auxclick.prevent
        @mousedown.middle.prevent
        @copy.prevent
        @cut.prevent
        @paste.prevent
        @dragstart.prevent
        class="space-y-6 quiz-taking-form"
      >
        <div class="bg-brand-navbar-cards-alt border border-brand-borders rounded-xl p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-text-primary">{{ quiz.title }}</h2>
            <div class="text-sm text-text-secondary">Question {{ currentQuestionIndex + 1 }} of {{ quiz.questions?.length || 0 }}</div>
          </div>
          <div class="progress-bar mb-6" role="progressbar" :aria-valuenow="((currentQuestionIndex + 1) / (quiz.questions?.length || 1)) * 100" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-fill" :style="{ width: `${((currentQuestionIndex + 1) / (quiz.questions?.length || 1)) * 100}%` }"></div>
          </div>

          <div v-for="(question, index) in quiz.questions" :key="question.id" v-show="index === currentQuestionIndex" class="space-y-4">
            <p class="quiz-question-content text-lg font-medium text-text-primary">{{ question.question }}</p>
            <div class="quiz-question-content space-y-3" role="radiogroup" :aria-label="`Question ${index + 1} options`">
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
              <button type="submit" v-else :disabled="isSubmitting" class="btn-primary bg-status-success hover:bg-status-success-hover" aria-label="Submit quiz">
                {{ isSubmitting ? 'Submitting...' : 'Submit Quiz' }}
              </button>
            </div>
            <p v-if="submissionError" class="input-error-message" role="alert">{{ submissionError }}</p>
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
            <p class="text-sm text-text-secondary mb-2">{{ answer.question?.question || quiz.questions?.find(q => q.id === answer.question_id)?.question }}</p>
            <div class="text-sm">
              <p><span class="font-medium text-text-primary">Your Answer:</span> {{ formatAnswer(answer, answer.selected_answer) }}</p>
              <p><span class="font-medium text-text-primary">Correct Answer:</span> {{ formatAnswer(answer, answer.question?.correct_answer || null) }}</p>
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

  <Teleport to="body">
    <div v-if="warningOpen" class="fixed inset-0 z-[120] flex items-center justify-center bg-black/70 p-4" role="presentation">
      <section class="w-full max-w-xl rounded-3xl border-2 border-status-warning/80 bg-brand-navbar-cards-alt p-6 shadow-2xl ring-4 ring-status-warning/20 sm:p-8" role="alertdialog" aria-modal="true" aria-labelledby="quiz-warning-title" aria-describedby="quiz-warning-message">
        <div class="flex flex-col items-center text-center">
          <div class="mb-4 flex h-18 w-18 items-center justify-center rounded-full bg-status-warning-light text-3xl">⚠️</div>
          <h2 id="quiz-warning-title" class="text-2xl font-extrabold text-text-primary">Warning</h2>
          <p id="quiz-warning-message" class="mt-4 text-base leading-7 text-text-primary">
            You have left the quiz page. Please remain on this page while taking the quiz.
          </p>
          <p class="mt-3 text-base leading-7 text-text-secondary">
            If you leave again, your quiz will be submitted automatically, and your current answers will be saved.
          </p>
          <button @click="acknowledgeWarning" class="mt-7 w-full rounded-xl bg-primary px-5 py-3 text-base font-semibold text-white shadow-lg transition hover:bg-primary/90">I Understand</button>
        </div>
      </section>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useStudentStore } from '@/stores/student';
import type { Quiz, QuizAnswer, QuizAttempt } from '@/types';

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
const isSubmitting = ref(false);
const submissionError = ref('');
const warningOpen = ref(false);
const awayIncidentCount = ref(0);
const wasAway = ref(false);

onMounted(async () => {
  document.addEventListener('visibilitychange', handleVisibilityChange);
  window.addEventListener('blur', handleFocusLoss);
  window.addEventListener('focus', handleFocusReturn);
  await loadQuiz();
});

onBeforeUnmount(() => {
  document.removeEventListener('visibilitychange', handleVisibilityChange);
  window.removeEventListener('blur', handleFocusLoss);
  window.removeEventListener('focus', handleFocusReturn);
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
  isSubmitting.value = false;
  submissionError.value = '';
  warningOpen.value = false;
  awayIncidentCount.value = 0;
  wasAway.value = false;
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
  if (isSubmitting.value || quizSubmitted.value || !quiz.value) return;

  isSubmitting.value = true;
  submissionError.value = '';
  try {
    attempt.value = await studentStore.submitQuiz(quiz.value!.id, answers.value, startedAt.value, quiz.value?.submission_id);
    quizSubmitted.value = true;
    warningOpen.value = false;
  } catch (err) {
    console.error('Failed to submit quiz:', err);
    submissionError.value = quiz.value?.submission_retry_safe === false
      ? 'Your quiz could not be submitted. Check quiz history before retrying to avoid a duplicate result.'
      : 'Your quiz could not be submitted. Check your connection and try again.';
  } finally {
    isSubmitting.value = false;
  }
}

function handleFocusLoss() {
  if (!quizStarted.value || quizSubmitted.value || isSubmitting.value || wasAway.value) return;

  wasAway.value = true;
  awayIncidentCount.value++;
  if (awayIncidentCount.value === 1) {
    warningOpen.value = true;
    return;
  }

  void submitQuiz();
}

function handleVisibilityChange() {
  if (document.hidden) handleFocusLoss();
  else handleFocusReturn();
}

function handleFocusReturn() {
  wasAway.value = false;
}

function acknowledgeWarning() {
  warningOpen.value = false;
}

function formatAnswer(answer: QuizAnswer, letter: string | null) {
  if (!letter) return 'Not answered';
  const question = answer.question;
  const optionKey = `option_${letter.toLowerCase()}` as 'option_a' | 'option_b' | 'option_c' | 'option_d';
  return `${letter}. ${question?.[optionKey] || ''}`.trim();
}

async function retakeQuiz() {
  await loadQuiz();
  quizStarted.value = false;
  quizSubmitted.value = false;
  currentQuestionIndex.value = 0;
  answers.value = {};
  attempt.value = null;
}
</script>

<style scoped>
.quiz-question-content,
.quiz-question-content * {
  -webkit-user-select: none;
  user-select: none;
  -webkit-touch-callout: none;
}
</style>
