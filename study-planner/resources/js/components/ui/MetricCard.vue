<template>
  <div class="glass-stat-card p-6" role="region" :aria-label="label">
    <div class="flex items-center justify-between gap-4">
      <div class="flex-1 min-w-0">
        <p class="text-sm font-medium text-text-secondary">{{ label }}</p>
        <div class="mt-1 flex flex-wrap items-baseline gap-x-2 gap-y-1">
          <p class="text-3xl font-bold text-text-primary">{{ value }}</p>
          <span v-if="trend" class="text-xs font-medium text-text-secondary">{{ trend }}</span>
        </div>
        <p v-if="trendLabel" class="mt-1 text-sm text-text-secondary">{{ trendLabel }}</p>
      </div>
      <div :class="['metric-icon', `metric-icon-${color}`]" aria-hidden="true">
        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPath" />
        </svg>
      </div>
    </div>
    <router-link v-if="link" :to="link" class="mt-4 flex items-center gap-1 text-sm font-medium text-secondary hover:text-secondary-hover transition-colors">
      View details
      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
      </svg>
    </router-link>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  label: string;
  value: string | number;
  icon: string;
  color: string;
  trend?: string;
  trendLabel?: string;
  link?: string;
}

const props = withDefaults(defineProps<Props>(), {
  trend: '',
  trendLabel: '',
  link: '',
});

const iconPaths: Record<string, string> = {
  users: 'M17 20h5v-2a3 3 0 00-5.356-1.857M9 20H2v-2a3 3 0 015.356-1.857M15 11a3 3 0 10-6 0 3 3 0 006 0zm6 9v-2a3 3 0 00-3-3h-1M3 20v-2a3 3 0 013-3h1',
  'user-check': 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622',
  book: 'M12 7v14m0-14C10.5 5.6 8.6 5 6.5 5S2 5.6 2 7v12c0-1.4 2.4-2 4.5-2s4 .6 5.5 2m0-12c1.5-1.4 3.4-2 5.5-2S22 5.6 22 7v12c0-1.4-2.4-2-4.5-2s-4 .6-5.5 2',
  layers: 'M12 3 2 9l10 6 10-6-10-6zm-10 11 10 6 10-6M2 15l10 6 10-6',
  'question-circle': 'M9.09 9a3 3 0 015.82 1c0 2-3 3-3 3m.09 4h.01M22 12a10 10 0 11-20 0 10 10 0 0120 0z',
  'clipboard-check': 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9 2 2 4-4',
};

const iconPath = computed(() => iconPaths[props.icon] || iconPaths.book);
</script>

<style scoped>
.metric-icon {
  display: flex;
  width: 3rem;
  height: 3rem;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  border-radius: 9999px;
}

.metric-icon-primary,
.metric-icon-pink {
  background-color: var(--color-primary);
}

.metric-icon-success {
  background-color: var(--color-status-success);
}

.metric-icon-info,
.metric-icon-purple {
  background-color: var(--color-status-info);
}

.metric-icon-warning {
  background-color: var(--color-status-warning);
}
</style>