import type {
  User, Subject, Topic, Task, StudySession, Quiz, Question, QuizAttempt,
  Resource, Notification, SystemSetting, StudyPreference, ProgressSummary, TopicPerformance, QuizAnswer,
  ApiResponse, PaginatedResponse, SetupStatus, AdminDashboardData
} from '@/types';

const STORAGE_KEYS = {
  USER: 'sp_user',
  TOKEN: 'sp_token',
  USERS: 'sp_users',
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
  DEMO_DATA_VERSION: 'sp_demo_data_version',
} as const;

export const DEMO_DATA_VERSION_KEY = '2026-10-07-v3';

export const DEMO_ACCOUNTS: Array<{
  email: string;
  passwords: string[];
  user: Omit<User, 'id'>;
}> = [
  {
    email: 'admin@studyplanner.test',
    passwords: ['AdminPassword123!', 'password', 'admin'],
    user: {
      name: 'System Administrator',
      email: 'admin@studyplanner.test',
      role: 'admin',
      is_active: true,
      created_at: '2026-09-15T08:00:00.000Z',
    },
  },
  {
    email: 'student@studyplanner.test',
    passwords: ['StudentPassword123!', 'StudentPass123!', 'password', 'student'],
    user: {
      name: 'Demo Student',
      email: 'student@studyplanner.test',
      role: 'student',
      course: 'Bachelor of Science in Information Technology',
      year_level: 3,
      is_active: true,
      created_at: '2026-09-15T08:30:00.000Z',
    },
  },
  {
    email: 'alex.chen@student.test',
    passwords: ['StudentPass123!', 'StudentPassword123!', 'password'],
    user: {
      name: 'Alex Chen',
      email: 'alex.chen@student.test',
      role: 'student',
      course: 'Bachelor of Science in Computer Science',
      year_level: 3,
      is_active: true,
      created_at: '2026-09-15T09:00:00.000Z',
    },
  },
  {
    email: 'maria.santos@student.test',
    passwords: ['StudentPass123!', 'password'],
    user: {
      name: 'Maria Santos',
      email: 'maria.santos@student.test',
      role: 'student',
      course: 'Bachelor of Science in Information Technology',
      year_level: 4,
      is_active: true,
      created_at: '2026-09-16T10:00:00.000Z',
    },
  },
  {
    email: 'james.wilson@student.test',
    passwords: ['StudentPass123!', 'password'],
    user: {
      name: 'James Wilson',
      email: 'james.wilson@student.test',
      role: 'student',
      course: 'Bachelor of Science in Data Science',
      year_level: 2,
      is_active: true,
      created_at: '2026-09-17T11:00:00.000Z',
    },
  },
  {
    email: 'sarah.kim@student.test',
    passwords: ['StudentPass123!', 'password'],
    user: {
      name: 'Sarah Kim',
      email: 'sarah.kim@student.test',
      role: 'student',
      course: 'Bachelor of Science in Software Engineering',
      year_level: 3,
      is_active: true,
      created_at: '2026-09-18T13:00:00.000Z',
    },
  },
];

function getNextId(): number {
  const current = parseInt(localStorage.getItem(STORAGE_KEYS.ID_COUNTER) || '100');
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
  try {
    localStorage.setItem(key, JSON.stringify(value));
  } catch (e) {
    console.warn('[localApi] Failed to save to localStorage:', e);
  }
}

function delay(ms: number = 60) {
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
    from: data.length > 0 ? 1 : 0,
    last_page: 1,
    last_page_url: '',
    links: [],
    next_page_url: null,
    path: '',
    per_page: Math.max(15, data.length),
    prev_page_url: null,
    to: data.length,
    total: data.length,
  };
}

export function ensureDemoDataSeeded(force: boolean = false) {
  const currentVersion = localStorage.getItem(STORAGE_KEYS.DEMO_DATA_VERSION);
  if (!force && currentVersion === DEMO_DATA_VERSION_KEY) {
    return;
  }

  console.log('[localApi] Seeding comprehensive demo data for mobile & interactive demo...');

  let users: User[] = DEMO_ACCOUNTS.map((account, index) => ({
    id: index + 1,
    ...account.user,
  }));
  setStored(STORAGE_KEYS.USERS, users);

  const dateAt = (offset: number) => {
    const date = dateOnly(dateKey(new Date()));
    date.setDate(date.getDate() + offset);
    return dateKey(date);
  };
  const timestampAt = (offset: number, time: string) => new Date(`${dateAt(offset)}T${time}`).toISOString();

  const subjects: Subject[] = [];
  const topics: Topic[] = [];
  const tasks: Task[] = [];
  const sessions: StudySession[] = [];
  const quizzes: Quiz[] = [];
  const questions: Question[] = [];
  const attempts: QuizAttempt[] = [];
  const notifications: Notification[] = [];

  const studentUsers = users.filter(u => u.role === 'student');

  studentUsers.forEach(student => {
    const pref: StudyPreference = {
      id: student.id,
      user_id: student.id,
      max_daily_minutes: 180,
      session_duration_minutes: 50,
      break_duration_minutes: 10,
      preferred_start_time: '18:00',
      preferred_end_time: '22:00',
    };
    if (student.email === 'student@studyplanner.test' || student.email === 'alex.chen@student.test') {
      setStored(STORAGE_KEYS.STUDY_PREFERENCE, pref);
    }

    const s1Id = getNextId();
    const csSubject: Subject = {
      id: s1Id,
      user_id: student.id,
      name: 'Computer Science',
      description: 'Core programming constructs, algorithms, and systems.',
      difficulty: 3,
      color: 'blue',
      is_active: true,
      topics_count: 2,
      tasks_count: 2,
    };

    const s2Id = getNextId();
    const webSubject: Subject = {
      id: s2Id,
      user_id: student.id,
      name: 'Web Systems & Technologies',
      description: 'Modern frontend frameworks, responsive UI, and REST APIs.',
      difficulty: 2,
      color: 'green',
      is_active: true,
      topics_count: 2,
      tasks_count: 2,
    };

    const s3Id = getNextId();
    const writingSubject: Subject = {
      id: s3Id,
      user_id: student.id,
      name: 'Academic Research & Writing',
      description: 'Literature synthesis, citation guidelines, and proposal writing.',
      difficulty: 2,
      color: 'pink',
      is_active: true,
      topics_count: 1,
      tasks_count: 1,
    };

    subjects.push(csSubject, webSubject, writingSubject);

    const t1Id = getNextId();
    const tDataStructures: Topic = {
      id: t1Id,
      subject_id: s1Id,
      name: 'Data Structures & Algorithms',
      description: 'Stacks, queues, binary search trees, and asymptotic analysis.',
      difficulty: 3,
      is_active: true,
      quizzes_count: 1,
      tasks_count: 1,
      subject: csSubject,
    };

    const t2Id = getNextId();
    const tNetworks: Topic = {
      id: t2Id,
      subject_id: s1Id,
      name: 'Computer Networks & Security',
      description: 'TCP/IP, subnet masking, DNS, and TLS transport security.',
      difficulty: 2,
      is_active: true,
      quizzes_count: 1,
      tasks_count: 1,
      subject: csSubject,
    };

    const t3Id = getNextId();
    const tVue: Topic = {
      id: t3Id,
      subject_id: s2Id,
      name: 'Modern JavaScript & Vue.js',
      description: 'Component lifecycles, Pinia stores, and Vue 3 Composition API.',
      difficulty: 2,
      is_active: true,
      quizzes_count: 1,
      tasks_count: 1,
      subject: webSubject,
    };

    const t4Id = getNextId();
    const tApis: Topic = {
      id: t4Id,
      subject_id: s2Id,
      name: 'RESTful APIs & Database Integration',
      description: 'HTTP methods, schema migrations, Eloquent relationships.',
      difficulty: 3,
      is_active: true,
      quizzes_count: 0,
      tasks_count: 1,
      subject: webSubject,
    };

    const t5Id = getNextId();
    const tWriting: Topic = {
      id: t5Id,
      subject_id: s3Id,
      name: 'Research Methodology & Technical Writing',
      description: 'Structured methodology, literature review, and APA formatting.',
      difficulty: 2,
      is_active: true,
      quizzes_count: 0,
      tasks_count: 1,
      subject: writingSubject,
    };

    topics.push(tDataStructures, tNetworks, tVue, tApis, tWriting);

    const task1: Task = {
      id: getNextId(),
      user_id: student.id,
      subject_id: s1Id,
      topic_id: t1Id,
      title: 'Review Stacks, Queues, and Binary Trees',
      description: 'Practice time complexity proofs and solve 5 queue-based tree traversal problems.',
      task_type: 'study',
      priority: 'high',
      start_date: dateAt(-4),
      deadline: dateAt(-1),
      estimated_minutes: 45,
      status: 'completed',
      created_at: timestampAt(-5, '08:00:00'),
      completed_at: timestampAt(-1, '19:00:00'),
      subject: csSubject,
      topic: tDataStructures,
    };

    const task2: Task = {
      id: getNextId(),
      user_id: student.id,
      subject_id: s1Id,
      topic_id: t2Id,
      title: 'Complete Subnetting & IP Addressing Problem Set',
      description: 'Calculate CIDR subnet masks, usable host ranges, and broadcast addresses.',
      task_type: 'assignment',
      priority: 'urgent',
      start_date: dateAt(0),
      deadline: dateAt(2),
      estimated_minutes: 50,
      status: 'in_progress',
      created_at: timestampAt(-1, '09:30:00'),
      subject: csSubject,
      topic: tNetworks,
    };

    const task3: Task = {
      id: getNextId(),
      user_id: student.id,
      subject_id: s2Id,
      topic_id: t3Id,
      title: 'Practice Vue 3 Composition API & State Store',
      description: 'Build reactive state composables and integrate with persistent local storage.',
      task_type: 'study',
      priority: 'high',
      start_date: dateAt(0),
      deadline: dateAt(3),
      estimated_minutes: 60,
      status: 'pending',
      created_at: timestampAt(0, '08:15:00'),
      subject: webSubject,
      topic: tVue,
    };

    const task4: Task = {
      id: getNextId(),
      user_id: student.id,
      subject_id: s3Id,
      topic_id: t5Id,
      title: 'Draft Literature Review for Research Proposal',
      description: 'Synthesize 5 peer-reviewed sources comparing mobile-first study planning tools.',
      task_type: 'assignment',
      priority: 'medium',
      start_date: dateAt(1),
      deadline: dateAt(6),
      estimated_minutes: 90,
      status: 'pending',
      created_at: timestampAt(0, '10:00:00'),
      subject: writingSubject,
      topic: tWriting,
    };

    const task5: Task = {
      id: getNextId(),
      user_id: student.id,
      subject_id: s2Id,
      topic_id: t4Id,
      title: 'Implement REST Endpoints with Request Validation',
      description: 'Write controller endpoints, form validation rules, and automated feature tests.',
      task_type: 'project',
      priority: 'low',
      start_date: dateAt(2),
      deadline: dateAt(7),
      estimated_minutes: 80,
      status: 'pending',
      created_at: timestampAt(0, '11:00:00'),
      subject: webSubject,
      topic: tApis,
    };

    tasks.push(task1, task2, task3, task4, task5);

    const session1: StudySession = {
      id: getNextId(),
      user_id: student.id,
      subject_id: s1Id,
      topic_id: t1Id,
      task_id: task1.id,
      scheduled_date: dateAt(-1),
      start_time: '18:00:00',
      end_time: '18:45:00',
      duration_minutes: 45,
      status: 'completed',
      completed_at: timestampAt(-1, '18:45:00'),
      task: task1,
      subject: csSubject,
      topic: tDataStructures,
    };

    const session2: StudySession = {
      id: getNextId(),
      user_id: student.id,
      subject_id: s1Id,
      topic_id: t2Id,
      task_id: task2.id,
      scheduled_date: dateAt(0),
      start_time: '18:00:00',
      end_time: '18:50:00',
      duration_minutes: 50,
      status: 'scheduled',
      task: task2,
      subject: csSubject,
      topic: tNetworks,
    };

    const session3: StudySession = {
      id: getNextId(),
      user_id: student.id,
      subject_id: s2Id,
      topic_id: t3Id,
      task_id: task3.id,
      scheduled_date: dateAt(1),
      start_time: '18:00:00',
      end_time: '18:50:00',
      duration_minutes: 50,
      status: 'scheduled',
      task: task3,
      subject: webSubject,
      topic: tVue,
    };

    const session4: StudySession = {
      id: getNextId(),
      user_id: student.id,
      subject_id: s3Id,
      topic_id: t5Id,
      task_id: task4.id,
      scheduled_date: dateAt(2),
      start_time: '19:00:00',
      end_time: '20:00:00',
      duration_minutes: 60,
      status: 'scheduled',
      task: task4,
      subject: writingSubject,
      topic: tWriting,
    };

    sessions.push(session1, session2, session3, session4);

    const q1Id = getNextId();
    const quiz1: Quiz = {
      id: q1Id,
      topic_id: t1Id,
      title: 'Data Structures & Algorithms Check-in',
      description: 'Assess foundational data structures, tree traversals, and search complexities.',
      passing_score: 70,
      is_active: true,
      questions_count: 4,
      topic: tDataStructures,
    };

    const q2Id = getNextId();
    const quiz2: Quiz = {
      id: q2Id,
      topic_id: t2Id,
      title: 'Computer Networks & Security Fundamentals',
      description: 'Test your understanding of OSI layers, IP subnetting, and network protocols.',
      passing_score: 70,
      is_active: true,
      questions_count: 3,
      topic: tNetworks,
    };

    const q3Id = getNextId();
    const quiz3: Quiz = {
      id: q3Id,
      topic_id: t3Id,
      title: 'Vue 3 & Frontend Architecture Quiz',
      description: 'Review Vue 3 reactivity fundamentals, lifecycle hooks, and single-page apps.',
      passing_score: 75,
      is_active: true,
      questions_count: 3,
      topic: tVue,
    };

    quizzes.push(quiz1, quiz2, quiz3);

    const quiz1Questions: Question[] = [
      { id: getNextId(), quiz_id: q1Id, question: 'Which data structure follows Last-In, First-Out (LIFO) behavior?', option_a: 'Stack', option_b: 'Queue', option_c: 'Graph', option_d: 'Hash Table', question_order: 1, correct_answer: 'A' },
      { id: getNextId(), quiz_id: q1Id, question: 'What is the average time complexity of searching a sorted array using Binary Search?', option_a: 'O(n)', option_b: 'O(log n)', option_c: 'O(n log n)', option_d: 'O(1)', question_order: 2, correct_answer: 'B' },
      { id: getNextId(), quiz_id: q1Id, question: 'Which tree traversal visits the root node before any child nodes?', option_a: 'In-order', option_b: 'Post-order', option_c: 'Pre-order', option_d: 'Level-order', question_order: 3, correct_answer: 'C' },
      { id: getNextId(), quiz_id: q1Id, question: 'What is the worst-case time complexity of standard Quicksort?', option_a: 'O(n log n)', option_b: 'O(n)', option_c: 'O(log n)', option_d: 'O(n²)', question_order: 4, correct_answer: 'D' },
    ];

    const quiz2Questions: Question[] = [
      { id: getNextId(), quiz_id: q2Id, question: 'Which layer of the OSI model does the HTTP/HTTPS protocol operate at?', option_a: 'Transport Layer', option_b: 'Network Layer', option_c: 'Application Layer', option_d: 'Data Link Layer', question_order: 1, correct_answer: 'C' },
      { id: getNextId(), quiz_id: q2Id, question: 'What is the default TCP port used for secure HTTPS communications?', option_a: '80', option_b: '443', option_c: '8080', option_d: '22', question_order: 2, correct_answer: 'B' },
      { id: getNextId(), quiz_id: q2Id, question: 'Which protocol translates human-readable domain names into IP addresses?', option_a: 'DHCP', option_b: 'ARP', option_c: 'DNS', option_d: 'FTP', question_order: 3, correct_answer: 'C' },
    ];

    const quiz3Questions: Question[] = [
      { id: getNextId(), quiz_id: q3Id, question: 'Which reactivity function is used to create a reactive primitive variable in Vue 3?', option_a: 'reactive()', option_b: 'ref()', option_c: 'computed()', option_d: 'watch()', question_order: 1, correct_answer: 'B' },
      { id: getNextId(), quiz_id: q3Id, question: 'Which lifecycle hook executes after the component DOM has been inserted into the page?', option_a: 'onBeforeMount', option_b: 'onMounted', option_c: 'onUpdated', option_d: 'onCreated', question_order: 2, correct_answer: 'B' },
      { id: getNextId(), quiz_id: q3Id, question: 'What HTTP method is considered idempotent and designed to replace a target resource completely?', option_a: 'POST', option_b: 'PUT', option_c: 'PATCH', option_d: 'DELETE', question_order: 3, correct_answer: 'B' },
    ];

    questions.push(...quiz1Questions, ...quiz2Questions, ...quiz3Questions);

    const att1Id = getNextId();
    const ans1: QuizAnswer[] = quiz1Questions.map(q => ({
      id: getNextId(),
      quiz_attempt_id: att1Id,
      question_id: q.id,
      selected_answer: q.correct_answer || 'A',
      is_correct: true,
      question: q,
    }));
    const attempt1: QuizAttempt = {
      id: att1Id,
      user_id: student.id,
      quiz_id: q1Id,
      score: 4,
      total_questions: 4,
      percentage: 100,
      started_at: timestampAt(-3, '18:00:00'),
      completed_at: timestampAt(-3, '18:14:00'),
      quiz: { ...quiz1, topic: tDataStructures },
      user: student,
      answers: ans1,
    };

    const att2Id = getNextId();
    const ans2: QuizAnswer[] = quiz2Questions.map((q, idx) => {
      const selected = idx === 0 ? q.correct_answer || 'C' : 'A';
      return {
        id: getNextId(),
        quiz_attempt_id: att2Id,
        question_id: q.id,
        selected_answer: selected,
        is_correct: selected === q.correct_answer,
        question: q,
      };
    });
    const attempt2: QuizAttempt = {
      id: att2Id,
      user_id: student.id,
      quiz_id: q2Id,
      score: 1,
      total_questions: 3,
      percentage: 33.3,
      started_at: timestampAt(-2, '19:00:00'),
      completed_at: timestampAt(-2, '19:08:00'),
      quiz: { ...quiz2, topic: tNetworks },
      user: student,
      answers: ans2,
    };

    const att3Id = getNextId();
    const ans3: QuizAnswer[] = quiz3Questions.map((q, idx) => {
      const selected = idx < 2 ? q.correct_answer || 'B' : 'A';
      return {
        id: getNextId(),
        quiz_attempt_id: att3Id,
        question_id: q.id,
        selected_answer: selected,
        is_correct: selected === q.correct_answer,
        question: q,
      };
    });
    const attempt3: QuizAttempt = {
      id: att3Id,
      user_id: student.id,
      quiz_id: q3Id,
      score: 2,
      total_questions: 3,
      percentage: 66.7,
      started_at: timestampAt(-1, '20:00:00'),
      completed_at: timestampAt(-1, '20:10:00'),
      quiz: { ...quiz3, topic: tVue },
      user: student,
      answers: ans3,
    };

    attempts.push(attempt1, attempt2, attempt3);

    notifications.push(
      {
        id: String(getNextId()),
        user_id: student.id,
        type: 'task_reminder',
        title: 'Upcoming Assignment Deadline',
        message: 'Subnetting & IP Addressing Problem Set is due in 2 days. Review your plan.',
        delivery_status: 'delivered',
        created_at: timestampAt(0, '08:00:00'),
      },
      {
        id: String(getNextId()),
        user_id: student.id,
        type: 'system_alert',
        title: 'Weekly Study Plan Ready',
        message: 'Your automated study sessions have been generated and scheduled for the week.',
        delivery_status: 'delivered',
        created_at: timestampAt(0, '08:30:00'),
      },
      {
        id: String(getNextId()),
        user_id: student.id,
        type: 'progress_update',
        title: 'Quiz Completed with Top Score',
        message: 'You scored 100% on Data Structures Check-in! Keep the momentum going.',
        delivery_status: 'delivered',
        created_at: timestampAt(-3, '18:15:00'),
        read_at: timestampAt(-3, '19:00:00'),
      },
      {
        id: String(getNextId()),
        user_id: student.id,
        type: 'task_reminder',
        title: 'Study Session Finished',
        message: 'You completed your 45-minute Data Structures study session yesterday.',
        delivery_status: 'delivered',
        created_at: timestampAt(-1, '18:46:00'),
        read_at: timestampAt(-1, '19:30:00'),
      }
    );
  });

  const allTopics = topics;
  const allSubjects = subjects;
  const r1Topics = allTopics.filter(t => t.name.includes('Data Structures'));
  const r2Topics = allTopics.filter(t => t.name.includes('Networks'));
  const r3Topics = allTopics.filter(t => t.name.includes('Vue'));
  const r4Topics = allTopics.filter(t => t.name.includes('RESTful'));
  const r5Topics = allTopics.filter(t => t.name.includes('Writing'));

  const demoResources: Resource[] = [
    {
      id: getNextId(),
      title: 'Mastering Data Structures: In-Depth Visual Guide',
      description: 'Comprehensive walkthrough of trees, heaps, stacks, and algorithmic complexity.',
      resource_type: 'tutorial',
      url: 'https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Indexed_collections',
      difficulty: 'intermediate',
      is_active: true,
      subjects: allSubjects.filter(s => s.name === 'Computer Science'),
      topics: r1Topics,
    },
    {
      id: getNextId(),
      title: 'Computer Networks & IP Subnetting Demystified',
      description: 'Step-by-step calculations for subnet masks, CIDR blocks, and network layer routing.',
      resource_type: 'article',
      url: 'https://developer.mozilla.org/en-US/docs/Web/HTTP/Overview',
      difficulty: 'beginner',
      is_active: true,
      subjects: allSubjects.filter(s => s.name === 'Computer Science'),
      topics: r2Topics,
    },
    {
      id: getNextId(),
      title: 'Vue 3 Composition API and Pinia State Management',
      description: 'Modern component design patterns, reactivity with ref/reactive, and stores.',
      resource_type: 'tutorial',
      url: 'https://vuejs.org/guide/introduction.html',
      difficulty: 'beginner',
      is_active: true,
      subjects: allSubjects.filter(s => s.name.includes('Web Systems')),
      topics: r3Topics,
    },
    {
      id: getNextId(),
      title: 'Architecting Scalable RESTful Web APIs',
      description: 'HTTP status codes, idempotent methods, resource nesting, and token auth.',
      resource_type: 'documentation',
      url: 'https://laravel.com/docs/controllers#api-resource-routes',
      difficulty: 'intermediate',
      is_active: true,
      subjects: allSubjects.filter(s => s.name.includes('Web Systems')),
      topics: r4Topics,
    },
    {
      id: getNextId(),
      title: 'Academic Writing & Literature Review Framework',
      description: 'Guide to organizing empirical evidence, synthesizing arguments, and APA citation.',
      resource_type: 'article',
      url: 'https://owl.purdue.edu/owl/general_writing/index.html',
      difficulty: 'beginner',
      is_active: true,
      subjects: allSubjects.filter(s => s.name.includes('Academic Research')),
      topics: r5Topics,
    },
    {
      id: getNextId(),
      title: 'Packet Analysis & Network Protocols with Wireshark',
      description: 'Deep dive into TCP handshakes, DNS queries, and TLS encrypted handshakes.',
      resource_type: 'video',
      url: 'https://www.wireshark.org/docs/',
      difficulty: 'advanced',
      is_active: true,
      subjects: allSubjects.filter(s => s.name === 'Computer Science'),
      topics: r2Topics,
    },
  ];

  const systemSettings: SystemSetting[] = [
    { id: getNextId(), key: 'weak_topic_threshold', value: '60', typed_value: 60, type: 'integer', description: 'Quiz score below which a topic is classified as a weak area.' },
    { id: getNextId(), key: 'review_topic_threshold', value: '79', typed_value: 79, type: 'integer', description: 'Quiz score below which a topic is classified as needing review.' },
    { id: getNextId(), key: 'deadline_reminder_hours', value: '24', typed_value: 24, type: 'integer', description: 'Hours before an assignment deadline to trigger alert notification.' },
    { id: getNextId(), key: 'study_session_reminder_minutes', value: '30', typed_value: 30, type: 'integer', description: 'Minutes before a study session to deliver a reminder.' },
    { id: getNextId(), key: 'max_study_plan_days', value: '30', typed_value: 30, type: 'integer', description: 'Maximum calendar days ahead for automated study session planning.' },
    { id: getNextId(), key: 'default_session_duration', value: '50', typed_value: 50, type: 'integer', description: 'Default continuous study session duration in minutes.' },
    { id: getNextId(), key: 'default_break_duration', value: '10', typed_value: 10, type: 'integer', description: 'Default rest interval between study sessions in minutes.' },
    { id: getNextId(), key: 'app_maintenance_mode', value: 'false', typed_value: false, type: 'boolean', description: 'Toggle application maintenance mode for scheduled updates.' },
  ];

  setStored(STORAGE_KEYS.SUBJECTS, subjects);
  setStored(STORAGE_KEYS.TOPICS, topics);
  setStored(STORAGE_KEYS.TASKS, tasks);
  setStored(STORAGE_KEYS.STUDY_SESSIONS, sessions);
  setStored(STORAGE_KEYS.QUIZZES, quizzes);
  setStored(STORAGE_KEYS.QUESTIONS, questions);
  setStored(STORAGE_KEYS.QUIZ_ATTEMPTS, attempts);
  setStored(STORAGE_KEYS.RESOURCES, demoResources);
  setStored(STORAGE_KEYS.NOTIFICATIONS, notifications);
  setStored(STORAGE_KEYS.SYSTEM_SETTINGS, systemSettings);
  localStorage.setItem(STORAGE_KEYS.DEMO_DATA_VERSION, DEMO_DATA_VERSION_KEY);

  console.log('[localApi] Demo data seeded successfully with full relations!');
}

export const localApi = {
  token: localStorage.getItem(STORAGE_KEYS.TOKEN),
  currentUser: getStored<User | null>(STORAGE_KEYS.USER, null),

  init() {
    ensureDemoDataSeeded();
    this.loadToken();
  },

  setToken(token: string) {
    this.token = token;
    localStorage.setItem(STORAGE_KEYS.TOKEN, token);
    localStorage.setItem('token', token);
  },

  clearToken() {
    this.token = null;
    this.currentUser = null;
    localStorage.removeItem(STORAGE_KEYS.TOKEN);
    localStorage.removeItem('token');
    localStorage.removeItem(STORAGE_KEYS.USER);
  },

  loadToken() {
    this.token = localStorage.getItem(STORAGE_KEYS.TOKEN) || localStorage.getItem('token');
    this.currentUser = getStored<User | null>(STORAGE_KEYS.USER, null);
  },

  async login(email: string, password: string) {
    await delay();
    ensureDemoDataSeeded();
    const users = getStored<User[]>(STORAGE_KEYS.USERS, []);
    const demoAccount = DEMO_ACCOUNTS.find(a => a.email.toLowerCase() === email.toLowerCase());

    let user = users.find(item => item.email.toLowerCase() === email.toLowerCase());

    const isDemoPassMatch = demoAccount && demoAccount.passwords.includes(password);
    const isStandardPassMatch = password === 'password' || password === 'StudentPassword123!' || password === 'AdminPassword123!' || password === 'StudentPass123!';

    if (demoAccount && (isDemoPassMatch || isStandardPassMatch)) {
      if (!user) {
        user = { id: getNextId(), ...demoAccount.user };
        users.push(user);
        setStored(STORAGE_KEYS.USERS, users);
      }
    } else if (!user || !isStandardPassMatch) {
      throw { response: { status: 401, data: { message: 'Invalid credentials. Use demo credentials to sign in.' } } };
    }

    if (!user) {
      throw { response: { status: 401, data: { message: 'User not found.' } } };
    }

    const token = 'local-token-' + Date.now();
    this.setToken(token);
    this.currentUser = user;
    setStored(STORAGE_KEYS.USER, user);
    return successResponse({ token, user });
  },

  async register(name: string, email: string, password: string) {
    await delay();
    ensureDemoDataSeeded();
    const users = getStored<User[]>(STORAGE_KEYS.USERS, []);
    if (users.find(u => u.email.toLowerCase() === email.toLowerCase())) {
      throw { response: { status: 422, data: { message: 'Email already exists.' } } };
    }
    const user: User = {
      id: getNextId(),
      name,
      email,
      role: 'student',
      is_active: true,
      created_at: new Date().toISOString(),
    };
    users.push(user);
    setStored(STORAGE_KEYS.USERS, users);
    const token = 'local-token-' + Date.now();
    this.setToken(token);
    this.currentUser = user;
    setStored(STORAGE_KEYS.USER, user);
    return successResponse({ token, user });
  },

  async logout() {
    await delay(30);
    this.clearToken();
  },

  async me() {
    await delay(30);
    if (!this.currentUser) {
      this.loadToken();
      if (!this.currentUser) throw { response: { status: 401, data: { message: 'Unauthenticated' } } };
    }
    return successResponse(this.currentUser);
  },

  async updateProfile(data: Partial<User>) {
    await delay();
    if (!this.currentUser) throw { response: { status: 401 } };
    this.currentUser = { ...this.currentUser, ...data };
    setStored(STORAGE_KEYS.USER, this.currentUser);
    const users = getStored<User[]>(STORAGE_KEYS.USERS, []);
    const idx = users.findIndex(u => u.id === this.currentUser!.id);
    if (idx >= 0) {
      users[idx] = this.currentUser;
      setStored(STORAGE_KEYS.USERS, users);
    }
    return successResponse(this.currentUser);
  },

  async updatePassword(_data: { current_password?: string; password?: string; password_confirmation?: string }) {
    await delay();
    return successResponse(null, 'Password updated successfully.');
  },

  async getDashboard() {
    await delay();
    ensureDemoDataSeeded();
    if (!this.currentUser) throw { response: { status: 401 } };

    const currentUserId = this.currentUser.id;
    const allTasks = getStored<Task[]>(STORAGE_KEYS.TASKS, []);
    const allSessions = getStored<StudySession[]>(STORAGE_KEYS.STUDY_SESSIONS, []);
    const allAttempts = getStored<QuizAttempt[]>(STORAGE_KEYS.QUIZ_ATTEMPTS, []);
    const allSubjects = getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, []);
    const allTopics = getStored<Topic[]>(STORAGE_KEYS.TOPICS, []);
    const allNotifications = getStored<Notification[]>(STORAGE_KEYS.NOTIFICATIONS, []);

    let tasks = allTasks.filter(t => t.user_id === currentUserId);
    let sessions = allSessions.filter(s => s.user_id === currentUserId);
    let attempts = allAttempts.filter(a => a.user_id === currentUserId);
    let subjects = allSubjects.filter(s => s.user_id === currentUserId);
    let notifications = allNotifications.filter(n => n.user_id === currentUserId);

    // Fallback to primary student's data if current user doesn't have records yet
    if (subjects.length === 0 && tasks.length === 0) {
      const primaryStudent = getStored<User[]>(STORAGE_KEYS.USERS, []).find(u => u.role === 'student');
      if (primaryStudent && primaryStudent.id !== currentUserId) {
        tasks = allTasks.filter(t => t.user_id === primaryStudent.id);
        sessions = allSessions.filter(s => s.user_id === primaryStudent.id);
        attempts = allAttempts.filter(a => a.user_id === primaryStudent.id);
        subjects = allSubjects.filter(s => s.user_id === primaryStudent.id);
        notifications = allNotifications.filter(n => n.user_id === primaryStudent.id);
      }
    }

    const today = dateKey(new Date());

    const upcomingTasks = tasks
      .filter(t => ['pending', 'in_progress'].includes(t.status))
      .sort((a, b) => a.deadline.localeCompare(b.deadline))
      .slice(0, 5)
      .map(task => ({
        ...task,
        subject: subjects.find(s => s.id === task.subject_id),
        topic: allTopics.find(t => t.id === task.topic_id),
      }));

    const upcomingSessions = sessions
      .filter(s => s.status === 'scheduled')
      .sort((a, b) => a.scheduled_date.localeCompare(b.scheduled_date) || a.start_time.localeCompare(b.start_time))
      .slice(0, 5)
      .map(session => ({
        ...session,
        subject: subjects.find(s => s.id === session.subject_id),
        topic: allTopics.find(t => t.id === session.topic_id),
        task: tasks.find(t => t.id === session.task_id),
      }));

    const recommendationsRes = await this.getRecommendations();

    const progressSummary: ProgressSummary = {
      tasks: {
        total: tasks.length,
        completed: tasks.filter(t => t.status === 'completed').length,
        completion_rate: tasks.length ? Math.round((tasks.filter(t => t.status === 'completed').length / tasks.length) * 100) : 0,
      },
      study_sessions: {
        total: sessions.length,
        completed: sessions.filter(s => s.status === 'completed').length,
        minutes_completed: sessions.filter(s => s.status === 'completed').reduce((sum, s) => sum + s.duration_minutes, 0),
      },
      quizzes: {
        attempts: attempts.length,
        average_percentage: attempts.length
          ? Math.round((attempts.reduce((sum, a) => sum + Number(a.percentage), 0) / attempts.length) * 10) / 10
          : 0,
      },
      generated_at: new Date().toISOString(),
    };

    return successResponse({
      progress: progressSummary,
      recommendations: recommendationsRes.data,
      setup_status: {
        has_preferences: !!getStored<StudyPreference | null>(STORAGE_KEYS.STUDY_PREFERENCE, null),
        subjects_count: subjects.length,
        tasks_count: tasks.length,
        study_sessions_count: sessions.length,
        quiz_attempts_count: attempts.length,
        is_fully_onboarded: subjects.length > 0 && tasks.length > 0,
      },
      upcoming_tasks: upcomingTasks,
      upcoming_sessions: upcomingSessions,
      unread_notifications: notifications.filter(n => !n.read_at).length,
    });
  },

  async getSubjects() {
    await delay();
    ensureDemoDataSeeded();
    const allSubjects = getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, []);
    const allTopics = getStored<Topic[]>(STORAGE_KEYS.TOPICS, []);
    const allTasks = getStored<Task[]>(STORAGE_KEYS.TASKS, []);

    let filtered = allSubjects.filter(s => s.user_id === this.currentUser?.id);
    if (filtered.length === 0 && this.currentUser?.role === 'student') {
      filtered = allSubjects;
    }

    const enriched = filtered.map(subject => ({
      ...subject,
      topics_count: allTopics.filter(t => t.subject_id === subject.id).length,
      tasks_count: allTasks.filter(t => t.subject_id === subject.id).length,
    }));

    return successResponse(enriched);
  },

  async getSubject(id: number) {
    await delay();
    ensureDemoDataSeeded();
    const subjects = getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, []);
    const subject = subjects.find(s => s.id === id);
    if (!subject) throw { response: { status: 404, data: { message: 'Subject not found' } } };
    const topics = getStored<Topic[]>(STORAGE_KEYS.TOPICS, []).filter(t => t.subject_id === id);
    return successResponse({ ...subject, topics });
  },

  async createSubject(data: Partial<Subject>) {
    await delay();
    const subjects = getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, []);
    const subject: Subject = {
      id: getNextId(),
      user_id: this.currentUser?.id || 1,
      name: data.name || '',
      description: data.description,
      difficulty: data.difficulty || 1,
      color: data.color || 'blue',
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
    ensureDemoDataSeeded();
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

  async getTasks(params?: { status?: string; priority?: string; subject_id?: number; from?: string; to?: string }) {
    await delay();
    ensureDemoDataSeeded();
    const allTasks = getStored<Task[]>(STORAGE_KEYS.TASKS, []);
    const subjects = getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, []);
    const topics = getStored<Topic[]>(STORAGE_KEYS.TOPICS, []);

    let tasks = allTasks.filter(t => t.user_id === this.currentUser?.id);
    if (tasks.length === 0 && this.currentUser?.role === 'student') {
      tasks = allTasks;
    }

    if (params?.status) tasks = tasks.filter(t => t.status === params.status);
    if (params?.priority) tasks = tasks.filter(t => t.priority === params.priority);
    if (params?.subject_id) tasks = tasks.filter(t => t.subject_id === Number(params.subject_id));
    if (params?.from) tasks = tasks.filter(task => (task.start_date || task.deadline || '').slice(0, 10) >= params.from!);
    if (params?.to) tasks = tasks.filter(task => (task.start_date || task.deadline || '').slice(0, 10) <= params.to!);

    const enriched = tasks.map(task => ({
      ...task,
      subject: subjects.find(s => s.id === task.subject_id),
      topic: topics.find(t => t.id === task.topic_id),
    }));

    return paginatedResponse(enriched);
  },

  async createTask(data: Partial<Task>) {
    await delay();
    const tasks = getStored<Task[]>(STORAGE_KEYS.TASKS, []);
    const subjects = getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, []);
    const topics = getStored<Topic[]>(STORAGE_KEYS.TOPICS, []);

    const task: Task = {
      id: getNextId(),
      user_id: this.currentUser?.id || 1,
      subject_id: Number(data.subject_id) || 0,
      topic_id: data.topic_id ? Number(data.topic_id) : undefined,
      title: data.title || '',
      description: data.description,
      task_type: data.task_type || 'study',
      priority: data.priority || 'medium',
      start_date: data.start_date || dateKey(new Date()),
      deadline: data.deadline || dateKey(new Date()),
      estimated_minutes: Number(data.estimated_minutes) || 30,
      status: 'pending',
      created_at: new Date().toISOString(),
      subject: subjects.find(s => s.id === Number(data.subject_id)),
      topic: topics.find(t => t.id === Number(data.topic_id)),
    };
    tasks.push(task);
    setStored(STORAGE_KEYS.TASKS, tasks);
    return successResponse(task);
  },

  async updateTask(id: number, data: Partial<Task>) {
    await delay();
    const tasks = getStored<Task[]>(STORAGE_KEYS.TASKS, []);
    const subjects = getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, []);
    const topics = getStored<Topic[]>(STORAGE_KEYS.TOPICS, []);
    const idx = tasks.findIndex(t => t.id === id);
    if (idx < 0) throw { response: { status: 404 } };

    tasks[idx] = {
      ...tasks[idx],
      ...data,
      subject: subjects.find(s => s.id === (data.subject_id !== undefined ? Number(data.subject_id) : tasks[idx].subject_id)),
      topic: topics.find(t => t.id === (data.topic_id !== undefined ? Number(data.topic_id) : tasks[idx].topic_id)),
    };
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
    return this.updateTask(id, { status: 'completed', completed_at: new Date().toISOString() });
  },

  async updateTaskStatus(id: number, status: Task['status']) {
    return this.updateTask(id, {
      status,
      completed_at: status === 'completed' ? new Date().toISOString() : undefined,
    });
  },

  async getStudySessions(params?: { from?: string; to?: string; status?: string }) {
    await delay();
    ensureDemoDataSeeded();
    const allSessions = getStored<StudySession[]>(STORAGE_KEYS.STUDY_SESSIONS, []);
    const tasks = getStored<Task[]>(STORAGE_KEYS.TASKS, []);
    const subjects = getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, []);
    const topics = getStored<Topic[]>(STORAGE_KEYS.TOPICS, []);

    let sessions = allSessions.filter(s => s.user_id === this.currentUser?.id);
    if (sessions.length === 0 && this.currentUser?.role === 'student') {
      sessions = allSessions;
    }

    if (params?.from) sessions = sessions.filter(session => dateKey(dateOnly(session.scheduled_date)) >= params.from!);
    if (params?.to) sessions = sessions.filter(session => dateKey(dateOnly(session.scheduled_date)) <= params.to!);
    if (params?.status) sessions = sessions.filter(s => s.status === params.status);

    const enriched = sessions
      .sort((left, right) => left.scheduled_date.localeCompare(right.scheduled_date) || left.start_time.localeCompare(right.start_time))
      .map(session => ({
        ...session,
        task: tasks.find(task => task.id === session.task_id),
        subject: subjects.find(subject => subject.id === session.subject_id),
        topic: topics.find(topic => topic.id === session.topic_id),
      }));

    return successResponse(enriched);
  },

  async generateStudyPlan(from: string, to: string) {
    await delay();
    ensureDemoDataSeeded();
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
      .filter(task => (task.user_id === this.currentUser!.id || this.currentUser!.role === 'student') && ['pending', 'in_progress'].includes(task.status))
      .filter(task => task.deadline.slice(0, 10) >= from && task.deadline.slice(0, 10) <= to)
      .sort((left, right) => {
        const priority: Record<string, number> = { urgent: 0, high: 1, medium: 2, low: 3 };
        return (priority[left.priority] ?? 2) - (priority[right.priority] ?? 2) || left.deadline.localeCompare(right.deadline);
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
    sessions[idx] = {
      ...sessions[idx],
      status,
      completed_at: status === 'completed' ? new Date().toISOString() : undefined,
    };
    setStored(STORAGE_KEYS.STUDY_SESSIONS, sessions);
    return successResponse(sessions[idx]);
  },

  async getQuizzes() {
    await delay();
    ensureDemoDataSeeded();
    const quizzes = getStored<Quiz[]>(STORAGE_KEYS.QUIZZES, []);
    const topics = getStored<Topic[]>(STORAGE_KEYS.TOPICS, []);
    const subjects = getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, []);

    const enriched = quizzes.map(quiz => {
      const topic = topics.find(t => t.id === quiz.topic_id);
      const subject = topic ? subjects.find(s => s.id === topic.subject_id) : undefined;
      return {
        ...quiz,
        topic: topic ? { ...topic, subject } : undefined,
      };
    });

    return successResponse(enriched);
  },

  async getQuiz(id: number) {
    await delay();
    ensureDemoDataSeeded();
    const quizzes = getStored<Quiz[]>(STORAGE_KEYS.QUIZZES, []);
    const quiz = quizzes.find(q => q.id === id);
    if (!quiz) throw { response: { status: 404, data: { message: 'Quiz not found' } } };

    const topics = getStored<Topic[]>(STORAGE_KEYS.TOPICS, []);
    const subjects = getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, []);
    const topic = topics.find(t => t.id === quiz.topic_id);
    const subject = topic ? subjects.find(s => s.id === topic.subject_id) : undefined;

    const questions = getStored<Question[]>(STORAGE_KEYS.QUESTIONS, [])
      .filter(question => question.quiz_id === id)
      .map(question => {
        const safeQuestion = { ...question };
        delete safeQuestion.correct_answer;
        return safeQuestion;
      });

    return successResponse({
      ...quiz,
      topic: topic ? { ...topic, subject } : undefined,
      questions,
    });
  },

  async submitQuiz(id: number, answers: Record<number, string>, started_at: string) {
    await delay();
    ensureDemoDataSeeded();
    const quiz = getStored<Quiz[]>(STORAGE_KEYS.QUIZZES, []).find(q => q.id === id);
    const questions = getStored<Question[]>(STORAGE_KEYS.QUESTIONS, []).filter(q => q.quiz_id === id);
    if (!quiz || !this.currentUser || questions.length === 0) {
      throw { response: { status: quiz ? 422 : 404, data: { message: 'Quiz is unavailable.' } } };
    }

    const topics = getStored<Topic[]>(STORAGE_KEYS.TOPICS, []);
    const subjects = getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, []);
    const topic = topics.find(t => t.id === quiz.topic_id);
    const subject = topic ? subjects.find(s => s.id === topic.subject_id) : undefined;

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
      user_id: this.currentUser.id,
      quiz_id: id,
      score,
      total_questions: questions.length,
      percentage: Math.round((score / questions.length) * 10000) / 100,
      started_at,
      completed_at: new Date().toISOString(),
      quiz: { ...quiz, topic: topic ? { ...topic, subject } : undefined },
      user: this.currentUser,
      answers: quizAnswers,
    };

    const attempts = getStored<QuizAttempt[]>(STORAGE_KEYS.QUIZ_ATTEMPTS, []);
    attempts.unshift(attempt);
    setStored(STORAGE_KEYS.QUIZ_ATTEMPTS, attempts);
    return successResponse(attempt);
  },

  async getQuizHistory() {
    await delay();
    ensureDemoDataSeeded();
    const allAttempts = getStored<QuizAttempt[]>(STORAGE_KEYS.QUIZ_ATTEMPTS, []);
    const quizzes = getStored<Quiz[]>(STORAGE_KEYS.QUIZZES, []);
    const topics = getStored<Topic[]>(STORAGE_KEYS.TOPICS, []);
    const subjects = getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, []);

    let attempts = allAttempts.filter(attempt => attempt.user_id === this.currentUser?.id);
    if (attempts.length === 0 && this.currentUser?.role === 'student') {
      attempts = allAttempts;
    }

    const enriched = attempts
      .map(attempt => {
        const quiz = quizzes.find(q => q.id === attempt.quiz_id);
        const topic = quiz ? topics.find(t => t.id === quiz.topic_id) : undefined;
        const subject = topic ? subjects.find(s => s.id === topic.subject_id) : undefined;
        return {
          ...attempt,
          quiz: quiz ? { ...quiz, topic: topic ? { ...topic, subject } : undefined } : attempt.quiz,
        };
      })
      .sort((left, right) => (right.completed_at || '').localeCompare(left.completed_at || ''));

    return successResponse(enriched);
  },

  async getQuizAttempt(id: number) {
    await delay();
    ensureDemoDataSeeded();
    const attempts = getStored<QuizAttempt[]>(STORAGE_KEYS.QUIZ_ATTEMPTS, []);
    const attempt = attempts.find(item => item.id === id);
    if (!attempt) throw { response: { status: 404, data: { message: 'Attempt not found' } } };

    const quizzes = getStored<Quiz[]>(STORAGE_KEYS.QUIZZES, []);
    const topics = getStored<Topic[]>(STORAGE_KEYS.TOPICS, []);
    const subjects = getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, []);
    const questions = getStored<Question[]>(STORAGE_KEYS.QUESTIONS, []);

    const quiz = quizzes.find(q => q.id === attempt.quiz_id);
    const topic = quiz ? topics.find(t => t.id === quiz.topic_id) : undefined;
    const subject = topic ? subjects.find(s => s.id === topic.subject_id) : undefined;

    const enrichedAnswers = (attempt.answers || []).map(ans => ({
      ...ans,
      question: questions.find(q => q.id === ans.question_id) || ans.question,
    }));

    return successResponse({
      ...attempt,
      quiz: quiz ? { ...quiz, topic: topic ? { ...topic, subject } : undefined } : attempt.quiz,
      answers: enrichedAnswers,
    });
  },

  async getRecommendations() {
    await delay();
    ensureDemoDataSeeded();
    if (!this.currentUser) throw { response: { status: 401 } };

    const targetUserId = this.currentUser.id;
    let focusAreas = getLocalTopicPerformance(targetUserId).filter(topic => topic.classification !== 'strong');
    if (focusAreas.length === 0) {
      // Check any student performance for demo display
      const anyStudent = getStored<User[]>(STORAGE_KEYS.USERS, []).find(u => u.role === 'student');
      if (anyStudent) {
        focusAreas = getLocalTopicPerformance(anyStudent.id).filter(topic => topic.classification !== 'strong');
      }
    }

    const allResources = getStored<Resource[]>(STORAGE_KEYS.RESOURCES, []);

    if (focusAreas.length === 0) {
      return successResponse(allResources.slice(0, 3));
    }

    const focusTopicIds = new Set(focusAreas.map(topic => topic.topic_id));
    const allTopics = getStored<Topic[]>(STORAGE_KEYS.TOPICS, []);
    const focusSubjectIds = new Set(focusAreas
      .map(topic => allTopics.find(item => item.id === topic.topic_id)?.subject_id)
      .filter((id): id is number => id !== undefined));

    const recommendationOrder: Record<string, number> = { weak: 0, needs_review: 1, related: 2 };

    const recommendations = allResources
      .filter(resource => resource.is_active)
      .map(resource => {
        const resourceTopicIds = (resource.topics || []).map(topic => topic.id);
        const resourceSubjectIds = (resource.subjects || []).map(subject => subject.id);
        const matchingAreas = focusAreas.filter(area =>
          focusTopicIds.has(area.topic_id) &&
          (resourceTopicIds.includes(area.topic_id) || focusSubjectIds.has(allTopics.find(t => t.id === area.topic_id)?.subject_id || -1))
        );

        const reason = matchingAreas.some(area => area.classification === 'weak')
          ? 'weak'
          : matchingAreas.some(area => area.classification === 'needs_review')
            ? 'needs_review'
            : 'related';

        return { ...resource, recommendation_reason: reason };
      })
      .sort((left, right) => (recommendationOrder[left.recommendation_reason || 'related'] ?? 2)
        - (recommendationOrder[right.recommendation_reason || 'related'] ?? 2)
        || left.title.localeCompare(right.title));

    return successResponse(recommendations);
  },

  async recordView(resourceId: number, topicId?: number) {
    await delay(30);
    const views = getStored<Array<{ id: number; user_id: number; resource_id: number; topic_id?: number; viewed_at: string }>>(STORAGE_KEYS.RECOMMENDATION_VIEWS, []);
    const view = {
      id: getNextId(),
      user_id: this.currentUser?.id || 1,
      resource_id: resourceId,
      topic_id: topicId,
      viewed_at: new Date().toISOString(),
    };
    views.push(view);
    setStored(STORAGE_KEYS.RECOMMENDATION_VIEWS, views);
    return successResponse(view);
  },

  async getStudyPreference() {
    await delay();
    ensureDemoDataSeeded();
    const pref = getStored<StudyPreference | null>(STORAGE_KEYS.STUDY_PREFERENCE, null);
    return successResponse(pref || {
      id: this.currentUser?.id || 1,
      user_id: this.currentUser?.id || 1,
      max_daily_minutes: 180,
      session_duration_minutes: 50,
      break_duration_minutes: 10,
      preferred_start_time: '18:00',
      preferred_end_time: '22:00',
    });
  },

  async upsertStudyPreference(data: Partial<StudyPreference>) {
    await delay();
    const existing = getStored<StudyPreference | null>(STORAGE_KEYS.STUDY_PREFERENCE, null);
    const pref: StudyPreference = {
      id: existing?.id || getNextId(),
      user_id: this.currentUser?.id || 1,
      max_daily_minutes: 180,
      session_duration_minutes: 50,
      break_duration_minutes: 10,
      preferred_start_time: '18:00',
      preferred_end_time: '22:00',
      ...existing,
      ...data,
    };
    setStored(STORAGE_KEYS.STUDY_PREFERENCE, pref);
    return successResponse(pref);
  },

  async getProgress() {
    await delay();
    ensureDemoDataSeeded();
    const currentUserId = this.currentUser?.id || 1;
    const allTasks = getStored<Task[]>(STORAGE_KEYS.TASKS, []);
    const allSessions = getStored<StudySession[]>(STORAGE_KEYS.STUDY_SESSIONS, []);
    const allAttempts = getStored<QuizAttempt[]>(STORAGE_KEYS.QUIZ_ATTEMPTS, []);

    let tasks = allTasks.filter(t => t.user_id === currentUserId);
    let sessions = allSessions.filter(s => s.user_id === currentUserId);
    let attempts = allAttempts.filter(a => a.user_id === currentUserId);

    if (tasks.length === 0 && sessions.length === 0) {
      tasks = allTasks;
      sessions = allSessions;
      attempts = allAttempts;
    }

    return successResponse({
      summary: {
        tasks: {
          total: tasks.length,
          completed: tasks.filter(t => t.status === 'completed').length,
          completion_rate: tasks.length ? Math.round((tasks.filter(t => t.status === 'completed').length / tasks.length) * 100) : 0,
        },
        study_sessions: {
          total: sessions.length,
          completed: sessions.filter(s => s.status === 'completed').length,
          minutes_completed: sessions.filter(s => s.status === 'completed').reduce((a, s) => a + s.duration_minutes, 0),
        },
        quizzes: {
          attempts: attempts.length,
          average_percentage: attempts.length
            ? Math.round((attempts.reduce((a, s) => a + Number(s.percentage), 0) / attempts.length) * 10) / 10
            : 0,
        },
        generated_at: new Date().toISOString(),
      },
      topic_performance: getLocalTopicPerformance(currentUserId),
    });
  },

  async getNotifications(params?: { unread?: boolean; page?: number }) {
    await delay();
    ensureDemoDataSeeded();
    let notifications = getStored<Notification[]>(STORAGE_KEYS.NOTIFICATIONS, []);
    if (this.currentUser) {
      const userNotifs = notifications.filter(n => n.user_id === this.currentUser!.id);
      if (userNotifs.length > 0) notifications = userNotifs;
    }
    if (params?.unread) notifications = notifications.filter(n => !n.read_at);
    return paginatedResponse(notifications);
  },

  async getNotificationCount() {
    await delay(30);
    ensureDemoDataSeeded();
    const notifications = getStored<Notification[]>(STORAGE_KEYS.NOTIFICATIONS, []);
    const targetUserId = this.currentUser?.id;
    const userNotifs = targetUserId ? notifications.filter(n => n.user_id === targetUserId) : notifications;
    const unread = userNotifs.filter(n => !n.read_at).length;
    return successResponse({ unread, total: userNotifs.length });
  },

  async markNotificationRead(id: string) {
    await delay(30);
    const notifications = getStored<Notification[]>(STORAGE_KEYS.NOTIFICATIONS, []);
    const idx = notifications.findIndex(n => n.id === id);
    if (idx >= 0) {
      notifications[idx] = { ...notifications[idx], read_at: new Date().toISOString() };
      setStored(STORAGE_KEYS.NOTIFICATIONS, notifications);
    }
    return successResponse(notifications[idx]);
  },

  async markAllNotificationsRead() {
    await delay(30);
    const notifications = getStored<Notification[]>(STORAGE_KEYS.NOTIFICATIONS, []);
    const updated = notifications.map(n => (!n.read_at ? { ...n, read_at: new Date().toISOString() } : n));
    setStored(STORAGE_KEYS.NOTIFICATIONS, updated);
    return successResponse({ marked_read: updated.filter(n => n.read_at).length });
  },

  async getAdminResources(params?: { resource_type?: string; difficulty?: string; search?: string; page?: number }) {
    await delay();
    ensureDemoDataSeeded();
    let resources = getStored<Resource[]>(STORAGE_KEYS.RESOURCES, []);

    if (params?.resource_type) {
      resources = resources.filter(r => r.resource_type === params.resource_type);
    }
    if (params?.difficulty) {
      resources = resources.filter(r => r.difficulty === params.difficulty);
    }
    if (params?.search) {
      const term = params.search.toLowerCase();
      resources = resources.filter(r => r.title.toLowerCase().includes(term) || (r.description || '').toLowerCase().includes(term));
    }

    return paginatedResponse(resources);
  },

  async getAdminResourceOptions() {
    await delay();
    ensureDemoDataSeeded();
    const subjects = getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, []);
    const topics = getStored<Topic[]>(STORAGE_KEYS.TOPICS, []);
    const enrichedTopics = topics.map(topic => ({
      ...topic,
      subject: subjects.find(s => s.id === topic.subject_id),
    }));

    return successResponse({
      subjects,
      topics: enrichedTopics,
    });
  },

  async getAdminSubjects() {
    await delay();
    ensureDemoDataSeeded();
    const subjects = getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, []);
    const topics = getStored<Topic[]>(STORAGE_KEYS.TOPICS, []);
    const users = getStored<User[]>(STORAGE_KEYS.USERS, []);

    const enriched = subjects.map(subject => ({
      ...subject,
      user: users.find(u => u.id === subject.user_id),
      topics: topics.filter(t => t.subject_id === subject.id),
      topics_count: topics.filter(t => t.subject_id === subject.id).length,
    }));

    return successResponse(enriched);
  },

  async getAdminSubjectOptions() {
    await delay();
    ensureDemoDataSeeded();
    return successResponse(getStored<User[]>(STORAGE_KEYS.USERS, []).filter(u => u.role === 'student'));
  },

  async createAdminSubject(data: Partial<Subject> & { user_id: number }) {
    await delay();
    const subjects = getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, []);
    const users = getStored<User[]>(STORAGE_KEYS.USERS, []);
    const subject: Subject = {
      id: getNextId(),
      user_id: data.user_id,
      name: data.name || '',
      description: data.description,
      difficulty: data.difficulty || 1,
      color: data.color || 'blue',
      is_active: true,
      topics_count: 0,
      tasks_count: 0,
      user: users.find(u => u.id === data.user_id),
      topics: [],
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
    resources.unshift(resource);
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

  async getAdminUsers(params?: { role?: string; is_active?: boolean | string; search?: string; page?: number }) {
    await delay();
    ensureDemoDataSeeded();
    let users = getStored<User[]>(STORAGE_KEYS.USERS, []);
    const subjects = getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, []);
    const tasks = getStored<Task[]>(STORAGE_KEYS.TASKS, []);
    const attempts = getStored<QuizAttempt[]>(STORAGE_KEYS.QUIZ_ATTEMPTS, []);

    if (params?.role) {
      users = users.filter(u => u.role === params.role);
    }
    if (params?.is_active !== undefined && params.is_active !== '') {
      const activeBool = String(params.is_active) === 'true';
      users = users.filter(u => u.is_active === activeBool);
    }
    if (params?.search) {
      const term = params.search.toLowerCase();
      users = users.filter(u => u.name.toLowerCase().includes(term) || u.email.toLowerCase().includes(term));
    }

    const enriched = users.map(user => ({
      ...user,
      subjects_count: subjects.filter(s => s.user_id === user.id).length,
      tasks_count: tasks.filter(t => t.user_id === user.id).length,
      quiz_attempts_count: attempts.filter(a => a.user_id === user.id).length,
    }));

    return paginatedResponse(enriched);
  },

  async updateAdminUser(id: number, data: Partial<User>) {
    await delay();
    const users = getStored<User[]>(STORAGE_KEYS.USERS, []);
    const idx = users.findIndex(u => u.id === id);
    if (idx < 0) throw { response: { status: 404 } };
    users[idx] = { ...users[idx], ...data };
    setStored(STORAGE_KEYS.USERS, users);
    return successResponse(users[idx]);
  },

  async getSystemSettings() {
    await delay();
    ensureDemoDataSeeded();
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
    ensureDemoDataSeeded();
    const users = getStored<User[]>(STORAGE_KEYS.USERS, []);
    const students = users.filter(u => u.role === 'student');
    const resources = getStored<Resource[]>(STORAGE_KEYS.RESOURCES, []);
    const subjects = getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, []);
    const quizzes = getStored<Quiz[]>(STORAGE_KEYS.QUIZZES, []);
    const attempts = getStored<QuizAttempt[]>(STORAGE_KEYS.QUIZ_ATTEMPTS, []);

    const averageQuizScore = attempts.length
      ? Math.round((attempts.reduce((sum, a) => sum + Number(a.percentage), 0) / attempts.length) * 10) / 10
      : 0;

    const recentAttempts = attempts.slice(0, 5).map(attempt => {
      const quiz = quizzes.find(q => q.id === attempt.quiz_id);
      const user = users.find(u => u.id === attempt.user_id);
      return {
        ...attempt,
        quiz: quiz ? { title: quiz.title } : { title: 'Assessment Quiz' },
        user: user ? { name: user.name } : { name: 'Student' },
      };
    });

    const recentUsers = students.slice(0, 5).map(user => ({
      ...user,
      created_at: user.created_at || new Date().toISOString(),
    }));

    return successResponse({
      metrics: {
        total_students: students.length,
        active_students: students.filter(u => u.is_active).length,
        total_resources: resources.length,
        total_subjects: subjects.length,
        total_quizzes: quizzes.length,
        total_quiz_attempts: attempts.length,
        average_quiz_score: averageQuizScore,
      },
      recent_users: recentUsers,
      recent_attempts: recentAttempts,
    });
  },

  async getAdminUser(id: number) {
    await delay();
    const users = getStored<User[]>(STORAGE_KEYS.USERS, []);
    const user = users.find(u => u.id === id);
    if (!user) throw { response: { status: 404 } };
    return successResponse(user);
  },

  async getSetupStatus() {
    await delay();
    ensureDemoDataSeeded();
    const targetUserId = this.currentUser?.id;
    const subjects = getStored<Subject[]>(STORAGE_KEYS.SUBJECTS, []).filter(s => s.user_id === targetUserId);
    const tasks = getStored<Task[]>(STORAGE_KEYS.TASKS, []).filter(t => t.user_id === targetUserId);
    const sessions = getStored<StudySession[]>(STORAGE_KEYS.STUDY_SESSIONS, []).filter(s => s.user_id === targetUserId);
    const attempts = getStored<QuizAttempt[]>(STORAGE_KEYS.QUIZ_ATTEMPTS, []).filter(a => a.user_id === targetUserId);
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

  // Fallback router for HTTP verb calls
  async get(url: string, config?: any): Promise<any> {
    const cleanUrl = url.replace(/^\/api/, '');
    if (cleanUrl.startsWith('/auth/me')) return this.me();
    if (cleanUrl.startsWith('/student/dashboard')) return this.getDashboard();
    if (cleanUrl.startsWith('/student/subjects/')) {
      const id = parseInt(cleanUrl.split('/')[3]);
      return this.getSubject(id);
    }
    if (cleanUrl.startsWith('/student/subjects')) return this.getSubjects();
    if (cleanUrl.includes('/topics/')) {
      const parts = cleanUrl.split('/');
      return this.getTopic(parseInt(parts[3]), parseInt(parts[5]));
    }
    if (cleanUrl.includes('/topics')) {
      const parts = cleanUrl.split('/');
      return this.getTopics(parseInt(parts[3]));
    }
    if (cleanUrl.startsWith('/student/tasks')) return this.getTasks(config?.params);
    if (cleanUrl.startsWith('/student/study-plan')) return this.getStudySessions(config?.params);
    if (cleanUrl.startsWith('/student/quizzes/')) return this.getQuiz(parseInt(cleanUrl.split('/')[3]));
    if (cleanUrl.startsWith('/student/quizzes')) return this.getQuizzes();
    if (cleanUrl.startsWith('/student/quiz-attempts/')) return this.getQuizAttempt(parseInt(cleanUrl.split('/')[3]));
    if (cleanUrl.startsWith('/student/quiz-attempts')) return this.getQuizHistory();
    if (cleanUrl.startsWith('/student/recommendations')) return this.getRecommendations();
    if (cleanUrl.startsWith('/student/progress')) return this.getProgress();
    if (cleanUrl.startsWith('/student/notifications/count')) return this.getNotificationCount();
    if (cleanUrl.startsWith('/student/notifications')) return this.getNotifications(config?.params);
    if (cleanUrl.startsWith('/student/study-preference')) return this.getStudyPreference();
    if (cleanUrl.startsWith('/student/setup-status')) return this.getSetupStatus();

    if (cleanUrl.startsWith('/admin/dashboard')) return this.getAdminDashboard();
    if (cleanUrl.startsWith('/admin/resources')) return this.getAdminResources(config?.params);
    if (cleanUrl.startsWith('/admin/resource-options')) return this.getAdminResourceOptions();
    if (cleanUrl.startsWith('/admin/subjects/options')) return this.getAdminSubjectOptions();
    if (cleanUrl.startsWith('/admin/subjects')) return this.getAdminSubjects();
    if (cleanUrl.startsWith('/admin/users/')) return this.getAdminUser(parseInt(cleanUrl.split('/')[3]));
    if (cleanUrl.startsWith('/admin/users')) return this.getAdminUsers(config?.params);
    if (cleanUrl.startsWith('/admin/system-settings')) return this.getSystemSettings();

    return successResponse({});
  },

  async post(url: string, data?: any): Promise<any> {
    const cleanUrl = url.replace(/^\/api/, '');
    if (cleanUrl.startsWith('/auth/login')) return this.login(data?.email, data?.password);
    if (cleanUrl.startsWith('/auth/register')) return this.register(data?.name, data?.email, data?.password);
    if (cleanUrl.startsWith('/auth/logout')) return this.logout();
    if (cleanUrl.startsWith('/student/subjects')) return this.createSubject(data);
    if (cleanUrl.includes('/topics')) {
      const parts = cleanUrl.split('/');
      return this.createTopic(parseInt(parts[3]), data);
    }
    if (cleanUrl.startsWith('/student/tasks/') && cleanUrl.endsWith('/complete')) {
      return this.completeTask(parseInt(cleanUrl.split('/')[3]));
    }
    if (cleanUrl.startsWith('/student/tasks')) return this.createTask(data);
    if (cleanUrl.startsWith('/student/study-plan/generate')) return this.generateStudyPlan(data?.from, data?.to);
    if (cleanUrl.startsWith('/student/quizzes/') && cleanUrl.endsWith('/submit')) {
      return this.submitQuiz(parseInt(cleanUrl.split('/')[3]), data?.answers, data?.started_at);
    }
    if (cleanUrl.startsWith('/student/recommendations/') && cleanUrl.endsWith('/view')) {
      return this.recordView(parseInt(cleanUrl.split('/')[3]), data?.topic_id);
    }
    if (cleanUrl.startsWith('/student/study-preference')) return this.upsertStudyPreference(data);
    if (cleanUrl.startsWith('/student/notifications/') && cleanUrl.endsWith('/read')) {
      return this.markNotificationRead(cleanUrl.split('/')[3]);
    }
    if (cleanUrl.startsWith('/student/notifications/read-all')) return this.markAllNotificationsRead();

    if (cleanUrl.startsWith('/admin/subjects')) return this.createAdminSubject(data);
    if (cleanUrl.startsWith('/admin/resources')) return this.createResource(data);
    return successResponse({});
  },

  async put(url: string, data?: any): Promise<any> {
    const cleanUrl = url.replace(/^\/api/, '');
    if (cleanUrl.startsWith('/auth/profile')) return this.updateProfile(data);
    if (cleanUrl.startsWith('/auth/password')) return this.updatePassword(data);
    if (cleanUrl.startsWith('/student/subjects/')) return this.updateSubject(parseInt(cleanUrl.split('/')[3]), data);
    if (cleanUrl.includes('/topics/')) {
      const parts = cleanUrl.split('/');
      return this.updateTopic(parseInt(parts[3]), parseInt(parts[5]), data);
    }
    if (cleanUrl.startsWith('/student/tasks/')) return this.updateTask(parseInt(cleanUrl.split('/')[3]), data);
    if (cleanUrl.startsWith('/admin/subjects/')) return this.updateAdminSubject(parseInt(cleanUrl.split('/')[3]), data);
    return successResponse({});
  },

  async patch(url: string, data?: any): Promise<any> {
    const cleanUrl = url.replace(/^\/api/, '');
    if (cleanUrl.startsWith('/student/tasks/') && cleanUrl.endsWith('/status')) {
      return this.updateTaskStatus(parseInt(cleanUrl.split('/')[3]), data?.status);
    }
    if (cleanUrl.startsWith('/student/study-plan/') && cleanUrl.endsWith('/status')) {
      return this.updateStudySessionStatus(parseInt(cleanUrl.split('/')[3]), data?.status);
    }
    if (cleanUrl.startsWith('/admin/resources/')) return this.updateResource(parseInt(cleanUrl.split('/')[3]), data);
    if (cleanUrl.startsWith('/admin/users/')) return this.updateAdminUser(parseInt(cleanUrl.split('/')[3]), data);
    if (cleanUrl.startsWith('/admin/system-settings/')) return this.updateSystemSetting(parseInt(cleanUrl.split('/')[3]), data);
    if (cleanUrl.includes('/topics/')) {
      const parts = cleanUrl.split('/');
      return this.updateAdminTopic(parseInt(parts[3]), parseInt(parts[5]), data);
    }
    return successResponse({});
  },

  async delete(url: string): Promise<any> {
    const cleanUrl = url.replace(/^\/api/, '');
    if (cleanUrl.startsWith('/student/subjects/')) return this.deleteSubject(parseInt(cleanUrl.split('/')[3]));
    if (cleanUrl.includes('/topics/')) {
      const parts = cleanUrl.split('/');
      return this.deleteTopic(parseInt(parts[3]), parseInt(parts[5]));
    }
    if (cleanUrl.startsWith('/student/tasks/')) return this.deleteTask(parseInt(cleanUrl.split('/')[3]));
    if (cleanUrl.startsWith('/admin/subjects/')) return this.deleteAdminSubject(parseInt(cleanUrl.split('/')[3]));
    if (cleanUrl.startsWith('/admin/resources/')) return this.deleteResource(parseInt(cleanUrl.split('/')[3]));
    return successResponse({});
  },
};

localApi.init();