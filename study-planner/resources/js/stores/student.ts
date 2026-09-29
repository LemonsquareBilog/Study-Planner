import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { api } from '@/services/api';
import type { Subject, Topic, Task, StudySession, Quiz, QuizAttempt, Resource, Notification, ProgressSummary, TopicPerformance, StudyPreference } from '@/types';

export const useStudentStore = defineStore('student', () => {
  const subjects = ref<Subject[]>([]);
  const topics = ref<Topic[]>([]);
  const tasks = ref<Task[]>([]);
  const studySessions = ref<StudySession[]>([]);
  const quizzes = ref<Quiz[]>([]);
  const quizAttempts = ref<QuizAttempt[]>([]);
  const recommendations = ref<Resource[]>([]);
  const notifications = ref<Notification[]>([]);
  const progress = ref<ProgressSummary | null>(null);
  const topicPerformance = ref<TopicPerformance[]>([]);
  const studyPreference = ref<StudyPreference | null>(null);
  const upcomingTasks = ref<Task[]>([]);
  const upcomingSessionsList = ref<StudySession[]>([]);
  const unreadNotificationsCount = ref(0);

  const loading = ref(false);
  const error = ref<string | null>(null);

  const activeTasks = computed(() => tasks.value.filter(t => t.status !== 'completed' && t.status !== 'cancelled'));
  const completedTasks = computed(() => tasks.value.filter(t => t.status === 'completed'));
  const upcomingSessions = computed(() => 
    studySessions.value
      .filter(s => s.status === 'scheduled' && new Date(s.scheduled_date) >= new Date())
      .sort((a, b) => new Date(a.scheduled_date).getTime() - new Date(b.scheduled_date).getTime())
  );
  const unreadNotifications = computed(() => notifications.value.filter(n => !n.read_at).length);

  async function fetchDashboard() {
    console.log('[StudentStore] fetchDashboard() called');
    loading.value = true;
    try {
      const data = await api.getDashboard();
      console.log('[StudentStore] Dashboard data received:', data);
      progress.value = data.progress;
      recommendations.value = data.recommendations;
      upcomingTasks.value = data.upcoming_tasks || [];
      upcomingSessionsList.value = data.upcoming_sessions || [];
      unreadNotificationsCount.value = data.unread_notifications || 0;
      return data;
    } catch (err: any) {
      console.error('[StudentStore] fetchDashboard failed:', err);
      error.value = err.response?.data?.message || 'Failed to load dashboard';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchSubjects() {
    loading.value = true;
    try {
      subjects.value = await api.getSubjects();
      return subjects.value;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load subjects';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchSubject(id: number) {
    try {
      return await api.getSubject(id);
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load subject';
      throw err;
    }
  }

  async function createSubject(data: Partial<Subject>) {
    loading.value = true;
    try {
      const subject = await api.createSubject(data);
      subjects.value.push(subject);
      return subject;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create subject';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function updateSubject(id: number, data: Partial<Subject>) {
    loading.value = true;
    try {
      const subject = await api.updateSubject(id, data);
      const index = subjects.value.findIndex(s => s.id === id);
      if (index !== -1) subjects.value[index] = subject;
      return subject;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update subject';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function deleteSubject(id: number) {
    loading.value = true;
    try {
      await api.deleteSubject(id);
      subjects.value = subjects.value.filter(s => s.id !== id);
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete subject';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchTopics(subjectId: number) {
    loading.value = true;
    try {
      const fetchedTopics = await api.getTopics(subjectId);
      topics.value = fetchedTopics;
      return fetchedTopics;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load topics';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function createTopic(subjectId: number, data: Partial<Topic>) {
    loading.value = true;
    try {
      const topic = await api.createTopic(subjectId, data);
      topics.value.push(topic);
      return topic;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create topic';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function updateTopic(subjectId: number, topicId: number, data: Partial<Topic>) {
    loading.value = true;
    try {
      const topic = await api.updateTopic(subjectId, topicId, data);
      const index = topics.value.findIndex(t => t.id === topicId);
      if (index !== -1) topics.value[index] = topic;
      return topic;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update topic';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function deleteTopic(subjectId: number, topicId: number) {
    loading.value = true;
    try {
      await api.deleteTopic(subjectId, topicId);
      topics.value = topics.value.filter(t => t.id !== topicId);
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete topic';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchTasks(params?: { status?: string; priority?: string; subject_id?: number }) {
    loading.value = true;
    try {
      const response = await api.getTasks(params);
      tasks.value = response.data;
      return response;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load tasks';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function createTask(data: Partial<Task>) {
    loading.value = true;
    try {
      const task = await api.createTask(data);
      tasks.value.unshift(task);
      return task;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create task';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function updateTask(id: number, data: Partial<Task>) {
    loading.value = true;
    try {
      const task = await api.updateTask(id, data);
      const index = tasks.value.findIndex(t => t.id === id);
      if (index !== -1) tasks.value[index] = task;
      return task;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update task';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function completeTask(id: number) {
    loading.value = true;
    try {
      const task = await api.completeTask(id);
      const index = tasks.value.findIndex(t => t.id === id);
      if (index !== -1) tasks.value[index] = task;
      return task;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to complete task';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchStudySessions(params?: { from?: string; to?: string; status?: string }) {
    loading.value = true;
    try {
      studySessions.value = await api.getStudySessions(params);
      return studySessions.value;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load study sessions';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function generateStudyPlan(from: string, to: string) {
    loading.value = true;
    try {
      const result = await api.generateStudyPlan(from, to);
      await fetchStudySessions({ from, to });
      return result;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to generate study plan';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function updateStudySessionStatus(id: number, status: StudySession['status']) {
    try {
      const session = await api.updateStudySessionStatus(id, status);
      const index = studySessions.value.findIndex(item => item.id === id);
      if (index !== -1) studySessions.value[index] = session;
      const upcomingIndex = upcomingSessionsList.value.findIndex(item => item.id === id);
      if (upcomingIndex !== -1) {
        if (status === 'completed') upcomingSessionsList.value.splice(upcomingIndex, 1);
        else upcomingSessionsList.value[upcomingIndex] = session;
      }
      return session;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update study session';
      throw err;
    }
  }

  async function fetchQuizzes() {
    loading.value = true;
    try {
      quizzes.value = await api.getQuizzes();
      return quizzes.value;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load quizzes';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchQuiz(id: number) {
    try {
      return await api.getQuiz(id);
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load quiz';
      throw err;
    }
  }

  async function submitQuiz(id: number, answers: Record<number, string>, started_at: string) {
    loading.value = true;
    try {
      const attempt = await api.submitQuiz(id, answers, started_at);
      return attempt;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to submit quiz';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchRecommendations() {
    loading.value = true;
    try {
      recommendations.value = await api.getRecommendations();
      return recommendations.value;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load recommendations';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchProgress() {
    loading.value = true;
    try {
      const data = await api.getProgress();
      progress.value = data.summary;
      topicPerformance.value = data.topic_performance;
      return data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load progress';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchNotifications(params?: { unread?: boolean; page?: number }) {
    loading.value = true;
    try {
      const response = await api.getNotifications(params);
      notifications.value = response.data;
      return response;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load notifications';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function markNotificationRead(id: string) {
    try {
      const notification = await api.markNotificationRead(id);
      const index = notifications.value.findIndex(n => n.id === id);
      if (index !== -1) notifications.value[index] = notification;
      return notification;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to mark notification as read';
      throw err;
    }
  }

  async function markAllNotificationsRead() {
    try {
      const result = await api.markAllNotificationsRead();
      notifications.value.forEach(n => n.read_at = new Date().toISOString());
      return result;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to mark all notifications as read';
      throw err;
    }
  }

  async function fetchStudyPreference() {
    loading.value = true;
    try {
      const response = await api.getStudyPreference();
      studyPreference.value = response.data;
      return response.data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load study preferences';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function upsertStudyPreference(data: Partial<StudyPreference>) {
    loading.value = true;
    try {
      const pref = await api.upsertStudyPreference(data);
      studyPreference.value = pref;
      return pref;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update study preferences';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  return {
    subjects,
    topics,
    tasks,
    studySessions,
    quizzes,
    quizAttempts,
    recommendations,
    notifications,
    progress,
    topicPerformance,
    studyPreference,
    upcomingTasks,
    upcomingSessionsList,
    unreadNotificationsCount,
    loading,
    error,
    activeTasks,
    completedTasks,
    upcomingSessions,
    unreadNotifications,
    fetchDashboard,
    fetchSubjects,
    fetchSubject,
    createSubject,
    updateSubject,
    deleteSubject,
    fetchTopics,
    createTopic,
    updateTopic,
    deleteTopic,
    fetchTasks,
    createTask,
    updateTask,
    completeTask,
    fetchStudySessions,
    generateStudyPlan,
    updateStudySessionStatus,
    fetchQuizzes,
    fetchQuiz,
    submitQuiz,
    fetchRecommendations,
    fetchProgress,
    fetchNotifications,
    markNotificationRead,
    markAllNotificationsRead,
    fetchStudyPreference,
    upsertStudyPreference,
  };
});