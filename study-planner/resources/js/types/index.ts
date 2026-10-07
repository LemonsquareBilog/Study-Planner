export interface User {
  id: number;
  name: string;
  email: string;
  role: 'admin' | 'student' | 'instructor';
  course?: string;
  year_level?: number;
  is_active: boolean;
  created_at?: string;
  study_preference?: StudyPreference;
}

export interface StudyPreference {
  id: number;
  user_id: number;
  max_daily_minutes: number;
  session_duration_minutes: number;
  break_duration_minutes: number;
  preferred_start_time: string;
  preferred_end_time: string;
}

export interface Subject {
  id: number;
  user_id: number;
  name: string;
  description?: string;
  difficulty: number;
  color: string;
  is_active: boolean;
  topics_count?: number;
  tasks_count?: number;
  topics?: Topic[];
  user?: User;
}

export interface Topic {
  id: number;
  subject_id: number;
  name: string;
  description?: string;
  difficulty: number;
  is_active: boolean;
  quizzes_count?: number;
  tasks_count?: number;
  resources?: Resource[];
  subject?: Subject;
}

export interface Task {
  id: number;
  user_id: number;
  subject_id: number;
  topic_id?: number;
  title: string;
  description?: string;
  task_type: string;
  priority: 'urgent' | 'high' | 'medium' | 'low';
  start_date?: string;
  deadline: string;
  estimated_minutes: number;
  status: 'pending' | 'in_progress' | 'completed' | 'cancelled';
  created_at?: string;
  completed_at?: string;
  subject?: Subject;
  topic?: Topic;
}

export interface StudySession {
  id: number;
  user_id: number;
  subject_id: number;
  topic_id?: number;
  task_id?: number;
  scheduled_date: string;
  start_time: string;
  end_time: string;
  duration_minutes: number;
  status: 'scheduled' | 'in_progress' | 'completed' | 'missed';
  started_at?: string;
  completed_at?: string;
  subject?: Subject;
  topic?: Topic;
  task?: Task;
}

export interface Quiz {
  id: number;
  topic_id: number;
  title: string;
  description?: string;
  passing_score: number;
  is_active: boolean;
  topic?: Topic;
  questions_count?: number;
  questions?: Question[];
}

export interface Question {
  id: number;
  quiz_id: number;
  question: string;
  option_a: string;
  option_b: string;
  option_c: string;
  option_d: string;
  question_order: number;
  correct_answer?: string;
}

export interface QuizAttempt {
  id: number;
  user_id: number;
  quiz_id: number;
  score: number;
  total_questions: number;
  percentage: number;
  started_at: string;
  completed_at: string;
  quiz?: Quiz;
  answers?: QuizAnswer[];
}

export interface QuizAnswer {
  id: number;
  quiz_attempt_id: number;
  question_id: number;
  selected_answer: string | null;
  is_correct: boolean;
  question?: Question;
}

export interface SetupStatus {
  has_preferences: boolean;
  subjects_count: number;
  tasks_count: number;
  study_sessions_count: number;
  quiz_attempts_count: number;
  is_fully_onboarded: boolean;
}

export interface AdminDashboardData {
  metrics: {
    total_students: number;
    active_students: number;
    total_resources: number;
    total_subjects: number;
    total_quizzes: number;
    total_quiz_attempts: number;
    average_quiz_score: number;
  };
  recent_users: User[];
  recent_attempts: any[];
}

export interface Resource {
  id: number;
  title: string;
  description?: string;
  resource_type: string;
  url: string;
  difficulty: string;
  is_active: boolean;
  topics?: Topic[];
  subjects?: Subject[];
  recommendation_reason?: 'weak' | 'needs_review' | 'related';
}

export interface Notification {
  id: string;
  user_id: number;
  type: string;
  title: string;
  message: string;
  scheduled_at?: string;
  delivery_status: string;
  sent_at?: string;
  read_at?: string;
  created_at: string;
}

export interface SystemSetting {
  id: number;
  key: string;
  value: string;
  typed_value: string | number | boolean;
  type: string;
  description?: string;
}

export interface ProgressSummary {
  tasks: {
    total: number;
    completed: number;
    completion_rate: number;
  };
  study_sessions: {
    total: number;
    completed: number;
    minutes_completed: number;
  };
  quizzes: {
    attempts: number;
    average_percentage: number;
  };
  generated_at: string;
}

export interface TopicPerformance {
  topic_id: number;
  topic_name: string;
  subject_name?: string;
  avg_percentage: number;
  attempt_count: number;
  best_score: number;
  worst_score: number;
  classification: 'weak' | 'needs_review' | 'strong';
}

export interface ApiResponse<T> {
  data: T;
  message: string;
}

export interface PaginatedResponse<T> {
  current_page: number;
  data: T[];
  first_page_url: string;
  from: number;
  last_page: number;
  last_page_url: string;
  links: { url: string | null; label: string; page: number | null; active: boolean }[];
  next_page_url: string | null;
  path: string;
  per_page: number;
  prev_page_url: string | null;
  to: number;
  total: number;
}