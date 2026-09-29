<template>
  <div class="space-y-8">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-text-primary">Study Plan</h1>
        <p class="mt-1 text-sm text-text-secondary">View and generate your personalized study schedule.</p>
      </div>
      <div class="flex items-center space-x-3">
        <div class="flex items-center space-x-2">
          <input type="date" v-model="planFrom" class="input-field-glass w-auto" @change="onDateChange" />
          <span class="text-text-secondary">to</span>
          <input type="date" v-model="planTo" class="input-field-glass w-auto" @change="onDateChange" />
        </div>
        <button @click="generatePlan" :disabled="generating" class="btn-primary" aria-label="Generate study plan">
          <span v-if="generating" class="flex items-center">
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
            Generating...
          </span>
          <span v-else>Generate Plan</span>
        </button>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-12" role="status" aria-label="Loading study plan">
      <svg class="animate-spin h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
      </svg>
    </div>

    <div v-else-if="groupedSessions.length === 0" class="text-center py-12 bg-brand-navbar-cards-alt border border-brand-borders rounded-xl">
      <svg class="mx-auto h-12 w-12 text-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-text-primary">No study sessions scheduled</h3>
      <p class="mt-1 text-sm text-text-secondary">Generate a study plan to create sessions based on your tasks and preferences.</p>
      <button @click="generatePlan" :disabled="generating" class="mt-4 btn-primary">Generate Plan</button>
    </div>

    <div v-else class="space-y-6" role="list">
      <div v-for="group in groupedSessions" :key="group.date" class="bg-brand-navbar-cards-alt border border-brand-borders rounded-xl overflow-hidden" role="listitem">
        <div class="px-6 py-4 bg-brand-navbar-cards border-b border-brand-borders flex items-center justify-between">
          <h3 class="text-lg font-semibold text-text-primary">{{ formatDayHeader(group.date) }}</h3>
          <span class="text-sm text-text-secondary">{{ group.sessions.length }} sessions · {{ group.totalMinutes }} min</span>
        </div>
        <div class="divide-y divide-brand-borders">
          <div v-for="session in group.sessions" :key="session.id" class="px-6 py-4 flex items-center justify-between hover:bg-brand-navbar-cards">
            <div class="flex items-center space-x-4 min-w-0 flex-1">
              <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" :class="subjectColor(session.subject?.color)" aria-hidden="true">
                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="min-w-0">
                <p class="text-sm font-medium text-text-primary truncate">{{ session.subject?.name }}</p>
                <p class="text-sm text-text-secondary">{{ session.topic?.name || 'General review' }}</p>
                <p class="text-xs text-text-secondary">{{ session.task?.title || 'Self-directed study' }}</p>
              </div>
            </div>
            <div class="flex items-center space-x-4 text-sm">
              <span class="text-text-secondary">{{ formatTime(session.start_time) }} - {{ formatTime(session.end_time) }}</span>
              <span class="inline-flex shrink-0 items-center whitespace-nowrap px-2 py-1 text-xs font-medium rounded-full" :class="statusClass(session.status)">{{ session.status.replace('_', ' ') }}</span>
              <span class="text-text-secondary">{{ session.duration_minutes }} min</span>
              <button v-if="session.status === 'scheduled'" @click="startSession(session)" class="btn-primary text-xs py-1 px-2">Start</button>
              <button v-else-if="session.status === 'in_progress'" @click="completeSession(session)" class="btn-primary text-xs py-1 px-2 bg-status-success hover:bg-status-success-hover">Complete</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useStudentStore } from '@/stores/student';
import type { StudySession } from '@/types';

const studentStore = useStudentStore();

const sessions = ref<StudySession[]>([]);
const loading = ref(true);
const generating = ref(false);
const planFrom = ref(new Date().toISOString().split('T')[0]);
const planTo = ref(new Date(Date.now() + 14 * 86400000).toISOString().split('T')[0]);

onMounted(async () => {
  await fetchSessions();
});

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
  } catch (err) {
    console.error('Failed to generate study plan:', err);
  } finally {
    generating.value = false;
  }
}

function onDateChange() {
  if (planFrom.value && planTo.value && planFrom.value <= planTo.value) {
    fetchSessions();
  }
}

const groupedSessions = computed(() => {
  const groups: Record<string, StudySession[]> = {};
  sessions.value.forEach(session => {
    if (!groups[session.scheduled_date]) groups[session.scheduled_date] = [];
    groups[session.scheduled_date].push(session);
  });
  return Object.entries(groups)
    .map(([date, sessions]) => ({
      date,
      sessions: sessions.sort((a, b) => a.start_time.localeCompare(b.start_time)),
      totalMinutes: sessions.reduce((sum, s) => sum + s.duration_minutes, 0),
    }))
    .sort((a, b) => a.date.localeCompare(b.date));
});

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
  const hour = parseInt(hours);
  const ampm = hour >= 12 ? 'PM' : 'AM';
  const hour12 = hour % 12 || 12;
  return `${hour12}:${minutes} ${ampm}`;
}

function subjectColor(color: string) {
  const colors: Record<string, string> = {
    blue: 'bg-blue-500', green: 'bg-green-500', purple: 'bg-purple-500',
    red: 'bg-red-500', yellow: 'bg-yellow-500', pink: 'bg-pink-500', indigo: 'bg-indigo-500',
  };
  return colors[color] || 'bg-gray-500';
}

function statusClass(status: string) {
  const classes = { scheduled: 'bg-primary-light text-primary', in_progress: 'bg-status-warning-light text-status-warning', completed: 'bg-status-success-light text-status-success', missed: 'bg-status-urgent-light text-status-urgent' };
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
</script>