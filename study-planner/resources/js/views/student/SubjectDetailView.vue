<template>
  <div class="space-y-8">
    <div v-if="loading" class="flex justify-center py-12" role="status" aria-label="Loading subject">
      <svg class="animate-spin h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
      </svg>
    </div>

    <div v-else-if="!subject" class="text-center py-12 bg-brand-navbar-cards-alt border border-brand-borders rounded-xl">
      <h3 class="text-lg font-medium text-text-primary">Subject not found</h3>
      <router-link to="/subjects" class="mt-2 text-secondary hover:underline transition-colors">Back to subjects</router-link>
    </div>

    <div v-else>
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <div :class="['w-16 h-16 rounded-lg flex items-center justify-center', subjectColor(subject.color)]" aria-hidden="true">
            <svg class="h-10 w-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
          </div>
          <div>
            <h1 class="text-2xl font-bold text-text-primary">{{ subject.name }}</h1>
            <div class="flex items-center space-x-4 mt-1">
              <span :class="difficultyClass(subject.difficulty)" class="inline-flex shrink-0 items-center whitespace-nowrap px-2 py-1 text-xs font-medium rounded-full">Level {{ subject.difficulty }}</span>
              <span v-if="!subject.is_active" class="px-2 py-1 text-xs font-medium bg-brand-borders text-text-secondary rounded-full">Inactive</span>
            </div>
          </div>
        </div>
        <div class="flex items-center space-x-2">
          <button @click="editSubject" class="btn-secondary">Edit</button>
          <button @click="confirmDelete" class="btn-secondary text-status-urgent border-status-urgent hover:bg-status-urgent-light/20">Delete</button>
        </div>
      </div>

      <div v-if="subject.description" class="prose max-w-none text-text-secondary">
        <p>{{ subject.description }}</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <section class="lg:col-span-2 space-y-8">
          <section aria-labelledby="topics-heading">
            <div class="flex items-center justify-between mb-4">
              <h2 id="topics-heading" class="text-lg font-semibold text-text-primary">Topics</h2>
              <button @click="showCreateTopicModal = true" class="btn-primary text-sm" aria-label="Create new topic">
                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Topic
              </button>
            </div>
            <div v-if="topicsLoading" class="flex justify-center py-8">
              <svg class="animate-spin h-6 w-6 text-primary" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /></svg>
            </div>
            <div v-else-if="topics.length === 0" class="text-center py-8 bg-brand-navbar-cards rounded-lg">
              <p class="text-text-secondary">No topics yet. Add your first topic to get started.</p>
            </div>
            <div v-else class="space-y-3" role="list">
              <div v-for="topic in topics" :key="topic.id" class="bg-brand-navbar-cards-alt border border-brand-borders rounded-lg p-4 hover:shadow-md transition-shadow" role="listitem">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-lg bg-status-info-light flex items-center justify-center" aria-hidden="true">
                      <svg class="h-5 w-5 text-status-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                    </div>
                    <div>
                      <h3 class="font-medium text-text-primary">{{ topic.name }}</h3>
                      <p class="text-sm text-text-secondary">Level {{ topic.difficulty }} · {{ topic.quizzes_count || 0 }} quizzes · {{ topic.tasks_count || 0 }} tasks</p>
                    </div>
                  </div>
                  <div class="flex items-center space-x-2">
                    <button @click="editTopic(topic)" class="text-secondary hover:text-secondary-hover text-sm font-medium transition-colors" aria-label="Edit topic">Edit</button>
                    <button @click="confirmTopicDelete(topic)" class="text-status-urgent hover:text-status-urgent-hover text-sm font-medium transition-colors" aria-label="Delete topic">Delete</button>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <section aria-labelledby="tasks-heading">
            <h2 id="tasks-heading" class="text-lg font-semibold text-text-primary mb-4">Recent Tasks</h2>
            <div v-if="tasksLoading" class="flex justify-center py-8">
              <svg class="animate-spin h-6 w-6 text-primary" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /></svg>
            </div>
            <div v-else-if="subject.tasks?.length === 0" class="text-center py-8 bg-brand-navbar-cards rounded-lg">
              <p class="text-text-secondary">No tasks for this subject.</p>
              <router-link to="/tasks" class="mt-2 text-secondary hover:underline inline-block transition-colors">View all tasks</router-link>
            </div>
            <div v-else class="space-y-3" role="list">
              <div v-for="task in subject.tasks?.slice(0, 10)" :key="task.id" class="bg-brand-navbar-cards-alt border border-brand-borders rounded-lg p-4 hover:shadow-md transition-shadow" role="listitem">
                <div class="flex items-center justify-between">
                  <div class="flex-1 min-w-0">
                    <p class="font-medium text-text-primary truncate">{{ task.title }}</p>
                    <p class="text-sm text-text-secondary">Due {{ formatDate(task.deadline) }} · {{ task.priority }} priority</p>
                  </div>
                  <span :class="statusClass(task.status)" class="inline-flex shrink-0 items-center whitespace-nowrap px-2 py-1 text-xs font-medium rounded-full">{{ formatStatus(task.status) }}</span>
                </div>
              </div>
            </div>
          </section>
        </section>

        <aside class="space-y-6">
          <section class="bg-brand-navbar-cards-alt border border-brand-borders rounded-xl p-6" aria-labelledby="resources-heading">
            <h2 id="resources-heading" class="text-lg font-semibold text-text-primary mb-4">Resources</h2>
            <div v-if="subject.resources?.length === 0" class="text-center py-8 text-text-secondary">
              <p>No resources linked to this subject.</p>
            </div>
            <ul v-else class="space-y-3" role="list">
              <li v-for="resource in subject.resources" :key="resource.id" class="flex items-center justify-between p-3 bg-brand-navbar-cards rounded-lg" role="listitem">
                <div class="flex items-center space-x-3">
                  <span class="px-2 py-1 text-xs font-medium rounded-full" :class="resourceTypeClass(resource.resource_type)">{{ resource.resource_type }}</span>
                  <span class="text-sm font-medium text-text-primary">{{ resource.title }}</span>
                </div>
                <a :href="resource.url" target="_blank" rel="noopener noreferrer" class="btn-primary text-xs py-1 px-2" aria-label="View resource">View</a>
              </li>
            </ul>
          </section>

          <section class="bg-brand-navbar-cards-alt border border-brand-borders rounded-xl p-6" aria-labelledby="study-sessions-heading">
            <h2 id="study-sessions-heading" class="text-lg font-semibold text-text-primary mb-4">Study Sessions</h2>
            <div v-if="sessionsLoading" class="flex justify-center py-8">
              <svg class="animate-spin h-6 w-6 text-primary" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /></svg>
            </div>
            <div v-else-if="studySessions.length === 0" class="text-center py-8 text-text-secondary">
              <p>No study sessions for this subject.</p>
              <router-link to="/study-plan" class="mt-2 text-secondary hover:underline inline-block transition-colors">View study plan</router-link>
            </div>
            <ul v-else class="space-y-3" role="list">
              <li v-for="session in studySessions.slice(0, 10)" :key="session.id" class="p-3 bg-brand-navbar-cards rounded-lg" role="listitem">
                <p class="text-sm font-medium text-text-primary">{{ formatDate(session.scheduled_date) }} at {{ formatTime(session.start_time) }}</p>
                <p class="text-xs text-text-secondary">{{ session.duration_minutes }} min · {{ session.status }}</p>
              </li>
            </ul>
          </section>
        </aside>
      </div>
    </div>
  </div>

  <!-- Create/Edit Topic Modal -->
  <Teleport to="body">
    <div v-if="showCreateTopicModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="topic-modal-title">
      <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" @click="closeTopicModal" aria-hidden="true"></div>
        <div class="relative bg-brand-navbar-cards-alt border border-brand-borders rounded-xl shadow-xl max-w-md w-full p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 id="topic-modal-title" class="text-lg font-semibold text-text-primary">{{ editingTopic ? 'Edit Topic' : 'Create Topic' }}</h2>
            <button @click="closeTopicModal" class="text-text-secondary hover:text-text-primary transition-colors" aria-label="Close modal">
              <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="submitTopic" class="space-y-4" novalidate>
            <div>
              <label for="topic-name" class="input-label">Name <span class="text-status-urgent">*</span></label>
              <input id="topic-name" v-model="topicForm.name" type="text" required class="input-field-glass" />
              <p v-if="topicErrors.name" class="input-error-message">{{ topicErrors.name }}</p>
            </div>
            <div>
              <label for="topic-description" class="input-label">Description</label>
              <textarea id="topic-description" v-model="topicForm.description" rows="3" class="input-field-glass"></textarea>
            </div>
            <div>
              <label for="topic-difficulty" class="input-label">Difficulty <span class="text-status-urgent">*</span></label>
              <select id="topic-difficulty" v-model="topicForm.difficulty" required class="input-field-glass">
                <option value="1">1 - Very Easy</option>
                <option value="2">2 - Easy</option>
                <option value="3">3 - Medium</option>
                <option value="4">4 - Hard</option>
                <option value="5">5 - Very Hard</option>
              </select>
            </div>
            <div>
              <label class="flex items-center space-x-2">
                <input type="checkbox" v-model="topicForm.is_active" class="h-4 w-4 text-primary border-brand-borders rounded focus:ring-primary/50" />
                <span class="text-sm text-text-secondary">Active</span>
              </label>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
              <button type="button" @click="closeTopicModal" class="btn-secondary">Cancel</button>
              <button type="submit" :disabled="topicSubmitting" class="btn-primary">
                <span v-if="topicSubmitting" class="flex items-center">
                  <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
                  Saving...
                </span>
                <span v-else>{{ editingTopic ? 'Update' : 'Create' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- Delete Topic Confirmation -->
  <Teleport to="body">
    <div v-if="deletingTopic" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="delete-topic-modal-title">
      <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" @click="cancelTopicDelete" aria-hidden="true"></div>
        <div class="relative bg-brand-navbar-cards-alt border border-brand-borders rounded-xl shadow-xl max-w-md w-full p-6">
          <h3 id="delete-topic-modal-title" class="text-lg font-semibold text-text-primary mb-2">Delete Topic</h3>
          <p class="text-text-secondary mb-6">Are you sure you want to delete "{{ deletingTopic.name }}"? This will also delete associated quizzes and tasks.</p>
          <div class="flex justify-end space-x-3">
            <button @click="cancelTopicDelete" class="btn-secondary">Cancel</button>
            <button @click="deleteTopic" :disabled="topicDeleting" class="btn-primary bg-status-urgent hover:bg-status-urgent-hover">
              <span v-if="topicDeleting">Deleting...</span>
              <span v-else>Delete</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- Delete Subject Confirmation -->
  <Teleport to="body">
    <div v-if="deletingSubject" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="delete-subject-modal-title">
      <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" @click="cancelSubjectDelete" aria-hidden="true"></div>
        <div class="relative bg-brand-navbar-cards-alt border border-brand-borders rounded-xl shadow-xl max-w-md w-full p-6">
          <h3 id="delete-subject-modal-title" class="text-lg font-semibold text-text-primary mb-2">Delete Subject</h3>
          <p class="text-text-secondary mb-6">Are you sure you want to delete "{{ subject?.name }}"? This will also delete all associated topics, tasks, and study sessions.</p>
          <div class="flex justify-end space-x-3">
            <button @click="cancelSubjectDelete" class="btn-secondary">Cancel</button>
            <button @click="deleteSubject" :disabled="subjectDeleting" class="btn-primary bg-status-urgent hover:bg-status-urgent-hover">
              <span v-if="subjectDeleting">Deleting...</span>
              <span v-else>Delete</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useStudentStore } from '@/stores/student';
import type { Subject, Topic } from '@/types';

const route = useRoute();
const router = useRouter();
const studentStore = useStudentStore();

const subject = ref<Subject | null>(null);
const loading = ref(true);
const topics = ref<Topic[]>([]);
const topicsLoading = ref(false);
const studySessions = ref([]);
const sessionsLoading = ref(false);
const tasksLoading = ref(false);

const showCreateTopicModal = ref(false);
const editingTopic = ref<Topic | null>(null);
const deletingTopic = ref<Topic | null>(null);
const topicSubmitting = ref(false);
const topicDeleting = ref(false);
const topicErrors = ref<Record<string, string>>({});

const deletingSubject = ref(false);
const subjectDeleting = ref(false);

const topicForm = ref({
  name: '',
  description: '',
  difficulty: 3,
  is_active: true,
});

onMounted(async () => {
  await loadSubject();
});

async function loadSubject() {
  loading.value = true;
  try {
    subject.value = await studentStore.fetchSubject(Number(route.params.id));
    if (subject.value) {
      await Promise.all([
        loadTopics(),
        loadStudySessions(),
      ]);
    }
  } catch (err) {
    console.error('Failed to load subject:', err);
  } finally {
    loading.value = false;
  }
}

async function loadTopics() {
  topicsLoading.value = true;
  try {
    topics.value = await studentStore.fetchTopics(subject.value!.id);
  } catch (err) {
    console.error('Failed to load topics:', err);
  } finally {
    topicsLoading.value = false;
  }
}

async function loadStudySessions() {
  sessionsLoading.value = true;
  try {
    studySessions.value = await studentStore.fetchStudySessions({ subject_id: subject.value!.id });
  } catch (err) {
    console.error('Failed to load study sessions:', err);
  } finally {
    sessionsLoading.value = false;
  }
}

function subjectColor(color: string) {
  const colors: Record<string, string> = {
    blue: 'bg-blue-500', green: 'bg-green-500', purple: 'bg-purple-500',
    red: 'bg-red-500', yellow: 'bg-yellow-500', pink: 'bg-pink-500', indigo: 'bg-indigo-500',
  };
  return colors[color] || 'bg-gray-500';
}

function difficultyClass(difficulty: number) {
  const classes = ['', 'bg-status-success-light text-status-success', 'bg-status-success-light text-status-success', 'bg-status-warning-light text-status-warning', 'bg-status-warning-light text-status-warning', 'bg-status-urgent-light text-status-urgent'];
  return `px-2 py-1 text-xs font-medium rounded-full ${classes[difficulty] || classes[3]}`;
}

function resourceTypeClass(type: string) {
  const classes: Record<string, string> = {
    tutorial: 'bg-primary-light text-primary',
    article: 'bg-status-success-light text-status-success',
    video: 'bg-status-info-light text-status-info',
    book: 'bg-status-warning-light text-status-warning',
    documentation: 'bg-brand-borders text-text-secondary',
  };
  return classes[type] || 'bg-brand-borders text-text-secondary';
}

function statusClass(status: string) {
  const classes = { pending: 'bg-brand-borders text-text-secondary', in_progress: 'bg-primary-light text-primary', completed: 'bg-status-success-light text-status-success', cancelled: 'bg-status-urgent-light text-status-urgent' };
  return classes[status as keyof typeof classes] || 'bg-brand-borders text-text-secondary';
}

function formatStatus(status: string) {
  return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function formatDate(dateString: string) {
  return new Date(dateString).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function formatTime(timeString: string) {
  const [hours, minutes] = timeString.split(':');
  const hour = parseInt(hours);
  const ampm = hour >= 12 ? 'PM' : 'AM';
  const hour12 = hour % 12 || 12;
  return `${hour12}:${minutes} ${ampm}`;
}

function editSubject() {
  router.push(`/subjects/${subject.value!.id}/edit`);
}

function confirmDelete() {
  deletingSubject.value = true;
}

function cancelSubjectDelete() {
  deletingSubject.value = false;
}

async function deleteSubject() {
  if (!subject.value) return;
  subjectDeleting.value = true;
  try {
    await studentStore.deleteSubject(subject.value.id);
    router.push('/subjects');
  } catch (err) {
    console.error('Failed to delete subject:', err);
  } finally {
    subjectDeleting.value = false;
  }
}

function openCreateTopicModal() {
  editingTopic.value = null;
  topicForm.value = { name: '', description: '', difficulty: 3, is_active: true };
  topicErrors.value = {};
  showCreateTopicModal.value = true;
}

function editTopic(topic: Topic) {
  editingTopic.value = topic;
  topicForm.value = { name: topic.name, description: topic.description || '', difficulty: topic.difficulty, is_active: topic.is_active };
  topicErrors.value = {};
  showCreateTopicModal.value = true;
}

function closeTopicModal() {
  showCreateTopicModal.value = false;
  editingTopic.value = null;
  topicErrors.value = {};
}

function confirmTopicDelete(topic: Topic) {
  deletingTopic.value = topic;
}

function cancelTopicDelete() {
  deletingTopic.value = null;
}

async function submitTopic() {
  topicErrors.value = {};
  topicSubmitting.value = true;

  try {
    const data = { ...topicForm.value, difficulty: Number(topicForm.value.difficulty) };
    if (editingTopic.value) {
      await studentStore.updateTopic(subject.value!.id, editingTopic.value.id, data);
    } else {
      await studentStore.createTopic(subject.value!.id, data);
    }
    closeTopicModal();
    await loadTopics();
  } catch (err: any) {
    if (err.response?.data?.errors) {
      topicErrors.value = err.response.data.errors;
    }
  } finally {
    topicSubmitting.value = false;
  }
}

async function deleteTopic() {
  if (!deletingTopic.value) return;
  topicDeleting.value = true;
  try {
    await studentStore.deleteTopic(subject.value!.id, deletingTopic.value.id);
    cancelTopicDelete();
    await loadTopics();
  } catch (err) {
    console.error('Failed to delete topic:', err);
  } finally {
    topicDeleting.value = false;
  }
}
</script>