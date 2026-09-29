import { defineStore } from 'pinia';
import { ref } from 'vue';
import { api } from '@/services/api';
import type { Resource, User, Subject, Topic, SystemSetting, PaginatedResponse } from '@/types';

export const useAdminStore = defineStore('admin', () => {
  const resources = ref<Resource[]>([]);
  const users = ref<User[]>([]);
  const systemSettings = ref<SystemSetting[]>([]);
  const subjects = ref<Subject[]>([]);

  const loading = ref(false);
  const error = ref<string | null>(null);

  async function fetchResources(params?: { resource_type?: string; difficulty?: string; search?: string }) {
    loading.value = true;
    try {
      const response = await api.getAdminResources(params);
      resources.value = response.data;
      return response;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load resources';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchResourceOptions() {
    try {
      return await api.getAdminResourceOptions();
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load resource options';
      throw err;
    }
  }

  async function fetchSubjects() {
    subjects.value = await api.getAdminSubjects();
    return subjects.value;
  }

  async function fetchSubjectOptions() {
    return await api.getAdminSubjectOptions();
  }

  async function createSubject(data: Partial<Subject> & { user_id: number }) {
    const subject = await api.createAdminSubject(data);
    subjects.value.unshift(subject);
    return subject;
  }

  async function updateSubject(id: number, data: Partial<Subject>) {
    const subject = await api.updateAdminSubject(id, data);
    const index = subjects.value.findIndex(item => item.id === id);
    if (index !== -1) subjects.value[index] = subject;
    return subject;
  }

  async function deleteSubject(id: number) {
    await api.deleteAdminSubject(id);
    subjects.value = subjects.value.filter(item => item.id !== id);
  }

  async function createTopic(subjectId: number, data: Partial<Topic>) {
    const topic = await api.createAdminTopic(subjectId, data);
    const subject = subjects.value.find(item => item.id === subjectId);
    subject?.topics?.push(topic);
    return topic;
  }

  async function updateTopic(subjectId: number, topicId: number, data: Partial<Topic>) {
    const topic = await api.updateAdminTopic(subjectId, topicId, data);
    const subject = subjects.value.find(item => item.id === subjectId);
    const index = subject?.topics?.findIndex(item => item.id === topicId) ?? -1;
    if (subject?.topics && index >= 0) subject.topics[index] = topic;
    return topic;
  }

  async function deleteTopic(subjectId: number, topicId: number) {
    await api.deleteAdminTopic(subjectId, topicId);
    const subject = subjects.value.find(item => item.id === subjectId);
    if (subject?.topics) subject.topics = subject.topics.filter(item => item.id !== topicId);
  }

  async function createResource(data: Partial<Resource> & { subject_ids?: number[]; topic_ids?: number[] }) {
    loading.value = true;
    try {
      const resource = await api.createResource(data);
      resources.value.unshift(resource);
      return resource;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create resource';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function updateResource(id: number, data: Partial<Resource> & { subject_ids?: number[]; topic_ids?: number[] }) {
    loading.value = true;
    try {
      const resource = await api.updateResource(id, data);
      const index = resources.value.findIndex(r => r.id === id);
      if (index !== -1) resources.value[index] = resource;
      return resource;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update resource';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function deleteResource(id: number) {
    loading.value = true;
    try {
      await api.deleteResource(id);
      resources.value = resources.value.filter(r => r.id !== id);
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete resource';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchUsers(params?: { role?: string; is_active?: boolean; search?: string }) {
    loading.value = true;
    try {
      const response = await api.getAdminUsers(params);
      users.value = response.data;
      return response;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load users';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function updateUser(id: number, data: Partial<User>) {
    loading.value = true;
    try {
      const user = await api.updateAdminUser(id, data);
      const index = users.value.findIndex(u => u.id === id);
      if (index !== -1) users.value[index] = user;
      return user;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update user';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchSystemSettings() {
    loading.value = true;
    try {
      systemSettings.value = await api.getSystemSettings();
      return systemSettings.value;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load system settings';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function updateSystemSetting(id: number, data: Partial<SystemSetting>) {
    loading.value = true;
    try {
      const setting = await api.updateSystemSetting(id, data);
      const index = systemSettings.value.findIndex(s => s.id === id);
      if (index !== -1) systemSettings.value[index] = setting;
      return setting;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update system setting';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  return {
    resources,
    subjects,
    users,
    systemSettings,
    loading,
    error,
    fetchResources,
    fetchResourceOptions,
    fetchSubjects,
    fetchSubjectOptions,
    createSubject,
    updateSubject,
    deleteSubject,
    createTopic,
    updateTopic,
    deleteTopic,
    createResource,
    updateResource,
    deleteResource,
    fetchUsers,
    updateUser,
    fetchSystemSettings,
    updateSystemSetting,
  };
});