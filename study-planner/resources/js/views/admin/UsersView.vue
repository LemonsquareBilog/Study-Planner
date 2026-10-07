<template>
  <div class="space-y-8">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-text-primary">Users</h1>
        <p class="mt-1 text-sm text-text-secondary">Manage user accounts and roles.</p>
      </div>
    </div>

    <div class="glass-card p-4 mb-6">
      <div class="flex flex-wrap gap-4">
        <input type="text" v-model="filters.search" @input="debouncedFetch" placeholder="Search users..." class="input-field-glass flex-1 min-w-[200px]" aria-label="Search users" />
        <select v-model="filters.role" @change="fetchUsers" class="input-field-glass w-auto" aria-label="Filter by role">
          <option value="">All Roles</option>
          <option value="admin">Admin</option>
          <option value="student">Student</option>
          <option value="instructor">Instructor</option>
        </select>
        <select v-model="filters.is_active" @change="fetchUsers" class="input-field-glass w-auto" aria-label="Filter by status">
          <option value="">All Statuses</option>
          <option value="true">Active</option>
          <option value="false">Inactive</option>
        </select>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-12" role="status" aria-label="Loading users">
      <svg class="animate-spin h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
      </svg>
    </div>

    <div v-else class="glass-card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-brand-borders/50" role="table">
          <thead class="bg-white/50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">User</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">Role</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">Course</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">Year</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">Stats</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-text-secondary uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-brand-borders/50" role="rowgroup">
            <tr v-for="user in users.data" :key="user.id" class="hover:bg-white/30 transition-colors" role="row">
              <td class="px-6 py-4">
                <div class="text-sm font-bold text-text-primary">{{ user.name }}</div>
                <div class="text-sm text-text-secondary">{{ user.email }}</div>
              </td>
              <td class="px-6 py-4">
                <span class="px-2 py-1 text-xs font-medium rounded-full" :class="roleClass(user.role)">{{ user.role }}</span>
              </td>
              <td class="px-6 py-4 text-sm text-text-secondary">{{ user.course || '—' }}</td>
              <td class="px-6 py-4 text-sm text-text-secondary">{{ user.year_level || '—' }}</td>
              <td class="px-6 py-4">
                <span :class="user.is_active ? 'bg-status-success-light text-status-success' : 'bg-brand-borders text-text-secondary'" class="px-2 py-1 text-xs font-medium rounded-full">{{ user.is_active ? 'Active' : 'Inactive' }}</span>
              </td>
              <td class="px-6 py-4 text-sm text-text-secondary">
                {{ user.subjects_count || 0 }} subjects · {{ user.tasks_count || 0 }} tasks · {{ user.quiz_attempts_count || 0 }} quizzes
              </td>
              <td class="px-6 py-4 text-right space-x-2">
                <button @click="editUser(user)" class="text-secondary hover:text-secondary-hover text-sm font-medium transition-colors" aria-label="Edit user">Edit</button>
              </td>
            </tr>
            <tr v-if="users.data.length === 0">
              <td colspan="7" class="px-6 py-12 text-center text-text-secondary">No users found.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="users.last_page > 1" class="px-6 py-4 border-t border-brand-borders/50">
        <nav class="flex items-center justify-between" aria-label="Pagination">
          <p class="text-sm text-text-secondary">Showing {{ users.from }} to {{ users.to }} of {{ users.total }} users</p>
          <div class="flex space-x-2">
            <button @click="fetchUsers({ page: users.current_page - 1 })" :disabled="users.current_page === 1" class="btn-secondary text-sm" aria-label="Previous page">Previous</button>
            <button @click="fetchUsers({ page: users.current_page + 1 })" :disabled="users.current_page === users.last_page" class="btn-secondary text-sm" aria-label="Next page">Next</button>
          </div>
        </nav>
      </div>
    </div>
  </div>

  <!-- Edit Modal -->
  <Teleport to="body">
    <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="edit-modal-title">
      <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" @click="closeEditModal" aria-hidden="true"></div>
        <div class="relative glass-card shadow-xl max-w-md w-full p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 id="edit-modal-title" class="text-lg font-semibold text-text-primary">Edit User</h2>
            <button @click="closeEditModal" class="text-text-secondary hover:text-text-primary transition-colors" aria-label="Close modal">
              <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="submitEdit" class="space-y-4" novalidate>
            <div>
              <label for="edit-name" class="input-label">Name</label>
              <input id="edit-name" v-model="editForm.name" type="text" class="input-field-glass" />
            </div>
            <div>
              <label for="edit-email" class="input-label">Email</label>
              <input id="edit-email" v-model="editForm.email" type="email" class="input-field-glass" />
            </div>
            <div>
              <label for="edit-role" class="input-label">Role</label>
              <select id="edit-role" v-model="editForm.role" class="input-field-glass">
                <option value="student">Student</option>
                <option value="instructor">Instructor</option>
                <option value="admin">Admin</option>
              </select>
            </div>
            <div>
              <label for="edit-course" class="input-label">Course</label>
              <input id="edit-course" v-model="editForm.course" type="text" class="input-field-glass" />
            </div>
            <div>
              <label for="edit-year_level" class="input-label">Year Level</label>
              <input id="edit-year_level" v-model.number="editForm.year_level" type="number" min="1" max="10" class="input-field-glass" />
            </div>
            <div>
              <label class="flex items-center space-x-2">
                <input type="checkbox" v-model="editForm.is_active" class="h-4 w-4 text-primary border-brand-borders rounded focus:ring-primary/50" />
                <span class="text-sm text-text-secondary">Active</span>
              </label>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
              <button type="button" @click="closeEditModal" class="btn-secondary">Cancel</button>
              <button type="submit" :disabled="editing" class="btn-primary">
                <span v-if="editing" class="flex items-center">
                  <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
                  Saving...
                </span>
                <span v-else>Save Changes</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { useAdminStore } from '@/stores/admin';
import type { User } from '@/types';

const adminStore = useAdminStore();

const users = ref({ data: [] as User[], current_page: 1, last_page: 1, from: 0, to: 0, total: 0 });
const loading = ref(true);
const showEditModal = ref(false);
const editingUser = ref<User | null>(null);
const editing = ref(false);
const filters = ref({ search: '', role: '', is_active: '' });

const editForm = ref({
  name: '',
  email: '',
  role: 'student',
  course: '',
  year_level: 1,
  is_active: true,
});

const debounceTimer = ref<ReturnType<typeof setTimeout> | null>(null);

onMounted(async () => {
  await fetchUsers();
});

watch(() => adminStore.users, (newUsers) => {
  if (newUsers.length > 0) {
    users.value.data = newUsers;
  }
});

function debouncedFetch() {
  if (debounceTimer.value) clearTimeout(debounceTimer.value);
  debounceTimer.value = setTimeout(() => {
    fetchUsers({ page: 1 });
  }, 300);
}

async function fetchUsers(params = {}) {
  loading.value = true;
  try {
    const query = { ...filters.value, ...params };
    Object.keys(query).forEach(key => {
      if (query[key] === '' || query[key] === null) delete query[key];
    });
    const response = await adminStore.fetchUsers(query);
    users.value = response;
  } catch (err) {
    console.error('Failed to load users:', err);
  } finally {
    loading.value = false;
  }
}

function roleClass(role: string) {
  const classes: Record<string, string> = {
    admin: 'bg-status-info-light text-status-info',
    student: 'bg-primary-light text-primary',
    instructor: 'bg-status-success-light text-status-success',
  };
  return classes[role] || 'bg-brand-borders text-text-secondary';
}

function editUser(user: User) {
  editingUser.value = user;
  editForm.value = { name: user.name, email: user.email, role: user.role, course: user.course || '', year_level: user.year_level || 1, is_active: user.is_active };
  showEditModal.value = true;
}

function closeEditModal() {
  showEditModal.value = false;
  editingUser.value = null;
}

async function submitEdit() {
  if (!editingUser.value) return;
  editing.value = true;
  try {
    await adminStore.updateUser(editingUser.value.id, editForm.value);
    closeEditModal();
    await fetchUsers();
  } catch (err) {
    console.error('Failed to update user:', err);
  } finally {
    editing.value = false;
  }
}
</script>