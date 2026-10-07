<template>
  <div class="space-y-8">
    <div class="flex items-start justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-text-primary">Subjects & Topics</h1>
        <p class="mt-1 text-sm text-text-secondary">Manage each student's subject structure and learning topics.</p>
      </div>
      <button class="btn-primary" @click="openSubjectModal">Add Subject</button>
    </div>

    <div v-if="loading" class="flex justify-center py-12" role="status" aria-label="Loading subjects">
      <svg class="animate-spin h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12 4z" /></svg>
    </div>

    <div v-else-if="subjects.length === 0" class="glass-card p-10 text-center">
      <h2 class="font-semibold text-text-primary">No subjects yet</h2>
      <p class="mt-1 text-sm text-text-secondary">Create a subject and assign it to a student to begin.</p>
    </div>

    <div v-else class="grid grid-cols-1 xl:grid-cols-2 gap-6">
      <article v-for="subject in subjects" :key="subject.id" class="glass-card p-6">
        <div class="flex items-start justify-between gap-4">
          <div class="min-w-0">
            <h2 class="text-lg font-bold text-text-primary truncate">{{ subject.name }}</h2>
            <p class="mt-1 text-sm text-text-secondary">Student: <strong class="font-bold text-text-primary">{{ subject.user?.name }}</strong> <span class="text-text-secondary">({{ subject.user?.email }})</span></p>
            <p class="mt-2 text-sm text-text-secondary">{{ subject.description || 'No description' }}</p>
          </div>
          <span class="inline-flex shrink-0 items-center whitespace-nowrap px-2 py-1 rounded-full text-xs font-semibold" :class="difficultyClass(subject.difficulty)">Level {{ subject.difficulty }}</span>
        </div>

        <div class="mt-5 flex items-center justify-between border-t border-brand-borders/50 pt-4">
          <h3 class="text-sm font-semibold text-text-primary">Topics <span class="text-text-secondary">({{ subject.topics?.length || 0 }})</span></h3>
          <div class="flex gap-3 text-sm">
            <button class="text-secondary hover:text-secondary-hover font-medium transition-colors" @click="openSubjectModal(subject)">Edit subject</button>
            <button class="text-status-urgent hover:text-status-urgent-hover font-medium transition-colors" @click="removeSubject(subject)">Delete</button>
          </div>
        </div>

        <div v-if="!subject.topics?.length" class="mt-3 rounded-lg glass-card p-4 text-sm text-text-secondary">No topics linked to this subject.</div>
        <div v-else class="mt-3 space-y-2">
          <div v-for="topic in subject.topics" :key="topic.id" class="flex items-center justify-between gap-3 rounded-lg glass-card px-3 py-3">
            <div class="min-w-0">
              <p class="font-semibold text-text-primary truncate">{{ topic.name }}</p>
              <p class="text-xs text-text-secondary truncate">{{ subject.name }} · Level {{ topic.difficulty }}<span v-if="topic.description"> · {{ topic.description }}</span></p>
            </div>
            <div class="flex shrink-0 gap-3 text-sm">
              <button class="text-secondary hover:text-secondary-hover transition-colors" @click="openTopicModal(subject, topic)">Edit</button>
              <button class="text-status-urgent hover:text-status-urgent-hover transition-colors" @click="removeTopic(subject, topic)">Delete</button>
            </div>
          </div>
        </div>
        <button class="mt-4 btn-secondary text-sm" @click="openTopicModal(subject)">Add topic to {{ subject.name }}</button>
      </article>
    </div>

    <Teleport to="body">
      <div v-if="subjectModalOpen" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="subject-modal-title">
        <div class="flex min-h-full items-center justify-center p-4">
          <div class="fixed inset-0 bg-black/50" @click="closeModals" aria-hidden="true"></div>
          <form class="relative w-full max-w-lg space-y-4 rounded-xl glass-card p-6 shadow-xl" @submit.prevent="saveSubject">
            <h2 id="subject-modal-title" class="text-lg font-bold text-text-primary">{{ editingSubject ? 'Edit Subject' : 'Add Subject' }}</h2>
            <div v-if="!editingSubject">
              <label class="input-label" for="subject-student">Student</label>
              <select id="subject-student" v-model.number="subjectForm.user_id" class="input-field-glass" required>
                <option :value="0">Select a student</option>
                <option v-for="student in students" :key="student.id" :value="student.id">{{ student.name }} ({{ student.email }})</option>
              </select>
            </div>
            <div>
              <label class="input-label" for="admin-subject-name">Subject name</label>
              <input id="admin-subject-name" v-model="subjectForm.name" class="input-field-glass" required />
            </div>
            <div>
              <label class="input-label" for="admin-subject-description">Description</label>
              <textarea id="admin-subject-description" v-model="subjectForm.description" class="input-field-glass" rows="3"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <label class="text-sm font-medium text-text-secondary">Difficulty<select v-model.number="subjectForm.difficulty" class="input-field-glass mt-1"><option v-for="level in 5" :key="level" :value="level">Level {{ level }}</option></select></label>
              <label class="text-sm font-medium text-text-secondary">Color<select v-model="subjectForm.color" class="input-field-glass mt-1"><option v-for="color in colors" :key="color" :value="color">{{ color }}</option></select></label>
            </div>
            <div class="flex justify-end gap-3 pt-3"><button type="button" class="btn-secondary" @click="closeModals">Cancel</button><button class="btn-primary" :disabled="saving">{{ saving ? 'Saving...' : 'Save subject' }}</button></div>
          </form>
        </div>
      </div>

      <div v-if="topicModalOpen" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="topic-modal-title">
        <div class="flex min-h-full items-center justify-center p-4">
          <div class="fixed inset-0 bg-black/50" @click="closeModals" aria-hidden="true"></div>
          <form class="relative w-full max-w-lg space-y-4 rounded-xl glass-card p-6 shadow-xl" @submit.prevent="saveTopic">
            <h2 id="topic-modal-title" class="text-lg font-bold text-text-primary">{{ editingTopic ? 'Edit Topic' : 'Add Topic' }}</h2>
            <p class="text-sm text-text-secondary">Subject: <strong class="font-bold text-text-primary">{{ selectedSubject?.name }}</strong></p>
            <div><label class="input-label" for="admin-topic-name">Topic name</label><input id="admin-topic-name" v-model="topicForm.name" class="input-field-glass" required /></div>
            <div><label class="input-label" for="admin-topic-description">Description</label><textarea id="admin-topic-description" v-model="topicForm.description" class="input-field-glass" rows="3"></textarea></div>
            <label class="text-sm font-medium text-text-secondary">Difficulty<select v-model.number="topicForm.difficulty" class="input-field-glass mt-1"><option v-for="level in 5" :key="level" :value="level">Level {{ level }}</option></select></label>
            <div class="flex justify-end gap-3 pt-3"><button type="button" class="btn-secondary" @click="closeModals">Cancel</button><button class="btn-primary" :disabled="saving">{{ saving ? 'Saving...' : 'Save topic' }}</button></div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useAdminStore } from '@/stores/admin';
import type { Subject, Topic, User } from '@/types';

const adminStore = useAdminStore();
const subjects = ref<Subject[]>([]);
const students = ref<User[]>([]);
const loading = ref(true);
const saving = ref(false);
const subjectModalOpen = ref(false);
const topicModalOpen = ref(false);
const editingSubject = ref<Subject | null>(null);
const editingTopic = ref<Topic | null>(null);
const selectedSubject = ref<Subject | null>(null);
const colors = ['blue', 'green', 'purple', 'red', 'yellow', 'pink', 'indigo'];
const subjectForm = ref({ user_id: 0, name: '', description: '', difficulty: 3, color: 'blue', is_active: true });
const topicForm = ref({ name: '', description: '', difficulty: 3, is_active: true });

onMounted(async () => {
  try {
    [subjects.value, students.value] = await Promise.all([adminStore.fetchSubjects(), adminStore.fetchSubjectOptions()]);
  } finally {
    loading.value = false;
  }
});

function difficultyClass(level: number) {
  return level >= 4 ? 'bg-status-urgent-light text-status-urgent' : level === 3 ? 'bg-status-warning-light text-status-warning' : 'bg-status-success-light text-status-success';
}

function openSubjectModal(subject?: Subject) {
  editingSubject.value = subject || null;
  subjectForm.value = subject
    ? { user_id: subject.user_id, name: subject.name, description: subject.description || '', difficulty: subject.difficulty, color: subject.color, is_active: subject.is_active }
    : { user_id: 0, name: '', description: '', difficulty: 3, color: 'blue', is_active: true };
  subjectModalOpen.value = true;
}

function openTopicModal(subject: Subject, topic?: Topic) {
  selectedSubject.value = subject;
  editingTopic.value = topic || null;
  topicForm.value = topic
    ? { name: topic.name, description: topic.description || '', difficulty: topic.difficulty, is_active: topic.is_active }
    : { name: '', description: '', difficulty: 3, is_active: true };
  topicModalOpen.value = true;
}

function closeModals() {
  subjectModalOpen.value = false;
  topicModalOpen.value = false;
  editingSubject.value = null;
  editingTopic.value = null;
  selectedSubject.value = null;
}

async function saveSubject() {
  saving.value = true;
  try {
    if (editingSubject.value) await adminStore.updateSubject(editingSubject.value.id, subjectForm.value);
    else await adminStore.createSubject(subjectForm.value);
    subjects.value = await adminStore.fetchSubjects();
    closeModals();
  } finally {
    saving.value = false;
  }
}

async function saveTopic() {
  if (!selectedSubject.value) return;
  saving.value = true;
  try {
    if (editingTopic.value) await adminStore.updateTopic(selectedSubject.value.id, editingTopic.value.id, topicForm.value);
    else await adminStore.createTopic(selectedSubject.value.id, topicForm.value);
    subjects.value = await adminStore.fetchSubjects();
    closeModals();
  } finally {
    saving.value = false;
  }
}

async function removeSubject(subject: Subject) {
  if (!window.confirm(`Delete ${subject.name} and its topics?`)) return;
  await adminStore.deleteSubject(subject.id);
  subjects.value = adminStore.subjects;
}

async function removeTopic(subject: Subject, topic: Topic) {
  if (!window.confirm(`Delete ${topic.name}?`)) return;
  await adminStore.deleteTopic(subject.id, topic.id);
  subjects.value = await adminStore.fetchSubjects();
}
</script>