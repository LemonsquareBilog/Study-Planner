<template>
  <div :class="['rounded-xl p-6 shadow-sm', cardClass]" role="region" :aria-label="title">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-text-secondary">{{ title }}</p>
        <p class="mt-1 text-3xl font-bold text-text-primary">
          <span v-if="total !== undefined">{{ value }} / {{ total }}</span>
          <span v-else>{{ value }}</span>
        </p>
        <p class="mt-1 text-sm text-text-secondary">{{ label }}</p>
      </div>
      <div :class="['p-3 rounded-full', iconColor]" aria-hidden="true">
        <component :is="iconComponent" class="h-6 w-6 text-white" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  title: string;
  value: number;
  total?: number;
  label: string;
  icon: 'check-circle' | 'clock' | 'academic-cap' | 'bell';
  color: 'green' | 'blue' | 'purple' | 'yellow' | 'red';
  cardClass?: string;
}

const props = defineProps<Props>();

const iconComponent = computed(() => {
  const icons: Record<string, string> = {
    'check-circle': 'CheckCircleIcon',
    'clock': 'ClockIcon',
    'academic-cap': 'AcademicCapIcon',
    'bell': 'BellIcon',
  };
  return icons[props.icon] || 'CheckCircleIcon';
});

const iconColor = computed(() => {
  const colors: Record<string, string> = {
    green: 'bg-status-success',
    blue: 'bg-primary',
    purple: 'bg-status-info',
    yellow: 'bg-status-warning',
    red: 'bg-status-urgent',
  };
  return colors[props.color] || 'bg-gray-500';
});

const cardClass = computed(() => {
  return props.cardClass || 'bg-brand-navbar-cards-alt border border-brand-borders';
});

// SVG Icon Components
const CheckCircleIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`
};

const ClockIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`
};

const AcademicCapIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>`
};

const BellIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>`
};
</script>