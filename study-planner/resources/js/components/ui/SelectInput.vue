<template>
  <div class="flex flex-col gap-1.5">
    <label v-if="label" :for="id" class="input-label">{{ label }}</label>
    <div class="relative">
      <select
        :id="id"
        :value="modelValue"
        @change="onChange"
        :disabled="disabled"
        :required="required"
        :aria-describedby="errorId"
        class="select-input"
        :class="{ 'select-error': error }"
      >
        <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
        <option
          v-for="option in options"
          :key="option.value"
          :value="option.value"
        >
          {{ option.label }}
        </option>
      </select>
      <div class="select-icon" aria-hidden="true">
        <svg class="h-5 w-5 text-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </div>
    </div>
    <p v-if="error" :id="errorId" class="input-error-message">{{ error }}</p>
    <p v-else-if="hint" :id="hintId" class="text-xs text-text-secondary mt-1">{{ hint }}</p>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Option {
  value: string | number;
  label: string;
}

const props = defineProps<{
  modelValue: string | number | null;
  options: Option[];
  label?: string;
  placeholder?: string;
  disabled?: boolean;
  required?: boolean;
  error?: string;
  hint?: string;
  id?: string;
  name?: string;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: string | number | null];
  change: [value: string | number | null];
}>();

const id = computed(() => props.id || `select-${Math.random().toString(36).slice(2, 9)}`);
const errorId = computed(() => `${id.value}-error`);
const hintId = computed(() => `${id.value}-hint`);

function onChange(event: Event) {
  const target = event.target as HTMLSelectElement;
  emit('update:modelValue', target.value);
  emit('change', target.value);
}
</script>

<style scoped>
.select-input {
  appearance: none;
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%237A5C66' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
  background-position: right 0.5rem center;
  background-repeat: no-repeat;
  background-size: 1.5em 1.5em;
  padding-right: 2.5rem;
  width: 100%;
  padding: 0.625rem 0.75rem;
  border: 1px solid #f3d2da;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  line-height: 1.5;
  color: #33222a;
  background-color: white;
  transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
}

.select-input:focus {
  outline: none;
  border-color: #b33f62;
  box-shadow: 0 0 0 2px rgba(179, 63, 98, 0.5);
  z-index: 10;
}

.select-input:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  background-color: #f9fafb;
}

.select-error {
  border-color: #c6485f;
  box-shadow: 0 0 0 2px rgba(198, 72, 95, 0.5);
  border-color: #c6485f;
}

.select-icon {
  position: absolute;
  inset: 0 0 0 auto;
  display: flex;
  align-items: center;
  padding-right: 0.75rem;
  pointer-events: none;
}
</style>