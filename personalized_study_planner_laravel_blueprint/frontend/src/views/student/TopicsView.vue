<script setup lang="ts>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { useForm } from 'vee-validate'
import * as zod from 'zod'
import { RouterLink } from 'vue-router'
import { 
  BookOpenIcon, ArrowLeftIcon, PlusIcon, PencilIcon, 
  TrashIcon, CheckCircleIcon, CalendarDaysIcon,
  ClockIcon, LightBulbIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(false)
const subjects = ref<import('@/types').Subject[]>([])
const topics = ref<import('@/types').Topic[]>([])
const showTopicForm = ref(false)
const editingTopic = ref<import('@/types').Topic | null>(null)
const deletingTopic = ref<import('@/types').Topic | null>(null)

const topicSchema = zod.object({
  name: zod.string().min(2).max(150),
  description: zod.string().max(1000).optional(),
  difficulty: zod.number().min(1).max(5),
  is_active: zod.boolean().default(true),
})

const topicForm = useForm({
  validationSchema: topicSchema,
  initialValues: {
    name: '',
    description: '',
    difficulty: 3,
    is_active: true,
  },
})

async function fetchTopics() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get(`/student/subjects/${route.params.subjectId}/topics`)
    topics.value = response.data.data || []
  } catch {
    // silent
  }
}

async function fetchSubjects() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get('/student/subjects')
    subjects.value = response.data.data || []
  } catch {
    // silent
  }
}

async function submitTopic() {
  loading.value = true
  try {
    await topicForm.validate()
    const api = (await import('@/utils/api')).default
    
    if (editingTopic.value) {
      await api.put(`/student/subjects/${route.params.subjectId}/topics/${editingTopic.value.id}`, topicForm.values)
      toastStore.success('Topic updated!')
    } else {
      await api.post(`/student/subjects/${route.params.subjectId}/topics`, {
        ...topicForm.values,
        subject_id: route.params.subjectId,
      })
      toastStore.success('Topic created!')
    }
    showTopicForm.value = false
    editingTopic.value = null
    topicForm.resetForm()
    await fetchTopics()
  } catch (error: any) {
    toastStore.error('Failed to save topic', error.message)
  } finally {
    loading.value = false
  }
}

async function deleteTopic(topic: import('@/types').Topic) {
  if (!confirm('Are you sure you want to delete this topic? This action cannot be undone.')) return
  
  try {
    const api = (await import('@/utils/api')).default
    await api.delete(`/student/subjects/${route.params.subjectId}/topics/${topic.id}`)
    toastStore.success('Topic deleted!')
    await fetchTopics()
  } catch (error: any) {
    toastStore.error('Failed to delete topic', error.message)
  }
}

function editTopic(topic: import('@/types').Topic) {
  editingTopic.value = topic
  topicForm.setValues({
    name: topic.name,
    description: topic.description || '',
    difficulty: topic.difficulty,
    is_active: topic.is_active,
  })
  showTopicForm.value = true
}

function newTopic() {
  editingTopic.value = null
  topicForm.resetForm()
  showTopicForm.value = true
}

function cancelForm() {
  showTopicForm.value = false
  editingTopic.value = null
  topicForm.resetForm()
}

function getDifficultyLabel(difficulty: number): string {
  const labels = ['', 'Very Easy', 'Easy', 'Moderate', 'Hard', 'Very Hard']
  return labels[difficulty] || 'Unknown'
}
</script>

<template>
  <DefaultLayout>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
          <RouterLink :to="`/subjects/${route.params.subjectId}`" class="p-2 rounded-xl text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 hover:bg-pink-50/50 dark:hover:bg-pink-900/20 transition-colors">
            <ArrowLeftIcon class="w-6 h-6" aria-hidden="true" />
          </RouterLink>
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Topics</h1>
            <p class="text-gray-600 dark:text-gray-400">Manage topics for this subject</p>
          </div>
        </div>
        
        <RouterLink :to="`/subjects/${route.params.subjectId}/topics/create`" class="btn-primary ml-auto flex-shrink-0">
          <PlusIcon class="w-5 h-5 mr-2" aria-hidden="true" />
          Add Topic
        </RouterLink>
      </div>

      <!-- Topic Form Modal -->
      <Transition name="slide-down">
        <div v-show="showTopicForm" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
          <div class="glass-card-lg rounded-2xl p-6 max-w-2xl w-full max-h-[90vh] overflow-y-auto animate-scale-in">
            <div class="flex items-center justify-between mb-6">
              <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                {{ editingTopic ? 'Edit Topic' : 'Create Topic' }}
              </h2>
              <button @click="cancelForm" class="p-2 rounded-xl text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100/50 dark:hover:bg-gray-800/50 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>

            <form @submit.prevent="submitTopic" class="space-y-6" novalidate>
              <div>
                <label for="name" class="input-label">Topic Name</label>
                <input
                  id="name"
                  type="text"
                  v-model="topicForm.values.name"
                  class="input-field"
                  :class="{ 'input-error': topicForm.errors.name }"
                  placeholder="e.g., Calculus Derivatives"
                />
                <p v-if="topicForm.errors.name" class="input-error-message">{{ topicForm.errors.name }}</p>
              </div>

              <div>
                <label for="description" class="input-label">Description (optional)</label>
                <textarea
                  id="description"
                  v-model="topicForm.values.description"
                  rows="3"
                  class="input-field resize-y"
                  placeholder="Brief description of this topic..."
                />
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label for="difficulty" class="input-label">Difficulty</label>
                  <select
                    id="difficulty"
                    v-model="topicForm.values.difficulty"
                    class="select-field input-field"
                  >
                    <option value="1">1 - Very Easy</option>
                    <option value="2">2 - Easy</option>
                    <option value="3">3 - Moderate</option>
                    <option value="4">4 - Hard</option>
                    <option value="5">5 - Very Hard</option>
                  </select>
                </div>

                <div>
                  <label for="is_active" class="input-label">Status</label>
                  <select
                    id="is_active"
                    v-model="topicForm.values.is_active"
                    class="select-field input-field"
                  >
                    <option value="true">Active</option>
                    <option value="false">Inactive</option>
                  </select>
                </div>
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
                  <span v-else>{{ editingTopic ? 'Update Topic' : 'Create Topic' }}</span>
                </button>
              </div>
            </form>
          </div>
        </Transition>

        <!-- Delete Confirmation Modal -->
        <Transition name="slide-down">
          <div v-show="deletingTopic" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="glass-card-lg rounded-2xl p-6 max-w-md w-full animate-scale-in">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Delete Topic</h3>
              <p class="text-gray-600 dark:text-gray-400 mb-6">Are you sure you want to delete "{{ deletingTopic?.name }}"? This action cannot be undone.</p>
              <div class="flex gap-3 justify-end">
                <button @click="deletingTopic = null" class="btn-secondary">Cancel</button>
                <button @click="deleteTopic(deletingTopic!)" class="btn-danger">Delete</button>
              </div>
            </div>
          </div>
        </Transition>

        <!-- Topics List -->
        <div v-if="topics.length === 0" class="text-center py-16">
          <BookOpenIcon class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" aria-hidden="true" />
          <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No topics yet</h3>
          <p class="text-gray-600 dark:text-gray-400 mb-6">Create your first topic to start organizing your studies</p>
          <button @click="newTopic" class="btn-primary">
            <PlusIcon class="w-5 h-5 mr-2" aria-hidden="true" />
            Create Your First Topic
          </button>
        </div>

        <div v-else class="divide-y divide-pink-100/50 dark:divide-pink-800/50">
          <RouterLink 
            v-for="topic in topics" 
            :key="topic.id" 
            :to="`/subjects/${route.params.subjectId}/topics/${topic.id}`"
            class="flex items-center justify-between px-6 py-4 hover:bg-pink-50/30 dark:hover:bg-pink-900/10 transition-colors"
          >
            <div class="flex items-center gap-4 flex-1 min-w-0">
              <div class="w-12 h-12 rounded-xl bg-pink-100/80 dark:bg-pink-900/30 flex items-center justify-center text-pink-600 dark:text-pink-400">
                <BookOpenIcon class="w-6 h-6" aria-hidden="true" />
              </div>
              <div class="min-w-0">
                <h3 class="font-medium text-gray-900 dark:text-white truncate">{{ topic.name }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium" :class="topic.difficulty <= 2 ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : topic.difficulty === 3 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'">
                    {{ getDifficultyLabel(topic.difficulty) }}
                  </span>
                  <span>{{ topic.quizzes_count }} quizzes</span>
                  <span>{{ topic.tasks_count }} tasks</span>
                  <span v-if="!topic.is_active" class="badge badge-gray">Inactive</span>
                </p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <button @click="editTopic(topic)" class="p-2 rounded-lg text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 hover:bg-pink-50/50 dark:hover:bg-pink-900/20 transition-colors" @click.stop>
                <PencilIcon class="w-5 h-5" aria-hidden="true" />
              </button>
              <button @click="deletingTopic = topic" class="p-2 rounded-lg text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50/50 dark:hover:bg-red-900/20 transition-colors" @click.stop>
                <TrashIcon class="w-5 h-5" aria-hidden="true" />
              </button>
            </div>
          </RouterLink>
        </div>
      </div>
    </DefaultLayout>
  </template>

  <script>
  import { onMounted } from 'vue'

  onMounted(async () => {
    await fetchTopics()
    await fetchSubjects()
  })
  </script>