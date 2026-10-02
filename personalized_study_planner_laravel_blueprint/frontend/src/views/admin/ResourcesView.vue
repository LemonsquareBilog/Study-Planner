<script setup lang="ts>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { useForm } from 'vee-validate'
import * as zod from 'zod'
import { RouterLink } from 'vue-router'
import { 
  BookOpenIcon, ArrowLeftIcon, PlusIcon, PencilIcon, 
  TrashIcon, CheckCircleIcon, ArrowRightIcon,
  VideoCameraIcon, DocumentTextIcon, GlobeAltIcon,
  AcademicCapIcon, CubeIcon
} from '@heroicons/vue/24/outline'
import { useSearch, createSuggestionExtractor } from '@/composables/useSearch'
import SearchInput from '@/components/ui/SearchInput.vue'

const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(true)
const resources = ref<import('@/types').Resource[]>([])
const showResourceForm = ref(false)
const editingResource = ref<import('@/types').Resource | null>(null)

const resourceSchema = zod.object({
  title: zod.string().min(2).max(200),
  description: zod.string().max(2000).optional(),
  resource_type: zod.enum(['book', 'video', 'article', 'website', 'course', 'other']),
  url: zod.string().url().optional().or(zod.literal('')),
  difficulty: zod.number().min(1).max(5),
  is_active: zod.boolean().default(true),
  subject_ids: zod.array(zod.number()).optional(),
  topic_ids: zod.array(zod.number()).optional(),
})

const resourceForm = useForm({
  validationSchema: zod.object({
    title: zod.string().min(2).max(200),
    description: zod.string().max(2000).optional(),
    resource_type: zod.enum(['book', 'video', 'article', 'website', 'course', 'other']),
    url: zod.string().url().optional().or(zod.literal('')),
    difficulty: zod.number().min(1).max(5),
    is_active: zod.boolean().default(true),
    subject_ids: zod.array(zod.number()).optional(),
    topic_ids: zod.array(zod.number()).optional(),
  }),
  initialValues: {
    title: '',
    description: '',
    resource_type: 'book',
    url: '',
    difficulty: 3,
    is_active: true,
    subject_ids: [],
    topic_ids: [],
  },
})

const searchComposable = useSearch({
  data: resources,
  searchFields: ['title', 'description', 'resource_type'],
  debounceMs: 150,
  minQueryLength: 1,
  fuzzyThreshold: 0.35,
  getSuggestions: createSuggestionExtractor(['title', 'description', 'resource_type'], 8)
})

const filteredResources = computed(() => searchComposable.results.value)

async function fetchResources() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get('/admin/resources')
    resources.value = response.data.data || []
  } catch (error: any) {
    toastStore.error('Failed to load resources', error.message)
  } finally {
    loading.value = false
  }
}

async function submitResource() {
  loading.value = true
  try {
    await resourceForm.validate()
    const api = (await import('@/utils/api')).default
    
    if (editingResource.value) {
      await api.put(`/admin/resources/${editingResource.value.id}`, resourceForm.values)
      toastStore.success('Resource updated!')
    } else {
      await api.post('/admin/resources', resourceForm.values)
      toastStore.success('Resource created!')
    }
    showResourceForm.value = false
    editingResource.value = null
    resourceForm.resetForm()
    await fetchResources()
  } catch (error: any) {
    toastStore.error('Failed to save resource', error.message)
  } finally {
    loading.value = false
  }
}

async function deleteResource(resource: import('@/types').Resource) {
  if (!confirm('Are you sure you want to delete this resource? This action cannot be undone.')) return
  
  try {
    const api = (await import('@/utils/api')).default
    await api.delete(`/admin/resources/${resource.id}`)
    toastStore.success('Resource deleted!')
    await fetchResources()
  } catch (error: any) {
    toastStore.error('Failed to delete resource', error.message)
  }
}

function confirmDelete(resource: import('@/types').Resource) {
  if (!confirm('Are you sure you want to delete this resource? This action cannot be undone.')) return
  deleteResource(resource)
}

function editResource(resource: import('@/types').Resource) {
  editingResource.value = resource
  resourceForm.setValues({
    ...resource,
    subject_ids: resource.subjects?.map(s => s.id) || [],
    topic_ids: resource.topics?.map(t => t.id) || [],
  })
  showResourceForm.value = true
}

function newResource() {
  editingResource.value = null
  resourceForm.resetForm()
  showResourceForm.value = true
}

function cancelForm() {
  showResourceForm.value = false
  editingResource.value = null
  resourceForm.resetForm()
}

function getTypeIcon(type: string) {
  const icons: Record<string, any> = {
    book: BookOpenIcon,
    video: VideoCameraIcon,
    article: DocumentTextIcon,
    website: GlobeAltIcon,
    course: AcademicCapIcon,
    other: CubeIcon,
  }
  return icons[type] || BookOpenIcon
}

function getTypeColor(type: string): string {
  const colors: Record<string, string> = {
    book: 'bg-primary-light/50 dark:bg-primary-dark/30 text-primary dark:text-primary-light',
    video: 'bg-purple-100/50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400',
    article: 'bg-blue-100/50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
    website: 'bg-green-100/50 dark:bg-green-900/30 text-green-600 dark:text-green-400',
    course: 'bg-amber-100/50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400',
    other: 'bg-gray-100/50 dark:bg-gray-800/30 text-gray-600 dark:text-gray-400',
  }
  return colors[type] || 'bg-gray-100/50 dark:bg-gray-800/30 text-gray-600 dark:text-gray-400'
}

function getTypeBadgeClass(type: string): string {
  const badges: Record<string, string> = {
    book: 'badge-primary',
    video: 'badge-purple',
    article: 'badge-blue',
    website: 'badge-success',
    course: 'badge-warning',
    other: 'badge-gray',
  }
  return badges[type] || 'badge-gray'
}

function getDifficultyColor(difficulty: number): string {
  if (difficulty <= 2) return 'text-green-600 dark:text-green-400'
  if (difficulty === 3) return 'text-yellow-600 dark:text-yellow-400'
  return 'text-red-600 dark:text-red-400'
}
</script>

<template>
  <DefaultLayout>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
          <RouterLink to="/admin" class="p-2 rounded-xl text-text-muted hover:text-text-primary dark:hover:text-white hover:bg-surface/50 dark:hover:bg-surface-alt/30 transition-colors">
            <ArrowLeftIcon class="w-6 h-6" aria-hidden="true" />
          </RouterLink>
          <div>
            <h1 class="text-3xl font-bold text-text-primary dark:text-white">Resources</h1>
            <p class="text-text-secondary dark:text-text-muted">Manage learning resources</p>
          </div>
        </div>
        
        <button @click="newResource" class="btn-primary ml-auto flex-shrink-0">
          <PlusIcon class="w-5 h-5 mr-2" aria-hidden="true" />
          Add Resource
        </button>
      </div>

      <!-- Search -->
      <div class="mb-6">
        <label for="resource-search" class="sr-only">Search resources</label>
        <SearchInput
          id="resource-search"
          v-model="searchComposable.query"
          :suggestions="searchComposable.suggestions"
          :loading="searchComposable.isSearching"
          placeholder="Search resources by title, description, type..."
          aria-label="Search resources"
          @search="searchComposable.clear"
        />
        <p v-if="searchComposable.query && !searchComposable.hasResults" class="mt-2 text-sm text-text-muted">
          No resources found matching "{{ searchComposable.query }}"
        </p>
      </div>

      <!-- Resource Form Modal -->
      <Transition name="slide-down">
        <div v-show="showResourceForm" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
          <div class="glass-card-lg rounded-2xl p-6 max-w-3xl w-full max-h-[90vh] overflow-y-auto animate-scale-in">
            <div class="flex items-center justify-between mb-6">
              <h2 class="text-xl font-bold text-text-primary dark:text-white">
                {{ editingResource ? 'Edit Resource' : 'Add Resource' }}
              </h2>
              <button @click="cancelForm" class="p-2 rounded-xl text-text-muted hover:text-text-primary dark:hover:text-white hover:bg-surface/50 dark:hover:bg-surface-alt/30 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>

            <form @submit.prevent="submitResource" class="space-y-6" novalidate>
              <div>
                <label for="title" class="input-label">Title</label>
                <input
                  id="title"
                  type="text"
                  v-model="resourceForm.values.title"
                  class="input-field"
                  :class="{ 'input-error': resourceForm.errors.title }"
                  placeholder="Resource title"
                />
                <p v-if="resourceForm.errors.title" class="input-error-message">{{ resourceForm.errors.title }}</p>
              </div>

              <div>
                <label for="description" class="input-label">Description</label>
                <textarea
                  id="description"
                  v-model="resourceForm.values.description"
                  rows="3"
                  class="input-field resize-y"
                  placeholder="Description (optional)"
                />
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label for="resource_type" class="input-label">Type</label>
                  <select
                    id="resource_type"
                    v-model="resourceForm.values.resource_type"
                    class="select-field input-field"
                  >
                    <option value="book">Book</option>
                    <option value="video">Video</option>
                    <option value="article">Article</option>
                    <option value="website">Website</option>
                    <option value="course">Course</option>
                    <option value="other">Other</option>
                  </select>
                </div>

                <div>
                  <label for="url" class="input-label">URL (optional)</label>
                  <input
                    id="url"
                    type="url"
                    v-model="resourceForm.values.url"
                    class="input-field"
                    placeholder="https://example.com"
                  />
                </div>

                <div>
                  <label for="difficulty" class="input-label">Difficulty</label>
                  <select
                    id="difficulty"
                    v-model="resourceForm.values.difficulty"
                    class="select-field input-field"
                  >
                    <option value="1">1 - Very Easy</option>
                    <option value="2">2 - Easy</option>
                    <option value="3">3 - Moderate</option>
                    <option value="4">4 - Hard</option>
                    <option value="5">5 - Very Hard</option>
                  </select>
                </div>
              </div>

              <div class="flex items-end">
                <label class="flex items-center gap-2 cursor-pointer">
                  <input type="checkbox" v-model="resourceForm.values.is_active" class="checkbox" />
                  <span class="text-sm text-text-secondary dark:text-text-muted">Active</span>
                </label>
              </div>

              <div class="flex gap-4 pt-4">
                <button type="button" @click="cancelForm" class="btn-secondary flex-1">
                  Cancel
                </button>
                <button type="submit" :disabled="loading" class="btn-primary flex-1">
                  <span v-if="loading" class="flex items-center justify-center gap-2">
                    <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                    Saving...
                  </span>
                  <span v-else>{{ editingResource ? 'Update Resource' : 'Create Resource' }}</span>
                </button>
              </div>
            </form>
          </div>
        </Transition>

        <!-- Resources List -->
        <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="i in 6" :key="i" class="glass-card-lg rounded-2xl p-6 animate-pulse">
            <div class="h-6 bg-surface-border/50 dark:bg-surface-border/30 rounded w-3/4 mb-4"></div>
            <div class="h-4 bg-surface-border/50 dark:bg-surface-border/30 rounded w-1/2"></div>
          </div>
        </div>

        <div v-else-if="filteredResources.length === 0" class="text-center py-16">
          <BookOpenIcon class="w-16 h-16 mx-auto text-text-muted mb-4" aria-hidden="true" />
          <h3 class="text-xl font-semibold text-text-primary dark:text-white mb-2">
            {{ searchComposable.query ? 'No resources found' : 'No resources yet' }}
          </h3>
          <p class="text-text-secondary dark:text-text-muted mb-6">
            {{ searchComposable.query ? 'Try adjusting your search' : 'Create your first learning resource' }}
          </p>
          <button @click="newResource" class="btn-primary">
            <PlusIcon class="w-5 h-5 mr-2" aria-hidden="true" />
            {{ searchComposable.query ? 'Clear Search' : 'Create Resource' }}
          </button>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <RouterLink 
            v-for="resource in filteredResources" 
            :key="resource.id" 
            :to="`/admin/resources/${resource.id}/edit`"
            class="glass-card-lg rounded-2xl p-6 hover:shadow-glass-lg hover:-translate-y-1 transition-all duration-300"
          >
            <div class="flex items-start justify-between mb-4">
              <div :class="['w-12 h-12 rounded-xl flex items-center justify-center', getTypeColor(resource.resource_type)]">
                <component :is="getTypeIcon(resource.resource_type)" class="w-6 h-6" aria-hidden="true" />
              </div>
              <div class="flex items-center gap-2">
                <RouterLink :to="`/admin/resources/${resource.id}/edit`" class="p-2 rounded-lg text-text-muted hover:text-text-primary dark:hover:text-white hover:bg-surface/50 dark:hover:bg-surface-alt/30 transition-colors" @click.stop>
                  <PencilIcon class="w-5 h-5" aria-hidden="true" />
                </RouterLink>
                <button @click="confirmDelete(resource)" class="p-2 rounded-lg text-text-muted hover:text-status-urgent dark:hover:text-status-urgent-light hover:bg-status-urgent/10 dark:hover:bg-status-urgent/20 transition-colors" @click.stop>
                  <TrashIcon class="w-5 h-5" aria-hidden="true" />
                </button>
              </div>
            </div>
            
            <h3 class="text-xl font-bold text-text-primary dark:text-white mb-2 truncate">{{ resource.title }}</h3>
            <p class="text-text-secondary dark:text-text-muted text-sm mb-4 line-clamp-2">{{ resource.description }}</p>
            
            <div class="flex flex-wrap gap-2 mb-4">
              <span class="badge" :class="getTypeBadgeClass(resource.resource_type)">{{ resource.resource_type }}</span>
              <span class="badge badge-gray">Difficulty: {{ resource.difficulty }}/5</span>
            </div>
            
            <div class="flex items-center justify-between">
              <span v-if="resource.is_active" class="badge badge-success">Active</span>
              <span v-else class="badge badge-gray">Inactive</span>
              <ArrowRightIcon class="w-5 h-5 text-text-muted group-hover:text-primary transition-colors" aria-hidden="true" />
            </div>
          </RouterLink>
        </div>
      </div>
    </DefaultLayout>
  </template>

  <script>
  import { onMounted } from 'vue'

  onMounted(async () => {
    await fetchResources()
  })
  </script>