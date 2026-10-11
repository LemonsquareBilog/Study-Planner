<template>
  <article 
    class="resource-card"
    role="region" 
    :aria-label="`${resource.title} - ${resource.resource_type}`"
  >
    <div class="resource-header">
      <span class="badge" :class="typeClass">{{ resource.resource_type }}</span>
      <span class="badge" :class="difficultyClass">{{ resource.difficulty }}</span>
    </div>
    
    <h3 class="resource-title">{{ resource.title }}</h3>
    
    <p v-if="resource.topics?.length" class="resource-meta">
      {{ resource.topics[0].subject?.name || resource.subjects?.[0]?.name || 'Subject' }} · {{ resource.topics[0].name }}
    </p>
    <p v-else-if="resource.subjects?.length" class="resource-meta">
      {{ resource.subjects[0].name }}
    </p>
    
    <p class="resource-description">{{ resource.description || 'No description available' }}</p>
    
    <div class="resource-footer">
      <span v-if="resource.recommendation_reason" class="badge" :class="reasonClass">
        {{ reasonLabel }}
      </span>
      <a
        :href="resource.url"
        target="_blank"
        rel="noopener noreferrer"
        @click="$emit('view', resource.id, resource.topics?.[0]?.id)"
        class="btn-primary"
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

<style scoped>
.resource-card {
  background: rgba(255, 249, 250, 0.9);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border: 1px solid #f3d2da;
  border-radius: 1rem;
  padding: 1rem;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  transition: box-shadow 0.2s ease;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.resource-card:hover {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.resource-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.resource-title {
  font-weight: 600;
  color: #33222a;
  margin-bottom: 0.25rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.resource-meta {
  font-size: 0.75rem;
  font-weight: 500;
  color: #b33f62;
  margin-bottom: 0.25rem;
}

.resource-description {
  font-size: 0.875rem;
  color: #7a5c66;
  margin-bottom: 0.75rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  flex: 1;
}

.resource-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: auto;
  padding-top: 0.75rem;
  border-top: 1px solid rgba(243, 210, 218, 0.5);
}

.badge {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
  font-weight: 500;
  border-radius: 9999px;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.5rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 500;
  border-radius: 9999px;
  color: white;
  background-color: #b33f62;
  transition: background-color 0.2s ease;
}

.btn-primary:hover {
  background-color: #96334f;
}

/* Mobile card layout adjustments */
@media (max-width: 768px) {
  .resource-card {
    padding: 1rem;
  }
  
  .resource-title {
    font-size: 1rem;
  }
  
  .resource-description {
    -webkit-line-clamp: 3;
  }
}
</style>