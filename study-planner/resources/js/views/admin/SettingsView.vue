<template>
  <div class="space-y-8">
    <div>
      <h1 class="text-2xl font-bold text-text-primary">System Settings</h1>
      <p class="mt-1 text-sm text-text-secondary">Configure application-wide settings and thresholds.</p>
    </div>

    <div v-if="loading" class="flex justify-center py-12" role="status" aria-label="Loading settings">
      <svg class="animate-spin h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
      </svg>
    </div>

    <div v-else-if="loadError" role="alert" class="glass-card p-6">
      <p>{{ loadError }}</p><button @click="fetchSettings" class="btn-secondary mt-3">Retry</button>
    </div>
    <p v-else-if="settings.length === 0" role="status" class="glass-card p-6">No system settings are configured.</p>
    <div v-else class="glass-card overflow-hidden">
      <div class="divide-y divide-brand-borders/50">
        <div v-for="setting in settings" :key="setting.id" class="px-6 py-6 hover:bg-white/30 transition-colors">
          <div class="flex items-center justify-between">
            <div class="flex-1 min-w-0">
              <h3 class="text-lg font-medium text-text-primary">{{ formatKey(setting.key) }}</h3>
              <p class="mt-1 text-sm text-text-secondary">{{ setting.description }}</p>
              <p class="mt-1 text-xs text-text-secondary font-mono">Key: {{ setting.key }} · Type: {{ setting.type }}</p>
            </div>
            <div class="ml-4 flex items-center space-x-4">
              <div>
                <SettingInput :setting="setting" :busy="!!updating[setting.id]" @update="updateSetting" />
                <p v-if="saveErrors[setting.id]" role="alert" class="text-sm text-status-urgent">{{ saveErrors[setting.id] }}</p>
                <p v-else-if="saved[setting.id]" role="status" class="text-sm text-status-success">Saved</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import SettingInput from '@/components/SettingInput.vue';
import { useAdminStore } from '@/stores/admin';
import type { SystemSetting } from '@/types';

const adminStore = useAdminStore();

const settings = ref<SystemSetting[]>([]);
const loading = ref(true);
const loadError = ref('');
const saveErrors = ref<Record<number, string>>({});
const saved = ref<Record<number, boolean>>({});
const updating = ref<Record<number, boolean>>({});

onMounted(async () => {
  await fetchSettings();
});

async function fetchSettings() {
  loading.value = true;
  loadError.value = '';
  try {
    settings.value = await adminStore.fetchSystemSettings();
  } catch (err) {
    loadError.value = 'Could not load settings. Please try again.';
  } finally {
    loading.value = false;
  }
}

async function updateSetting(setting: SystemSetting, value: string | number | boolean) {
  updating.value[setting.id] = true;
  saveErrors.value[setting.id] = '';
  saved.value[setting.id] = false;
  try {
    await adminStore.updateSystemSetting(setting.id, { value: String(value) });
    setting.value = String(value);
    saved.value[setting.id] = true;
    setting.typed_value = parseTypedValue(value, setting.type);
  } catch (err) {
    saveErrors.value[setting.id] = 'Could not save this setting. Your input is preserved; please retry.';
  } finally {
    updating.value[setting.id] = false;
  }
}

function formatKey(key: string) {
  return key.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
}

function parseTypedValue(value: string | number | boolean, type: string) {
  if (type === 'integer') return Number(value);
  if (type === 'boolean') return value === 'true' || value === true;
  return String(value);
}
</script>