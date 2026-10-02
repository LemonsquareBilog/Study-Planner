import type {
  User, Subject, Topic, Task, StudySession, Quiz, Question, QuizAttempt,
  Resource, Notification, SystemSetting, StudyPreference, ProgressSummary, TopicPerformance,
  ApiResponse, PaginatedResponse, SetupStatus, AdminDashboardData
} from '@/types';

const STORAGE_KEYS = {
  USER: 'sp_user',
  TOKEN: 'sp_token',
  SUBJECTS: 'sp_subjects',
  TOPICS: 'sp_topics',
  TASKS: 'sp_tasks',
  STUDY_SESSIONS: 'sp_study_sessions',
  QUIZZES: 'sp_quizzes',
  QUESTIONS: 'sp_questions',
  QUIZ_ATTEMPTS: 'sp_quiz_attempts',
  RESOURCES: 'sp_resources',
  NOTIFICATIONS: 'sp_notifications',
  STUDY_PREFERENCE: 'sp_study_preference',
  SYSTEM_SETTINGS: 'sp_system_settings',
  ID_COUNTER: 'sp_id_counter',
} as const;

function getNextId(): number {
  const current = parseInt(localStorage.getItem(STORAGE_KEYS.ID_COUNTER) || '1');
  localStorage.setItem(STORAGE_KEYS.ID_COUNTER, String(current + 1));
  return current;
}

function getStored<T>(key: string, fallback: T): T {
  try {
    const data = localStorage.getItem(key);
    return data ? JSON.parse(data) : fallback;
  } catch {
    return fallback;
  }
}

function setStored<T>(key: string, value: T) {
  localStorage.setItem(key, JSON.stringify(value));
}

function delay(ms: number = 100) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

function successResponse<T>(data: T, message = 'Success'): ApiResponse<T> {
  return { data, message };
}

function paginatedResponse<T>(data: T[]): PaginatedResponse<T> {
  return {
    current_page: 1,
    data,
    first_page_url: '',
    from: 1,
    last_page: 1,
    last_page_url: '',
    links: [],
    next_page_url: null,
    path: '',
    per_page: data.length,
    prev_page_url: null,
    to: data.length,
    total: data.length,
  };
}

export const localApi = {
  token: localStorage.getItem(STORAGE_KEYS.TOKEN),
  currentUser: getStored<User | null>(STORAGE_KEYS.USER, null),

  setToken(token: string) {
    this.token = token;
    localStorage.setItem(STORAGE_KEYS.TOKEN, token);
  },

  clearToken() {
    this.token = null;
    this.currentUser = null;
    localStorage.removeItem(STORAGE_KEYS.TOKEN);
    localStorage.removeItem(STORAGE_KEYS.USER);
  },

  loadToken() {
    this.token = localStorage.getItem(STORAGE_KEYS.TOKEN);
    this.currentUser = getStored<User | null>(STORAGE_KEYS.USER, null);
  },

  async login(email: string, password: string) {
    await delay();
    const users = getStored<User[]>('sp_users', []);
    const user = users.find(u => u.email === email);
    if (!user || password !== 'password') {
      throw { response: { status: 401, data: { message: 'Invalid credentials' } } };
    }
    const token = 'local-token-' + Date.now();
    this.setToken(token);
    this.currentUser = user;
    setStored(STORAGE_KEYS.USER, user);
    return successResponse({ token, user });
  },

  async register(name: string, email: string, password: string) {
    await delay();
    const users = getStored<User[]>('sp_users', []);
    if (users.find(u => u.email === email)) {
      throw { response: { status: 422, data: { message: 'Email already exists' } } };
    }
    const user: User = {
      id: getNextId(),
      name,
      email,
      role: 'student',
      is_active: true,
    };
    users.push(user);
    setStored('sp_users', users);
    const token = 'local-token-' + Date.now();
    this.setToken(token);
    this.currentUser = user;
    setStored(STORAGE_KEYS.USER, user);
    return successResponse({ token, user });
  },

  async logout() {
    await delay();
    this.clearToken();
  },

  async me() {
    await delay();
    if (!this.currentUser) throw { response: { status: 401 } };
    return successResponse(this.currentUser);
  },

  async updateProfile(data: Partial<User>) {
    await delay();
    if (!this.currentUser) throw { response: { status: 401 } };
    this.currentUser = { ...this.currentUser, ...data };
    setStored(STORAGE_KEYS.USER, this.currentUser);
    const users = getStored<User[]>('sp_users', []);
    const idx = users.findIndex(u => u.id === this.currentUser!.id);
    if (idx >= 0) users[idx] = this.currentUser;
    setStored('sp_users', users);
    return successResponse(this.currentUser);
  },

  async getSubjects() {
    await delay();
    const subjects = getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, []);
    return successResponse(subjects.filter(s => s.user_id === this.currentUser?.id));
  },

  async getSubject(id: number) {
    await delay();
    const subjects = getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, []);
    const subject = subjects.find(s => s.id === id);
    if (!subject) throw { response: { status: 404 } };
    return successResponse(subject);
  },

  async createSubject(data: Partial<Subject>) {
    await delay();
    const subjects = getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, []);
    const subject: Subject = {
      id: getNextId(),
      user_id: this.currentUser!.id,
      name: data.name || '',
      description: data.description,
      difficulty: data.difficulty || 1,
      color: data.color || '#3b82f6',
      is_active: true,
      topics_count: 0,
      tasks_count: 0,
    };
    subjects.push(subject);
    setStored(STORAGE_KEYS.SUBJECTS, subjects);
    return successResponse(subject);
  },

  async updateSubject(id: number, data: Partial<Subject>) {
    await delay();
    const subjects = getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, []);
    const idx = subjects.findIndex(s => s.id === id);
    if (idx < 0) throw { response: { status: 404 } };
    subjects[idx] = { ...subjects[idx], ...data };
    setStored(STORAGE_KEYS.SUBJECTS, subjects);
    return successResponse(subjects[idx]);
  },

  async deleteSubject(id: number) {
    await delay();
    const subjects = getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, []);
    setStored(STORAGE_KEYS.SUBJECTS, subjects.filter(s => s.id !== id));
    const topics = getStored<Topic[]>(STORAGE_KEYS.TOPICS, []);
    setStored(STORAGE_KEYS.TOPICS, topics.filter(t => t.subject_id !== id));
  },

  async getTopics(subjectId: number) {
    await delay();
    const topics = getStored<Topic[]>(STORAGE_KEYS.TOPICS, []);
    return successResponse(topics.filter(t => t.subject_id === subjectId));
  },

  async getTopic(subjectId: number, topicId: number) {
    await delay();
    const topics = getStored<Topic[]>(STORAGE_KEYS.TOPICS, []);
    const topic = topics.find(t => t.id === topicId && t.subject_id === subjectId);
    if (!topic) throw { response: { status: 404 } };
    return successResponse(topic);
  },

  async createTopic(subjectId: number, data: Partial<Topic>) {
    await delay();
    const topics = getStored<Topic[]>(STORAGE_KEYS.TOPICS, []);
    const topic: Topic = {
      id: getNextId(),
      subject_id: subjectId,
      name: data.name || '',
      description: data.description,
      difficulty: data.difficulty || 1,
      is_active: true,
      quizzes_count: 0,
      tasks_count: 0,
    };
    topics.push(topic);
    setStored(STORAGE_KEYS.TOPICS, topics);
    return successResponse(topic);
  },

  async updateTopic(subjectId: number, topicId: number, data: Partial<Topic>) {
    await delay();
    const topics = getStored<Topic[]>(STORAGE_KEYS.TOPICS, []);
    const idx = topics.findIndex(t => t.id === topicId && t.subject_id === subjectId);
    if (idx < 0) throw { response: { status: 404 } };
    topics[idx] = { ...topics[idx], ...data };
    setStored(STORAGE_KEYS.TOPICS, topics);
    return successResponse(topics[idx]);
  },

  async deleteTopic(subjectId: number, topicId: number) {
    await delay();
    const topics = getStored<Topic[]>(STORAGE_KEYS.TOPICS, []);
    setStored(STORAGE_KEYS.TOPICS, topics.filter(t => t.id !== topicId));
  },

  async getTasks(params?: { status?: string; subject_id?: number }) {
    await delay();
    let tasks = getStored<Task[]>(STORAGE_KEYS.TASKS, []);
    tasks = tasks.filter(t => t.user_id === this.currentUser?.id);
    if (params?.status) tasks = tasks.filter(t => t.status === params.status);
    if (params?.subject_id) tasks = tasks.filter(t => t.subject_id === params.subject_id);
    return paginatedResponse(tasks);
  },

  async createTask(data: Partial<Task>) {
    await delay();
    const tasks = getStored<Task[]>(STORAGE_KEYS.TASKS, []);
    const task: Task = {
      id: getNextId(),
      user_id: this.currentUser!.id,
      subject_id: data.subject_id || 0,
      topic_id: data.topic_id,
      title: data.title || '',
      description: data.description,
      task_type: data.task_type || 'study',
      priority: data.priority || 'medium',
      deadline: data.deadline || new Date().toISOString().split('T')[0],
      estimated_minutes: data.estimated_minutes || 30,
      status: 'pending',
    };
    tasks.push(task);
    setStored(STORAGE_KEYS.TASKS, tasks);
    return successResponse(task);
  },

  async updateTask(id: number, data: Partial<Task>) {
    await delay();
    const tasks = getStored<Task[]>(STORAGE_KEYS.TASKS, []);
    const idx = tasks.findIndex(t => t.id === id);
    if (idx < 0) throw { response: { status: 404 } };
    tasks[idx] = { ...tasks[idx], ...data };
    setStored(STORAGE_KEYS.TASKS, tasks);
    return successResponse(tasks[idx]);
  },

  async deleteTask(id: number) {
    await delay();
    const tasks = getStored<Task[]>(STORAGE_KEYS.TASKS, []);
    setStored(STORAGE_KEYS.TASKS, tasks.filter(t => t.id !== id));
  },

  async completeTask(id: number) {
    await delay();
    const tasks = getStored<Task[]>(STORAGE_KEYS.TASKS, []);
    const idx = tasks.findIndex(t => t.id === id);
    if (idx < 0) throw { response: { status: 404 } };
    tasks[idx] = { ...tasks[idx], status: 'completed', completed_at: new Date().toISOString() };
    setStored(STORAGE_KEYS.TASKS, tasks);
    return successResponse(tasks[idx]);
  },

  async updateTaskStatus(id: number, status: Task['status']) {
    return this.updateTask(id, { status });
  },

  async getStudySessions(params?: { from?: string; to?: string; status?: string }) {
    await delay();
    let sessions = getStored<StudySession[]>(STORAGE_KEYS.STUDY_SESSIONS, []);
    sessions = sessions.filter(s => s.user_id === this.currentUser?.id);
    if (params?.status) sessions = sessions.filter(s => s.status === params.status);
    return successResponse(sessions);
  },

  async generateStudyPlan(from: string, to: string) {
    await delay();
    return successResponse({ created_sessions: 0 });
  },

  async updateStudySessionStatus(id: number, status: StudySession['status']) {
    await delay();
    const sessions = getStored<StudySession[]>(STORAGE_KEYS.STUDY_SESSIONS, []);
    const idx = sessions.findIndex(s => s.id === id);
    if (idx < 0) throw { response: { status: 404 } };
    sessions[idx] = { ...sessions[idx], status };
    setStored(STORAGE_KEYS.STUDY_SESSIONS, sessions);
    return successResponse(sessions[idx]);
  },

  async getQuizzes() {
    await delay();
    const quizzes = getStored<Quiz[]>(STORAGE_KEYS.QUIZZES, []);
    return successResponse(quizzes);
  },

  async getQuiz(id: number) {
    await delay();
    const quizzes = getStored<Quiz[]>(STORAGE_KEYS.QUIZZES, []);
    const quiz = quizzes.find(q => q.id === id);
    if (!quiz) throw { response: { status: 404 } };
    const questions = getStored<Question[]>(STORAGE_KEYS.QUESTIONS, []).filter(q => q.quiz_id === id);
    return successResponse({ ...quiz, questions });
  },

  async submitQuiz(id: number, answers: Record<number, string>, started_at: string) {
    await delay();
    const quiz = getStored<Quiz[]>(STORAGE_KEYS.QUIZZES, []).find(q => q.id === id);
    const questions = getStored<Question[]>(STORAGE_KEYS.QUESTIONS, []).filter(q => q.quiz_id === id);
    let score = 0;
    const quizAnswers = questions.map(q => {
      const isCorrect = answers[q.id] === q.correct_answer;
      if (isCorrect) score++;
      return { question_id: q.id, selected_answer: answers[q.id], is_correct: isCorrect };
    });
    const attempt: QuizAttempt = {
      id: getNextId(),
      user_id: this.currentUser!.id,
      quiz_id: id,
      score,
      total_questions: questions.length,
      percentage: Math.round((score / questions.length) * 100),
      started_at,
      completed_at: new Date().toISOString(),
    };
    const attempts = getStored<QuizAttempt[]>(STORAGE_KEYS.QUIZ_ATTEMPTS, []);
    attempts.push(attempt);
    setStored(STORAGE_KEYS.QUIZ_ATTEMPTS, attempts);
    return successResponse(attempt);
  },

  async getRecommendations() {
    await delay();
    return successResponse(getStored<Resource[]>(STORAGE_KEYS.RESOURCES, []));
  },

  async recordView(resourceId: number) {
    await delay();
    return successResponse({});
  },

  async getStudyPreference() {
    await delay();
    const pref = getStored<StudyPreference | null>(STORAGE_KEYS.STUDY_PREFERENCE, null);
    return successResponse(pref || {
      id: 0,
      user_id: this.currentUser!.id,
      max_daily_minutes: 240,
      session_duration_minutes: 50,
      break_duration_minutes: 10,
      preferred_start_time: '09:00',
      preferred_end_time: '22:00',
    });
  },

  async upsertStudyPreference(data: Partial<StudyPreference>) {
    await delay();
    const pref = {
      id: getNextId(),
      user_id: this.currentUser!.id,
      max_daily_minutes: 240,
      session_duration_minutes: 50,
      break_duration_minutes: 10,
      preferred_start_time: '09:00',
      preferred_end_time: '22:00',
      ...data,
    };
    setStored(STORAGE_KEYS.STUDY_PREFERENCE, pref);
    return successResponse(pref);
  },

  async getProgress() {
    await delay();
    const tasks = getStored<Task[]>(STORAGE_KEYS.TASKS, []).filter(t => t.user_id === this.currentUser?.id);
    const sessions = getStored<StudySession[]>(STORAGE_KEYS.STUDY_SESSIONS, []).filter(s => s.user_id === this.currentUser?.id);
    const attempts = getStored<QuizAttempt[]>(STORAGE_KEYS.QUIZ_ATTEMPTS, []).filter(a => a.user_id === this.currentUser?.id);
    return successResponse({
      summary: {
        tasks: { total: tasks.length, completed: tasks.filter(t => t.status === 'completed').length, completion_rate: tasks.length ? tasks.filter(t => t.status === 'completed').length / tasks.length * 100 : 0 },
        study_sessions: { total: sessions.length, completed: sessions.filter(s => s.status === 'completed').length, minutes_completed: sessions.filter(s => s.status === 'completed').reduce((a, s) => a + s.duration_minutes, 0) },
        quizzes: { attempts: attempts.length, average_percentage: attempts.length ? attempts.reduce((a, s) => a + s.percentage, 0) / attempts.length : 0 },
        generated_at: new Date().toISOString(),
      },
      topic_performance: [],
    });
  },

  async getNotifications(params?: { unread?: boolean }) {
    await delay();
    let notifications = getStored<Notification[]>(STORAGE_KEYS.NOTIFICATIONS, []);
    notifications = notifications.filter(n => n.user_id === this.currentUser?.id);
    if (params?.unread) notifications = notifications.filter(n => !n.read_at);
    return paginatedResponse(notifications);
  },

  async getNotificationCount() {
    await delay();
    const notifications = getStored<Notification[]>(STORAGE_KEYS.NOTIFICATIONS, []);
    const unread = notifications.filter(n => n.user_id === this.currentUser?.id && !n.read_at).length;
    return successResponse({ unread, total: notifications.length });
  },

  async markNotificationRead(id: string) {
    await delay();
    const notifications = getStored<Notification[]>(STORAGE_KEYS.NOTIFICATIONS, []);
    const idx = notifications.findIndex(n => n.id === id);
    if (idx >= 0) {
      notifications[idx] = { ...notifications[idx], read_at: new Date().toISOString() };
      setStored(STORAGE_KEYS.NOTIFICATIONS, notifications);
    }
    return successResponse(notifications[idx]);
  },

  async markAllNotificationsRead() {
    await delay();
    const notifications = getStored<Notification[]>(STORAGE_KEYS.NOTIFICATIONS, []);
    const updated = notifications.map(n => n.user_id === this.currentUser?.id && !n.read_at ? { ...n, read_at: new Date().toISOString() } : n);
    setStored(STORAGE_KEYS.NOTIFICATIONS, updated);
    return successResponse({ marked_read: updated.filter(n => n.read_at).length });
  },

  async getAdminResources() {
    await delay();
    return paginatedResponse(getStored<Resource[]>(STORAGE_KEYS.RESOURCES, []));
  },

  async getAdminResourceOptions() {
    await delay();
    return successResponse({
      subjects: getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, []),
      topics: getStored<Topic[]>(STORAGE_KEYS.TOPICS, []),
    });
  },

  async getAdminSubjects() {
    await delay();
    return successResponse(getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, []));
  },

  async getAdminSubjectOptions() {
    await delay();
    return successResponse(getStored<User[]>('sp_users', []).filter(u => u.role === 'student'));
  },

  async createAdminSubject(data: Partial<Subject> & { user_id: number }) {
    await delay();
    const subjects = getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, []);
    const subject: Subject = {
      id: getNextId(),
      user_id: data.user_id,
      name: data.name || '',
      description: data.description,
      difficulty: data.difficulty || 1,
      color: data.color || '#3b82f6',
      is_active: true,
      topics_count: 0,
      tasks_count: 0,
    };
    subjects.push(subject);
    setStored(STORAGE_KEYS.SUBJECTS, subjects);
    return successResponse(subject);
  },

  async updateAdminSubject(id: number, data: Partial<Subject>) {
    return this.updateSubject(id, data);
  },

  async deleteAdminSubject(id: number) {
    return this.deleteSubject(id);
  },

  async createAdminTopic(subjectId: number, data: Partial<Topic>) {
    return this.createTopic(subjectId, data);
  },

  async updateAdminTopic(subjectId: number, topicId: number, data: Partial<Topic>) {
    return this.updateTopic(subjectId, topicId, data);
  },

  async deleteAdminTopic(subjectId: number, topicId: number) {
    return this.deleteTopic(subjectId, topicId);
  },

  async createResource(data: Partial<Resource>) {
    await delay();
    const resources = getStored<Resource[]>(STORAGE_KEYS.RESOURCES, []);
    const resource: Resource = {
      id: getNextId(),
      title: data.title || '',
      description: data.description,
      resource_type: data.resource_type || 'article',
      url: data.url || '',
      difficulty: data.difficulty || 'beginner',
      is_active: true,
    };
    resources.push(resource);
    setStored(STORAGE_KEYS.RESOURCES, resources);
    return successResponse(resource);
  },

  async updateResource(id: number, data: Partial<Resource>) {
    await delay();
    const resources = getStored<Resource[]>(STORAGE_KEYS.RESOURCES, []);
    const idx = resources.findIndex(r => r.id === id);
    if (idx < 0) throw { response: { status: 404 } };
    resources[idx] = { ...resources[idx], ...data };
    setStored(STORAGE_KEYS.RESOURCES, resources);
    return successResponse(resources[idx]);
  },

  async deleteResource(id: number) {
    await delay();
    const resources = getStored<Resource[]>(STORAGE_KEYS.RESOURCES, []);
    setStored(STORAGE_KEYS.RESOURCES, resources.filter(r => r.id !== id));
  },

  async getAdminUsers() {
    await delay();
    return paginatedResponse(getStored<User[]>('sp_users', []));
  },

  async updateAdminUser(id: number, data: Partial<User>) {
    await delay();
    const users = getStored<User[]>('sp_users', []);
    const idx = users.findIndex(u => u.id === id);
    if (idx < 0) throw { response: { status: 404 } };
    users[idx] = { ...users[idx], ...data };
    setStored('sp_users', users);
    return successResponse(users[idx]);
  },

  async getSystemSettings() {
    await delay();
    return successResponse(getStored<SystemSetting[]>(STORAGE_KEYS.SYSTEM_SETTINGS, []));
  },

  async updateSystemSetting(id: number, data: Partial<SystemSetting>) {
    await delay();
    const settings = getStored<SystemSetting[]>(STORAGE_KEYS.SYSTEM_SETTINGS, []);
    const idx = settings.findIndex(s => s.id === id);
    if (idx < 0) throw { response: { status: 404 } };
    settings[idx] = { ...settings[idx], ...data };
    setStored(STORAGE_KEYS.SYSTEM_SETTINGS, settings);
    return successResponse(settings[idx]);
  },

  async getAdminDashboard() {
    await delay();
    const users = getStored<User[]>('sp_users', []);
    const students = users.filter(u => u.role === 'student');
    return successResponse({
      metrics: {
        total_students: students.length,
        active_students: students.filter(u => u.is_active).length,
        total_resources: getStored<Resource[]>(STORAGE_KEYS.RESOURCES, []).length,
        total_subjects: getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, []).length,
        total_quizzes: getStored<Quiz[]>(STORAGE_KEYS.QUIZZES, []).length,
        total_quiz_attempts: getStored<QuizAttempt[]>(STORAGE_KEYS.QUIZ_ATTEMPTS, []).length,
        average_quiz_score: 0,
      },
      recent_users: students.slice(-5),
      recent_attempts: [],
    });
  },

  async getAdminUser(id: number) {
    await delay();
    const users = getStored<User[]>('sp_users', []);
    const user = users.find(u => u.id === id);
    if (!user) throw { response: { status: 404 } };
    return successResponse(user);
  },

  async getSetupStatus() {
    await delay();
    const subjects = getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, []).filter(s => s.user_id === this.currentUser?.id);
    const tasks = getStored<Task[]>(STORAGE_KEYS.TASKS, []).filter(t => t.user_id === this.currentUser?.id);
    const sessions = getStored<StudySession[]>(STORAGE_KEYS.STUDY_SESSIONS, []).filter(s => s.user_id === this.currentUser?.id);
    const attempts = getStored<QuizAttempt[]>(STORAGE_KEYS.QUIZ_ATTEMPTS, []).filter(a => a.user_id === this.currentUser?.id);
    const pref = getStored<StudyPreference | null>(STORAGE_KEYS.STUDY_PREFERENCE, null);
    return successResponse({
      has_preferences: !!pref,
      subjects_count: subjects.length,
      tasks_count: tasks.length,
      study_sessions_count: sessions.length,
      quiz_attempts_count: attempts.length,
      is_fully_onboarded: subjects.length > 0 && tasks.length > 0 && !!pref,
    } as SetupStatus);
  },
};