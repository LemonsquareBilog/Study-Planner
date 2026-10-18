<template>
  <div class="relative">
    <input :id="id" :name="id" :type="visible ? 'text' : 'password'"
      :value="modelValue" @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
      autocomplete="new-password" required :aria-describedby="describedby"
      :aria-invalid="invalid || undefined" :placeholder="placeholder"
      class="w-full rounded-xl border border-white/60 bg-white/50 py-3.5 pl-4 pr-12 text-text-primary focus:outline-primary" />
    <button type="button" @click="visible = !visible" :aria-label="`${visible ? 'Hide' : 'Show'} ${label}`"
      :aria-pressed="visible" :aria-controls="id"
      class="absolute right-1 top-1/2 -translate-y-1/2 p-3 rounded-lg focus-visible:outline-primary">
      <component :is="visible ? EyeSlashIcon : EyeIcon" class="w-5 h-5" aria-hidden="true" />
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline';
defineProps<{ id: string; label: string; modelValue: string; describedby?: string; invalid?: boolean; placeholder?: string }>();
defineEmits<{ 'update:modelValue': [value: string] }>();
const visible = ref(false);
</script>
