import { Capacitor } from '@capacitor/core';
import axios, { AxiosInstance, AxiosError, InternalAxiosRequestConfig } from 'axios';
import { localApi } from './localApi';
import type { ApiResponse, PaginatedResponse } from '@/types';
import type { User, Subject, Topic, Task, StudySession, Quiz, Question, QuizAttempt, Resource, Notification, SystemSetting, StudyPreference, ProgressSummary, TopicPerformance, SetupStatus, AdminDashboardData } from '@/types';

const API_BASE_URL = import.meta.env.MODE === 'web'
  ? '/api'
  : import.meta.env.VITE_API_BASE_URL || '/api';
const IS_MOBILE = Capacitor.isNativePlatform();

class ApiService {
  private httpClient: AxiosInstance | null = null;
  private token: string | null = null;

  constructor() {
    if (!IS_MOBILE) {
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
          console.log('[API] Request:', config.method?.toUpperCase(), config.url);
          if (apiService.token) {
            config.headers.Authorization = `Bearer ${apiService.token}`;
          }
          return config;
        },
        (error: AxiosError) => {
          console.error('[API] Request error:', error);
          return Promise.reject(error);
        }
      );

      this.httpClient.interceptors.response.use(
        (response) => {
          console.log('[API] Response:', response.config.method?.toUpperCase(), response.config.url, response.status);
          return response;
        },
        (error: AxiosError) => {
          console.error('[API] Response error:', error.config?.method?.toUpperCase(), error.config?.url, error.response?.status, error.message);
          if (error.response?.status === 401) {
            apiService.clearToken();
            window.location.href = '/login';
          }
          return Promise.reject(error);
        }
      );
    }
  }

  private get api() {
    return IS_MOBILE ? localApi : this.httpClient!;
  }

  setToken(token: string) {
    this.token = token;
    localStorage.setItem('token', token);
    if (IS_MOBILE) localApi.setToken(token);
  }

  clearToken() {
    this.token = null;
    localStorage.removeItem('token');
    if (IS_MOBILE) localApi.clearToken();
  }

  loadToken() {
    this.token = localStorage.getItem('token');
    if (IS_MOBILE) localApi.loadToken();
  }

  async login(email: string, password: string) {
    if (IS_MOBILE) {
      const response = await localApi.login(email, password);
      this.token = response.data.token;
      return response.data;
    }
    const response = await this.api.post<ApiResponse<{ token: string; user: User }>>('/auth/login', { email, password });
    this.setToken(response.data.data.token);
    return response.data.data;
  }

  async register(name: string, email: string, password: string, password_confirmation: string) {
    if (IS_MOBILE) {
      const response = await localApi.register(name, email, password);
      this.token = response.data.token;
      return response.data;
    }
    const response = await this.api.post<ApiResponse<{ token: string; user: User }>>('/auth/register', { name, email, password, password_confirmation });
    this.setToken(response.data.data.token);
    return response.data.data;
  }

  async logout() {
    if (IS_MOBILE) {
      await localApi.logout();
      this.token = null;
      return;
    }
    await this.api.post('/auth/logout');
    this.clearToken();
  }

  async me() {
    if (IS_MOBILE) {
      const response = await localApi.me();
      return response.data;
    }
    const response = await this.api.get<ApiResponse<User>>('/auth/me');
    return response.data.data;
  }

  async updateProfile(data: { name: string; course?: string; year_level?: number }) {
    if (IS_MOBILE) {
      const response = await localApi.updateProfile(data);
      return response.data;
    }
    const response = await this.api.put<ApiResponse<User>>('/auth/profile', data);
    return response.data.data;
  }

  async updatePassword(data: { current_password: string; password: string; password_confirmation: string }) {
    if (IS_MOBILE) {
      await localApi.updatePassword(data);
      return;
    }
    const response = await this.api.put<ApiResponse<null>>('/auth/password', data);
    return response.data;
  }

  async getDashboard() {
    if (IS_MOBILE) {
      const subjects = await localApi.getSubjects();
      const tasks = await localApi.getTasks();
      const sessions = await localApi.getStudySessions();
      return {
        subjects: subjects.data,
        tasks: tasks.data,
        study_sessions: sessions.data,
      };
    }
    const response = await this.api.get<ApiResponse<any>>('/student/dashboard');
    return response.data.data;
  }

  async getSubjects() {
    const response = await this.api.get<ApiResponse<Subject[]>>('/student/subjects');
    return IS_MOBILE ? response.data : response.data.data;
  }

  async getSubject(id: number) {
    const response = await this.api.get<ApiResponse<Subject>>(`/student/subjects/${id}`);
    return IS_MOBILE ? response.data : response.data.data;
  }

  async createSubject(data: Partial<Subject>) {
    const response = await this.api.post<ApiResponse<Subject>>('/student/subjects', data);
    return IS_MOBILE ? response.data : response.data.data;
  }

  async updateSubject(id: number, data: Partial<Subject>) {
    const response = await this.api.put<ApiResponse<Subject>>(`/student/subjects/${id}`, data);
    return IS_MOBILE ? response.data : response.data.data;
  }

  async deleteSubject(id: number) {
    await this.api.delete(`/student/subjects/${id}`);
  }

  async getTopics(subjectId: number) {
    const response = await this.api.get<ApiResponse<Topic[]>>(`/student/subjects/${subjectId}/topics`);
    return IS_MOBILE ? response.data : response.data.data;
  }

  async getTopic(subjectId: number, topicId: number) {
    const response = await this.api.get<ApiResponse<Topic>>(`/student/subjects/${subjectId}/topics/${topicId}`);
    return IS_MOBILE ? response.data : response.data.data;
  }

  async createTopic(subjectId: number, data: Partial<Topic>) {
    const response = await this.api.post<ApiResponse<Topic>>(`/student/subjects/${subjectId}/topics`, data);
    return IS_MOBILE ? response.data : response.data.data;
  }

  async updateTopic(subjectId: number, topicId: number, data: Partial<Topic>) {
    const response = await this.api.put<ApiResponse<Topic>>(`/student/subjects/${subjectId}/topics/${topicId}`, data);
    return IS_MOBILE ? response.data : response.data.data;
  }

  async deleteTopic(subjectId: number, topicId: number) {
    await this.api.delete(`/student/subjects/${subjectId}/topics/${topicId}`);
  }

  async getTasks(params?: { status?: string; priority?: string; subject_id?: number; from?: string; to?: string }) {
    const response = await this.api.get<PaginatedResponse<Task>>('/student/tasks', { params });
    return IS_MOBILE ? response : response.data;
  }

  async createTask(data: Partial<Task>) {
    const response = await this.api.post<ApiResponse<Task>>('/student/tasks', data);
    return IS_MOBILE ? response.data : response.data.data;
  }

  async updateTask(id: number, data: Partial<Task>) {
    const response = await this.api.put<ApiResponse<Task>>(`/student/tasks/${id}`, data);
    return IS_MOBILE ? response.data : response.data.data;
  }

  async deleteTask(id: number) {
    await this.api.delete(`/student/tasks/${id}`);
  }

  async completeTask(id: number) {
    const response = await this.api.post<ApiResponse<Task>>(`/student/tasks/${id}/complete`);
    return IS_MOBILE ? response.data : response.data.data;
  }

  async updateTaskStatus(id: number, status: string) {
    const response = await this.api.patch<ApiResponse<Task>>(`/student/tasks/${id}/status`, { status });
    return IS_MOBILE ? response.data : response.data.data;
  }

  async getStudySessions(params?: { from?: string; to?: string; status?: string }) {
    const response = await this.api.get<ApiResponse<StudySession[]>>('/student/study-plan', { params });
    return IS_MOBILE ? response.data : response.data.data;
  }

  async generateStudyPlan(from: string, to: string) {
    const response = await this.api.post<ApiResponse<{ created_sessions: number }>>('/student/study-plan/generate', { from, to });
    return IS_MOBILE ? response.data : response.data.data;
  }

  async updateStudySessionStatus(id: number, status: StudySession['status']) {
    const response = await this.api.patch<ApiResponse<StudySession>>(`/student/study-plan/${id}/status`, { status });
    return IS_MOBILE ? response.data : response.data.data;
  }

  async getQuizzes() {
    const response = await this.api.get<ApiResponse<Quiz[]>>('/student/quizzes');
    return IS_MOBILE ? response.data : response.data.data;
  }

  async getQuiz(id: number) {
    const response = await this.api.get<ApiResponse<Quiz>>(`/student/quizzes/${id}`);
    return IS_MOBILE ? response.data : response.data.data;
  }

  async submitQuiz(id: number, answers: Record<number, string>, started_at: string) {
    const response = await this.api.post<ApiResponse<QuizAttempt>>(`/student/quizzes/${id}/submit`, { answers, started_at });
    return IS_MOBILE ? response.data : response.data.data;
  }

  async getQuizHistory() {
    const response = await this.api.get<ApiResponse<QuizAttempt[]>>('/student/quiz-attempts');
    return IS_MOBILE ? response.data : response.data.data;
  }

  async getQuizAttempt(id: number) {
    const response = await this.api.get<ApiResponse<QuizAttempt>>(`/student/quiz-attempts/${id}`);
    return IS_MOBILE ? response.data : response.data.data;
  }

  async getRecommendations() {
    const response = await this.api.get<ApiResponse<Resource[]>>('/student/recommendations');
    return IS_MOBILE ? response.data : response.data.data;
  }

  async recordView(resourceId: number, topicId?: number) {
    const response = await this.api.post<ApiResponse<any>>(`/student/recommendations/${resourceId}/view`, { topic_id: topicId });
    return IS_MOBILE ? response.data : response.data.data;
  }

  async getStudyPreference() {
    const response = await this.api.get<ApiResponse<StudyPreference>>('/student/study-preference');
    return IS_MOBILE ? response.data : response.data.data;
  }

  async upsertStudyPreference(data: Partial<StudyPreference>) {
    const response = await this.api.post<ApiResponse<StudyPreference>>('/student/study-preference', data);
    return IS_MOBILE ? response.data : response.data.data;
  }

  async getProgress() {
    const response = await this.api.get<ApiResponse<{ summary: ProgressSummary; topic_performance: TopicPerformance[] }>>('/student/progress');
    return IS_MOBILE ? response.data : response.data.data;
  }

  async getNotifications(params?: { unread?: boolean; page?: number }) {
    const response = await this.api.get<ApiResponse<PaginatedResponse<Notification>>>('/student/notifications', { params });
    return IS_MOBILE ? response : response.data.data;
  }

  async getNotificationCount() {
    const response = await this.api.get<ApiResponse<{ unread: number; total: number }>>('/student/notifications/count');
    return IS_MOBILE ? response.data : response.data.data;
  }

  async markNotificationRead(id: string) {
    const response = await this.api.post<ApiResponse<Notification>>(`/student/notifications/${id}/read`);
    return IS_MOBILE ? response.data : response.data.data;
  }

  async markAllNotificationsRead() {
    const response = await this.api.post<ApiResponse<{ marked_read: number }>>('/student/notifications/read-all');
    return IS_MOBILE ? response.data : response.data.data;
  }

  async getAdminResources(params?: { resource_type?: string; difficulty?: string; search?: string }) {
    const response = await this.api.get<PaginatedResponse<Resource>>('/admin/resources', { params });
    return IS_MOBILE ? response : response.data;
  }

  async getAdminResourceOptions() {
    const response = await this.api.get<ApiResponse<{ subjects: Subject[]; topics: (Topic & { subject?: Subject })[] }>>('/admin/resource-options');
    return IS_MOBILE ? response.data : response.data.data;
  }

  async getAdminSubjects() {
    const response = await this.api.get<ApiResponse<Subject[]>>('/admin/subjects');
    return IS_MOBILE ? response.data : response.data.data;
  }

  async getAdminSubjectOptions() {
    const response = await this.api.get<ApiResponse<User[]>>('/admin/subjects/options');
    return IS_MOBILE ? response.data : response.data.data;
  }

  async createAdminSubject(data: Partial<Subject> & { user_id: number }) {
    const response = await this.api.post<ApiResponse<Subject>>('/admin/subjects', data);
    return IS_MOBILE ? response.data : response.data.data;
  }

  async updateAdminSubject(id: number, data: Partial<Subject>) {
    const response = await this.api.put<ApiResponse<Subject>>(`/admin/subjects/${id}`, data);
    return IS_MOBILE ? response.data : response.data.data;
  }

  async deleteAdminSubject(id: number) {
    await this.api.delete(`/admin/subjects/${id}`);
  }

  async createAdminTopic(subjectId: number, data: Partial<Topic>) {
    const response = await this.api.post<ApiResponse<Topic>>(`/admin/subjects/${subjectId}/topics`, data);
    return IS_MOBILE ? response.data : response.data.data;
  }

  async updateAdminTopic(subjectId: number, topicId: number, data: Partial<Topic>) {
    const response = await this.api.patch<ApiResponse<Topic>>(`/admin/subjects/${subjectId}/topics/${topicId}`, data);
    return IS_MOBILE ? response.data : response.data.data;
  }

  async deleteAdminTopic(subjectId: number, topicId: number) {
    await this.api.delete(`/admin/subjects/${subjectId}/topics/${topicId}`);
  }

  async createResource(data: Partial<Resource> & { subject_ids?: number[]; topic_ids?: number[] }) {
    const response = await this.api.post<ApiResponse<Resource>>('/admin/resources', data);
    return IS_MOBILE ? response.data : response.data.data;
  }

  async updateResource(id: number, data: Partial<Resource> & { subject_ids?: number[]; topic_ids?: number[] }) {
    const response = await this.api.patch<ApiResponse<Resource>>(`/admin/resources/${id}`, data);
    return IS_MOBILE ? response.data : response.data.data;
  }

  async deleteResource(id: number) {
    await this.api.delete(`/admin/resources/${id}`);
  }

  async getAdminUsers(params?: { role?: string; is_active?: boolean; search?: string }) {
    const response = await this.api.get<PaginatedResponse<User>>('/admin/users', { params });
    return IS_MOBILE ? response : response.data;
  }

  async updateAdminUser(id: number, data: Partial<User>) {
    const response = await this.api.patch<ApiResponse<User>>(`/admin/users/${id}`, data);
    return IS_MOBILE ? response.data : response.data.data;
  }

  async getSystemSettings() {
    const response = await this.api.get<ApiResponse<SystemSetting[]>>('/admin/system-settings');
    return IS_MOBILE ? response.data : response.data.data;
  }

  async updateSystemSetting(id: number, data: Partial<SystemSetting>) {
    const response = await this.api.patch<ApiResponse<SystemSetting>>(`/admin/system-settings/${id}`, data);
    return IS_MOBILE ? response.data : response.data.data;
  }

  async getAdminDashboard() {
    const response = await this.api.get<ApiResponse<any>>('/admin/dashboard');
    return IS_MOBILE ? response.data : response.data.data;
  }

  async getAdminUser(id: number) {
    const response = await this.api.get<ApiResponse<User>>(`/admin/users/${id}`);
    return IS_MOBILE ? response.data : response.data.data;
  }

  async getSetupStatus() {
    const response = await this.api.get<ApiResponse<SetupStatus>>('/student/setup-status');
    return IS_MOBILE ? response.data : response.data.data;
  }
}

export const api = new ApiService();