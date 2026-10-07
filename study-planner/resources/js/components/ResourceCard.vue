<template>
  <article class="bg-brand-navbar-cards-alt border border-brand-borders rounded-xl p-4 hover:shadow-md transition-shadow" role="region" :aria-label="`${resource.title} - ${resource.resource_type}`">
    <div class="flex items-start justify-between mb-2">
      <span class="px-2 py-1 text-xs font-medium rounded-full" :class="typeClass">{{ resource.resource_type }}</span>
      <span class="px-2 py-1 text-xs font-medium rounded-full" :class="difficultyClass">{{ resource.difficulty }}</span>
    </div>
    <h3 class="font-semibold text-text-primary mb-1 line-clamp-2">{{ resource.title }}</h3>
    <p v-if="resource.topics?.length" class="text-xs font-medium text-primary mb-1">{{ resource.topics[0].subject?.name || resource.subjects?.[0]?.name || 'Subject' }} · {{ resource.topics[0].name }}</p>
    <p v-else-if="resource.subjects?.length" class="text-xs font-medium text-primary mb-1">{{ resource.subjects[0].name }}</p>
    <p class="text-sm text-text-secondary mb-3 line-clamp-2">{{ resource.description || 'No description available' }}</p>
    <div class="flex items-center justify-between">
      <span v-if="resource.recommendation_reason" class="px-2 py-1 text-xs font-medium rounded-full" :class="reasonClass">
        {{ reasonLabel }}
      </span>
      <a
        :href="resource.url"
        target="_blank"
        rel="noopener noreferrer"
        @click="$emit('view', resource.id, resource.topics?.[0]?.id)"
        class="btn-primary text-xs py-1 px-3"
        :aria-label="`View ${resource.title}`"
      >
        View
      </a>
    </div>
  </article>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { Resource } from '@/types';

interface Props {
  resource: Resource;
}

interface Emits {
  view: [resourceId: number, topicId?: number];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const typeClass = computed(() => {
  const classes: Record<string, string> = {
    tutorial: 'bg-primary-light text-primary',
    article: 'bg-status-success-light text-status-success',
    video: 'bg-status-info-light text-status-info',
    book: 'bg-status-warning-light text-status-warning',
    documentation: 'bg-brand-borders text-text-secondary',
  };
  return classes[props.resource.resource_type] || 'bg-brand-borders text-text-secondary';
});

const difficultyClass = computed(() => {
  const classes: Record<string, string> = {
    beginner: 'bg-status-success-light text-status-success',
    intermediate: 'bg-status-warning-light text-status-warning',
    advanced: 'bg-status-urgent-light text-status-urgent',
  };
  return classes[props.resource.difficulty] || 'bg-brand-borders text-text-secondary';
});

const reasonClass = computed(() => {
  const classes: Record<string, string> = {
    weak: 'bg-status-urgent-light text-status-urgent',
    needs_review: 'bg-status-warning-light text-status-warning',
    related: 'bg-primary-light text-primary',
  };
  return classes[props.resource.recommendation_reason || 'related'] || 'bg-brand-borders text-text-secondary';
});

const reasonLabel = computed(() => {
  const labels: Record<string, string> = {
    weak: 'Weak Area',
    needs_review: 'Needs Review',
    related: 'Related',
  };
  return labels[props.resource.recommendation_reason || 'related'] || 'Related';
});
</script>