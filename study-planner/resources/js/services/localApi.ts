import type {
  User, Subject, Topic, Task, StudySession, Quiz, Question, QuizAttempt,
  Resource, Notification, SystemSetting, StudyPreference, ProgressSummary, TopicPerformance, QuizAnswer,
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
  RECOMMENDATION_VIEWS: 'sp_recommendation_views',
  SYSTEM_SETTINGS: 'sp_system_settings',
  ID_COUNTER: 'sp_id_counter',
} as const;

const DEMO_ACCOUNTS: Array<{
  email: string;
  password: string;
  user: Omit<User, 'id'>;
}> = [
  {
    email: 'admin@studyplanner.test',
    password: 'AdminPassword123!',
    user: { name: 'System Administrator', email: 'admin@studyplanner.test', role: 'admin', is_active: true },
  },
  {
    email: 'alex.chen@student.test',
    password: 'StudentPass123!',
    user: { name: 'Alex Chen', email: 'alex.chen@student.test', role: 'student', course: 'Bachelor of Science in Computer Science', year_level: 3, is_active: true },
  },
  {
    email: 'maria.santos@student.test',
    password: 'StudentPass123!',
    user: { name: 'Maria Santos', email: 'maria.santos@student.test', role: 'student', course: 'Bachelor of Science in Information Technology', year_level: 4, is_active: true },
  },
  {
    email: 'james.wilson@student.test',
    password: 'StudentPass123!',
    user: { name: 'James Wilson', email: 'james.wilson@student.test', role: 'student', course: 'Bachelor of Science in Data Science', year_level: 2, is_active: true },
  },
  {
    email: 'sarah.kim@student.test',
    password: 'StudentPass123!',
    user: { name: 'Sarah Kim', email: 'sarah.kim@student.test', role: 'student', course: 'Bachelor of Science in Software Engineering', year_level: 3, is_active: true },
  },
];

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

function dateOnly(value: string): Date {
  const [year, month, day] = value.slice(0, 10).split('-').map(Number);
  return new Date(year, month - 1, day, 12);
}

function dateKey(value: Date): string {
  return `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}`;
}

function timeToMinutes(value: string): number {
  const [hours, minutes] = value.slice(0, 5).split(':').map(Number);
  return hours * 60 + minutes;
}

function minutesToTime(value: number): string {
  const hours = Math.floor(value / 60);
  const minutes = value % 60;
  return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:00`;
}

function findLocalStudySlot(sessions: StudySession[], preferences: StudyPreference, duration: number): number | null {
  const startLimit = timeToMinutes(preferences.preferred_start_time || '18:00');
  const endLimit = timeToMinutes(preferences.preferred_end_time || '22:00');
  const dailyEnd = endLimit > startLimit ? endLimit : 1440;
  let cursor = startLimit;

  for (const session of sessions.sort((left, right) => left.start_time.localeCompare(right.start_time))) {
    const sessionStart = timeToMinutes(session.start_time);
    const sessionEnd = Math.max(sessionStart, timeToMinutes(session.end_time));
    if (cursor + duration <= sessionStart) return cursor;
    if (cursor < sessionEnd) cursor = sessionEnd + preferences.break_duration_minutes;
  }

  return cursor + duration <= dailyEnd ? cursor : null;
}

function getLocalTopicPerformance(userId: number): TopicPerformance[] {
  const attempts = getStored<QuizAttempt[]>(STORAGE_KEYS.QUIZ_ATTEMPTS, []).filter(attempt => attempt.user_id === userId);
  const quizzes = new Map(getStored<Quiz[]>(STORAGE_KEYS.QUIZZES, []).map(quiz => [quiz.id, quiz]));
  const topics = new Map(getStored<Topic[]>(STORAGE_KEYS.TOPICS, []).map(topic => [topic.id, topic]));
  const subjects = new Map(getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, []).map(subject => [subject.id, subject]));
  const scoresByTopic = new Map<number, number[]>();

  for (const attempt of attempts) {
    const topicId = quizzes.get(attempt.quiz_id)?.topic_id;
    if (topicId === undefined) continue;
    const scores = scoresByTopic.get(topicId) || [];
    scores.push(Number(attempt.percentage));
    scoresByTopic.set(topicId, scores);
  }

  const settings = getStored<SystemSetting[]>(STORAGE_KEYS.SYSTEM_SETTINGS, []);
  const weakThreshold = Number(settings.find(setting => setting.key === 'weak_topic_threshold')?.value || 60);
  const reviewThreshold = Number(settings.find(setting => setting.key === 'review_topic_threshold')?.value || 79);

  return [...scoresByTopic.entries()].map(([topicId, scores]) => {
    const topic = topics.get(topicId);
    const average = scores.reduce((sum, score) => sum + score, 0) / scores.length;
    return {
      topic_id: topicId,
      topic_name: topic?.name || 'Topic',
      subject_name: topic ? subjects.get(topic.subject_id)?.name : undefined,
      avg_percentage: average,
      attempt_count: scores.length,
      best_score: Math.max(...scores),
      worst_score: Math.min(...scores),
      classification: average < weakThreshold ? 'weak' : average <= reviewThreshold ? 'needs_review' : 'strong',
    };
  }).sort((left, right) => left.avg_percentage - right.avg_percentage);
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
    const demoAccount = DEMO_ACCOUNTS.find(account => account.email === email);
    let user = users.find(item => item.email === email);

    if (demoAccount && password === demoAccount.password) {
      for (const account of DEMO_ACCOUNTS) {
        const existingIndex = users.findIndex(item => item.email === account.email);
        if (existingIndex < 0) {
          users.push({ id: getNextId(), ...account.user });
        } else if (users[existingIndex].role !== account.user.role) {
          users[existingIndex] = { ...users[existingIndex], role: account.user.role };
        }
      }
      setStored('sp_users', users);
      user = users.find(item => item.email === email);
    } else if (!user || password !== 'password') {
      throw { response: { status: 401, data: { message: 'Invalid credentials' } } };
    }

    if (!user) throw { response: { status: 401, data: { message: 'Invalid credentials' } } };
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

  async getTasks(params?: { status?: string; subject_id?: number; from?: string; to?: string }) {
    await delay();
    let tasks = getStored<Task[]>(STORAGE_KEYS.TASKS, []);
    tasks = tasks.filter(t => t.user_id === this.currentUser?.id);
    if (params?.status) tasks = tasks.filter(t => t.status === params.status);
    if (params?.subject_id) tasks = tasks.filter(t => t.subject_id === params.subject_id);
    if (params?.from) tasks = tasks.filter(task => (task.start_date || task.deadline || '').slice(0, 10) >= params.from);
    if (params?.to) tasks = tasks.filter(task => (task.start_date || task.deadline || '').slice(0, 10) <= params.to);
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
      start_date: data.start_date || dateKey(new Date()),
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
    let sessions = getStored<StudySession[]>(STORAGE_KEYS.STUDY_SESSIONS, [])
      .filter(session => session.user_id === this.currentUser?.id);
    if (params?.from) sessions = sessions.filter(session => dateKey(dateOnly(session.scheduled_date)) >= params.from!);
    if (params?.to) sessions = sessions.filter(session => dateKey(dateOnly(session.scheduled_date)) <= params.to!);
    if (params?.status) sessions = sessions.filter(s => s.status === params.status);
    const tasks = getStored<Task[]>(STORAGE_KEYS.TASKS, []);
    const subjects = getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, []);
    const topics = getStored<Topic[]>(STORAGE_KEYS.TOPICS, []);
    return successResponse(sessions
      .sort((left, right) => left.scheduled_date.localeCompare(right.scheduled_date) || left.start_time.localeCompare(right.start_time))
      .map(session => ({
        ...session,
        task: tasks.find(task => task.id === session.task_id),
        subject: subjects.find(subject => subject.id === session.subject_id),
        topic: topics.find(topic => topic.id === session.topic_id),
      })));
  },

  async generateStudyPlan(from: string, to: string) {
    await delay();
    if (!this.currentUser) throw { response: { status: 401 } };

    const preferences = getStored<StudyPreference | null>(STORAGE_KEYS.STUDY_PREFERENCE, null) || {
      id: 0,
      user_id: this.currentUser.id,
      max_daily_minutes: 180,
      session_duration_minutes: 50,
      break_duration_minutes: 10,
      preferred_start_time: '18:00',
      preferred_end_time: '22:00',
    };
    const tasks = getStored<Task[]>(STORAGE_KEYS.TASKS, [])
      .filter(task => task.user_id === this.currentUser!.id && ['pending', 'in_progress'].includes(task.status))
      .filter(task => task.deadline.slice(0, 10) >= from && task.deadline.slice(0, 10) <= to)
      .sort((left, right) => {
        const priority = { urgent: 0, high: 1, medium: 2, low: 3 };
        return priority[left.priority] - priority[right.priority] || left.deadline.localeCompare(right.deadline);
      });
    const sessions = getStored<StudySession[]>(STORAGE_KEYS.STUDY_SESSIONS, []);
    const today = dateKey(new Date());
    const created: StudySession[] = [];

    for (const task of tasks) {
      let remaining = task.estimated_minutes - sessions
        .filter(session => session.task_id === task.id && ['scheduled', 'in_progress', 'completed'].includes(session.status))
        .reduce((sum, session) => sum + session.duration_minutes, 0);
      let scheduledDate = [from, today, task.start_date || task.created_at?.slice(0, 10) || from].sort().at(-1)!;
      const deadlineDate = task.deadline.slice(0, 10);

      while (remaining > 0 && scheduledDate <= to && scheduledDate <= deadlineDate) {
        const existing = sessions.filter(session => session.user_id === this.currentUser!.id
          && session.scheduled_date === scheduledDate
          && ['scheduled', 'in_progress', 'completed'].includes(session.status));
        const usedMinutes = existing.reduce((sum, session) => sum + session.duration_minutes, 0);
        const capacity = Math.max(0, preferences.max_daily_minutes - usedMinutes);
        const duration = Math.min(remaining, capacity, preferences.session_duration_minutes);
        const slot = duration > 0 ? findLocalStudySlot(existing, preferences, duration) : null;

        if (slot !== null) {
          const session: StudySession = {
            id: getNextId(),
            user_id: this.currentUser.id,
            subject_id: task.subject_id,
            topic_id: task.topic_id,
            task_id: task.id,
            scheduled_date: scheduledDate,
            start_time: minutesToTime(slot),
            end_time: minutesToTime(slot + duration),
            duration_minutes: duration,
            status: 'scheduled',
            task,
          };
          sessions.push(session);
          created.push(session);
          remaining -= duration;
        }

        const nextDate = dateOnly(scheduledDate);
        nextDate.setDate(nextDate.getDate() + 1);
        scheduledDate = dateKey(nextDate);
      }
    }

    setStored(STORAGE_KEYS.STUDY_SESSIONS, sessions);
    return successResponse({ created_sessions: created.length, created_sessions_list: created });
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
    const questions = getStored<Question[]>(STORAGE_KEYS.QUESTIONS, [])
      .filter(question => question.quiz_id === id)
      .map(question => {
        const safeQuestion = { ...question };
        delete safeQuestion.correct_answer;
        return safeQuestion;
      });
    return successResponse({ ...quiz, questions });
  },

  async submitQuiz(id: number, answers: Record<number, string>, started_at: string) {
    await delay();
    const quiz = getStored<Quiz[]>(STORAGE_KEYS.QUIZZES, []).find(q => q.id === id);
    const questions = getStored<Question[]>(STORAGE_KEYS.QUESTIONS, []).filter(q => q.quiz_id === id);
    if (!quiz || !this.currentUser || questions.length === 0) {
      throw { response: { status: quiz ? 422 : 404, data: { message: 'Quiz is unavailable.' } } };
    }

    let score = 0;
    const attemptId = getNextId();
    const quizAnswers: QuizAnswer[] = questions.map(question => {
      const selectedAnswer = answers[question.id] || null;
      const isCorrect = selectedAnswer === question.correct_answer;
      if (isCorrect) score++;
      return {
        id: getNextId(),
        quiz_attempt_id: attemptId,
        question_id: question.id,
        selected_answer: selectedAnswer,
        is_correct: isCorrect,
        question,
      };
    });
    const attempt: QuizAttempt = {
      id: attemptId,
      user_id: this.currentUser!.id,
      quiz_id: id,
      score,
      total_questions: questions.length,
      percentage: Math.round((score / questions.length) * 10000) / 100,
      started_at,
      completed_at: new Date().toISOString(),
      quiz,
      answers: quizAnswers,
    };
    const attempts = getStored<QuizAttempt[]>(STORAGE_KEYS.QUIZ_ATTEMPTS, []);
    attempts.push(attempt);
    setStored(STORAGE_KEYS.QUIZ_ATTEMPTS, attempts);
    return successResponse(attempt);
  },

  async getQuizHistory() {
    await delay();
    const attempts = getStored<QuizAttempt[]>(STORAGE_KEYS.QUIZ_ATTEMPTS, [])
      .filter(attempt => attempt.user_id === this.currentUser?.id)
      .sort((left, right) => right.completed_at.localeCompare(left.completed_at));
    return successResponse(attempts);
  },

  async getQuizAttempt(id: number) {
    await delay();
    const attempt = getStored<QuizAttempt[]>(STORAGE_KEYS.QUIZ_ATTEMPTS, [])
      .find(item => item.id === id && item.user_id === this.currentUser?.id);
    if (!attempt) throw { response: { status: 404 } };
    return successResponse(attempt);
  },

  async getRecommendations() {
    await delay();
    if (!this.currentUser) throw { response: { status: 401 } };
    const focusAreas = getLocalTopicPerformance(this.currentUser.id)
      .filter(topic => topic.classification !== 'strong');
    if (focusAreas.length === 0) return successResponse([]);

    const focusTopicIds = new Set(focusAreas.map(topic => topic.topic_id));
    const focusSubjectIds = new Set(focusAreas
      .map(topic => getStored<Topic[]>(STORAGE_KEYS.TOPICS, []).find(item => item.id === topic.topic_id)?.subject_id)
      .filter((id): id is number => id !== undefined));

    const recommendationOrder: Record<string, number> = { weak: 0, needs_review: 1, related: 2 };
    const recommendations = getStored<Resource[]>(STORAGE_KEYS.RESOURCES, [])
      .filter(resource => resource.is_active)
      .flatMap(resource => {
        const resourceTopicIds = (resource.topics || []).map(topic => topic.id);
        const resourceSubjectIds = (resource.subjects || []).map(subject => subject.id);
        const matchingAreas = focusAreas.filter(area => focusTopicIds.has(area.topic_id)
          && (resourceTopicIds.includes(area.topic_id)
            || (focusSubjectIds.has(getStored<Topic[]>(STORAGE_KEYS.TOPICS, []).find(item => item.id === area.topic_id)?.subject_id || -1)
              && resourceSubjectIds.includes(getStored<Topic[]>(STORAGE_KEYS.TOPICS, []).find(item => item.id === area.topic_id)?.subject_id || -1))));

        if (matchingAreas.length === 0) return [];
        const reason = matchingAreas.some(area => area.classification === 'weak')
          ? 'weak'
          : matchingAreas.some(area => area.classification === 'needs_review') ? 'needs_review' : 'related';
        return [{ ...resource, recommendation_reason: reason }];
      })
      .sort((left, right) => recommendationOrder[left.recommendation_reason || 'related']
        - recommendationOrder[right.recommendation_reason || 'related']
        || left.title.localeCompare(right.title));

    return successResponse(recommendations);
  },

  async recordView(resourceId: number, topicId?: number) {
    await delay();
    const views = getStored<Array<{ id: number; user_id: number; resource_id: number; topic_id?: number; viewed_at: string }>>(STORAGE_KEYS.RECOMMENDATION_VIEWS, []);
    const view = { id: getNextId(), user_id: this.currentUser!.id, resource_id: resourceId, topic_id: topicId, viewed_at: new Date().toISOString() };
    views.push(view);
    setStored(STORAGE_KEYS.RECOMMENDATION_VIEWS, views);
    return successResponse(view);
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
      topic_performance: getLocalTopicPerformance(this.currentUser!.id),
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

  async createResource(data: Partial<Resource> & { subject_ids?: number[]; topic_ids?: number[] }) {
    await delay();
    const resources = getStored<Resource[]>(STORAGE_KEYS.RESOURCES, []);
    const allTopics = getStored<Topic[]>(STORAGE_KEYS.TOPICS, []);
    const linkedTopics = allTopics.filter(topic => data.topic_ids?.includes(topic.id));
    const linkedSubjectIds = new Set([
      ...(data.subject_ids || []),
      ...linkedTopics.map(topic => topic.subject_id),
    ]);
    const linkedSubjects = getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, [])
      .filter(subject => linkedSubjectIds.has(subject.id));
    const resource: Resource = {
      id: getNextId(),
      title: data.title || '',
      description: data.description,
      resource_type: data.resource_type || 'article',
      url: data.url || '',
      difficulty: data.difficulty || 'beginner',
      is_active: true,
      subjects: linkedSubjects,
      topics: linkedTopics.map(topic => ({ ...topic, subject: linkedSubjects.find(subject => subject.id === topic.subject_id) })),
    };
    resources.push(resource);
    setStored(STORAGE_KEYS.RESOURCES, resources);
    return successResponse(resource);
  },

  async updateResource(id: number, data: Partial<Resource> & { subject_ids?: number[]; topic_ids?: number[] }) {
    await delay();
    const resources = getStored<Resource[]>(STORAGE_KEYS.RESOURCES, []);
    const idx = resources.findIndex(r => r.id === id);
    if (idx < 0) throw { response: { status: 404 } };
    const { subject_ids, topic_ids, ...fields } = data;
    let associations: Pick<Resource, 'subjects' | 'topics'> = {};
    if (subject_ids || topic_ids) {
      const allTopics = getStored<Topic[]>(STORAGE_KEYS.TOPICS, []);
      const linkedTopics = topic_ids ? allTopics.filter(topic => topic_ids.includes(topic.id)) : resources[idx].topics || [];
      const linkedSubjectIds = new Set([
        ...(subject_ids || resources[idx].subjects?.map(subject => subject.id) || []),
        ...linkedTopics.map(topic => topic.subject_id),
      ]);
      const linkedSubjects = getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, [])
        .filter(subject => linkedSubjectIds.has(subject.id));
      associations = {
        subjects: linkedSubjects,
        topics: linkedTopics.map(topic => ({ ...topic, subject: linkedSubjects.find(subject => subject.id === topic.subject_id) })),
      };
    }
    resources[idx] = { ...resources[idx], ...fields, ...associations };
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