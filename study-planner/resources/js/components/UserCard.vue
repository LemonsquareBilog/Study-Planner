<template>
  <article class="user-card" role="region" :aria-label="`User: ${user.name}`">
    <div class="user-header">
      <div class="user-avatar">
        <span class="avatar-text">{{ user.name.charAt(0).toUpperCase() }}</span>
      </div>
      <div class="user-info">
        <h3 class="user-name">{{ user.name }}</h3>
        <p class="user-email">{{ user.email }}</p>
        <div class="user-meta">
          <span v-if="user.course" class="meta-item">
            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <span>{{ user.course }}</span>
          </span>
          <span v-if="user.year_level" class="meta-item">
            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Year {{ user.year_level }}</span>
          </span>
        </div>
      </div>
      <span class="user-role-badge" :class="roleClass">{{ user.role }}</span>
    </div>

    <div class="user-stats">
      <div class="stat-item">
        <span class="stat-value">{{ user.subjects_count || 0 }}</span>
        <span class="stat-label">Subjects</span>
      </div>
      <div class="stat-divider" aria-hidden="true"></div>
      <div class="stat-item">
        <span class="stat-value">{{ user.tasks_count || 0 }}</span>
        <span class="stat-label">Tasks</span>
      </div>
      <div class="stat-divider" aria-hidden="true"></div>
      <div class="stat-item">
        <span class="stat-value">{{ user.quiz_attempts_count || 0 }}</span>
        <span class="stat-label">Quizzes</span>
      </div>
    </div>

    <div class="user-footer">
      <span class="status-badge" :class="user.is_active ? 'bg-status-success-light text-status-success' : 'bg-brand-borders text-text-secondary'">
        {{ user.is_active ? 'Active' : 'Inactive' }}
      </span>
      <div class="user-actions">
        <button @click="$emit('edit', user)" class="action-btn" aria-label="Edit user">
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
          </svg>
        </button>
      </div>
    </div>
  </article>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { User } from '@/types';

interface Props {
  user: User;
}

interface Emits {
  edit: [user: User];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const roleClass = computed(() => {
  const classes: Record<string, string> = {
    admin: 'bg-status-info-light text-status-info',
    student: 'bg-primary-light text-primary',
    instructor: 'bg-status-success-light text-status-success',
  };
  return classes[props.user.role] || 'bg-brand-borders text-text-secondary';
});
</script>

<style scoped>
.user-card {
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

.user-card:hover {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.user-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.user-avatar {
  display: flex;
  height: 3rem;
  width: 3rem;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  border-radius: 0.75rem;
  background-color: #b33f62;
}

.avatar-text {
  font-size: 1.25rem;
  font-weight: 700;
  color: white;
}

.user-info {
  flex: 1;
  min-width: 0;
}

.user-name {
  font-size: 1rem;
  font-weight: 600;
  color: #33222a;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.user-email {
  font-size: 0.875rem;
  color: #7a5c66;
  margin-top: 0.125rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.user-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.meta-item {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.75rem;
  color: #7a5c66;
}

.meta-item svg {
  height: 0.75rem;
  width: 0.75rem;
  color: #7a5c66;
  opacity: 0.7;
}

.user-role-badge {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
  font-weight: 500;
  border-radius: 9999px;
  flex-shrink: 0;
}

.user-stats {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 0;
  border-top: 1px solid rgba(243, 210, 218, 0.5);
  border-bottom: 1px solid rgba(243, 210, 218, 0.5);
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  flex: 1;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #33222a;
}

.stat-label {
  font-size: 0.75rem;
  color: #7a5c66;
  margin-top: 0.125rem;
}

.stat-divider {
  width: 1px;
  height: 2rem;
  background-color: rgba(243, 210, 218, 0.5);
}

.user-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 1rem;
  padding-top: 0.75rem;
  border-top: 1px solid rgba(243, 210, 218, 0.5);
}

.status-badge {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
  font-weight: 500;
  border-radius: 9999px;
}

.user-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.action-btn {
  padding: 0.5rem;
  border-radius: 0.5rem;
  color: #7a5c66;
  transition: color 0.2s ease, background-color 0.2s ease;
}

.action-btn:hover {
  color: #b33f62;
  background-color: #fff5f7;
}

@media (max-width: 768px) {
  .user-card {
    padding: 1rem;
  }
  
  .user-header {
    gap: 0.5rem;
  }
  
  .user-avatar {
    height: 2.5rem;
    width: 2.5rem;
  }
  
  .avatar-text {
    font-size: 1.125rem;
  }
  
  .stat-value {
    font-size: 1.25rem;
  }
  
  .stat-label {
    font-size: 0.75rem;
  }
}
</style>