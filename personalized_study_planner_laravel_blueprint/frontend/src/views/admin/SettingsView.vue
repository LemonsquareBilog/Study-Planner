<script setup lang="ts>
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { useForm } from 'vee-validate'
import * as zod from 'zod'

const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(true)
const settings = ref<import('@/types').SystemSetting[]>([])
const searchQuery = ref('')

const filteredSettings = computed(() => 
  settings.value.filter(s => 
    s.key.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    s.label?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    s.group?.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
)

const groups = computed(() => {
  const groups: Record<string, import('@/types').SystemSetting[]> = {}
  filteredSettings.value.forEach(setting => {
    const group = setting.group || 'General'
    if (!groups[group]) groups[group] = []
    groups[group].push(setting)
  })
  return groups
})

async function fetchSettings() {
  try {
    const api = (await import('@/utils/api')).default
    const response = await api.get('/admin/system-settings')
    settings.value = response.data.data || []
  } catch (error: any) {
    toastStore.error('Failed to load settings', error.message)
  } finally {
    loading.value = false
  }
}

async function saveSetting(setting: import('@/types').SystemSetting) {
  try {
    const api = (await import('@/utils/api')).default
    await api.patch(`/admin/system-settings/${setting.id}`, { value: setting.value })
    toastStore.success('Setting saved!')
    await fetchSettings()
  } catch (error: any) {
    toastStore.error('Failed to save setting', error.message)
  }
}
</script>

<template>
  <DefaultLayout>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
          <RouterLink to="/admin" class="p-2 rounded-xl text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 hover:bg-pink-50/50 dark:hover:bg-pink-900/20 transition-colors">
            <ArrowLeftIcon class="w-6 h-6" aria-hidden="true" />
          </RouterLink>
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">System Settings</h1>
            <p class="text-gray-600 dark:text-gray-400">Configure system-wide settings</p>
          </div>
        </div>
        
        <div class="flex gap-3 ml-auto">
          <select v-model="searchQuery" class="select-field input-field w-full sm:w-40">
            <option value="">All Settings</option>
            <option value="general">General</option>
            <option value="notifications">Notifications</option>
            <option value="recommendations">Recommendations</option>
            <option value="study_plan">Study Plan</option>
            <option value="quiz">Quiz</option>
            <option value="security">Security</option>
          </select>
        </div>
      </div>

      <div v-if="loading" class="animate-pulse space-y-4">
        <div v-for="i in 5" :key="i" class="glass-card-lg rounded-2xl p-6">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-gray-200/50 dark:bg-gray-700/50"></div>
            <div class="flex-1">
              <div class="h-5 bg-gray-200/50 dark:bg-gray-700/50 rounded w-3/4 mb-2"></div>
              <div class="h-4 bg-gray-200/50 dark:bg-gray-700/50 rounded w-1/2"></div>
            </div>
          </div>
        </div>
      </div>

      <div v-else>
        <template v-for="(groupSettings, groupName) in groups" :key="groupName">
          <div class="mb-8">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
              <span class="w-8 h-8 rounded-lg bg-pink-100/80 dark:bg-pink-900/30 flex items-center justify-center text-pink-600 dark:text-pink-400">
                <Cog6ToothIcon class="w-5 h-5" aria-hidden="true" />
              </span>
              {{ groupName }}
            </h2>
            
            <div class="space-y-3">
              <div v-for="setting in groupSettings" :key="setting.key" class="glass-card rounded-xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-3 mb-1">
                    <span class="font-medium text-gray-900 dark:text-white">{{ setting.label || setting.key }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ setting.key }}</span>
                  </div>
                  <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ setting.description }}</p>
                  <p v-if="setting.impact_description" class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ setting.impact_description }}</p>
                </div>
                
                <div class="flex items-center gap-3">
                  <div class="flex-1 sm:w-48">
                    <input
                      type="text"
                      v-model="setting.value"
                      class="input-field"
                      placeholder="Value"
                    />
                  </div>
                  <button 
                    @click="saveSetting(setting)"
                    class="btn-primary text-sm px-4"
                    :disabled="loading"
                  >
                    Save
                  </button>
                </div>
              </div>
            </div>
          </div>
        </template>

        <div v-else class="text-center py-16">
          <Cog6ToothIcon class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" aria-hidden="true" />
          <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No settings found</h3>
          <p class="text-gray-600 dark:text-gray-400">Try adjusting your filter</p>
        </div>
      </DefaultLayout>
    </template>

    <script>
    import { onMounted } from 'vue'

    onMounted(async () => {
      await fetchSettings()
    })
    </script>