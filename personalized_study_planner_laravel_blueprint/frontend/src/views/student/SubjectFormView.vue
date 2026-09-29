<script setup lang="ts>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { useForm } from 'vee-validate'
import * as zod from 'zod'
import { RouterLink } from 'vue-router'
import { 
  ArrowLeftIcon, BookOpenIcon, PlusIcon, PencilIcon, 
  TrashIcon, CheckCircleIcon, CalendarDaysIcon,
  ClockIcon, LightBulbIcon, ArrowRightIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(false)
const isEditing = ref(false)
const subjects = ref<import('@/types').Subject[]>([])

const subjectSchema = zod.object({
  name: zod.string().min(2).max(150),
  description: zod.string().max(1000).optional(),
  difficulty: zod.number().min(1).max(5),
  color: zod.string(),
  is_active: zod.boolean().default(true),
})

const subjectForm = useForm({
  validationSchema: subjectSchema,
  initialValues: {
    name: '',
    description: '',
    difficulty: 3,
    color: 'blue',
    is_active: true,
  },
})

async function fetchSubject() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get(`/student/subjects/${route.params.id}`)
    const subject = response.data.data
    subjectForm.setValues({
      name: subject.name,
      description: subject.description || '',
      difficulty: subject.difficulty,
      color: subject.color,
      is_active: subject.is_active,
    })
  } catch (error: any) {
    toastStore.error('Failed to load subject', error.message)
    router.push('/subjects')
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

async function submitSubject() {
  loading.value = true
  try {
    await subjectForm.validate()
    const api = (await import('@/utils/api')).default
    
    if (isEditing.value) {
      await api.put(`/student/subjects/${route.params.id}`, subjectForm.values)
      toastStore.success('Subject updated!')
    } else {
      await api.post('/student/subjects', subjectForm.values)
      toastStore.success('Subject created!')
    }
    router.push('/subjects')
  } catch (error: any) {
    toastStore.error('Failed to save subject', error.message)
  } finally {
    loading.value = false
  }
}

function getColorOptions() {
  return [
    { value: 'blue', label: 'Blue', color: 'bg-blue-500' },
    { value: 'indigo', label: 'Indigo', color: 'bg-indigo-500' },
    { value: 'purple', label: 'Purple', color: 'bg-purple-500' },
    { value: 'pink', label: 'Pink', color: 'bg-pink-500' },
    { value: 'red', label: 'Red', color: 'bg-red-500' },
    { value: 'orange', label: 'Orange', color: 'bg-orange-500' },
    { value: 'amber', label: 'Amber', color: 'bg-amber-500' },
    { value: 'yellow', label: 'Yellow', color: 'bg-yellow-500' },
    { value: 'green', label: 'Green', color: 'bg-green-500' },
    { value: 'teal', label: 'Teal', color: 'bg-teal-500' },
    { value: 'cyan', label: 'Cyan', color: 'bg-cyan-500' },
    { value: 'gray', label: 'Gray', color: 'bg-gray-500' },
  ]
}
</script>

<template>
  <DefaultLayout>
    <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex items-center gap-4 mb-8">
        <RouterLink to="/subjects" class="p-2 rounded-xl text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 hover:bg-pink-50/50 dark:hover:bg-pink-900/20 transition-colors">
          <ArrowLeftIcon class="w-6 h-6" aria-hidden="true" />
        </RouterLink>
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ isEditing ? 'Edit Subject' : 'Create Subject' }}</h1>
          <p class="text-gray-600 dark:text-gray-400">{{ isEditing ? 'Update your subject details' : 'Add a new subject to organize your studies' }}</p>
        </div>
      </div>

      <form @submit.prevent="submitSubject" class="space-y-6" novalidate>
        <div>
          <label for="name" class="input-label">Subject Name</label>
          <input
            id="name"
            type="text"
            v-model="subjectForm.values.name"
            class="input-field"
            :class="{ 'input-error': subjectForm.errors.name }"
            placeholder="e.g., Mathematics, Biology, History"
          />
          <p v-if="subjectForm.errors.name" class="input-error-message">{{ subjectForm.errors.name }}</p>
        </div>

        <div>
          <label for="description" class="input-label">Description (optional)</label>
          <textarea
            id="description"
            v-model="subjectForm.values.description"
            rows="3"
            class="input-field resize-y"
            placeholder="Brief description of this subject..."
          />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label for="difficulty" class="input-label">Difficulty</label>
            <select
              id="difficulty"
              v-model="subjectForm.values.difficulty"
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
            <label for="color" class="input-label">Color</label>
            <select
              id="color"
              v-model="subjectForm.values.color"
              class="select-field input-field"
            >
              <option v-for="opt in getColorOptions()" :key="opt.value" :value="opt.value">
                <span class="flex items-center gap-2">
                  <span :class="['w-4 h-4 rounded-full', opt.color]"></span>
                  {{ opt.label }}
                </span>
              </option>
            </select>
          </div>

          <div class="flex items-end">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" v-model="subjectForm.values.is_active" class="checkbox" />
              <span class="text-sm text-gray-600 dark:text-gray-400">Active</span>
            </label>
          </div>
        </div>

        <div class="flex gap-4 pt-4">
          <RouterLink to="/subjects" class="btn-secondary flex-1">
            <ArrowLeftIcon class="mr-2 w-5 h-5" aria-hidden="true" />
            Cancel
          </RouterLink>
          <button type="submit" :disabled="loading" class="btn-primary flex-1">
            <span v-if="loading" class="flex items-center justify-center gap-2">
              <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
              Saving...
            </span>
            <span v-else>{{ isEditing ? 'Update Subject' : 'Create Subject' }} <ArrowRightIcon class="ml-2 w-5 h-5" aria-hidden="true" /></span>
          </button>
        </div>
      </form>
    </DefaultLayout>
  </template>

  <script>
  import { onMounted } from 'vue'

  onMounted(async () => {
    if (route.params.id) {
      isEditing.value = true
      await fetchSubject()
    }
    await fetchSubjects()
  })
  </script>