<template>
  <form class="flex items-center gap-2" @submit.prevent="$emit('update', setting, draft)">
    <label :for="`setting-${setting.id}`" class="sr-only">{{ setting.key }}</label>
    <input v-if="setting.type === 'boolean'" :id="`setting-${setting.id}`" v-model="draft" type="checkbox" :disabled="busy" />
    <input v-else :id="`setting-${setting.id}`" v-model="draft" :type="setting.type === 'integer' ? 'number' : 'text'" class="input-field-glass max-w-48" :disabled="busy" />
    <button type="submit" class="btn-secondary" :disabled="busy">{{ busy ? 'Saving...' : 'Save' }}</button>
  </form>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import type { SystemSetting } from '@/types';
const props = defineProps<{ setting: SystemSetting; busy?: boolean }>();
defineEmits<{ update: [setting: SystemSetting, value: string | number | boolean] }>();
const draft = ref<string | number | boolean>(props.setting.typed_value ?? props.setting.value);
watch(() => props.setting.value, () => { draft.value = props.setting.typed_value ?? props.setting.value; });
</script>
