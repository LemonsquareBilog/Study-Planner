<template>
  <div class="flex items-center">
    <input
      v-if="setting.type === 'boolean'"
      type="checkbox"
      :checked="setting.typed_value === true || setting.typed_value === 'true'"
      @change="onChange($event.target.checked)"
      class="h-4 w-4 text-primary border-brand-borders rounded focus:ring-primary/50"
      :disabled="disabled"
    />
    <input
      v-else-if="setting.type === 'integer'"
      type="number"
      :value="setting.typed_value"
      @change="onChange($event.target.value)"
      class="input-field-glass w-32"
      :disabled="disabled"
    />
    <input
      v-else
      type="text"
      :value="setting.value"
      @change="onChange($event.target.value)"
      class="input-field-glass w-48"
      :disabled="disabled"
    />
    <span v-if="disabled" class="text-xs text-status-success">Saved</span>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import type { SystemSetting } from '@/types';

interface Props {
  setting: SystemSetting;
}

interface Emits {
  update: [setting: SystemSetting, value: string | number | boolean];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const disabled = ref(false);

function onChange(value: string | number | boolean) {
  disabled.value = true;
  emit('update', props.setting, value);
  setTimeout(() => disabled.value = false, 1000);
}
</script>

<style scoped>
.input-field-glass {
  @apply appearance-none relative block w-full px-3 py-2 border border-brand-borders placeholder-text-secondary text-text-primary rounded-md focus:outline-none focus:ring-primary/50 focus:border-primary focus:z-10 sm:text-sm transition-colors;
  background: rgba(255, 255, 255, 0.5);
}
.input-field-glass:focus {
  background: rgba(255, 255, 255, 0.7);
}
</style>