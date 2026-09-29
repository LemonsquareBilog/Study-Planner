<template>
  <div class="space-y-8">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-text-primary">Subjects</h1>
        <p class="mt-1 text-sm text-text-secondary">Manage your academic subjects and topics.</p>
      </div>
      <button @click="showCreateModal = true" class="btn-primary" aria-label="Create new subject">
        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        New Subject
      </button>
    </div>

    <div v-if="loading" class="flex justify-center py-12" role="status" aria-label="Loading subjects">
      <svg class="animate-spin h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
      </svg>
    </div>

    <div v-else-if="subjects.length === 0" class="text-center py-12 bg-brand-navbar-cards-alt border border-brand-borders rounded-xl">
      <svg class="mx-auto h-12 w-12 text-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-text-primary">No subjects yet</h3>
      <p class="mt-1 text-sm text-text-secondary">Get started by creating your first subject.</p>
      <button @click="showCreateModal = true" class="mt-4 btn-primary" aria-label="Create your first subject">
        Create Subject
      </button>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" role="list">
      <article v-for="subject in subjects" :key="subject.id" class="bg-brand-navbar-cards-alt border border-brand-borders rounded-xl p-6 hover:shadow-md transition-shadow" role="listitem">
        <div class="flex items-start justify-between mb-4">
          <div class="flex items-center space-x-3">
            <div :class="['w-10 h-10 rounded-lg flex items-center justify-center', subjectColor(subject.color)]" aria-hidden="true">
              <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
            </div>
            <div>
              <h3 class="text-lg font-semibold text-text-primary">{{ subject.name }}</h3>
              <p class="text-sm text-text-secondary">{{ subject.topics_count || 0 }} topics</p>
            </div>
          </div>
          <span :class="difficultyClass(subject.difficulty)" class="inline-flex shrink-0 items-center whitespace-nowrap px-2 py-1 text-xs font-medium rounded-full">
            Level {{ subject.difficulty }}
          </span>
        </div>

        <p class="text-sm text-text-secondary mb-4 line-clamp-2">{{ subject.description || 'No description' }}</p>

        <div class="flex items-center justify-between pt-4 border-t border-brand-borders">
          <router-link :to="`/subjects/${subject.id}`" class="text-sm text-secondary hover:text-secondary-hover font-medium transition-colors">
            View Details
          </router-link>
          <div class="flex items-center space-x-2">
            <button @click="editSubject(subject)" class="text-sm text-text-secondary hover:text-text-primary transition-colors" aria-label="Edit subject">
              Edit
            </button>
            <button @click="confirmDelete(subject)" class="text-sm text-status-urgent hover:text-status-urgent-hover transition-colors" aria-label="Delete subject">
              Delete
            </button>
          </div>
        </div>
      </article>
    </div>
  </div>

  <!-- Create/Edit Modal -->
  <Teleport to="body">
    <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="modal-title">
      <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" @click="closeModal" aria-hidden="true"></div>
        <div class="relative bg-brand-navbar-cards-alt border border-brand-borders rounded-xl shadow-xl max-w-md w-full p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 id="modal-title" class="text-lg font-semibold text-text-primary">{{ editingSubject ? 'Edit Subject' : 'Create Subject' }}</h2>
            <button @click="closeModal" class="text-text-secondary hover:text-text-primary transition-colors" aria-label="Close modal">
              <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="submitForm" class="space-y-4" novalidate>
            <div>
              <label for="name" class="input-label">Name <span class="text-status-urgent">*</span></label>
              <input id="name" v-model="form.name" type="text" required class="input-field-glass" aria-describedby="name-error" />
              <p v-if="errors.name" id="name-error" class="input-error-message">{{ errors.name }}</p>
            </div>
            <div>
              <label for="description" class="input-label">Description</label>
              <textarea id="description" v-model="form.description" rows="3" class="input-field-glass"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label for="difficulty" class="input-label">Difficulty <span class="text-status-urgent">*</span></label>
                <select id="difficulty" v-model="form.difficulty" required class="input-field-glass">
                  <option value="1">1 - Very Easy</option>
                  <option value="2">2 - Easy</option>
                  <option value="3">3 - Medium</option>
                  <option value="4">4 - Hard</option>
                  <option value="5">5 - Very Hard</option>
                </select>
              </div>
              <div>
                <label for="color" class="input-label">Color <span class="text-status-urgent">*</span></label>
                <select id="color" v-model="form.color" required class="input-field-glass">
                  <option value="blue">Blue</option>
                  <option value="green">Green</option>
                  <option value="purple">Purple</option>
                  <option value="red">Red</option>
                  <option value="yellow">Yellow</option>
                  <option value="pink">Pink</option>
                  <option value="indigo">Indigo</option>
                </select>
              </div>
            </div>
            <div>
              <label class="flex items-center space-x-2">
                <input type="checkbox" v-model="form.is_active" class="h-4 w-4 text-primary border-brand-borders rounded focus:ring-primary/50" />
                <span class="text-sm text-text-secondary">Active</span>
              </label>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
              <button type="button" @click="closeModal" class="btn-secondary">Cancel</button>
              <button type="submit" :disabled="submitting" class="btn-primary">
                <span v-if="submitting" class="flex items-center">
                  <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
                  Saving...
                </span>
                <span v-else>{{ editingSubject ? 'Update' : 'Create' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- Delete Confirmation Modal -->
  <Teleport to="body">
    <div v-if="deletingSubject" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title">
      <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" @click="cancelDelete" aria-hidden="true"></div>
        <div class="relative bg-brand-navbar-cards-alt border border-brand-borders rounded-xl shadow-xl max-w-md w-full p-6">
          <h3 id="delete-modal-title" class="text-lg font-semibold text-text-primary mb-2">Delete Subject</h3>
          <p class="text-text-secondary mb-6">Are you sure you want to delete "{{ deletingSubject.name }}"? This will also delete all associated topics and tasks.</p>
          <div class="flex justify-end space-x-3">
            <button @click="cancelDelete" class="btn-secondary">Cancel</button>
            <button @click="deleteSubject" :disabled="deleting" class="btn-primary bg-status-urgent hover:bg-status-urgent-hover">
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
import { ref, onMounted } from 'vue';
import { useStudentStore } from '@/stores/student';
import type { Subject } from '@/types';

const studentStore = useStudentStore();

const subjects = ref<Subject[]>([]);
const loading = ref(true);
const showCreateModal = ref(false);
const editingSubject = ref<Subject | null>(null);
const deletingSubject = ref<Subject | null>(null);
const submitting = ref(false);
const deleting = ref(false);
const errors = ref<Record<string, string>>({});

const form = ref({
  name: '',
  description: '',
  difficulty: 3,
  color: 'blue',
  is_active: true,
});

onMounted(async () => {
  try {
    await studentStore.fetchSubjects();
    subjects.value = studentStore.subjects;
  } catch (err) {
    console.error('Failed to load subjects:', err);
  } finally {
    loading.value = false;
  }
});

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

function difficultyClass(difficulty: number) {
  const classes = ['', 'bg-status-success-light text-status-success', 'bg-status-success-light text-status-success', 'bg-status-warning-light text-status-warning', 'bg-status-warning-light text-status-warning', 'bg-status-urgent-light text-status-urgent'];
  return `px-2 py-1 text-xs font-medium rounded-full ${classes[difficulty] || classes[3]}`;
}

function openCreateModal() {
  editingSubject.value = null;
  form.value = { name: '', description: '', difficulty: 3, color: 'blue', is_active: true };
  errors.value = {};
  showCreateModal.value = true;
}

function editSubject(subject: Subject) {
  editingSubject.value = subject;
  form.value = { name: subject.name, description: subject.description || '', difficulty: subject.difficulty, color: subject.color, is_active: subject.is_active };
  errors.value = {};
  showCreateModal.value = true;
}

function closeModal() {
  showCreateModal.value = false;
  editingSubject.value = null;
  errors.value = {};
}

function confirmDelete(subject: Subject) {
  deletingSubject.value = subject;
}

function cancelDelete() {
  deletingSubject.value = null;
}

async function submitForm() {
  errors.value = {};
  submitting.value = true;

  try {
    if (editingSubject.value) {
      await studentStore.updateSubject(editingSubject.value.id, form.value);
    } else {
      await studentStore.createSubject(form.value);
    }
    closeModal();
    await studentStore.fetchSubjects();
    subjects.value = studentStore.subjects;
  } catch (err: any) {
    if (err.response?.data?.errors) {
      errors.value = err.response.data.errors;
    }
  } finally {
    submitting.value = false;
  }
}

async function deleteSubject() {
  if (!deletingSubject.value) return;
  deleting.value = true;
  try {
    await studentStore.deleteSubject(deletingSubject.value.id);
    cancelDelete();
    await studentStore.fetchSubjects();
    subjects.value = studentStore.subjects;
  } catch (err) {
    console.error('Failed to delete subject:', err);
  } finally {
    deleting.value = false;
  }
}
</script>