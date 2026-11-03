<template>
  <div class="space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-text-primary">Resources</h1>
        <p class="mt-1 text-sm text-text-secondary">Manage the learning resource library.</p>
      </div>
      <button @click="showCreateModal = true" class="btn-primary" aria-label="Create new resource">
        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Add Resource
      </button>
    </div>

    <div class="glass-card p-4 mb-6">
      <div class="flex flex-wrap gap-4">
        <input type="text" v-model="filters.search" @input="debouncedFetch" placeholder="Search resources..." class="input-field-glass flex-1 min-w-[200px]" aria-label="Search resources" />
        <select v-model="filters.resource_type" @change="fetchResources" class="input-field-glass w-auto" aria-label="Filter by type">
          <option value="">All Types</option>
          <option value="tutorial">Tutorial</option>
          <option value="article">Article</option>
          <option value="video">Video</option>
          <option value="book">Book</option>
          <option value="documentation">Documentation</option>
        </select>
        <select v-model="filters.difficulty" @change="fetchResources" class="input-field-glass w-auto" aria-label="Filter by difficulty">
          <option value="">All Difficulties</option>
          <option value="beginner">Beginner</option>
          <option value="intermediate">Intermediate</option>
          <option value="advanced">Advanced</option>
        </select>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-12" role="status" aria-label="Loading resources">
      <svg class="animate-spin h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
      </svg>
    </div>

    <div v-else class="space-y-4">
      <!-- Desktop Table View -->
      <div class="hidden lg:block glass-card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-brand-borders/50" role="table">
            <thead class="bg-white/50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">Resource</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">Type</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">Difficulty</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">Subjects</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">Topics</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-text-secondary uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-brand-borders/50" role="rowgroup">
              <tr v-for="resource in resources.data" :key="resource.id" class="hover:bg-white/30 transition-colors" role="row">
                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-text-primary">{{ resource.title }}</div>
                  <div class="text-sm text-text-secondary line-clamp-1">{{ resource.description }}</div>
                  <a :href="resource.url" target="_blank" rel="noopener noreferrer" class="text-xs text-secondary hover:underline transition-colors">{{ resource.url }}</a>
                </td>
                <td class="px-6 py-4">
                  <span class="px-2 py-1 text-xs font-medium rounded-full" :class="typeClass(resource.resource_type)">{{ resource.resource_type }}</span>
                </td>
                <td class="px-6 py-4">
                  <span class="px-2 py-1 text-xs font-medium rounded-full" :class="difficultyClass(resource.difficulty)">{{ resource.difficulty }}</span>
                </td>
                <td class="px-6 py-4">
                  <div class="flex flex-wrap gap-1">
                    <span v-for="subject in resource.subjects" :key="subject.id" class="px-2 py-1 text-xs bg-brand-borders/50 text-text-secondary rounded">{{ subject.name }}</span>
                    <span v-if="resource.subjects.length === 0" class="text-xs text-text-secondary">None</span>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="flex flex-wrap gap-1">
                    <span v-for="topic in resource.topics" :key="topic.id" class="px-2 py-1 text-xs bg-status-info-light text-status-info rounded">{{ topic.name }}</span>
                    <span v-if="resource.topics.length === 0" class="text-xs text-text-secondary">None</span>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span :class="resource.is_active ? 'bg-status-success-light text-status-success' : 'bg-brand-borders text-text-secondary'" class="px-2 py-1 text-xs font-medium rounded-full">{{ resource.is_active ? 'Active' : 'Inactive' }}</span>
                </td>
                <td class="px-6 py-4 text-right space-x-2">
                  <button @click="editResource(resource)" class="text-secondary hover:text-secondary-hover text-sm font-medium transition-colors" aria-label="Edit resource">Edit</button>
                  <button @click="confirmDelete(resource)" class="text-status-urgent hover:text-status-urgent-hover text-sm font-medium transition-colors" aria-label="Delete resource">Delete</button>
                </td>
              </tr>
              <tr v-if="resources.data.length === 0">
                <td colspan="7" class="px-6 py-12 text-center text-text-secondary">No resources found. <button @click="showCreateModal = true" class="text-secondary hover:underline transition-colors">Create one</button>.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="resources.last_page > 1" class="px-6 py-4 border-t border-brand-borders/50">
          <nav class="flex items-center justify-between" aria-label="Pagination">
            <p class="text-sm text-text-secondary">Showing {{ resources.from }} to {{ resources.to }} of {{ resources.total }} resources</p>
            <div class="flex space-x-2">
              <button @click="fetchResources({ page: resources.current_page - 1 })" :disabled="resources.current_page === 1" class="btn-secondary text-sm" aria-label="Previous page">Previous</button>
              <button @click="fetchResources({ page: resources.current_page + 1 })" :disabled="resources.current_page === resources.last_page" class="btn-secondary text-sm" aria-label="Next page">Next</button>
            </div>
          </nav>
        </div>
      </div>

      <!-- Mobile Card View -->
      <div class="lg:hidden">
        <div class="grid grid-cols-1 gap-4" role="list">
          <ResourceCard
            v-for="resource in resources.data"
            :key="resource.id"
            :resource="resource"
            @view="recordView"
            role="listitem"
          />
          <div v-if="resources.data.length === 0" class="rounded-xl border border-dashed border-brand-borders bg-brand-navbar-cards-alt p-10 text-center" role="listitem">
            <p class="text-text-secondary">No resources found. <button @click="showCreateModal = true" class="text-secondary hover:underline transition-colors">Create one</button>.</p>
          </div>
        </div>

        <div v-if="resources.last_page > 1" class="px-4 py-4 border-t border-brand-borders/50">
          <nav class="flex items-center justify-between" aria-label="Pagination">
            <p class="text-sm text-text-secondary">Showing {{ resources.from }} to {{ resources.to }} of {{ resources.total }} resources</p>
            <div class="flex space-x-2">
              <button @click="fetchResources({ page: resources.current_page - 1 })" :disabled="resources.current_page === 1" class="btn-secondary text-sm" aria-label="Previous page">Previous</button>
              <button @click="fetchResources({ page: resources.current_page + 1 })" :disabled="resources.current_page === resources.last_page" class="btn-secondary text-sm" aria-label="Next page">Next</button>
            </div>
          </nav>
        </div>
      </div>
    </div>
  </div>

  <!-- Create/Edit Modal -->
  <Teleport to="body">
    <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="modal-title">
      <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" @click="closeModal" aria-hidden="true"></div>
        <div class="relative glass-card shadow-xl max-w-3xl w-full p-6 max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between mb-4">
            <h2 id="modal-title" class="text-lg font-semibold text-text-primary">{{ editingResource ? 'Edit Resource' : 'Create Resource' }}</h2>
            <button @click="closeModal" class="text-text-secondary hover:text-text-primary transition-colors" aria-label="Close modal">
              <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="submitForm" class="space-y-4" novalidate>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label for="title" class="input-label">Title <span class="text-status-urgent">*</span></label>
                <input id="title" v-model="form.title" type="text" required class="input-field-glass" />
                <p v-if="errors.title" class="input-error-message">{{ errors.title }}</p>
              </div>
              <div>
                <label for="resource_type" class="input-label">Type <span class="text-status-urgent">*</span></label>
                <select id="resource_type" v-model="form.resource_type" required class="input-field-glass">
                  <option value="tutorial">Tutorial</option>
                  <option value="article">Article</option>
                  <option value="video">Video</option>
                  <option value="book">Book</option>
                  <option value="documentation">Documentation</option>
                </select>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label for="difficulty" class="input-label">Difficulty <span class="text-status-urgent">*</span></label>
                <select id="difficulty" v-model="form.difficulty" required class="input-field-glass">
                  <option value="beginner">Beginner</option>
                  <option value="intermediate">Intermediate</option>
                  <option value="advanced">Advanced</option>
                </select>
              </div>
              <div>
                <label for="url" class="input-label">URL <span class="text-status-urgent">*</span></label>
                <input id="url" v-model="form.url" type="url" required class="input-field-glass" placeholder="https://example.com" />
                <p v-if="errors.url" class="input-error-message">{{ errors.url }}</p>
              </div>
            </div>
            <div>
              <label for="description" class="input-label">Description</label>
              <textarea id="description" v-model="form.description" rows="3" class="input-field-glass"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-6">
              <div>
                <label class="input-label">Subjects</label>
                <div class="space-y-2 max-h-40 overflow-y-auto glass-card p-3">
                  <label v-for="subject in allSubjects" :key="subject.id" class="flex items-center space-x-2">
                    <input type="checkbox" :value="subject.id" v-model="form.subject_ids" class="h-4 w-4 text-primary border-brand-borders rounded focus:ring-primary/50" />
                    <span class="text-sm text-text-secondary">{{ subject.name }}</span>
                  </label>
                  <p v-if="allSubjects.length === 0" class="text-sm text-text-secondary">No subjects available</p>
                </div>
              </div>
              <div>
                <label class="input-label">Topics</label>
                <div class="space-y-2 max-h-40 overflow-y-auto glass-card p-3">
                  <label v-for="topic in allTopics" :key="topic.id" class="flex items-center space-x-2">
                    <input type="checkbox" :value="topic.id" v-model="form.topic_ids" class="h-4 w-4 text-primary border-brand-borders rounded focus:ring-primary/50" />
                    <span class="text-sm text-text-secondary">{{ topic.name }} ({{ topic.subject_name }})</span>
                  </label>
                  <p v-if="allTopics.length === 0" class="text-sm text-text-secondary">No topics available</p>
                </div>
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
                <span v-else>{{ editingResource ? 'Update' : 'Create' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- Delete Confirmation Modal -->
  <Teleport to="body">
    <div v-if="deletingResource" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title">
      <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" @click="cancelDelete" aria-hidden="true"></div>
        <div class="relative glass-card shadow-xl max-w-md w-full p-6">
          <h3 id="delete-modal-title" class="text-lg font-semibold text-text-primary mb-2">Delete Resource</h3>
          <p class="text-text-secondary mb-6">Are you sure you want to delete "{{ deletingResource.title }}"?</p>
          <div class="flex justify-end space-x-3">
            <button @click="cancelDelete" class="btn-secondary">Cancel</button>
            <button @click="deleteResource" :disabled="deleting" class="btn-primary bg-status-urgent hover:bg-status-urgent-hover">
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
import { ref, onMounted, watch, computed } from 'vue';
import { useAdminStore } from '@/stores/admin';
import type { Resource, Subject, Topic } from '@/types';
import ResourceCard from '@/components/ResourceCard.vue';

const adminStore = useAdminStore();

const resources = ref({ data: [] as Resource[], current_page: 1, last_page: 1, from: 0, to: 0, total: 0 });
const loading = ref(true);
const showCreateModal = ref(false);
const editingResource = ref<Resource | null>(null);
const deletingResource = ref<Resource | null>(null);
const submitting = ref(false);
const deleting = ref(false);
const errors = ref<Record<string, string>>({});
const filters = ref({ search: '', resource_type: '', difficulty: '' });
const allSubjects = ref<Subject[]>([]);
const allTopics = ref<Topic[]>([]);

const form = ref({
  title: '',
  resource_type: 'tutorial',
  difficulty: 'beginner',
  url: '',
  description: '',
  subject_ids: [] as number[],
  topic_ids: [] as number[],
  is_active: true,
});

const debounceTimer = ref<ReturnType<typeof setTimeout> | null>(null);

onMounted(async () => {
  void adminStore.fetchResourceOptions().then(optionsData => {
    allSubjects.value = optionsData?.subjects || [];
    allTopics.value = (optionsData?.topics || []).map((topic: any) => ({ ...topic, subject_name: topic.subject?.name }));
  }).catch(err => console.error('Failed to load resource options:', err));
  await fetchResources();
});

watch(() => adminStore.resources, (newResources) => {
  if (newResources.length > 0) {
    resources.value.data = newResources;
  }
});

function debouncedFetch() {
  if (debounceTimer.value) clearTimeout(debounceTimer.value);
  debounceTimer.value = setTimeout(() => {
    fetchResources({ page: 1 });
  }, 300);
}

async function fetchResources(params = {}) {
  loading.value = true;
  try {
    const query = { ...filters.value, ...params };
    Object.keys(query).forEach(key => query[key] === '' && delete query[key]);
    const response = await adminStore.fetchResources(query);
    resources.value = response;
  } catch (err) {
    console.error('Failed to load resources:', err);
  } finally {
    loading.value = false;
  }
}

function typeClass(type: string) {
  const classes: Record<string, string> = {
    tutorial: 'bg-primary-light text-primary',
    article: 'bg-status-success-light text-status-success',
    video: 'bg-status-info-light text-status-info',
    book: 'bg-status-warning-light text-status-warning',
    documentation: 'bg-brand-borders text-text-secondary',
  };
  return classes[type] || 'bg-brand-borders text-text-secondary';
}

function difficultyClass(difficulty: string) {
  const classes: Record<string, string> = {
    beginner: 'bg-status-success-light text-status-success',
    intermediate: 'bg-status-warning-light text-status-warning',
    advanced: 'bg-status-urgent-light text-status-urgent',
  };
  return classes[difficulty] || 'bg-brand-borders text-text-secondary';
}

function openCreateModal() {
  editingResource.value = null;
  form.value = { title: '', resource_type: 'tutorial', difficulty: 'beginner', url: '', description: '', subject_ids: [], topic_ids: [], is_active: true };
  errors.value = {};
  showCreateModal.value = true;
}

function editResource(resource: Resource) {
  editingResource.value = resource;
  form.value = { title: resource.title, resource_type: resource.resource_type, difficulty: resource.difficulty, url: resource.url, description: resource.description || '', subject_ids: resource.subjects?.map(s => s.id) || [], topic_ids: resource.topics?.map(t => t.id) || [], is_active: resource.is_active };
  errors.value = {};
  showCreateModal.value = true;
}

function closeModal() {
  showCreateModal.value = false;
  editingResource.value = null;
  errors.value = {};
}

function confirmDelete(resource: Resource) {
  deletingResource.value = resource;
}

function cancelDelete() {
  deletingResource.value = null;
}

async function submitForm() {
  errors.value = {};
  submitting.value = true;

  try {
    const data = { ...form.value };
    if (editingResource.value) {
      await adminStore.updateResource(editingResource.value.id, data);
    } else {
      await adminStore.createResource(data);
    }
    closeModal();
    await adminStore.fetchResources(filters.value);
  } catch (err: any) {
    if (err.response?.data?.errors) {
      errors.value = err.response.data.errors;
    }
  } finally {
    submitting.value = false;
  }
}

async function deleteResource() {
  if (!deletingResource.value) return;
  deleting.value = true;
  try {
    await adminStore.deleteResource(deletingResource.value.id);
    cancelDelete();
    await adminStore.fetchResources(filters.value);
  } catch (err) {
    console.error('Failed to delete resource:', err);
  } finally {
    deleting.value = false;
  }
}
</script>
