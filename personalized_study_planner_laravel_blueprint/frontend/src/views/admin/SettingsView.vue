<script setup lang="ts>
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { Cog6ToothIcon } from '@heroicons/vue/24/outline'
import { RouterLink } from 'vue-router'
import { useSearch, createSuggestionExtractor } from '@/composables/useSearch'
import SearchInput from '@/components/ui/SearchInput.vue'

const authStore = useAuthStore()
const toastStore = useToastStore()

const loading = ref(true)
const settings = ref<import('@/types').SystemSetting[]>([])
const groupFilter = ref('')

const searchComposable = useSearch({
  data: settings,
  searchFields: ['key', 'label', 'group', 'description'],
  debounceMs: 150,
  minQueryLength: 1,
  fuzzyThreshold: 0.35,
  getSuggestions: createSuggestionExtractor(['key', 'label', 'group', 'description'], 8)
})

const filteredSettings = computed(() => 
  searchComposable.results.value.filter(s => 
    !groupFilter.value || (s.group || 'General') === groupFilter.value
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

const allGroups = computed(() => 
  [...new Set(settings.value.map(s => s.group || 'General'))].sort()
)

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
          <RouterLink to="/admin" class="p-2 rounded-xl text-text-muted hover:text-text-primary dark:hover:text-white hover:bg-surface/50 dark:hover:bg-surface-alt/30 transition-colors">
            <ArrowLeftIcon class="w-6 h-6" aria-hidden="true" />
          </RouterLink>
          <div>
            <h1 class="text-3xl font-bold text-text-primary dark:text-white">System Settings</h1>
            <p class="text-text-secondary dark:text-text-muted">Configure system-wide settings</p>
          </div>
        </div>
        
        <div class="flex gap-3 ml-auto">
          <select v-model="groupFilter" class="select-field input-field w-full sm:w-48">
            <option value="">All Groups</option>
            <option v-for="group in allGroups" :key="group" :value="group">{{ group }}</option>
          </select>
        </div>
      </div>

      <!-- Search -->
      <div class="mb-6">
        <label for="settings-search" class="sr-only">Search settings</label>
        <SearchInput
          id="settings-search"
          v-model="searchComposable.query"
          :suggestions="searchComposable.suggestions"
          :loading="searchComposable.isSearching"
          placeholder="Search settings by key, label, group, description..."
          aria-label="Search settings"
        />
        <p v-if="searchComposable.query && !searchComposable.hasResults" class="mt-2 text-sm text-text-muted">
          No settings found matching "{{ searchComposable.query }}"
        </p>
      </div>

      <div v-if="loading" class="animate-pulse space-y-4">
        <div v-for="i in 5" :key="i" class="glass-card-lg rounded-2xl p-6">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-surface-border/50 dark:bg-surface-border/30"></div>
            <div class="flex-1">
              <div class="h-5 bg-surface-border/50 dark:bg-surface-border/30 rounded w-3/4 mb-2"></div>
              <div class="h-4 bg-surface-border/50 dark:bg-surface-border/30 rounded w-1/2"></div>
            </div>
          </div>
        </div>
      </div>

      <div v-else>
        <template v-for="(groupSettings, groupName) in groups" :key="groupName">
          <div class="mb-8">
            <h2 class="text-lg font-semibold text-text-primary dark:text-white mb-4 flex items-center gap-2">
              <span class="w-8 h-8 rounded-lg bg-primary-light/50 dark:bg-primary-dark/30 flex items-center justify-center text-primary dark:text-primary-light">
                <Cog6ToothIcon class="w-5 h-5" aria-hidden="true" />
              </span>
              {{ groupName }}
            </h2>
            
            <div class="space-y-3">
              <div v-for="setting in groupSettings" :key="setting.key" class="glass-card rounded-xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-3 mb-1">
                    <span class="font-medium text-text-primary dark:text-white">{{ setting.label || setting.key }}</span>
                    <span class="text-xs text-text-muted font-mono">{{ setting.key }}</span>
                  </div>
                  <p class="text-sm text-text-secondary dark:text-text-muted truncate">{{ setting.description }}</p>
                  <p v-if="setting.impact_description" class="text-xs text-text-muted mt-1">{{ setting.impact_description }}</p>
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

        <div v-if="Object.keys(groups).length === 0" class="text-center py-16">
          <Cog6ToothIcon class="w-16 h-16 mx-auto text-text-muted mb-4" aria-hidden="true" />
          <h3 class="text-xl font-semibold text-text-primary dark:text-white mb-2">No settings found</h3>
          <p class="text-text-secondary dark:text-text-muted">
            {{ searchComposable.query || groupFilter ? 'Try adjusting your filters' : 'No settings available' }}
          </p>
        </div>
      </div>
    </DefaultLayout>
  </template>

  <script>
  import { onMounted } from 'vue'
  import { ArrowLeftIcon } from '@heroicons/vue/24/outline'

  onMounted(async () => {
    await fetchSettings()
  })
  </script>