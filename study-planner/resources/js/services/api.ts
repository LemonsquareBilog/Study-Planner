import { Capacitor } from '@capacitor/core';
import axios, { AxiosInstance, AxiosError, InternalAxiosRequestConfig } from 'axios';
import { localApi } from './localApi';
import type {
  ApiResponse,
  PaginatedResponse,
  User,
  Subject,
  Topic,
  Task,
  StudySession,
  Quiz,
  Question,
  QuizAttempt,
  Resource,
  Notification,
  SystemSetting,
  StudyPreference,
  ProgressSummary,
  TopicPerformance,
  SetupStatus,
  AdminDashboardData,
} from '@/types';

const API_BASE_URL = ['web', 'development'].includes(import.meta.env.MODE)
  ? '/api'
  : import.meta.env.VITE_API_BASE_URL || '/api';

export function isLocalPlatform(): boolean {
  if (Capacitor.isNativePlatform()) return !import.meta.env.VITE_API_BASE_URL;
  if (typeof window !== 'undefined') {
    if (window.location.protocol === 'file:') return true;
    if (localStorage.getItem('sp_demo_mode') === 'true') return true;
    if (window.location.pathname.includes('study-planner-preview')) return true;
  }
  return false;
}

export function isBackendUnavailable(err: any): boolean {
  if (!err) return false;
  if (!err.response) return true;
  const status = err.response.status;
  return status === 502 || status === 503 || status === 504 || status === 404;
}

class ApiService {
  private httpClient: AxiosInstance | null = null;
  public token: string | null = null;

  constructor() {
    this.token = typeof window !== 'undefined' ? localStorage.getItem('token') : null;

    this.httpClient = axios.create({
      baseURL: API_BASE_URL,
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      timeout: 15000,
      withCredentials: true,
    });

    const apiService = this;

    this.httpClient.interceptors.request.use(
      (config: InternalAxiosRequestConfig) => {
        if (apiService.token) {
          config.headers.Authorization = `Bearer ${apiService.token}`;
        }
        return config;
      },
      (error: AxiosError) => {
        return Promise.reject(error);
      }
    );

    this.httpClient.interceptors.response.use(
      (response) => response,
      (error: AxiosError) => {
        if (error.response?.status === 401 && !isLocalPlatform()) {
          apiService.clearToken();
          if (typeof window !== 'undefined') {
            void import('@/stores/auth').then(({ useAuthStore }) => {
              const authStore = useAuthStore();
              authStore.user = null;
              authStore.isAuthenticated = false;
              return import('@/router');
            }).then(({ default: router }) => router.replace('/login'));
          }
        }
        return Promise.reject(error);
      }
    );
  }

  public get isLocal(): boolean {
    return isLocalPlatform();
  }

  setToken(token: string) {
    this.token = token;
    localStorage.setItem('token', token);
    localApi.setToken(token);
  }

  clearToken() {
    this.token = null;
    localStorage.removeItem('token');
    localApi.clearToken();
  }

  loadToken() {
    this.token = localStorage.getItem('token');
    localApi.loadToken();
  }

  async login(email: string, password: string) {
    if (this.isLocal) {
      const response = await localApi.login(email, password);
      this.setToken(response.data.token);
      return response.data;
    }
<<<<<<< HEAD
    const response = await this.httpClient!.post<ApiResponse<{ token: string; user: User }>>('/auth/login', { email, password });
    this.setToken(response.data.data.token);
    return response.data.data;
=======
    try {
      const response = await this.httpClient!.post<ApiResponse<{ token: string; user: User }>>('/auth/login', { email, password });
      this.setToken(response.data.data.token);
      return response.data.data;
    } catch (err: any) {
      // Fallback to localApi if backend is unreachable (no response, 502/503/504 proxy error, or 404)
      if (isBackendUnavailable(err) && typeof window !== 'undefined') {
        console.warn('[API] Backend unreachable, falling back to local demo mode');
        localStorage.setItem('sp_demo_mode', 'true');
        const response = await localApi.login(email, password);
        this.setToken(response.data.token);
        return response.data;
      }
      throw err;
    }
>>>>>>> 4d79286ed13a9ba0e37eb7bd18e6be79301d8a22
  }

  async register(name: string, email: string, password: string, password_confirmation: string) {
    if (this.isLocal) {
      const response = await localApi.register(name, email, password);
      this.setToken(response.data.token);
      return response.data;
    }
<<<<<<< HEAD
    const response = await this.httpClient!.post<ApiResponse<{ token: string; user: User }>>('/auth/register', { name, email, password, password_confirmation });
    this.setToken(response.data.data.token);
    return response.data.data;
=======
    try {
      const response = await this.httpClient!.post<ApiResponse<{ token: string; user: User }>>('/auth/register', { name, email, password, password_confirmation });
      this.setToken(response.data.data.token);
      return response.data.data;
    } catch (err: any) {
      if (isBackendUnavailable(err) && typeof window !== 'undefined') {
        localStorage.setItem('sp_demo_mode', 'true');
        const response = await localApi.register(name, email, password);
        this.setToken(response.data.token);
        return response.data;
      }
      throw err;
    }
>>>>>>> 4d79286ed13a9ba0e37eb7bd18e6be79301d8a22
  }

  async logout() {
    if (this.isLocal) {
      await localApi.logout();
      this.token = null;
      return;
    }
    try {
      await this.httpClient!.post('/auth/logout');
    } finally {
      this.clearToken();
    }
  }

  async me() {
    if (this.isLocal) {
      const response = await localApi.me();
      return response.data;
    }
    try {
      const response = await this.httpClient!.get<ApiResponse<User>>('/auth/me');
      return response.data.data;
    } catch (err: any) {
      if ((isBackendUnavailable(err) || !err.response) && (this.token?.startsWith('local-token-') || isLocalPlatform())) {
        const response = await localApi.me();
        return response.data;
      }
      throw err;
    }
  }

  async updateProfile(data: { name: string; course?: string; year_level?: number }) {
    if (this.isLocal) {
      const response = await localApi.updateProfile(data);
      return response.data;
    }
    const response = await this.httpClient!.put<ApiResponse<User>>('/auth/profile', data);
    return response.data.data;
  }

  async updatePassword(data: { current_password: string; password: string; password_confirmation: string }) {
    if (this.isLocal) {
      await localApi.updatePassword(data);
      return;
    }
    const response = await this.httpClient!.put<ApiResponse<null>>('/auth/password', data);
    return response.data;
  }

  async getDashboard() {
    if (this.isLocal) {
      const response = await localApi.getDashboard();
      return response.data;
    }
    const response = await this.httpClient!.get<ApiResponse<any>>('/student/dashboard');
    return response.data.data;
  }

  async getSubjects() {
    if (this.isLocal) {
      const response = await localApi.getSubjects();
      return response.data;
    }
    const response = await this.httpClient!.get<ApiResponse<Subject[]>>('/student/subjects');
    return response.data.data;
  }

  async getSubject(id: number) {
    if (this.isLocal) {
      const response = await localApi.getSubject(id);
      return response.data;
    }
    const response = await this.httpClient!.get<ApiResponse<Subject>>(`/student/subjects/${id}`);
    return response.data.data;
  }

  async createSubject(data: Partial<Subject>) {
    if (this.isLocal) {
      const response = await localApi.createSubject(data);
      return response.data;
    }
    const response = await this.httpClient!.post<ApiResponse<Subject>>('/student/subjects', data);
    return response.data.data;
  }

  async updateSubject(id: number, data: Partial<Subject>) {
    if (this.isLocal) {
      const response = await localApi.updateSubject(id, data);
      return response.data;
    }
    const response = await this.httpClient!.put<ApiResponse<Subject>>(`/student/subjects/${id}`, data);
    return response.data.data;
  }

  async deleteSubject(id: number) {
    if (this.isLocal) {
      await localApi.deleteSubject(id);
      return;
    }
    await this.httpClient!.delete(`/student/subjects/${id}`);
  }

  async getTopics(subjectId: number) {
    if (this.isLocal) {
      const response = await localApi.getTopics(subjectId);
      return response.data;
    }
    const response = await this.httpClient!.get<ApiResponse<Topic[]>>(`/student/subjects/${subjectId}/topics`);
    return response.data.data;
  }

  async getTopic(subjectId: number, topicId: number) {
    if (this.isLocal) {
      const response = await localApi.getTopic(subjectId, topicId);
      return response.data;
    }
    const response = await this.httpClient!.get<ApiResponse<Topic>>(`/student/subjects/${subjectId}/topics/${topicId}`);
    return response.data.data;
  }

  async createTopic(subjectId: number, data: Partial<Topic>) {
    if (this.isLocal) {
      const response = await localApi.createTopic(subjectId, data);
      return response.data;
    }
    const response = await this.httpClient!.post<ApiResponse<Topic>>(`/student/subjects/${subjectId}/topics`, data);
    return response.data.data;
  }

  async updateTopic(subjectId: number, topicId: number, data: Partial<Topic>) {
    if (this.isLocal) {
      const response = await localApi.updateTopic(subjectId, topicId, data);
      return response.data;
    }
    const response = await this.httpClient!.put<ApiResponse<Topic>>(`/student/subjects/${subjectId}/topics/${topicId}`, data);
    return response.data.data;
  }

  async deleteTopic(subjectId: number, topicId: number) {
    if (this.isLocal) {
      await localApi.deleteTopic(subjectId, topicId);
      return;
    }
    await this.httpClient!.delete(`/student/subjects/${subjectId}/topics/${topicId}`);
  }

  async getTasks(params?: { status?: string; priority?: string; subject_id?: number; from?: string; to?: string }) {
    if (this.isLocal) {
      return await localApi.getTasks(params);
    }
    const response = await this.httpClient!.get<PaginatedResponse<Task>>('/student/tasks', { params });
    return response.data;
  }

  async createTask(data: Partial<Task>) {
    if (this.isLocal) {
      const response = await localApi.createTask(data);
      return response.data;
    }
    const response = await this.httpClient!.post<ApiResponse<Task>>('/student/tasks', data);
    return response.data.data;
  }

  async updateTask(id: number, data: Partial<Task>) {
    if (this.isLocal) {
      const response = await localApi.updateTask(id, data);
      return response.data;
    }
    const response = await this.httpClient!.put<ApiResponse<Task>>(`/student/tasks/${id}`, data);
    return response.data.data;
  }

  async deleteTask(id: number) {
    if (this.isLocal) {
      await localApi.deleteTask(id);
      return;
    }
    await this.httpClient!.delete(`/student/tasks/${id}`);
  }

  async completeTask(id: number) {
    if (this.isLocal) {
      const response = await localApi.completeTask(id);
      return response.data;
    }
    const response = await this.httpClient!.post<ApiResponse<Task>>(`/student/tasks/${id}/complete`);
    return response.data.data;
  }

  async updateTaskStatus(id: number, status: string) {
    if (this.isLocal) {
      const response = await localApi.updateTaskStatus(id, status as any);
      return response.data;
    }
    const response = await this.httpClient!.patch<ApiResponse<Task>>(`/student/tasks/${id}/status`, { status });
    return response.data.data;
  }

  async getStudySessions(params?: { from?: string; to?: string; status?: string }) {
    if (this.isLocal) {
      const response = await localApi.getStudySessions(params);
      return response.data;
    }
    const response = await this.httpClient!.get<ApiResponse<StudySession[]>>('/student/study-plan', { params });
    return response.data.data;
  }

  async generateStudyPlan(from: string, to: string) {
    if (this.isLocal) {
      const response = await localApi.generateStudyPlan(from, to);
      return response.data;
    }
    const response = await this.httpClient!.post<ApiResponse<{ created_sessions: number }>>('/student/study-plan/generate', { from, to });
    return response.data.data;
  }

  async updateStudySessionStatus(id: number, status: StudySession['status']) {
    if (this.isLocal) {
      const response = await localApi.updateStudySessionStatus(id, status);
      return response.data;
    }
    const response = await this.httpClient!.patch<ApiResponse<StudySession>>(`/student/study-plan/${id}/status`, { status });
    return response.data.data;
  }

  async getQuizzes() {
    if (this.isLocal) {
      const response = await localApi.getQuizzes();
      return response.data;
    }
    const response = await this.httpClient!.get<ApiResponse<Quiz[]>>('/student/quizzes');
    return response.data.data;
  }

  async getQuiz(id: number) {
    if (this.isLocal) {
      const response = await localApi.getQuiz(id);
      return response.data;
    }
    const response = await this.httpClient!.get<ApiResponse<Quiz>>(`/student/quizzes/${id}`);
    return response.data.data;
  }

  async submitQuiz(id: number, answers: Record<number, string>, started_at: string) {
    if (this.isLocal) {
      const response = await localApi.submitQuiz(id, answers, started_at);
      return response.data;
    }
    const response = await this.httpClient!.post<ApiResponse<QuizAttempt>>(`/student/quizzes/${id}/submit`, { answers, started_at });
    return response.data.data;
  }

  async getQuizHistory() {
    if (this.isLocal) {
      const response = await localApi.getQuizHistory();
      return response.data;
    }
    const response = await this.httpClient!.get<ApiResponse<QuizAttempt[]>>('/student/quiz-attempts');
    return response.data.data;
  }

  async getQuizAttempt(id: number) {
    if (this.isLocal) {
      const response = await localApi.getQuizAttempt(id);
      return response.data;
    }
    const response = await this.httpClient!.get<ApiResponse<QuizAttempt>>(`/student/quiz-attempts/${id}`);
    return response.data.data;
  }

  async getRecommendations() {
    if (this.isLocal) {
      const response = await localApi.getRecommendations();
      return response.data;
    }
    const response = await this.httpClient!.get<ApiResponse<Resource[]>>('/student/recommendations');
    return response.data.data;
  }

  async recordView(resourceId: number, topicId?: number) {
    if (this.isLocal) {
      const response = await localApi.recordView(resourceId, topicId);
      return response.data;
    }
    const response = await this.httpClient!.post<ApiResponse<any>>(`/student/recommendations/${resourceId}/view`, { topic_id: topicId });
    return response.data.data;
  }

  async getStudyPreference() {
    if (this.isLocal) {
      const response = await localApi.getStudyPreference();
      return response.data;
    }
    const response = await this.httpClient!.get<ApiResponse<StudyPreference>>('/student/study-preference');
    return response.data.data;
  }

  async upsertStudyPreference(data: Partial<StudyPreference>) {
    if (this.isLocal) {
      const response = await localApi.upsertStudyPreference(data);
      return response.data;
    }
    const response = await this.httpClient!.post<ApiResponse<StudyPreference>>('/student/study-preference', data);
    return response.data.data;
  }

  async getProgress() {
    if (this.isLocal) {
      const response = await localApi.getProgress();
      return response.data;
    }
    const response = await this.httpClient!.get<ApiResponse<{ summary: ProgressSummary; topic_performance: TopicPerformance[] }>>('/student/progress');
    return response.data.data;
  }

  async getNotifications(params?: { unread?: boolean; page?: number }) {
    if (this.isLocal) {
      return await localApi.getNotifications(params);
    }
    const response = await this.httpClient!.get<ApiResponse<PaginatedResponse<Notification>>>('/student/notifications', { params });
    return response.data.data;
  }

  async getNotificationCount() {
    if (this.isLocal) {
      const response = await localApi.getNotificationCount();
      return response.data;
    }
    const response = await this.httpClient!.get<ApiResponse<{ unread: number; total: number }>>('/student/notifications/count');
    return response.data.data;
  }

  async markNotificationRead(id: string) {
    if (this.isLocal) {
      const response = await localApi.markNotificationRead(id);
      return response.data;
    }
    const response = await this.httpClient!.post<ApiResponse<Notification>>(`/student/notifications/${id}/read`);
    return response.data.data;
  }

  async markAllNotificationsRead() {
    if (this.isLocal) {
      const response = await localApi.markAllNotificationsRead();
      return response.data;
    }
    const response = await this.httpClient!.post<ApiResponse<{ marked_read: number }>>('/student/notifications/read-all');
    return response.data.data;
  }

  async getAdminResources(params?: { resource_type?: string; difficulty?: string; search?: string; page?: number }) {
    if (this.isLocal) {
      return await localApi.getAdminResources(params);
    }
    const response = await this.httpClient!.get<PaginatedResponse<Resource>>('/admin/resources', { params });
    return response.data;
  }

  async getAdminResourceOptions() {
    if (this.isLocal) {
      const response = await localApi.getAdminResourceOptions();
      return response.data;
    }
    const response = await this.httpClient!.get<ApiResponse<{ subjects: Subject[]; topics: (Topic & { subject?: Subject })[] }>>('/admin/resource-options');
    return response.data.data;
  }

  async getAdminSubjects() {
    if (this.isLocal) {
      const response = await localApi.getAdminSubjects();
      return response.data;
    }
    const response = await this.httpClient!.get<ApiResponse<Subject[]>>('/admin/subjects');
    return response.data.data;
  }

  async getAdminSubjectOptions() {
    if (this.isLocal) {
      const response = await localApi.getAdminSubjectOptions();
      return response.data;
    }
    const response = await this.httpClient!.get<ApiResponse<User[]>>('/admin/subjects/options');
    return response.data.data;
  }

  async createAdminSubject(data: Partial<Subject> & { user_id: number }) {
    if (this.isLocal) {
      const response = await localApi.createAdminSubject(data);
      return response.data;
    }
    const response = await this.httpClient!.post<ApiResponse<Subject>>('/admin/subjects', data);
    return response.data.data;
  }

  async updateAdminSubject(id: number, data: Partial<Subject>) {
    if (this.isLocal) {
      const response = await localApi.updateAdminSubject(id, data);
      return response.data;
    }
    const response = await this.httpClient!.put<ApiResponse<Subject>>(`/admin/subjects/${id}`, data);
    return response.data.data;
  }

  async deleteAdminSubject(id: number) {
    if (this.isLocal) {
      await localApi.deleteAdminSubject(id);
      return;
    }
    await this.httpClient!.delete(`/admin/subjects/${id}`);
  }

  async createAdminTopic(subjectId: number, data: Partial<Topic>) {
    if (this.isLocal) {
      const response = await localApi.createAdminTopic(subjectId, data);
      return response.data;
    }
    const response = await this.httpClient!.post<ApiResponse<Topic>>(`/admin/subjects/${subjectId}/topics`, data);
    return response.data.data;
  }

  async updateAdminTopic(subjectId: number, topicId: number, data: Partial<Topic>) {
    if (this.isLocal) {
      const response = await localApi.updateAdminTopic(subjectId, topicId, data);
      return response.data;
    }
    const response = await this.httpClient!.patch<ApiResponse<Topic>>(`/admin/subjects/${subjectId}/topics/${topicId}`, data);
    return response.data.data;
  }

  async deleteAdminTopic(subjectId: number, topicId: number) {
    if (this.isLocal) {
      await localApi.deleteAdminTopic(subjectId, topicId);
      return;
    }
    await this.httpClient!.delete(`/admin/subjects/${subjectId}/topics/${topicId}`);
  }

  async createResource(data: Partial<Resource> & { subject_ids?: number[]; topic_ids?: number[] }) {
    if (this.isLocal) {
      const response = await localApi.createResource(data);
      return response.data;
    }
    const response = await this.httpClient!.post<ApiResponse<Resource>>('/admin/resources', data);
    return response.data.data;
  }

  async updateResource(id: number, data: Partial<Resource> & { subject_ids?: number[]; topic_ids?: number[] }) {
    if (this.isLocal) {
      const response = await localApi.updateResource(id, data);
      return response.data;
    }
    const response = await this.httpClient!.patch<ApiResponse<Resource>>(`/admin/resources/${id}`, data);
    return response.data.data;
  }

  async deleteResource(id: number) {
    if (this.isLocal) {
      await localApi.deleteResource(id);
      return;
    }
    await this.httpClient!.delete(`/admin/resources/${id}`);
  }

  async getAdminUsers(params?: { role?: string; is_active?: boolean; search?: string; page?: number }) {
    if (this.isLocal) {
      return await localApi.getAdminUsers(params);
    }
    const response = await this.httpClient!.get<PaginatedResponse<User>>('/admin/users', { params });
    return response.data;
  }

  async updateAdminUser(id: number, data: Partial<User>) {
    if (this.isLocal) {
      const response = await localApi.updateAdminUser(id, data);
      return response.data;
    }
    const response = await this.httpClient!.patch<ApiResponse<User>>(`/admin/users/${id}`, data);
    return response.data.data;
  }

  async getSystemSettings() {
    if (this.isLocal) {
      const response = await localApi.getSystemSettings();
      return response.data;
    }
    const response = await this.httpClient!.get<ApiResponse<SystemSetting[]>>('/admin/system-settings');
    return response.data.data;
  }

  async updateSystemSetting(id: number, data: Partial<SystemSetting>) {
    if (this.isLocal) {
      const response = await localApi.updateSystemSetting(id, data);
      return response.data;
    }
    const response = await this.httpClient!.patch<ApiResponse<SystemSetting>>(`/admin/system-settings/${id}`, data);
    return response.data.data;
  }

  async getAdminDashboard() {
    if (this.isLocal) {
      const response = await localApi.getAdminDashboard();
      return response.data;
    }
    const response = await this.httpClient!.get<ApiResponse<any>>('/admin/dashboard');
    return response.data.data;
  }

  async getAdminUser(id: number) {
    if (this.isLocal) {
      const response = await localApi.getAdminUser(id);
      return response.data;
    }
    const response = await this.httpClient!.get<ApiResponse<User>>(`/admin/users/${id}`);
    return response.data.data;
  }

  async getSetupStatus() {
    if (this.isLocal) {
      const response = await localApi.getSetupStatus();
      return response.data;
    }
    const response = await this.httpClient!.get<ApiResponse<SetupStatus>>('/student/setup-status');
    return response.data.data;
  }
}

export const api = new ApiService();
