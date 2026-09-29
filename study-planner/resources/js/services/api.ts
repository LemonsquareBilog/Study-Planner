import axios, { AxiosInstance, AxiosError, InternalAxiosRequestConfig } from 'axios';
import type { ApiResponse, PaginatedResponse } from '@/types';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api';

class ApiService {
  private client: AxiosInstance;
  private token: string | null = null;

  constructor() {
    this.client = axios.create({
      baseURL: API_BASE_URL,
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      timeout: 15000,
      withCredentials: true,
    });

    const apiService = this;

    this.client.interceptors.request.use(
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

    this.client.interceptors.response.use(
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

  setToken(token: string) {
    this.token = token;
    localStorage.setItem('token', token);
  }

  clearToken() {
    this.token = null;
    localStorage.removeItem('token');
  }

  loadToken() {
    this.token = localStorage.getItem('token');
  }

  async login(email: string, password: string) {
    const response = await this.client.post<ApiResponse<{ token: string; user: User }>>('/auth/login', { email, password });
    this.setToken(response.data.data.token);
    return response.data.data;
  }

  async register(name: string, email: string, password: string, password_confirmation: string) {
    const response = await this.client.post<ApiResponse<{ token: string; user: User }>>('/auth/register', { name, email, password, password_confirmation });
    this.setToken(response.data.data.token);
    return response.data.data;
  }

  async logout() {
    await this.client.post('/auth/logout');
    this.clearToken();
  }

  async me() {
    const response = await this.client.get<ApiResponse<User>>('/auth/me');
    return response.data.data;
  }

  async updateProfile(data: { name: string; course?: string; year_level?: number }) {
    const response = await this.client.put<ApiResponse<User>>('/auth/profile', data);
    return response.data.data;
  }

  async updatePassword(data: { current_password: string; password: string; password_confirmation: string }) {
    const response = await this.client.put<ApiResponse<null>>('/auth/password', data);
    return response.data;
  }

  async getDashboard() {
    const response = await this.client.get<ApiResponse<any>>('/student/dashboard');
    return response.data.data;
  }

  async getSubjects() {
    const response = await this.client.get<ApiResponse<Subject[]>>('/student/subjects');
    return response.data.data;
  }

  async getSubject(id: number) {
    const response = await this.client.get<ApiResponse<Subject>>(`/student/subjects/${id}`);
    return response.data.data;
  }

  async createSubject(data: Partial<Subject>) {
    const response = await this.client.post<ApiResponse<Subject>>('/student/subjects', data);
    return response.data.data;
  }

  async updateSubject(id: number, data: Partial<Subject>) {
    const response = await this.client.put<ApiResponse<Subject>>(`/student/subjects/${id}`, data);
    return response.data.data;
  }

  async deleteSubject(id: number) {
    await this.client.delete(`/student/subjects/${id}`);
  }

  async getTopics(subjectId: number) {
    const response = await this.client.get<ApiResponse<Topic[]>>(`/student/subjects/${subjectId}/topics`);
    return response.data.data;
  }

  async getTopic(subjectId: number, topicId: number) {
    const response = await this.client.get<ApiResponse<Topic>>(`/student/subjects/${subjectId}/topics/${topicId}`);
    return response.data.data;
  }

  async createTopic(subjectId: number, data: Partial<Topic>) {
    const response = await this.client.post<ApiResponse<Topic>>(`/student/subjects/${subjectId}/topics`, data);
    return response.data.data;
  }

  async updateTopic(subjectId: number, topicId: number, data: Partial<Topic>) {
    const response = await this.client.put<ApiResponse<Topic>>(`/student/subjects/${subjectId}/topics/${topicId}`, data);
    return response.data.data;
  }

  async deleteTopic(subjectId: number, topicId: number) {
    await this.client.delete(`/student/subjects/${subjectId}/topics/${topicId}`);
  }

  async getTasks(params?: { status?: string; priority?: string; subject_id?: number }) {
    const response = await this.client.get<PaginatedResponse<Task>>('/student/tasks', { params });
    return response.data;
  }

  async createTask(data: Partial<Task>) {
    const response = await this.client.post<ApiResponse<Task>>('/student/tasks', data);
    return response.data.data;
  }

  async updateTask(id: number, data: Partial<Task>) {
    const response = await this.client.put<ApiResponse<Task>>(`/student/tasks/${id}`, data);
    return response.data.data;
  }

  async deleteTask(id: number) {
    await this.client.delete(`/student/tasks/${id}`);
  }

  async completeTask(id: number) {
    const response = await this.client.post<ApiResponse<Task>>(`/student/tasks/${id}/complete`);
    return response.data.data;
  }

  async updateTaskStatus(id: number, status: string) {
    const response = await this.client.patch<ApiResponse<Task>>(`/student/tasks/${id}/status`, { status });
    return response.data.data;
  }

  async getStudySessions(params?: { from?: string; to?: string; status?: string }) {
    const response = await this.client.get<ApiResponse<StudySession[]>>('/student/study-plan', { params });
    return response.data.data;
  }

  async generateStudyPlan(from: string, to: string) {
    const response = await this.client.post<ApiResponse<{ created_sessions: number }>>('/student/study-plan/generate', { from, to });
    return response.data.data;
  }

  async updateStudySessionStatus(id: number, status: StudySession['status']) {
    const response = await this.client.patch<ApiResponse<StudySession>>(`/student/study-plan/${id}/status`, { status });
    return response.data.data;
  }

  async getQuizzes() {
    const response = await this.client.get<ApiResponse<Quiz[]>>('/student/quizzes');
    return response.data.data;
  }

  async getQuiz(id: number) {
    const response = await this.client.get<ApiResponse<Quiz>>(`/student/quizzes/${id}`);
    return response.data.data;
  }

  async submitQuiz(id: number, answers: Record<number, string>, started_at: string) {
    const response = await this.client.post<ApiResponse<QuizAttempt>>(`/student/quizzes/${id}/submit`, { answers, started_at });
    return response.data.data;
  }

  async getRecommendations() {
    const response = await this.client.get<ApiResponse<Resource[]>>('/student/recommendations');
    return response.data.data;
  }

  async recordView(resourceId: number, topicId?: number) {
    const response = await this.client.post<ApiResponse<any>>(`/student/recommendations/${resourceId}/view`, { topic_id: topicId });
    return response.data.data;
  }

  async getStudyPreference() {
    const response = await this.client.get<ApiResponse<StudyPreference>>('/student/study-preference');
    return response.data;
  }

  async upsertStudyPreference(data: Partial<StudyPreference>) {
    const response = await this.client.post<ApiResponse<StudyPreference>>('/student/study-preference', data);
    return response.data.data;
  }

  async getProgress() {
    const response = await this.client.get<ApiResponse<{ summary: ProgressSummary; topic_performance: TopicPerformance[] }>>('/student/progress');
    return response.data.data;
  }

  async getNotifications(params?: { unread?: boolean; page?: number }) {
    const response = await this.client.get<ApiResponse<PaginatedResponse<Notification>>>('/student/notifications', { params });
    return response.data.data;
  }

  async getNotificationCount() {
    const response = await this.client.get<ApiResponse<{ unread: number; total: number }>>('/student/notifications/count');
    return response.data.data;
  }

  async markNotificationRead(id: string) {
    const response = await this.client.post<ApiResponse<Notification>>(`/student/notifications/${id}/read`);
    return response.data.data;
  }

  async markAllNotificationsRead() {
    const response = await this.client.post<ApiResponse<{ marked_read: number }>>('/student/notifications/read-all');
    return response.data.data;
  }

  async getAdminResources(params?: { resource_type?: string; difficulty?: string; search?: string }) {
    const response = await this.client.get<PaginatedResponse<Resource>>('/admin/resources', { params });
    return response.data;
  }

  async getAdminResourceOptions() {
    const response = await this.client.get<ApiResponse<{ subjects: Subject[]; topics: (Topic & { subject?: Subject })[] }>>('/admin/resource-options');
    return response.data.data;
  }

  async getAdminSubjects() {
    const response = await this.client.get<ApiResponse<Subject[]>>('/admin/subjects');
    return response.data.data;
  }

  async getAdminSubjectOptions() {
    const response = await this.client.get<ApiResponse<User[]>>('/admin/subjects/options');
    return response.data.data;
  }

  async createAdminSubject(data: Partial<Subject> & { user_id: number }) {
    const response = await this.client.post<ApiResponse<Subject>>('/admin/subjects', data);
    return response.data.data;
  }

  async updateAdminSubject(id: number, data: Partial<Subject>) {
    const response = await this.client.put<ApiResponse<Subject>>(`/admin/subjects/${id}`, data);
    return response.data.data;
  }

  async deleteAdminSubject(id: number) {
    await this.client.delete(`/admin/subjects/${id}`);
  }

  async createAdminTopic(subjectId: number, data: Partial<Topic>) {
    const response = await this.client.post<ApiResponse<Topic>>(`/admin/subjects/${subjectId}/topics`, data);
    return response.data.data;
  }

  async updateAdminTopic(subjectId: number, topicId: number, data: Partial<Topic>) {
    const response = await this.client.patch<ApiResponse<Topic>>(`/admin/subjects/${subjectId}/topics/${topicId}`, data);
    return response.data.data;
  }

  async deleteAdminTopic(subjectId: number, topicId: number) {
    await this.client.delete(`/admin/subjects/${subjectId}/topics/${topicId}`);
  }

  async createResource(data: Partial<Resource> & { subject_ids?: number[]; topic_ids?: number[] }) {
    const response = await this.client.post<ApiResponse<Resource>>('/admin/resources', data);
    return response.data.data;
  }

  async updateResource(id: number, data: Partial<Resource> & { subject_ids?: number[]; topic_ids?: number[] }) {
    const response = await this.client.patch<ApiResponse<Resource>>(`/admin/resources/${id}`, data);
    return response.data.data;
  }

  async deleteResource(id: number) {
    await this.client.delete(`/admin/resources/${id}`);
  }

  async getAdminUsers(params?: { role?: string; is_active?: boolean; search?: string }) {
    const response = await this.client.get<PaginatedResponse<User>>('/admin/users', { params });
    return response.data;
  }

  async updateAdminUser(id: number, data: Partial<User>) {
    const response = await this.client.patch<ApiResponse<User>>(`/admin/users/${id}`, data);
    return response.data.data;
  }

  async getSystemSettings() {
    const response = await this.client.get<ApiResponse<SystemSetting[]>>('/admin/system-settings');
    return response.data.data;
  }

  async updateSystemSetting(id: number, data: Partial<SystemSetting>) {
    const response = await this.client.patch<ApiResponse<SystemSetting>>(`/admin/system-settings/${id}`, data);
    return response.data.data;
  }

  async getAdminDashboard() {
    const response = await this.client.get<ApiResponse<any>>('/admin/dashboard');
    return response.data.data;
  }

  async getAdminUser(id: number) {
    const response = await this.client.get<ApiResponse<User>>(`/admin/users/${id}`);
    return response.data.data;
  }
}

export const api = new ApiService();