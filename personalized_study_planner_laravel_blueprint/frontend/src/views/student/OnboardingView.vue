<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { useForm } from 'vee-validate'
import * as zod from 'zod'
import { 
  ClockIcon, CheckCircleIcon, ArrowRightIcon, 
  ArrowLeftIcon, BookOpenIcon, PlusIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()

const currentStep = ref(1)
const totalSteps = 4
const loading = ref(false)

const preferencesSchema = zod.object({
  max_daily_minutes: zod.number().min(15).max(720),
  session_duration_minutes: zod.number().min(10).max(180),
  break_duration_minutes: zod.number().min(1).max(60),
  preferred_start_time: zod.string().regex(/^([01]\d|2[0-3]):([0-5]\d)$/, 'Invalid time format (HH:MM)'),
  preferred_end_time: zod.string().regex(/^([01]\d|2[0-3]):([0-5]\d)$/, 'Invalid time format (HH:MM)'),
})

const subjectSchema = zod.object({
  name: zod.string().min(2).max(150),
  description: zod.string().max(1000).optional(),
  difficulty: zod.number().min(1).max(5),
  color: zod.string(),
  is_active: zod.boolean().default(true),
})

const taskSchema = zod.object({
  subject_id: zod.number().min(1),
  topic_id: zod.number().optional(),
  title: zod.string().min(2).max(200),
  description: zod.string().optional(),
  task_type: zod.enum(['assignment', 'project', 'exam', 'quiz', 'reading', 'review', 'other']),
  priority: zod.enum(['low', 'medium', 'high', 'urgent']),
  deadline: zod.string().datetime(),
  estimated_minutes: zod.number().min(1).max(1440),
})

const generateSchema = zod.object({
  from: zod.string().date(),
  to: zod.string().date(),
}).refine((data) => new Date(data.to) >= new Date(data.from), {
  message: 'End date must be after or equal to start date',
  path: ['to'],
})

const steps = [
  { number: 1, title: 'Study Preferences', description: 'Set your preferred study hours and session length', icon: ClockIcon, complete: false },
  { number: 2, title: 'First Subject', description: 'Add your first subject to organize your studies', icon: BookOpenIcon, complete: false },
  { number: 3, title: 'First Task', description: 'Create your first task to start tracking work', icon: CheckCircleIcon, complete: false },
  { number: 4, title: 'Generate Plan', description: 'Generate your first AI-powered study plan', icon: ArrowRightIcon, complete: false },
]

const preferencesForm = useForm({
  validationSchema: preferencesSchema,
  initialValues: {
    max_daily_minutes: 180,
    session_duration_minutes: 50,
    break_duration_minutes: 10,
    preferred_start_time: '18:00',
    preferred_end_time: '22:00',
  },
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

const taskForm = useForm({
  validationSchema: taskSchema,
  initialValues: {
    subject_id: 0,
    title: '',
    description: '',
    task_type: 'assignment',
    priority: 'medium',
    deadline: '',
    estimated_minutes: 60,
  },
})

const generateForm = useForm({
  validationSchema: generateSchema,
  initialValues: {
    from: new Date().toISOString().split('T')[0],
    to: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
  },
})

const subjects = ref<import('@/types').Subject[]>([])
const topics = ref<import('@/types').Topic[]>([])

const progressPercent = computed(() => (currentStep.value / totalSteps) * 100)

async function fetchSubjects() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get('/student/subjects')
    subjects.value = response.data.data || []
  } catch {
    // Silent
  }
}

async function fetchTopics() {
  if (!subjectForm.values.subject_id) return
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get(`/student/subjects/${subjectForm.values.subject_id}/topics`)
    topics.value = response.data.data || []
  } catch {
    topics.value = []
  }
}

async function submitPreferences() {
  loading.value = true
  try {
    await preferencesForm.validate()
    const api = (await import('@/utils/api')).default
    await api.post('/student/study-preference', preferencesForm.values)
    toastStore.success('Preferences saved!')
    currentStep.value = 2
  } catch (error: any) {
    toastStore.error('Failed to save preferences', error.message)
  } finally {
    loading.value = false
  }
}

async function submitSubject() {
  loading.value = true
  try {
    await subjectForm.validate()
    const api = (await import('@/utils/api')).default
    const response = await api.post('/student/subjects', subjectForm.values)
    toastStore.success('Subject created!')
    taskForm.setFieldValue('subject_id', response.data.data.id)
    currentStep.value = 3
  } catch (error: any) {
    toastStore.error('Failed to create subject', error.message)
  } finally {
    loading.value = false
  }
}

async function submitTask() {
  loading.value = true
  try {
    await taskForm.validate()
    const api = (await import('@/utils/api')).default
    await api.post('/student/tasks', taskForm.values)
    toastStore.success('Task created!')
    currentStep.value = 4
  } catch (error: any) {
    toastStore.error('Failed to create task', error.message)
  } finally {
    loading.value = false
  }
}

async function submitGenerate() {
  loading.value = true
  try {
    await generateForm.validate()
    const api = (await import('@/utils/api')).default
    const response = await api.post('/student/study-plan/generate', generateForm.values)
    toastStore.success(`${response.data.data.created_sessions} study sessions generated!`)
    router.push('/study-plan')
  } catch (error: any) {
    toastStore.error('Failed to generate plan', error.message)
  } finally {
    loading.value = false
  }
}

function prevStep() {
  if (currentStep.value > 1) currentStep.value--
}

function nextStep() {
  if (currentStep.value < totalSteps) currentStep.value++
}

function skipStep() {
  if (currentStep.value < totalSteps) currentStep.value++
}

onMounted(async () => {
  await fetchSubjects()
})
</script>

<template>
  <DefaultLayout>
    <div class="min-h-screen">
      <!-- Progress Header -->
      <div class="mx-auto max-w-3xl px-4 py-8">
        <div class="text-center mb-8">
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Welcome to StudyPlanner! 🎓</h1>
          <p class="text-gray-600 dark:text-gray-400">Let's get you set up in a few minutes</p>
        </div>
        
        <!-- Progress Steps -->
        <div class="flex items-center justify-center gap-4 mb-8">
          <template v-for="(step, index) in steps" :key="index">
            <div class="flex items-center">
              <div 
                :class="[
                  'w-10 h-10 rounded-full flex items-center justify-center text-sm font-semibold transition-all duration-300',
                  step.number < currentStep ? 'bg-green-500 text-white' : 
                  step.number === currentStep ? 'bg-pink-500 text-white ring-4 ring-pink-500/20' : 
                  'bg-gray-200/50 dark:bg-gray-700/50 text-gray-400'
                ]"
              >
                <CheckCircleIcon v-if="step.number < currentStep" class="w-5 h-5" aria-hidden="true" />
                <span v-else>{{ step.number }}</span>
              </div>
              <div v-if="index < steps.length - 1" 
                :class="[
                  'w-16 h-1 rounded-full mx-2',
                  step.number < currentStep ? 'bg-green-500' : 'bg-gray-200/50 dark:bg-gray-700/50'
                ]" />
            </div>
          </template>
        </div>
        
        <div class="text-center text-gray-600 dark:text-gray-400">
          Step {{ currentStep }} of {{ totalSteps }} — {{ Math.round((currentStep / totalSteps) * 100) }}% complete
        </div>
      </div>

      <!-- Step Content -->
      <div class="mx-auto max-w-2xl px-4 pb-12">
        <div class="glass-card-lg rounded-2xl p-8">
          <!-- Step 1: Study Preferences -->
          <div v-show="currentStep === 1" class="animate-fade-in">
            <div class="text-center mb-8">
              <ClockIcon class="w-12 h-12 mx-auto text-pink-600 dark:text-pink-400 mb-4" aria-hidden="true" />
              <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Study Preferences</h2>
              <p class="text-gray-600 dark:text-gray-400">Set your preferred study hours and session preferences</p>
            </div>

            <form @submit.prevent="submitPreferences" class="space-y-6" novalidate>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label for="max_daily_minutes" class="input-label">Max Daily Study Time (minutes)</label>
                  <input
                    id="max_daily_minutes"
                    type="number"
                    v-model="preferencesForm.values.max_daily_minutes"
                    min="15"
                    max="720"
                    class="input-field"
                    :class="{ 'input-error': preferencesForm.errors.max_daily_minutes }"
                  />
                  <p v-if="preferencesForm.errors.max_daily_minutes" class="input-error-message">{{ preferencesForm.errors.max_daily_minutes }}</p>
                </div>

                <div>
                  <label for="session_duration_minutes" class="input-label">Session Duration (minutes)</label>
                  <input
                    id="session_duration_minutes"
                    type="number"
                    v-model="preferencesForm.values.session_duration_minutes"
                    min="10"
                    max="180"
                    class="input-field"
                    :class="{ 'input-error': preferencesForm.errors.session_duration_minutes }"
                  />
                  <p v-if="preferencesForm.errors.session_duration_minutes" class="input-error-message">{{ preferencesForm.errors.session_duration_minutes }}</p>
                </div>

                <div>
                  <label for="break_duration_minutes" class="input-label">Break Duration (minutes)</label>
                  <input
                    id="break_duration_minutes"
                    type="number"
                    v-model="preferencesForm.values.break_duration_minutes"
                    min="1"
                    max="60"
                    class="input-field"
                    :class="{ 'input-error': preferencesForm.errors.break_duration_minutes }"
                  />
                  <p v-if="preferencesForm.errors.break_duration_minutes" class="input-error-message">{{ preferencesForm.errors.break_duration_minutes }}</p>
                </div>

                <div>
                  <label for="preferred_start_time" class="input-label">Preferred Start Time</label>
                  <input
                    id="preferred_start_time"
                    type="time"
                    v-model="preferencesForm.values.preferred_start_time"
                    class="input-field"
                    :class="{ 'input-error': preferencesForm.errors.preferred_start_time }"
                  />
                  <p v-if="preferencesForm.errors.preferred_start_time" class="input-error-message">{{ preferencesForm.errors.preferred_start_time }}</p>
                </div>

                <div class="md:col-span-2">
                  <label for="preferred_end_time" class="input-label">Preferred End Time</label>
                  <input
                    id="preferred_end_time"
                    type="time"
                    v-model="preferencesForm.values.preferred_end_time"
                    class="input-field"
                    :class="{ 'input-error': preferencesForm.errors.preferred_end_time }"
                  />
                  <p v-if="preferencesForm.errors.preferred_end_time" class="input-error-message">{{ preferencesForm.errors.preferred_end_time }}</p>
                </div>
              </div>

              <div class="flex gap-4 pt-4">
                <button type="button" @click="skipStep" class="btn-secondary flex-1">Skip for now</button>
                <button type="submit" :disabled="loading" class="btn-primary flex-1">
                  <span v-if="loading" class="flex items-center justify-center gap-2">
                    <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                    Saving...
                  </span>
                  <span v-else>Save & Continue <ArrowRightIcon class="ml-2 w-5 h-5" aria-hidden="true" /></span>
                </button>
              </div>
            </form>
          </div>

          <!-- Step 2: First Subject -->
          <div v-show="currentStep === 2" class="animate-fade-in">
            <div class="text-center mb-8">
              <BookOpenIcon class="w-12 h-12 mx-auto text-pink-600 dark:text-pink-400 mb-4" aria-hidden="true" />
              <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Create Your First Subject</h2>
              <p class="text-gray-600 dark:text-gray-400">Add a subject to start organizing your studies</p>
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
                    <option value="blue">Blue</option>
                    <option value="indigo">Indigo</option>
                    <option value="purple">Purple</option>
                    <option value="pink">Pink</option>
                    <option value="red">Red</option>
                    <option value="orange">Orange</option>
                    <option value="amber">Amber</option>
                    <option value="yellow">Yellow</option>
                    <option value="green">Green</option>
                    <option value="teal">Teal</option>
                    <option value="cyan">Cyan</option>
                    <option value="gray">Gray</option>
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
                <button type="button" @click="prevStep" class="btn-secondary flex-1">
                  <ArrowLeftIcon class="mr-2 w-5 h-5" aria-hidden="true" />
                  Back
                </button>
                <button type="submit" :disabled="loading" class="btn-primary flex-1">
                  <span v-if="loading" class="flex items-center justify-center gap-2">
                    <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                    Creating...
                  </span>
                  <span v-else>Create Subject <ArrowRightIcon class="ml-2 w-5 h-5" aria-hidden="true" /></span>
                </button>
              </div>
            </form>
          </div>

          <!-- Step 3: First Task -->
          <div v-show="currentStep === 3" class="animate-fade-in">
            <div class="text-center mb-8">
              <CheckCircleIcon class="w-12 h-12 mx-auto text-pink-600 dark:text-pink-400 mb-4" aria-hidden="true" />
              <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Create Your First Task</h2>
              <p class="text-gray-600 dark:text-gray-400">Add a task to start tracking your work</p>
            </div>

            <form @submit.prevent="submitTask" class="space-y-6" novalidate>
              <div>
                <label for="subject_id" class="input-label">Subject</label>
                <select
                  id="subject_id"
                  v-model="taskForm.values.subject_id"
                  @change="fetchTopics"
                  class="select-field input-field"
                  :class="{ 'input-error': taskForm.errors.subject_id }"
                >
                  <option value="0">Select a subject</option>
                  <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
                <p v-if="taskForm.errors.subject_id" class="input-error-message">{{ taskForm.errors.subject_id }}</p>
              </div>

              <div v-if="topics.length > 0">
                <label for="topic_id" class="input-label">Topic (optional)</label>
                <select
                  id="topic_id"
                  v-model="taskForm.values.topic_id"
                  class="select-field input-field"
                >
                  <option value="0">Select a topic (optional)</option>
                  <option v-for="t in topics" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>
              </div>

              <div>
                <label for="title" class="input-label">Task Title</label>
                <input
                  id="title"
                  type="text"
                  v-model="taskForm.values.title"
                  class="input-field"
                  :class="{ 'input-error': taskForm.errors.title }"
                  placeholder="e.g., Complete Chapter 3 exercises"
                />
                <p v-if="taskForm.errors.title" class="input-error-message">{{ taskForm.errors.title }}</p>
              </div>

              <div>
                <label for="description" class="input-label">Description (optional)</label>
                <textarea
                  id="description"
                  v-model="taskForm.values.description"
                  rows="3"
                  class="input-field resize-y"
                  placeholder="Details about this task..."
                />
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label for="task_type" class="input-label">Task Type</label>
                  <select
                    id="task_type"
                    v-model="taskForm.values.task_type"
                    class="select-field input-field"
                  >
                    <option value="assignment">Assignment</option>
                    <option value="project">Project</option>
                    <option value="exam">Exam</option>
                    <option value="quiz">Quiz</option>
                    <option value="reading">Reading</option>
                    <option value="review">Review</option>
                    <option value="other">Other</option>
                  </select>
                </div>

                <div>
                  <label for="priority" class="input-label">Priority</label>
                  <select
                    id="priority"
                    v-model="taskForm.values.priority"
                    class="select-field input-field"
                  >
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                  </select>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label for="deadline" class="input-label">Deadline</label>
                  <input
                    id="deadline"
                    type="datetime-local"
                    v-model="taskForm.values.deadline"
                    class="input-field"
                    :class="{ 'input-error': taskForm.errors.deadline }"
                  />
                  <p v-if="taskForm.errors.deadline" class="input-error-message">{{ taskForm.errors.deadline }}</p>
                </div>

                <div>
                  <label for="estimated_minutes" class="input-label">Estimated Time (minutes)</label>
                  <input
                    id="estimated_minutes"
                    type="number"
                    v-model="taskForm.values.estimated_minutes"
                    min="1"
                    max="1440"
                    class="input-field"
                    :class="{ 'input-error': taskForm.errors.estimated_minutes }"
                  />
                  <p v-if="taskForm.errors.estimated_minutes" class="input-error_message">{{ taskForm.errors.estimated_minutes }}</p>
                </div>
              </div>

              <div class="flex gap-4 pt-4">
                <button type="button" @click="prevStep" class="btn-secondary flex-1">
                  <ArrowLeftIcon class="mr-2 w-5 h-5" aria-hidden="true" />
                  Back
                </button>
                <button type="submit" :disabled="loading" class="btn-primary flex-1">
                  <span v-if="loading" class="flex items-center justify-center gap-2">
                    <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                    Creating...
                  </span>
                  <span v-else>Create Task <ArrowRightIcon class="ml-2 w-5 h-5" aria-hidden="true" /></span>
                </button>
              </div>
            </form>
          </div>

          <!-- Step 4: Generate Study Plan -->
          <div v-show="currentStep === 4" class="animate-fade-in">
            <div class="text-center mb-8">
              <ArrowRightIcon class="w-12 h-12 mx-auto text-pink-600 dark:text-pink-400 mb-4" aria-hidden="true" />
              <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Generate Your Study Plan</h2>
              <p class="text-gray-600 dark:text-gray-400">Let AI create a personalized study schedule for you</p>
            </div>

            <form @submit.prevent="submitGenerate" class="space-y-6" novalidate>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label for="from" class="input-label">Start Date</label>
                  <input
                    id="from"
                    type="date"
                    v-model="generateForm.values.from"
                    class="input-field"
                    :class="{ 'input-error': generateForm.errors.from }"
                    :min="new Date().toISOString().split('T')[0]"
                  />
                  <p v-if="generateForm.errors.from" class="input-error-message">{{ generateForm.errors.from }}</p>
                </div>

                <div>
                  <label for="to" class="input-label">End Date</label>
                  <input
                    id="to"
                    type="date"
                    v-model="generateForm.values.to"
                    class="input-field"
                    :class="{ 'input-error': generateForm.errors.to }"
                    :min="generateForm.values.from"
                  />
                  <p v-if="generateForm.errors.to" class="input-error_message">{{ generateForm.errors.to }}</p>
                </div>
              </div>

              <div class="glass-card rounded-xl p-4 bg-pink-50/50 dark:bg-pink-900/20">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  <strong>How it works:</strong> Based on your study preferences, tasks, and deadlines, our AI will generate an optimized study schedule that fits your available time and prioritizes urgent work.
                </p>
              </div>

              <div class="flex gap-4 pt-4">
                <button type="button" @click="prevStep" class="btn-secondary flex-1">
                  <ArrowLeftIcon class="mr-2 w-5 h-5" aria-hidden="true" />
                  Back
                </button>
                <button type="submit" :disabled="loading" class="btn-primary flex-1">
                  <span v-if="loading" class="flex items-center justify-center gap-2">
                    <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                    Generating...
                  </span>
                  <span v-else>Generate Plan <ArrowRightIcon class="ml-2 w-5 h-5" aria-hidden="true" /></span>
                </button>
              </div>
            </form>
          </div>

          <!-- Navigation Dots -->
          <div class="flex justify-center gap-2 mt-8">
            <button 
              v-for="(step, index) in steps" 
              :key="index"
              @click="currentStep = index + 1"
              :disabled="index + 1 > currentStep + 1"
              :class="[
                'w-3 h-3 rounded-full transition-all duration-300',
                index + 1 === currentStep ? 'bg-pink-500' : 
                index + 1 < currentStep ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600'
              ]"
              :aria-label="`Go to step ${index + 1}: ${step.title}`"
            />
          </div>
        </div>
      </div>
    </DefaultLayout>
  </template>

  <script>
  import { onMounted } from 'vue'

  onMounted(async () => {
    await fetchSubjects()
  })
  </script>