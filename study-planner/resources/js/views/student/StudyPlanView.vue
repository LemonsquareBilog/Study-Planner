<template>
  <div class="space-y-8">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-text-primary">Study Plan</h1>
        <p class="mt-1 text-sm text-text-secondary">View and manage your study schedule for the selected range.</p>
      </div>

      <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="flex items-center gap-2 rounded-xl border border-brand-borders bg-brand-navbar-cards-alt px-3 py-2">
          <input type="date" v-model="planFrom" class="input-field-glass w-auto min-w-[132px]" @change="onDateChange" />
          <span class="text-sm text-text-secondary">to</span>
          <input type="date" v-model="planTo" class="input-field-glass w-auto min-w-[132px]" @change="onDateChange" />
        </div>
        <button @click="generatePlan" :disabled="generating" class="btn-primary" aria-label="Generate study plan">
          <span v-if="generating" class="flex items-center">
            <svg class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
            </svg>
            Generating...
          </span>
          <span v-else>Generate Plan</span>
        </button>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-12" role="status" aria-label="Loading study plan">
      <svg class="h-8 w-8 animate-spin text-primary" fill="none" viewBox="0 0 24 24" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
      </svg>
    </div>

    <div v-else class="space-y-6">
      <section class="space-y-4" aria-labelledby="tasks-in-plan-heading">
        <div class="flex items-center justify-between">
          <h2 id="tasks-in-plan-heading" class="text-lg font-semibold text-text-primary">Tasks in your plan</h2>
          <router-link to="/tasks" class="text-sm font-medium text-secondary hover:text-secondary-hover">Manage tasks</router-link>
        </div>

        <div v-if="tasksInPlan.length" class="grid grid-cols-1 gap-4 lg:grid-cols-2">
          <article
            v-for="task in tasksInPlan"
            :key="task.id"
            class="group relative cursor-pointer overflow-hidden rounded-2xl border border-brand-borders bg-brand-navbar-cards-alt p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/50 hover:shadow-md"
            role="button"
            tabindex="0"
            @click="openTask(task)"
            @keydown.enter.prevent="openTask(task)"
            @keydown.space.prevent="openTask(task)"
          >
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0 flex-1">
                <h3 class="text-lg font-semibold text-text-primary">{{ task.title }}</h3>
                <p class="mt-1 text-sm text-text-secondary">{{ task.subject?.name }}<span v-if="task.topic?.name"> · {{ task.topic.name }}</span></p>
              </div>
              <span class="shrink-0 rounded-full px-2 py-1 text-[11px] font-semibold uppercase tracking-wide" :class="priorityClass(task.priority)">{{ task.priority }}</span>
            </div>

            <p v-if="task.description" class="mt-3 line-clamp-2 text-sm text-text-secondary">{{ task.description }}</p>

            <div class="mt-4 grid grid-cols-2 gap-2 text-xs text-text-secondary sm:grid-cols-3">
              <div class="rounded-lg bg-brand-navbar-cards px-2 py-1.5">
                <span class="block text-[10px] uppercase tracking-wide text-text-secondary/80">Date</span>
                <span class="mt-1 block font-medium text-text-primary">{{ formatShortDate(task.start_date || task.deadline) }}</span>
              </div>
              <div class="rounded-lg bg-brand-navbar-cards px-2 py-1.5">
                <span class="block text-[10px] uppercase tracking-wide text-text-secondary/80">Duration</span>
                <span class="mt-1 block font-medium text-text-primary">{{ task.estimated_minutes }} min</span>
              </div>
              <div class="rounded-lg bg-brand-navbar-cards px-2 py-1.5">
                <span class="block text-[10px] uppercase tracking-wide text-text-secondary/80">Status</span>
                <span class="mt-1 block font-medium text-text-primary capitalize">{{ task.status.replace('_', ' ') }}</span>
              </div>
            </div>

            <div class="mt-4 flex items-center justify-between gap-3 border-t border-brand-borders pt-3">
              <div class="flex-1">
                <div class="mb-1 flex items-center justify-between text-[11px] uppercase tracking-wide text-text-secondary">
                  <span>Progress</span>
                  <span>{{ getTaskProgress(task) }}%</span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-brand-borders">
                  <div class="h-full rounded-full bg-primary transition-all" :style="{ width: `${getTaskProgress(task)}%` }"></div>
                </div>
              </div>

              <button
                type="button"
                class="btn-primary shrink-0 px-3 py-2 text-xs font-semibold"
                @click.stop="markTaskDone(task)"
                :disabled="task.status === 'completed'"
              >
                {{ task.status === 'completed' ? 'Completed' : 'Done' }}
              </button>
            </div>
          </article>
        </div>
        <p v-else class="rounded-xl border border-dashed border-brand-borders bg-brand-navbar-cards-alt p-5 text-sm text-text-secondary">No tasks in the selected range. Adjust the dates or add new tasks.</p>
      </section>

      <div v-if="groupedSessions.length === 0" class="rounded-2xl border border-dashed border-brand-borders bg-brand-navbar-cards-alt p-10 text-center">
        <svg class="mx-auto h-12 w-12 text-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-text-primary">No study sessions scheduled</h3>
        <p class="mt-1 text-sm text-text-secondary">Generate a study plan to create sessions based on your tasks and preferences.</p>
        <button @click="generatePlan" :disabled="generating" class="mt-4 btn-primary">Generate Plan</button>
      </div>

      <div v-else class="space-y-6" role="list">
        <div v-for="group in groupedSessions" :key="group.date" class="overflow-hidden rounded-2xl border border-brand-borders bg-brand-navbar-cards-alt" role="listitem">
          <div class="flex items-center justify-between border-b border-brand-borders bg-brand-navbar-cards px-5 py-4">
            <h3 class="text-lg font-semibold text-text-primary">{{ formatDayHeader(group.date) }}</h3>
            <span class="text-sm text-text-secondary">{{ group.sessions.length }} sessions · {{ group.totalMinutes }} min</span>
          </div>

          <div class="divide-y divide-brand-borders">
            <div v-for="session in group.sessions" :key="session.id" class="flex flex-col gap-3 px-5 py-4 hover:bg-brand-navbar-cards md:flex-row md:items-center md:justify-between">
              <div class="flex min-w-0 flex-1 items-start gap-3">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl" :class="subjectColor(session.subject?.color)" aria-hidden="true">
                  <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>

                <div class="min-w-0 flex-1">
                  <p class="truncate text-sm font-medium text-text-primary">{{ session.task?.title || session.subject?.name }}</p>
                  <p class="text-sm text-text-secondary">{{ session.topic?.name || 'General review' }}</p>
                  <p v-if="session.task?.description" class="mt-1 text-xs text-text-secondary">{{ session.task.description }}</p>
                  <p v-if="session.task" class="mt-1 text-xs text-text-secondary">
                    {{ formatTaskDate(session.task.start_date || session.task.created_at || session.task.deadline) }} · {{ session.task.priority }} priority · {{ session.task.estimated_minutes }} min
                  </p>
                </div>
              </div>

              <div class="flex flex-wrap items-center gap-2 text-sm">
                <span class="text-text-secondary">{{ formatTime(session.start_time) }} - {{ formatTime(session.end_time) }}</span>
                <span class="inline-flex shrink-0 items-center whitespace-nowrap rounded-full px-2 py-1 text-[11px] font-medium" :class="statusClass(session.status)">{{ session.status.replace('_', ' ') }}</span>
                <span class="text-text-secondary">{{ session.duration_minutes }} min</span>
                <button v-if="session.status === 'scheduled'" @click="startSession(session)" class="btn-primary px-3 py-1.5 text-xs">Start</button>
                <button v-else-if="session.status === 'in_progress'" @click="completeSession(session)" class="btn-primary bg-status-success px-3 py-1.5 text-xs hover:bg-status-success-hover">Complete</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <Teleport to="body">
    <div v-if="selectedTask" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
      <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-3xl border border-brand-borders bg-brand-navbar-cards-alt p-6 shadow-2xl">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-secondary">Study task</p>
            <h2 class="mt-2 text-2xl font-bold text-text-primary">{{ selectedTask.title }}</h2>
          </div>
          <button type="button" class="btn-secondary px-3 py-2 text-sm" @click="closeTaskDetail">Close</button>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
          <div class="rounded-2xl border border-brand-borders bg-brand-navbar-cards p-4">
            <p class="text-xs uppercase tracking-wide text-text-secondary">Subject</p>
            <p class="mt-2 text-base font-semibold text-text-primary">{{ selectedTask.subject?.name || 'General' }}</p>
          </div>
          <div class="rounded-2xl border border-brand-borders bg-brand-navbar-cards p-4">
            <p class="text-xs uppercase tracking-wide text-text-secondary">Topic</p>
            <p class="mt-2 text-base font-semibold text-text-primary">{{ selectedTask.topic?.name || 'General review' }}</p>
          </div>
          <div class="rounded-2xl border border-brand-borders bg-brand-navbar-cards p-4">
            <p class="text-xs uppercase tracking-wide text-text-secondary">Scheduled</p>
            <p class="mt-2 text-base font-semibold text-text-primary">{{ formatDateTime(selectedTask.start_date || selectedTask.deadline) }}</p>
          </div>
          <div class="rounded-2xl border border-brand-borders bg-brand-navbar-cards p-4">
            <p class="text-xs uppercase tracking-wide text-text-secondary">Priority</p>
            <p class="mt-2 text-base font-semibold text-text-primary capitalize">{{ selectedTask.priority }}</p>
          </div>
          <div class="rounded-2xl border border-brand-borders bg-brand-navbar-cards p-4">
            <p class="text-xs uppercase tracking-wide text-text-secondary">Duration</p>
            <p class="mt-2 text-base font-semibold text-text-primary">{{ selectedTask.estimated_minutes }} minutes</p>
          </div>
          <div class="rounded-2xl border border-brand-borders bg-brand-navbar-cards p-4">
            <p class="text-xs uppercase tracking-wide text-text-secondary">Completion</p>
            <p class="mt-2 text-base font-semibold text-text-primary capitalize">{{ selectedTask.status.replace('_', ' ') }}</p>
          </div>
        </div>

        <div class="mt-6 rounded-2xl border border-brand-borders bg-brand-navbar-cards p-5">
          <p class="text-xs uppercase tracking-wide text-text-secondary">Description</p>
          <p class="mt-3 text-sm leading-7 text-text-primary">
            {{ selectedTask.description || 'No detailed description provided for this task yet.' }}
          </p>
        </div>

        <div class="mt-6 rounded-2xl border border-brand-borders bg-brand-navbar-cards p-5">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <p class="text-xs uppercase tracking-wide text-text-secondary">Study session</p>
              <p class="mt-1 text-sm text-text-secondary" v-if="timerModeLabel">{{ timerModeLabel }}</p>
            </div>
            <div class="text-3xl font-bold text-text-primary">{{ formatCountdown(activeTaskCountdownMs) }}</div>
          </div>

          <div class="mt-4 flex flex-wrap gap-2">
            <button v-if="selectedTaskTimer?.status !== 'running' && selectedTaskTimer?.status !== 'completed'" type="button" class="btn-primary" @click="startSelectedTaskTimer">Start Task</button>
            <button v-else-if="selectedTaskTimer?.status === 'running'" type="button" class="btn-secondary" @click="pauseSelectedTaskTimer">Pause</button>
            <button v-if="selectedTaskTimer?.status === 'paused'" type="button" class="btn-primary" @click="resumeSelectedTaskTimer">Resume</button>
            <button type="button" class="btn-secondary" @click="takeBreak">Take a Break</button>
            <button type="button" class="btn-primary bg-status-success hover:bg-status-success-hover" @click="finishSelectedTask">Finish Task</button>
          </div>

          <div v-if="timerStatusMessage" class="mt-3 text-sm font-medium" :class="timerStatusMessage.includes('paused') ? 'text-status-warning' : (timerStatusMessage.includes('complete') ? 'text-status-success' : 'text-secondary')">
            {{ timerStatusMessage }}
          </div>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-2">
          <div class="rounded-2xl border border-brand-borders bg-brand-navbar-cards p-5">
            <p class="text-xs uppercase tracking-wide text-text-secondary">Related resources</p>
            <ul v-if="relatedResources.length" class="mt-3 space-y-3">
              <li v-for="resource in relatedResources.slice(0, 3)" :key="resource.id" class="rounded-xl border border-brand-borders bg-brand-navbar-cards-alt p-3">
                <p class="font-medium text-text-primary">{{ resource.title }}</p>
                <p class="mt-1 text-sm text-text-secondary">{{ resource.resource_type }} · {{ resource.difficulty }}</p>
                <a v-if="resource.url" :href="resource.url" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex text-sm font-medium text-secondary hover:text-secondary-hover">Open resource</a>
              </li>
            </ul>
            <p v-else class="mt-3 text-sm text-text-secondary">No related resources are assigned to this topic yet.</p>
          </div>

          <div class="rounded-2xl border border-brand-borders bg-brand-navbar-cards p-5">
            <p class="text-xs uppercase tracking-wide text-text-secondary">Related quiz</p>
            <div v-if="relatedQuiz" class="mt-3">
              <p class="font-medium text-text-primary">{{ relatedQuiz.title }}</p>
              <p class="mt-1 text-sm text-text-secondary">{{ relatedQuiz.description || 'Review quiz for this topic.' }}</p>
              <router-link :to="`/quizzes/${relatedQuiz.id}`" class="mt-3 inline-flex text-sm font-medium text-secondary hover:text-secondary-hover" @click="closeTaskDetail">Open quiz</router-link>
            </div>
            <p v-else class="mt-3 text-sm text-text-secondary">No quiz is linked to this task currently.</p>
          </div>
        </div>
      </div>
    </div>
  </Teleport>

  <Teleport to="body">
    <div v-if="inAppNotification" class="pointer-events-none fixed inset-x-0 bottom-4 z-[60] flex justify-center px-4">
      <div class="max-w-md rounded-2xl border border-brand-borders bg-brand-navbar-cards-alt px-4 py-3 shadow-lg">
        <p class="text-sm font-semibold text-text-primary">{{ inAppNotification.title }}</p>
        <p class="mt-1 text-sm text-text-secondary">{{ inAppNotification.message }}</p>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { useStudentStore } from '@/stores/student';
import type { StudySession, Task, Resource, Quiz } from '@/types';

const studentStore = useStudentStore();

const sessions = ref<StudySession[]>([]);
const loading = ref(true);
const generating = ref(false);
const planFrom = ref(new Date().toISOString().split('T')[0]);
const planTo = ref(new Date(Date.now() + 14 * 86400000).toISOString().split('T')[0]);
const selectedTask = ref<Task | null>(null);
const inAppNotification = ref<{ title: string; message: string } | null>(null);
const timerTick = ref(Date.now());

type TimerMode = 'study' | 'break';
type TimerStatus = 'idle' | 'running' | 'paused' | 'completed';

type TimerState = {
  id: number;
  totalMs: number;
  remainingMs: number;
  startedAt: number | null;
  status: TimerStatus;
  mode: TimerMode;
};

const timerStorageKey = 'study-plan-timer-state-v1';
const timerStates = ref<Record<number, TimerState>>({});
let timerInterval: number | null = null;

onMounted(async () => {
  loadSavedTimerState();
  timerInterval = window.setInterval(() => {
    timerTick.value = Date.now();
    const activeTimer = getActiveSelectedTaskTimer();
    if (activeTimer && activeTimer.status === 'running' && getTimerRemainingMs(activeTimer) <= 0) {
      void completeStudyTimer();
    }
  }, 250);

  await Promise.all([
    fetchSessions(),
    studentStore.fetchTasks({ from: planFrom.value, to: planTo.value }).catch(err => console.error('Failed to load study plan tasks:', err)),
    studentStore.fetchQuizzes().catch(err => console.error('Failed to load quizzes:', err)),
    studentStore.fetchRecommendations().catch(err => console.error('Failed to load recommendations:', err)),
  ]);
});

onBeforeUnmount(() => {
  if (timerInterval) {
    window.clearInterval(timerInterval);
  }
});

function loadSavedTimerState() {
  try {
    const raw = localStorage.getItem(timerStorageKey);
    if (!raw) return;
    const parsed = JSON.parse(raw) as Record<number, TimerState>;
    timerStates.value = parsed ?? {};
  } catch (error) {
    console.warn('Failed to restore saved study timers:', error);
  }
}

function persistTimerState() {
  try {
    localStorage.setItem(timerStorageKey, JSON.stringify(timerStates.value));
  } catch (error) {
    console.warn('Failed to save study timer state:', error);
  }
}

function isDateInRange(dateValue?: string | null): boolean {
  if (!dateValue) return false;
  const normalized = dateValue.slice(0, 10);
  return normalized >= planFrom.value && normalized <= planTo.value;
}

function getTaskProgress(task: Task): number {
  if (task.status === 'completed') return 100;
  if (task.status === 'in_progress') return 60;
  if (task.status === 'cancelled') return 0;
  return 25;
}

function formatDateTime(dateString?: string | null): string {
  if (!dateString) return 'Not scheduled';
  const value = new Date(dateString.includes('T') ? dateString : `${dateString}T00:00:00`);
  return value.toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  });
}

function formatShortDate(dateString?: string | null): string {
  if (!dateString) return 'TBD';
  const value = new Date(dateString.includes('T') ? dateString : `${dateString}T00:00:00`);
  return value.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function formatTaskDate(dateString?: string | null): string {
  if (!dateString) return 'TBD';
  const value = new Date(dateString.includes('T') ? dateString : `${dateString}T00:00:00`);
  return value.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

async function fetchSessions() {
  loading.value = true;
  try {
    sessions.value = await studentStore.fetchStudySessions({ from: planFrom.value, to: planTo.value });
  } catch (err) {
    console.error('Failed to load study sessions:', err);
  } finally {
    loading.value = false;
  }
}

async function generatePlan() {
  generating.value = true;
  try {
    await studentStore.generateStudyPlan(planFrom.value, planTo.value);
    await fetchSessions();
    await studentStore.fetchTasks({ from: planFrom.value, to: planTo.value });
  } catch (err) {
    console.error('Failed to generate study plan:', err);
  } finally {
    generating.value = false;
  }
}

function onDateChange() {
  if (planFrom.value && planTo.value && planFrom.value <= planTo.value) {
    void fetchSessions();
    void studentStore.fetchTasks({ from: planFrom.value, to: planTo.value });
  }
}

const groupedSessions = computed(() => {
  const groups: Record<string, StudySession[]> = {};
  sessions.value.forEach(session => {
    if (!groups[session.scheduled_date]) groups[session.scheduled_date] = [];
    groups[session.scheduled_date].push(session);
  });

  return Object.entries(groups)
    .map(([date, grouped]) => ({
      date,
      sessions: grouped.sort((a, b) => a.start_time.localeCompare(b.start_time)),
      totalMinutes: grouped.reduce((sum, session) => sum + session.duration_minutes, 0),
    }))
    .sort((a, b) => a.date.localeCompare(b.date));
});

const tasksInPlan = computed(() => {
  return studentStore.tasks.filter(task => {
    if (task.status === 'cancelled') return false;
    const matchesDateRange =
      isDateInRange(task.start_date) ||
      isDateInRange(task.deadline) ||
      sessions.value.some(session => session.task_id === task.id && isDateInRange(session.scheduled_date));

    return matchesDateRange;
  });
});

const selectedTaskSession = computed(() => {
  if (!selectedTask.value) return null;
  return sessions.value.find(session => session.task_id === selectedTask.value?.id) ?? null;
});

const selectedTaskDurationMinutes = computed(() => {
  if (!selectedTask.value) return 45;
  return selectedTaskSession.value?.duration_minutes ?? selectedTask.value.estimated_minutes ?? 45;
});

function getTimerStateForId(id: number): TimerState {
  if (!timerStates.value[id]) {
    timerStates.value[id] = {
      id,
      totalMs: selectedTaskDurationMinutes.value * 60_000,
      remainingMs: selectedTaskDurationMinutes.value * 60_000,
      startedAt: null,
      status: 'idle',
      mode: 'study',
    };
  }

  const state = timerStates.value[id];
  if (state.totalMs <= 0) {
    state.totalMs = selectedTaskDurationMinutes.value * 60_000;
    state.remainingMs = state.totalMs;
  }
  return state;
}

function getActiveSelectedTaskTimer(): TimerState | null {
  if (!selectedTask.value) return null;
  const timerId = selectedTaskSession.value?.id ?? selectedTask.value.id;
  return getTimerStateForId(timerId);
}

const selectedTaskTimer = computed(() => getActiveSelectedTaskTimer());

const activeTaskCountdownMs = computed(() => {
  const state = selectedTaskTimer.value;
  if (!state) return 0;
  return getTimerRemainingMs(state);
});

const timerModeLabel = computed(() => {
  const state = selectedTaskTimer.value;
  if (!state) return 'Study time';
  return state.mode === 'break' ? 'Break session' : 'Study session';
});

const timerStatusMessage = computed(() => {
  const state = selectedTaskTimer.value;
  if (!state) return 'No active timer';
  if (state.status === 'running') return state.mode === 'break' ? 'Break in progress' : 'Study session in progress';
  if (state.status === 'paused') return state.mode === 'break' ? 'Break paused' : 'Study session paused';
  if (state.status === 'completed') return state.mode === 'break' ? 'Break complete' : 'Study session complete';
  return state.mode === 'break' ? 'Break ready' : 'Study session ready';
});

function getTimerRemainingMs(state: TimerState): number {
  if (!state) return 0;
  if (state.status === 'running' && state.startedAt) {
    return Math.max(0, state.totalMs - (Date.now() - state.startedAt));
  }
  if (state.status === 'completed') {
    return 0;
  }
  return Math.max(0, state.remainingMs);
}

function formatCountdown(ms: number): string {
  const totalSeconds = Math.max(0, Math.ceil(ms / 1000));
  const minutes = Math.floor(totalSeconds / 60);
  const seconds = totalSeconds % 60;
  return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

function openTask(task: Task) {
  selectedTask.value = task;
}

function closeTaskDetail() {
  selectedTask.value = null;
}

function startSelectedTaskTimer() {
  const state = selectedTaskTimer.value;
  if (!state) return;
  const currentRemaining = getTimerRemainingMs(state);
  state.mode = 'study';
  state.status = 'running';
  state.startedAt = Date.now() - (state.totalMs - currentRemaining);
  state.remainingMs = currentRemaining;
  if (selectedTaskSession.value) {
    void studentStore.updateStudySessionStatus(selectedTaskSession.value.id, 'in_progress').catch(err => console.error('Failed to start session:', err));
  }
  persistTimerState();
}

function pauseSelectedTaskTimer() {
  const state = selectedTaskTimer.value;
  if (!state || state.status !== 'running') return;
  state.remainingMs = getTimerRemainingMs(state);
  state.startedAt = null;
  state.status = 'paused';
  persistTimerState();
}

function resumeSelectedTaskTimer() {
  const state = selectedTaskTimer.value;
  if (!state || state.status !== 'paused') return;
  const currentRemaining = getTimerRemainingMs(state);
  state.mode = 'study';
  state.status = 'running';
  state.startedAt = Date.now() - (state.totalMs - currentRemaining);
  state.remainingMs = currentRemaining;
  persistTimerState();
}

async function finishSelectedTask() {
  const state = selectedTaskTimer.value;
  if (!state) return;
  state.status = 'completed';
  state.startedAt = null;
  state.remainingMs = 0;
  persistTimerState();

  if (selectedTaskSession.value) {
    try {
      await studentStore.updateStudySessionStatus(selectedTaskSession.value.id, 'completed');
    } catch (err) {
      console.error('Failed to complete study session:', err);
    }
  }

  if (selectedTask.value) {
    await markTaskDone(selectedTask.value).catch(err => console.error('Failed to complete task:', err));
  }

  showInAppNotification('Study Session Complete', 'Your scheduled study session has finished.');
  void playCompletionBell();
}

async function completeStudyTimer() {
  const state = selectedTaskTimer.value;
  if (!state || state.status === 'completed') return;
  state.status = 'completed';
  state.startedAt = null;
  state.remainingMs = 0;
  persistTimerState();

  if (selectedTaskSession.value) {
    try {
      await studentStore.updateStudySessionStatus(selectedTaskSession.value.id, 'completed');
    } catch (err) {
      console.error('Failed to mark study session as complete:', err);
    }
  }

  showInAppNotification('Study Session Complete', 'Your scheduled study session has finished.');
  void playCompletionBell();
}

function takeBreak() {
  const state = selectedTaskTimer.value;
  if (!state) return;
  state.mode = 'break';
  state.totalMs = 5 * 60_000;
  state.remainingMs = state.totalMs;
  state.startedAt = Date.now();
  state.status = 'running';
  persistTimerState();
}

function showInAppNotification(title: string, message: string) {
  inAppNotification.value = { title, message };
  window.setTimeout(() => {
    inAppNotification.value = null;
  }, 6000);
}

async function playCompletionBell() {
  try {
    if ('Notification' in window && Notification.permission === 'granted') {
      new Notification('Study Session Complete', { body: 'Your scheduled study session has finished.' });
    } else if ('Notification' in window && Notification.permission === 'default') {
      await Notification.requestPermission().catch(() => undefined);
      if (Notification.permission === 'granted') {
        new Notification('Study Session Complete', { body: 'Your scheduled study session has finished.' });
      }
    }
  } catch (error) {
    console.warn('Notification API unavailable or blocked:', error);
  }

  try {
    const AudioCtor = window.AudioContext || (window as any).webkitAudioContext;
    if (!AudioCtor) return;
    const audioCtx = new AudioCtor();
    const oscillator = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    oscillator.type = 'sine';
    oscillator.frequency.setValueAtTime(880, audioCtx.currentTime);
    oscillator.frequency.exponentialRampToValueAtTime(660, audioCtx.currentTime + 0.25);
    gain.gain.setValueAtTime(0.0001, audioCtx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.08, audioCtx.currentTime + 0.02);
    gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + 0.4);
    oscillator.connect(gain);
    gain.connect(audioCtx.destination);
    oscillator.start();
    oscillator.stop(audioCtx.currentTime + 0.45);
    await audioCtx.resume();
    setTimeout(() => audioCtx.close().catch(() => undefined), 600);
  } catch (error) {
    console.warn('Audio bell unavailable:', error);
  }
}

async function markTaskDone(task: Task) {
  if (task.status === 'completed') return;
  try {
    const updated = await studentStore.completeTask(task.id);
    const index = studentStore.tasks.findIndex(item => item.id === task.id);
    if (index !== -1) {
      studentStore.tasks[index] = updated;
    }
    if (selectedTask.value?.id === task.id) {
      selectedTask.value = { ...selectedTask.value, status: updated.status, completed_at: updated.completed_at };
    }
  } catch (err) {
    console.error('Failed to mark task as done:', err);
  }
}

function sessionsForTask(taskId: number) {
  return sessions.value.filter(session => session.task_id === taskId).length;
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

function formatDayHeader(dateString: string) {
  const date = new Date(dateString);
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const tomorrow = new Date(today);
  tomorrow.setDate(tomorrow.getDate() + 1);

  if (date.getTime() === today.getTime()) return 'Today';
  if (date.getTime() === tomorrow.getTime()) return 'Tomorrow';
  return date.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric' });
}

function formatTime(timeString: string) {
  const [hours, minutes] = timeString.split(':');
  const hour = parseInt(hours, 10);
  const ampm = hour >= 12 ? 'PM' : 'AM';
  const hour12 = hour % 12 || 12;
  return `${hour12}:${minutes} ${ampm}`;
}

function subjectColor(color: string) {
  const colors: Record<string, string> = {
    blue: 'bg-blue-500',
    green: 'bg-green-500',
    purple: 'bg-purple-500',
    red: 'bg-red-500',
    yellow: 'bg-yellow-500',
    pink: 'bg-pink-500',
    indigo: 'bg-indigo-500',
  };
  return colors[color] || 'bg-gray-500';
}

function statusClass(status: string) {
  const classes = {
    scheduled: 'bg-primary-light text-primary',
    in_progress: 'bg-status-warning-light text-status-warning',
    completed: 'bg-status-success-light text-status-success',
    missed: 'bg-status-urgent-light text-status-urgent',
  };
  return classes[status as keyof typeof classes] || 'bg-brand-borders text-text-secondary';
}

async function startSession(session: StudySession) {
  try {
    const updated = await studentStore.updateStudySessionStatus(session.id, 'in_progress');
    Object.assign(session, updated);
  } catch (err) {
    console.error('Failed to start study session:', err);
  }
}

async function completeSession(session: StudySession) {
  try {
    const updated = await studentStore.updateStudySessionStatus(session.id, 'completed');
    Object.assign(session, updated);
  } catch (err) {
    console.error('Failed to complete study session:', err);
  }
}

const relatedResources = computed<Resource[]>(() => {
  if (!selectedTask.value) return [];
  return studentStore.recommendations.filter(resource => {
    const matchesSubject = resource.subjects?.some(subject => subject.id === selectedTask.value?.subject_id);
    const matchesTopic = resource.topics?.some(topic => topic.id === selectedTask.value?.topic_id);
    return matchesSubject || matchesTopic;
  });
});

const relatedQuiz = computed<Quiz | null>(() => {
  if (!selectedTask.value) return null;
  return studentStore.quizzes.find(quiz => quiz.topic_id === selectedTask.value?.topic_id) ?? null;
});

const timerStatusClass = computed(() => {
  const state = selectedTaskTimer.value;
  if (!state) return 'text-text-secondary';
  return state.status === 'paused' ? 'text-status-warning' : state.status === 'completed' ? 'text-status-success' : 'text-text-primary';
});
</script>