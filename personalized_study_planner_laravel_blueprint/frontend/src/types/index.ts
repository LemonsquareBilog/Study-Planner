// User types
export interface User {
  id: number
  name: string
  email: string
  role: 'student' | 'admin'
  course?: string | null
  year_level?: number | null
  is_active: boolean
  email_verified_at?: string | null
  two_factor_confirmed_at?: string | null
  created_at: string
  updated_at: string
  study_preference?: StudyPreference | null
}

export interface StudyPreference {
  id: number
  user_id: number
  max_daily_minutes: number
  session_duration_minutes: number
  break_duration_minutes: number
  preferred_start_time: string
  preferred_end_time: string
  created_at: string
  updated_at: string
}

// Auth types
export interface LoginCredentials {
  email: string
  password: string
  two_factor_code?: string
  remember?: boolean
}

export interface RegisterData {
  name: string
  email: string
  password: string
  password_confirmation: string
  course?: string
  year_level?: number
}

export interface PasswordReset {
  token: string
  email: string
  password: string
  password_confirmation: string
}

export interface TwoFactorData {
  code: string
}

export interface Enable2FAResponse {
  secret: string
  qr_code_svg: string
  recovery_codes: string[]
}

// Subject types
export interface Subject {
  id: number
  user_id: number
  name: string
  description?: string | null
  difficulty: number
  color: string
  color_hex: string
  is_active: boolean
  topics_count: number
  tasks_count: number
  created_at: string
  updated_at: string
  topics?: Topic[]
}

export interface SubjectFormData {
  name: string
  description?: string
  difficulty: number
  color: string
  is_active?: boolean
}

// Topic types
export interface Topic {
  id: number
  subject_id: number
  name: string
  description?: string | null
  difficulty: number
  is_active: boolean
  quizzes_count: number
  tasks_count: number
  created_at: string
  updated_at: string
  quizzes?: Quiz[]
  resources?: Resource[]
}

export interface TopicFormData {
  name: string
  description?: string
  difficulty: number
  is_active?: boolean
}

// Task types
export type TaskPriority = 'low' | 'medium' | 'high' | 'urgent'
export type TaskStatus = 'pending' | 'in_progress' | 'completed' | 'cancelled'
export type TaskType = 'assignment' | 'project' | 'exam' | 'quiz' | 'reading' | 'review' | 'other'

export interface Task {
  id: number
  user_id: number
  subject_id: number
  topic_id?: number | null
  title: string
  description?: string | null
  task_type: TaskType
  priority: TaskPriority
  status: TaskStatus
  deadline: string
  estimated_minutes: number
  completed_at?: string | null
  created_at: string
  updated_at: string
  subject?: Subject
  topic?: Topic
  study_sessions?: StudySession[]
}

export interface TaskFormData {
  subject_id: number
  topic_id?: number
  title: string
  description?: string
  task_type: TaskType
  priority: TaskPriority
  deadline: string
  estimated_minutes: number
}

export interface TaskStatusUpdate {
  status: TaskStatus
}

// Study Plan types
export type SessionStatus = 'scheduled' | 'in_progress' | 'completed' | 'skipped'

export interface StudySession {
  id: number
  user_id: number
  subject_id: number
  topic_id?: number | null
  task_id?: number | null
  scheduled_date: string
  start_time: string
  end_time: string
  duration_minutes: number
  status: SessionStatus
  started_at?: string | null
  completed_at?: string | null
  created_at: string
  updated_at: string
  subject?: Subject
  topic?: Topic
  task?: Task
}

export interface StudySessionFormData {
  subject_id: number
  topic_id?: number
  task_id?: number
  scheduled_date: string
  start_time: string
  end_time: string
  duration_minutes: number
  status?: SessionStatus
}

export interface GenerateStudyPlanRequest {
  from: string
  to: string
}

export interface GenerateStudyPlanResponse {
  created_sessions: number
  sessions: StudySession[]
  reason?: string
}

export interface OnboardingStatus {
  steps: {
    study_preferences: boolean
    subjects: boolean
    tasks: boolean
    study_plan: boolean
  }
  completed_steps: number
  total_steps: number
  completion_percentage: number
  next_action: string | null
  is_complete: boolean
  counts: {
    subjects: number
    tasks: number
    study_sessions: number
  }
}

// Quiz types
export interface Quiz {
  id: number
  topic_id: number
  title: string
  description?: string | null
  is_active: boolean
  total_questions: number
  topic?: Topic
  attempt_count?: number
  last_attempt?: QuizAttempt | null
}

export interface Question {
  id: number
  quiz_id: number
  question_text: string
  options: Record<string, string>
}

export interface QuizAttempt {
  id: number
  user_id: number
  quiz_id: number
  status: 'draft' | 'in_progress' | 'completed'
  score?: number | null
  total_questions?: number | null
  percentage?: number | null
  started_at?: string | null
  completed_at?: string | null
  created_at: string
  updated_at: string
  quiz?: Quiz
  answers?: QuizAnswer[]
}

export interface QuizAnswer {
  id: number
  quiz_attempt_id: number
  question_id: number
  selected_answer?: string | null
  is_correct: boolean
  correct_answer?: string | null
  question?: Question
}

export interface QuizAttemptFormData {
  answers: Record<number, string>
  started_at: string
}

// Recommendation types
export interface Resource {
  id: number
  title: string
  description?: string | null
  resource_type: string
  url?: string | null
  difficulty: number
  is_active: boolean
  subjects?: Subject[]
  topics?: Topic[]
  recommendation_reason?: string
}

export interface RecommendationView {
  id: number
  user_id: number
  resource_id: number
  topic_id?: number | null
  viewed_at: string
}

export interface RecommendationResponse {
  data: Resource[]
  reason?: string | null
  message: string
}

// Progress types
export interface ProgressSummary {
  tasks: {
    total: number
    completed: number
    in_progress: number
    pending: number
    completion_rate: number
  }
  study_minutes: {
    total: number
    this_week: number
    this_month: number
  }
  quizzes: {
    total_attempts: number
    average_score: number
    best_score: number
  }
  streak: {
    current: number
    longest: number
  }
}

export interface TopicPerformance {
  topic_id: number
  topic_name: string
  avg_percentage: number
  attempt_count: number
  best_score: number
  worst_score: number
  classification: 'weak' | 'needs_review' | 'strong'
}

export interface ProgressReport {
  summary: ProgressSummary
  topic_performance: TopicPerformance[]
}

// Notification types
export interface Notification {
  id: string
  user_id: number
  type: string
  title: string
  message: string
  scheduled_at?: string | null
  read_at?: string | null
  sent_at?: string | null
  delivery_status: string
  created_at: string
  updated_at: string
}

export interface NotificationCount {
  unread: number
  total: number
}

// Admin types
export interface AdminDashboardData {
  users: {
    total: number
    students: number
    admins: number
    active: number
    inactive: number
  }
  resources: {
    total: number
    active: number
  }
  subjects: {
    total: number
  }
  tasks: {
    total: number
    completed: number
    pending: number
    completion_rate: number
  }
  quizzes: {
    total_attempts: number
    average_score: number
  }
  recent_registrations: Array<{
    id: number
    name: string
    email: string
    created_at: string
  }>
}

export interface AdminAnalyticsData {
  users_over_time: Array<{ date: string; count: number }>
  resources_by_type: Array<{ type: string; count: number }>
  quiz_performance: Array<{ topic: string; avg_score: number }>
  study_hours: Array<{ date: string; minutes: number }>
}

export interface SystemSetting {
  id: number
  key: string
  value: string
  typed_value: string | number | boolean | object | null
  type: string
  label?: string | null
  group?: string | null
  sort_order: number
  validation_rules?: Record<string, unknown> | null
  impact_description?: string | null
  created_at: string
  updated_at: string
}

// API Response types
export interface ApiResponse<T> {
  data: T
  message: string
}

export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  per_page: number
  total: number
  last_page: number
  from: number | null
  to: number | null
  has_more_pages: boolean
}

export interface ApiError {
  error: {
    code: string
    message: string
    details?: Record<string, string[]>
  }
}

// Form validation types
export interface ValidationError {
  field: string
  message: string
}

// File upload types
export interface FileUploadResponse {
  path: string
  url: string
  filename: string
  original_name: string
  mime_type: string
  size: number
  extension: string
}

// Pusher types
export interface PusherChannel {
  channel: string
  event: string
  data: unknown
}

// WebSocket types
export interface WebSocketEvent {
  event: string
  channel: string
  data: unknown
}

// Date range types
export interface DateRange {
  from: string
  to: string
}

// Export types
export interface ExportOptions {
  format: 'csv' | 'json' | 'pdf'
  filters?: Record<string, unknown>
}