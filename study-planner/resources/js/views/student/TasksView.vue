<template>
  <div class="space-y-8">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-text-primary">Tasks</h1>
        <p class="mt-1 text-sm text-text-secondary">Manage your assignments, exams, and projects.</p>
      </div>
      <button @click="openCreateModal" class="btn-primary" aria-label="Create new task">
        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        New Task
      </button>
    </div>

    <div v-if="loading" class="flex justify-center py-12" role="status" aria-label="Loading tasks">
      <svg class="animate-spin h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
      </svg>
    </div>

    <div v-else class="bg-brand-navbar-cards-alt border border-brand-borders rounded-xl overflow-hidden">
      <div class="px-6 py-4 border-b border-brand-borders flex flex-wrap gap-4">
        <input v-model="filters.search" type="search" class="input-field-glass w-auto" aria-label="Search tasks" placeholder="Search tasks" @input="debouncedFetch" />
        <select v-model="filters.sort" @change="fetchTasks()" class="form-select" aria-label="Sort tasks">
          <option value="deadline_asc">Soonest deadline</option><option value="deadline_desc">Latest deadline</option>
          <option value="priority">Priority</option><option value="created">Newest created</option>
        </select>
        <select v-model="filters.status" @change="fetchTasks()" class="form-select" aria-label="Filter by status">
          <option value="">All Statuses</option>
          <option value="pending">Pending</option>
          <option value="in_progress">In Progress</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>
        <select v-model="filters.priority" @change="fetchTasks()" class="form-select" aria-label="Filter by priority">
          <option value="">All Priorities</option>
          <option value="urgent">Urgent</option>
          <option value="high">High</option>
          <option value="medium">Medium</option>
          <option value="low">Low</option>
        </select>
        <select v-model="filters.subject_id" @change="fetchTasks()" class="form-select" aria-label="Filter by subject">
          <option value="">All Subjects</option>
          <option v-for="subject in studentStore.subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
        </select>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-brand-borders" role="table">
          <thead class="bg-brand-navbar-cards">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">Task</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">Subject</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">Priority</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">Deadline</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-text-secondary uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-brand-borders" role="rowgroup">
            <tr v-for="task in tasks.data" :key="task.id" class="hover:bg-brand-navbar-cards" role="row">
              <td class="px-6 py-4">
                <div class="text-sm font-medium text-text-primary">{{ task.title }}</div>
                <div v-if="task.description" class="text-sm text-text-secondary line-clamp-1">{{ task.description }}</div>
                <div v-if="task.topic" class="text-xs text-text-secondary">{{ task.topic.name }}</div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center space-x-2">
                  <div :class="['w-3 h-3 rounded-full', subjectColor(task.subject?.color)]" aria-hidden="true"></div>
                  <span class="text-sm text-text-primary">{{ task.subject?.name }}</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <span :class="priorityClass(task.priority)" class="px-2 py-1 text-xs font-medium rounded-full">{{ task.priority }}</span>
              </td>
              <td class="px-6 py-4 text-sm text-text-primary">{{ formatDate(task.deadline) }}</td>
              <td class="px-6 py-4">
                <span :class="statusClass(task.status)" class="inline-flex shrink-0 items-center whitespace-nowrap px-2 py-1 text-xs font-medium rounded-full">{{ formatStatus(task.status) }}</span>
              </td>
              <td class="px-6 py-4 text-right space-x-2">
                <button @click="editTask(task)" class="text-secondary hover:text-secondary-hover text-sm font-medium transition-colors" aria-label="Edit task">Edit</button>
                <button v-if="task.status !== 'completed'" @click="completeTask(task)" class="text-status-success hover:text-status-success-hover text-sm font-medium transition-colors" aria-label="Mark complete">Complete</button>
                <button @click="confirmDelete(task)" class="text-status-urgent hover:text-status-urgent-hover text-sm font-medium transition-colors" aria-label="Delete task">Delete</button>
              </td>
            </tr>
            <tr v-if="tasks.data.length === 0">
              <td colspan="6" class="px-6 py-12 text-center text-text-secondary">No tasks found. <button @click="openCreateModal" class="text-secondary hover:underline transition-colors">Create one</button>.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="tasks.last_page > 1" class="px-6 py-4 border-t border-brand-borders">
        <nav class="flex items-center justify-between" aria-label="Pagination">
          <p class="text-sm text-text-secondary">Showing {{ tasks.from }} to {{ tasks.to }} of {{ tasks.total }} tasks</p>
          <div class="flex space-x-2">
            <button @click="fetchTasks({ page: tasks.current_page - 1 })" :disabled="tasks.current_page === 1" class="btn-secondary text-sm" aria-label="Previous page">Previous</button>
            <button @click="fetchTasks({ page: tasks.current_page + 1 })" :disabled="tasks.current_page === tasks.last_page" class="btn-secondary text-sm" aria-label="Next page">Next</button>
          </div>
        </nav>
      </div>
    </div>
  </div>

  <!-- Create/Edit Modal -->
  <Teleport to="body">
    <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="modal-title">
      <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" @click="closeModal" aria-hidden="true"></div>
        <div class="relative bg-brand-navbar-cards-alt border border-brand-borders rounded-xl shadow-xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between mb-4">
            <h2 id="modal-title" class="text-lg font-semibold text-text-primary">{{ editingTask ? 'Edit Task' : 'Create Task' }}</h2>
            <button @click="closeModal" class="text-text-secondary hover:text-text-primary transition-colors" aria-label="Close modal">
              <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="submitForm" class="space-y-4" novalidate>
            <div>
              <label for="title" class="input-label">Title <span class="text-status-urgent">*</span></label>
              <input id="title" v-model="form.title" type="text" required class="input-field-glass" aria-describedby="title-error" />
              <p v-if="errors.title" id="title-error" class="input-error-message">{{ errors.title }}</p>
            </div>
            <div>
              <label for="description" class="input-label">Description</label>
              <textarea id="description" v-model="form.description" rows="3" class="input-field-glass"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label for="subject_id" class="input-label">Subject <span class="text-status-urgent">*</span></label>
                <select id="subject_id" v-model="form.subject_id" required @change="onSubjectChange" class="input-field-glass">
                  <option value="">Select subject</option>
                  <option v-for="subject in studentStore.subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
                </select>
                <p v-if="errors.subject_id" class="input-error-message">{{ errors.subject_id }}</p>
              </div>
              <div>
                <label for="topic_id" class="input-label">Topic (optional)</label>
                <select id="topic_id" v-model="form.topic_id" class="input-field-glass" :disabled="!form.subject_id">
                  <option value="">Select topic</option>
                  <option v-for="topic in currentTopics" :key="topic.id" :value="topic.id">{{ topic.name }}</option>
                </select>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label for="task_type" class="input-label">Type <span class="text-status-urgent">*</span></label>
                <select id="task_type" v-model="form.task_type" required class="input-field-glass">
                  <option value="assignment">Assignment</option>
                  <option value="exam">Exam</option>
                  <option value="project">Project</option>
                  <option value="reading">Reading</option>
                  <option value="other">Other</option>
                </select>
              </div>
              <div>
                <label for="priority" class="input-label">Priority <span class="text-status-urgent">*</span></label>
                <select id="priority" v-model="form.priority" required class="input-field-glass">
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label for="start_date" class="input-label">Start Date</label>
                <input id="start_date" v-model="form.start_date" type="date" class="input-field-glass" />
                <p v-if="errors.start_date" class="input-error-message">{{ errors.start_date }}</p>
              </div>
              <div>
                <label for="deadline" class="input-label">Deadline <span class="text-status-urgent">*</span></label>
                <input id="deadline" v-model="form.deadline" type="datetime-local" required class="input-field-glass" />
                <p v-if="errors.deadline" class="input-error-message">{{ errors.deadline }}</p>
              </div>
            </div>
            <div>
              <label for="estimated_minutes" class="input-label">Estimated Minutes <span class="text-status-urgent">*</span></label>
              <input id="estimated_minutes" v-model.number="form.estimated_minutes" type="number" min="1" required class="input-field-glass" />
              <p v-if="errors.estimated_minutes" class="input-error-message">{{ errors.estimated_minutes }}</p>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
              <button type="button" @click="closeModal" class="btn-secondary">Cancel</button>
              <button type="submit" :disabled="submitting" class="btn-primary">
                <span v-if="submitting" class="flex items-center">
                  <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
                  Saving...
                </span>
                <span v-else>{{ editingTask ? 'Update' : 'Create' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- Delete Confirmation Modal -->
  <Teleport to="body">
    <div v-if="deletingTask" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title">
      <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" @click="cancelDelete" aria-hidden="true"></div>
        <div class="relative bg-brand-navbar-cards-alt border border-brand-borders rounded-xl shadow-xl max-w-md w-full p-6">
          <h3 id="delete-modal-title" class="text-lg font-semibold text-text-primary mb-2">Delete Task</h3>
          <p class="text-text-secondary mb-6">Are you sure you want to delete "{{ deletingTask.title }}"?</p>
          <div class="flex justify-end space-x-3">
            <button @click="cancelDelete" class="btn-secondary">Cancel</button>
            <button @click="deleteTask" :disabled="deleting" class="btn-primary bg-status-urgent hover:bg-status-urgent-hover">
              <span v-if="deleting">Deleting...</span>
              <span v-else>Delete</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, onBeforeUnmount } from 'vue';
import { useStudentStore } from '@/stores/student';
import type { Task, TaskQuery } from '@/types';

const studentStore = useStudentStore();

const tasks = ref({ data: [] as Task[], current_page: 1, last_page: 1, from: 0, to: 0, total: 0 });
const loading = ref(true);
const showCreateModal = ref(false);
const editingTask = ref<Task | null>(null);
const deletingTask = ref<Task | null>(null);
const submitting = ref(false);
const deleting = ref(false);
const errors = ref<Record<string, string>>({});
const filters = ref({ status: '', priority: '', subject_id: '', search: '', sort: 'deadline_asc' as TaskQuery['sort'] });
let searchTimer: ReturnType<typeof setTimeout>;
let requestVersion = 0;
onBeforeUnmount(() => clearTimeout(searchTimer));
function debouncedFetch() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => fetchTasks(), 300);
}
const currentTopics = ref([]);

function createEmptyTaskForm() {
  return {
    title: '',
    description: '',
    subject_id: '',
    topic_id: '',
    start_date: '',
    task_type: 'assignment',
    priority: 'medium',
    deadline: new Date(Date.now() + 86400000).toISOString().slice(0, 16),
    estimated_minutes: 60,
  };
}

const form = ref(createEmptyTaskForm());

onMounted(async () => {
  await studentStore.fetchSubjects();
  await fetchTasks();
});

watch(() => studentStore.subjects, (newSubjects) => {
  if (newSubjects.length > 0 && form.value.subject_id) {
    const subject = newSubjects.find(s => s.id === Number(form.value.subject_id));
    if (subject) {
      studentStore.fetchTopics(subject.id).then(topics => {
        currentTopics.value = topics;
      });
    }
  }
});

async function fetchTasks(params: { page?: number } = {}) {
  const version = ++requestVersion;
  loading.value = true;
  try {
    const query = { ...filters.value, ...params };
    Object.keys(query).forEach(key => query[key] === '' && delete query[key]);
    const response = await studentStore.fetchTasks(query);
    if (version === requestVersion) tasks.value = response;
  } catch (err) {
    console.error('Failed to load tasks:', err);
  } finally {
    if (version === requestVersion) loading.value = false;
  }
}

function onSubjectChange() {
  currentTopics.value = [];
  form.value.topic_id = '';
  const subject = studentStore.subjects.find(s => s.id === Number(form.value.subject_id));
  if (subject) {
    studentStore.fetchTopics(subject.id).then(topics => {
      currentTopics.value = topics;
    });
  }
}

function subjectColor(color: string) {
  const colors: Record<string, string> = {
    blue: 'bg-blue-500', green: 'bg-green-500', purple: 'bg-purple-500',
    red: 'bg-red-500', yellow: 'bg-yellow-500', pink: 'bg-pink-500', indigo: 'bg-indigo-500',
  };
  return colors[color] || 'bg-gray-500';
}

function priorityClass(priority: string) {
  const classes = { 
    urgent: 'bg-status-urgent-light text-status-urgent', 
    high: 'bg-status-warning-light text-status-warning', 
    medium: 'bg-status-warning-light text-status-warning', 
    low: 'bg-status-success-light text-status-success' 
  };
  return classes[priority as keyof typeof classes] || 'bg-brand-borders text-text-secondary';
}

function statusClass(status: string) {
  const classes = { 
    pending: 'bg-brand-borders text-text-secondary', 
    in_progress: 'bg-primary-light text-primary', 
    completed: 'bg-status-success-light text-status-success', 
    cancelled: 'bg-status-urgent-light text-status-urgent' 
  };
  return classes[status as keyof typeof classes] || 'bg-brand-borders text-text-secondary';
}

function formatStatus(status: string) {
  return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function formatDate(dateString: string) {
  return new Date(dateString).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function openCreateModal() {
  editingTask.value = null;
  form.value = createEmptyTaskForm();
  errors.value = {};
  currentTopics.value = [];
  showCreateModal.value = true;
}

function editTask(task: Task) {
  editingTask.value = task;
  form.value = { title: task.title, description: task.description || '', subject_id: task.subject_id.toString(), topic_id: task.topic_id?.toString() || '', start_date: task.start_date ? new Date(task.start_date).toISOString().slice(0, 10) : '', task_type: task.task_type, priority: task.priority, deadline: new Date(task.deadline).toISOString().slice(0, 16), estimated_minutes: task.estimated_minutes };
  errors.value = {};
  if (task.subject_id) {
    studentStore.fetchTopics(task.subject_id).then(topics => {
      currentTopics.value = topics;
    });
  }
  showCreateModal.value = true;
}

function closeModal() {
  showCreateModal.value = false;
  editingTask.value = null;
  form.value = createEmptyTaskForm();
  currentTopics.value = [];
  errors.value = {};
}

function confirmDelete(task: Task) {
  deletingTask.value = task;
}

function cancelDelete() {
  deletingTask.value = null;
}

async function submitForm() {
  errors.value = {};
  submitting.value = true;

  try {
    const data = { ...form.value, subject_id: Number(form.value.subject_id), topic_id: form.value.topic_id ? Number(form.value.topic_id) : undefined, estimated_minutes: Number(form.value.estimated_minutes) };
    if (editingTask.value) {
      await studentStore.updateTask(editingTask.value.id, data);
    } else {
      await studentStore.createTask(data);
    }
    closeModal();
    await fetchTasks();
  } catch (err: any) {
    if (err.response?.data?.errors) {
      errors.value = err.response.data.errors;
    }
  } finally {
    submitting.value = false;
  }
}

async function completeTask(task: Task) {
  try {
    await studentStore.completeTask(task.id);
    await fetchTasks();
  } catch (err) {
    console.error('Failed to complete task:', err);
  }
}

async function deleteTask() {
  if (!deletingTask.value) return;
  deleting.value = true;
  try {
    await studentStore.deleteTask(deletingTask.value.id);
    cancelDelete();
    await fetchTasks();
  } catch (err) {
    console.error('Failed to delete task:', err);
  } finally {
    deleting.value = false;
  }
}
</script>