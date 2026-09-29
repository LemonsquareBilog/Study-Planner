<template>
  <div class="space-y-8">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-text-primary">Notifications</h1>
        <p class="mt-1 text-sm text-text-secondary">Stay updated with reminders and system notifications.</p>
      </div>
      <div class="flex items-center space-x-3">
        <select v-model="filterUnread" @change="fetchNotifications" class="input-field-glass w-auto" aria-label="Filter notifications">
          <option value="false">All</option>
          <option value="true">Unread only</option>
        </select>
        <button v-if="unreadCount > 0" @click="markAllRead" class="btn-secondary text-sm" aria-label="Mark all as read">
          Mark all read ({{ unreadCount }})
        </button>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-12" role="status" aria-label="Loading notifications">
      <svg class="animate-spin h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
      </svg>
    </div>

    <div v-else-if="notifications.data.length === 0" class="text-center py-12 bg-brand-navbar-cards-alt border border-brand-borders rounded-xl">
      <svg class="mx-auto h-12 w-12 text-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-text-primary">No notifications</h3>
      <p class="mt-1 text-sm text-text-secondary">You're all caught up!</p>
    </div>

    <div v-else class="bg-brand-navbar-cards-alt border border-brand-borders rounded-xl overflow-hidden">
      <div class="divide-y divide-brand-borders">
        <div v-for="notification in notifications.data" :key="notification.id" class="p-6 hover:bg-brand-navbar-cards" :class="{ 'bg-primary-light/30': !notification.read_at }">
          <div class="flex items-start justify-between">
            <div class="flex-1 min-w-0">
              <div class="flex items-center space-x-3 mb-2">
                <h3 class="text-lg font-semibold text-text-primary">{{ notification.title }}</h3>
                <span v-if="!notification.read_at" class="px-2 py-1 text-xs font-medium bg-primary-light text-primary rounded-full">New</span>
                <span class="px-2 py-1 text-xs font-medium rounded-full" :class="typeClass(notification.type)">{{ formatType(notification.type) }}</span>
              </div>
              <p class="text-text-secondary">{{ notification.message }}</p>
              <p class="mt-2 text-sm text-text-secondary">{{ formatDate(notification.created_at) }}</p>
            </div>
            <div class="flex items-center space-x-2 ml-4">
              <button v-if="!notification.read_at" @click="markRead(notification)" class="btn-secondary text-sm py-1 px-2" aria-label="Mark as read">Mark read</button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="notifications.last_page > 1" class="px-6 py-4 border-t border-brand-borders">
        <nav class="flex items-center justify-between" aria-label="Pagination">
          <p class="text-sm text-text-secondary">Showing {{ notifications.from }} to {{ notifications.to }} of {{ notifications.total }} notifications</p>
          <div class="flex space-x-2">
            <button @click="fetchNotifications({ page: notifications.current_page - 1, unread: filterUnread === 'true' ? true : undefined })" :disabled="notifications.current_page === 1" class="btn-secondary text-sm" aria-label="Previous page">Previous</button>
            <button @click="fetchNotifications({ page: notifications.current_page + 1, unread: filterUnread === 'true' ? true : undefined })" :disabled="notifications.current_page === notifications.last_page" class="btn-secondary text-sm" aria-label="Next page">Next</button>
          </div>
        </nav>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useStudentStore } from '@/stores/student';

const studentStore = useStudentStore();

const notifications = ref({ data: [], current_page: 1, last_page: 1, from: 0, to: 0, total: 0 });
const loading = ref(true);
const filterUnread = ref('false');

onMounted(async () => {
  await fetchNotifications();
});

async function fetchNotifications(params = {}) {
  loading.value = true;
  try {
    const query = { ...params, unread: filterUnread.value === 'true' };
    const response = await studentStore.fetchNotifications(query);
    notifications.value = response;
  } catch (err) {
    console.error('Failed to load notifications:', err);
  } finally {
    loading.value = false;
  }
}

async function markRead(notification: any) {
  try {
    await studentStore.markNotificationRead(notification.id);
    notification.read_at = new Date().toISOString();
  } catch (err) {
    console.error('Failed to mark notification as read:', err);
  }
}

async function markAllRead() {
  try {
    await studentStore.markAllNotificationsRead();
    notifications.value.data.forEach((n: any) => n.read_at = new Date().toISOString());
  } catch (err) {
    console.error('Failed to mark all notifications as read:', err);
  }
}

const unreadCount = computed(() => notifications.value.data?.filter((n: any) => !n.read_at).length || 0);

function typeClass(type: string) {
  const classes: Record<string, string> = {
    task_reminder: 'bg-primary-light text-primary',
    deadline_reminder: 'bg-status-urgent-light text-status-urgent',
    study_session_reminder: 'bg-status-success-light text-status-success',
    recommendation: 'bg-status-info-light text-status-info',
    system: 'bg-brand-borders text-text-secondary',
  };
  return classes[type] || 'bg-brand-borders text-text-secondary';
}

function formatType(type: string) {
  return type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function formatDate(dateString: string) {
  return new Date(dateString).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>