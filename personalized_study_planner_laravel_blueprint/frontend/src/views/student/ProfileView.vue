<script setup lang="ts>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { useForm } from 'vee-validate'
import * as zod from 'zod'
import { 
  ArrowLeftIcon, UserCircleIcon, CameraIcon, 
  CheckCircleIcon, ArrowRightIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(false)
const avatarLoading = ref(false)
const avatarFile = ref<File | null>(null)
const avatarPreview = ref<string | null>(null)

const profileSchema = zod.object({
  name: zod.string().min(2, 'Name must be at least 2 characters').max(100),
  email: zod.string().email('Please enter a valid email address'),
  course: zod.string().max(150).optional(),
  year_level: zod.number().min(1).max(8).optional(),
})

const profileForm = useForm({
  validationSchema: profileSchema,
  initialValues: {
    name: '',
    email: '',
    course: '',
    year_level: undefined,
  },
})

async function fetchProfile() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get('/auth/me')
    const user = response.data.data
    profileForm.setValues({
      name: user.name,
      email: user.email,
      course: user.course || '',
      year_level: user.year_level,
    })
    avatarPreview.value = user.avatar_url || null
  } catch (error: any) {
    toastStore.error('Failed to load profile', error.message)
  }
}

async function submitProfile() {
  loading.value = true
  try {
    await profileForm.validate()
    const api = (await import('@/utils/api')).default
    await api.put('/auth/me', profileForm.values)
    toastStore.success('Profile updated!')
    await authStore.fetchUser()
  } catch (error: any) {
    toastStore.error('Failed to update profile', error.message)
  } finally {
    loading.value = false
  }
}

async function handleAvatarUpload(event: Event) {
  const input = event.target as HTMLInputElement
  if (input.files && input.files[0]) {
    const file = input.files[0]
    if (!file.type.startsWith('image/')) {
      toastStore.error('Invalid file type', 'Please select an image file')
      return
    }
    if (file.size > 2 * 1024 * 1024) {
      toastStore.error('File too large', 'Avatar must be less than 2MB')
      return
    }
    
    avatarFile.value = file
    avatarPreview.value = URL.createObjectURL(file)
    
    // Upload avatar
    avatarLoading.value = true
    try {
      const api = (await import('@/utils/api')).default
      const formData = new FormData()
      formData.append('avatar', file)
      const response = await api.post('/auth/me/avatar', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      avatarPreview.value = response.data.data.avatar_url
      toastStore.success('Avatar updated!')
      await authStore.fetchUser()
    } catch (error: any) {
      toastStore.error('Failed to upload avatar', error.message)
    } finally {
      avatarLoading.value = false
    }
  }
}

function removeAvatar() {
  avatarPreview.value = null
  avatarFile.value = null
}
</script>

<template>
  <DefaultLayout>
    <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex items-center gap-4 mb-8">
        <RouterLink to="/dashboard" class="p-2 rounded-xl text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 hover:bg-pink-50/50 dark:hover:bg-pink-900/20 transition-colors">
          <ArrowLeftIcon class="w-6 h-6" aria-hidden="true" />
        </RouterLink>
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Profile</h1>
          <p class="text-gray-600 dark:text-gray-400">Manage your account information</p>
        </div>
      </div>

      <div class="glass-card-lg rounded-2xl p-8 space-y-8">
        <!-- Avatar Section -->
        <div class="flex flex-col sm:flex-row sm:items-center gap-6">
          <div class="relative">
            <div class="w-32 h-32 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-800 flex items-center justify-center" :class="avatarPreview ? '' : 'bg-gray-200 dark:bg-gray-700'">
              <img v-if="avatarPreview" :src="avatarPreview" alt="Profile avatar" class="w-full h-full object-cover rounded-full" />
              <template v-else>
                <UserCircleIcon class="w-16 h-16 text-gray-400 dark:text-gray-500" aria-hidden="true" />
              </template>
            </div>
            <div class="absolute bottom-0 right-0">
              <label class="p-2 rounded-full bg-pink-500 text-white hover:bg-pink-600 cursor-pointer transition-colors" :class="avatarLoading ? 'opacity-50 cursor-not-allowed' : ''">
                <input type="file" accept="image/*" @change="handleAvatarUpload" class="sr-only" :disabled="avatarLoading" />
                <CameraIcon class="w-5 h-5" aria-hidden="true" />
              </label>
              <button v-if="avatarPreview" @click="removeAvatar" class="absolute -top-2 -right-2 p-1 rounded-full bg-red-500 text-white hover:bg-red-600 transition-colors" aria-label="Remove avatar">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>
          </div>
          
          <div class="flex-1">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Profile Photo</h2>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Upload a profile picture to personalize your account</p>
            <div class="mt-4 flex items-center gap-4">
              <span v-if="avatarLoading" class="flex items-center gap-2 text-sm text-pink-600 dark:text-pink-400">
                <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                Uploading...
              </span>
              <button v-if="avatarPreview && !avatarLoading" @click="removeAvatar" class="text-sm text-red-600 dark:text-red-400 hover:underline">Remove photo</button>
            </div>
          </div>
        </div>

        <!-- Profile Form -->
        <form @submit.prevent="submitProfile" class="space-y-6" novalidate>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="name" class="input-label">Full Name</label>
              <input
                id="name"
                type="text"
                v-model="profileForm.values.name"
                class="input-field"
                :class="{ 'input-error': profileForm.errors.name }"
                placeholder="John Doe"
              />
              <p v-if="profileForm.errors.name" class="input-error-message">{{ profileForm.errors.name }}</p>
            </div>

            <div>
              <label for="email" class="input-label">Email</label>
              <input
                id="email"
                type="email"
                v-model="profileForm.values.email"
                class="input-field"
                :class="{ 'input-error': profileForm.errors.email }"
                placeholder="you@example.com"
              />
              <p v-if="profileForm.errors.email" class="input-error-message">{{ profileForm.errors.email }}</p>
            </div>

            <div>
              <label for="course" class="input-label">Course / Major (optional)</label>
              <input
                id="course"
                type="text"
                v-model="profileForm.values.course"
                class="input-field"
                :class="{ 'input-error': profileForm.errors.course }"
                placeholder="Computer Science"
              />
              <p v-if="profileForm.errors.course" class="input-error-message">{{ profileForm.errors.course }}</p>
            </div>

            <div>
              <label for="year_level" class="input-label">Year Level (optional)</label>
              <select
                id="year_level"
                v-model="profileForm.values.year_level"
                class="select-field input-field"
              >
                <option value="">Select year</option>
                <option value="1">1st Year</option>
                <option value="2">2nd Year</option>
                <option value="3">3rd Year</option>
                <option value="4">4th Year</option>
                <option value="5">5th Year</option>
                <option value="6">6th Year</option>
                <option value="7">7th Year</option>
                <option value="8">8th Year</option>
              </select>
            </div>
          </div>

          <button type="submit" :disabled="loading" class="btn-primary w-full">
            <span v-if="loading" class="flex items-center justify-center gap-2">
              <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
              Saving...
            </span>
            <span v-else>Save Changes</span>
          </button>
        </form>
      </div>
    </DefaultLayout>
  </template>

  <script>
  import { onMounted } from 'vue'

  onMounted(async () => {
    await fetchProfile()
  })
  </script>