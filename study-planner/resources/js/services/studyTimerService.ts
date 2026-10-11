import { ref, computed, watch } from 'vue';
import { Capacitor } from '@capacitor/core';
import { App } from '@capacitor/app';
import type { StudySession, Task } from '@/types';

// Timer mode types
export type TimerMode = 'study' | 'break';
export type TimerStatus = 'idle' | 'running' | 'paused' | 'completed';

// Timer state for a specific task/session
export interface TimerState {
  id: number;
  totalMs: number;
  remainingMs: number;
  startedAt: number | null;
  status: TimerStatus;
  mode: TimerMode;
  sessionId?: number;
  taskId?: number;
  subjectName?: string;
  taskTitle?: string;
  updatedAt: number;
}

// Background timer tick event
interface TimerTickEvent {
  timerId: number;
  remainingMs: number;
  status: TimerStatus;
}

const STORAGE_KEY = 'study-plan-timer-states-v2';
const BACKGROUND_TICK_INTERVAL = 1000; // 1 second for background updates

class StudyTimerService {
  private timerStates = ref<Record<number, TimerState>>({});
  private timerInterval: number | null = null;
  private backgroundTaskRegistered = false;
  private listeners: Set<(event: TimerTickEvent) => void> = new Set();
  private isInitialized = false;

  // Get all timer states
  get states(): Record<number, TimerState> {
    return this.timerStates.value;
  }

  // Get a specific timer state
  getState(id: number): TimerState | undefined {
    return this.timerStates.value[id];
  }

  // Get timer state for a specific task/session
  getStateForTask(taskId: number, sessionId?: number): TimerState | undefined {
    const key = sessionId ?? taskId;
    return this.timerStates.value[key];
  }

  // Get all active (running) timers
  get activeTimers(): TimerState[] {
    return Object.values(this.timerStates.value).filter(
      state => state.status === 'running'
    );
  }

  // Initialize the service
  async initialize(): Promise<void> {
    if (this.isInitialized) return;

    // Load persisted state
    this.loadPersistedState();

    // Start the main timer interval
    this.startTimerInterval();

    // Register app state listeners for mobile
    if (Capacitor.isNativePlatform()) {
      await this.registerAppStateListeners();
    }

    // Listen for app visibility changes
    document.addEventListener('visibilitychange', this.handleVisibilityChange.bind(this));
    
    this.isInitialized = true;
  }

  // Create or get timer state for a task/session
  getOrCreateTimerState(params: {
    id: number;
    totalMinutes: number;
    sessionId?: number;
    taskId?: number;
    subjectName?: string;
    taskTitle?: string;
  }): TimerState {
    const key = params.sessionId ?? params.id;
    
    if (!this.timerStates.value[key]) {
      this.timerStates.value[key] = {
        id: key,
        totalMs: params.totalMinutes * 60_000,
        remainingMs: params.totalMinutes * 60_000,
        startedAt: null,
        status: 'idle',
        mode: 'study',
        sessionId: params.sessionId,
        taskId: params.taskId,
        subjectName: params.subjectName,
        taskTitle: params.taskTitle,
        updatedAt: Date.now(),
      };
    } else {
      // Ensure totalMs is correct
      const state = this.timerStates.value[key];
      if (state.totalMs <= 0) {
        state.totalMs = params.totalMinutes * 60_000;
        state.remainingMs = state.totalMs;
      }
    }
    
    return this.timerStates.value[key];
  }

  // Start a timer
  startTimer(id: number): void {
    const state = this.timerStates.value[id];
    if (!state || state.status === 'running') return;

    const currentRemaining = this.getRemainingMs(state);
    state.mode = 'study';
    state.status = 'running';
    state.startedAt = Date.now() - (state.totalMs - currentRemaining);
    state.remainingMs = currentRemaining;
    state.updatedAt = Date.now();
    
    this.persistState();
    this.notifyListeners(id);
  }

  // Pause a timer
  pauseTimer(id: number): void {
    const state = this.timerStates.value[id];
    if (!state || state.status !== 'running') return;

    state.remainingMs = this.getRemainingMs(state);
    state.startedAt = null;
    state.status = 'paused';
    state.updatedAt = Date.now();
    
    this.persistState();
    this.notifyListeners(id);
  }

  // Resume a paused timer
  resumeTimer(id: number): void {
    const state = this.timerStates.value[id];
    if (!state || state.status !== 'paused') return;

    const currentRemaining = this.getRemainingMs(state);
    state.status = 'running';
    state.mode = 'study';
    state.startedAt = Date.now() - (state.totalMs - currentRemaining);
    state.remainingMs = currentRemaining;
    state.updatedAt = Date.now();
    
    this.persistState();
    this.notifyListeners(id);
  }

  // Complete a timer
  completeTimer(id: number): void {
    const state = this.timerStates.value[id];
    if (!state || state.status === 'completed') return;

    state.status = 'completed';
    state.startedAt = null;
    state.remainingMs = 0;
    state.updatedAt = Date.now();
    
    this.persistState();
    this.notifyListeners(id);
  }

  // Start a break timer
  startBreak(id: number, breakMinutes: number = 5): void {
    const state = this.timerStates.value[id];
    if (!state) return;

    state.mode = 'break';
    state.totalMs = breakMinutes * 60_000;
    state.remainingMs = state.totalMs;
    state.startedAt = Date.now();
    state.status = 'running';
    state.updatedAt = Date.now();
    
    this.persistState();
    this.notifyListeners(id);
  }

  // Switch timer mode (study/break)
  switchMode(id: number, mode: TimerMode): void {
    const state = this.timerStates.value[id];
    if (!state) return;

    const currentRemaining = this.getRemainingMs(state);
    const wasRunning = state.status === 'running';
    
    state.mode = mode;
    state.remainingMs = currentRemaining;
    state.updatedAt = Date.now();
    
    if (wasRunning) {
      state.startedAt = Date.now() - (state.totalMs - currentRemaining);
    }
    
    this.persistState();
    this.notifyListeners(id);
  }

  // Get remaining milliseconds for a timer state
  getRemainingMs(state: TimerState, now: number = Date.now()): number {
    if (!state) return 0;
    if (state.status === 'running' && state.startedAt) {
      return Math.max(0, state.totalMs - (now - state.startedAt));
    }
    if (state.status === 'completed') {
      return 0;
    }
    return Math.max(0, state.remainingMs);
  }

  // Format countdown for display
  formatCountdown(ms: number): string {
    const totalSeconds = Math.max(0, Math.ceil(ms / 1000));
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;
    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
  }

  // Subscribe to timer updates
  subscribe(listener: (event: TimerTickEvent) => void): () => void {
    this.listeners.add(listener);
    return () => this.listeners.delete(listener);
  }

  // Notify all listeners of a timer update
  private notifyListeners(timerId: number): void {
    const state = this.timerStates.value[timerId];
    if (!state) return;

    const event: TimerTickEvent = {
      timerId,
      remainingMs: this.getRemainingMs(state),
      status: state.status,
    };

    this.listeners.forEach(listener => {
      try {
        listener(event);
      } catch (error) {
        console.warn('Timer listener error:', error);
      }
    });
  }

  // Persist all timer states to localStorage
  private persistState(): void {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(this.timerStates.value));
    } catch (error) {
      console.warn('Failed to save study timer state:', error);
    }
  }

  // Load persisted timer states from localStorage
  private loadPersistedState(): void {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return;
      const parsed = JSON.parse(raw) as Record<number, TimerState>;
      this.timerStates.value = parsed ?? {};
      
      // Reset any running timers to paused state (they can't run while app was closed)
      Object.values(this.timerStates.value).forEach(state => {
        if (state.status === 'running') {
          state.status = 'paused';
          state.remainingMs = Math.max(0, state.remainingMs);
          state.startedAt = null;
        }
      });
    } catch (error) {
      console.warn('Failed to restore saved study timers:', error);
    }
  }

  // Start the main timer interval
  private startTimerInterval(): void {
    this.timerInterval = window.setInterval(() => {
      this.activeTimers.forEach(state => {
        const remaining = this.getRemainingMs(state);
        
        if (remaining <= 0 && state.status === 'running') {
          this.completeTimer(state.id);
          
          // Trigger completion notification
          if (state.taskId) {
            this.dispatchTimerCompleteEvent(state);
          }
        } else {
          // Update remaining time for running timers
          this.notifyListeners(state.id);
        }
      });
    }, 250);
  }

  // Dispatch timer completion event for notifications
  private dispatchTimerCompleteEvent(state: TimerState): void {
    window.dispatchEvent(
      new CustomEvent('study-timer-complete', {
        detail: {
          timerId: state.id,
          taskId: state.taskId,
          sessionId: state.sessionId,
          subjectName: state.subjectName,
          taskTitle: state.taskTitle,
          mode: state.mode,
        },
      })
    );
  }

  // Handle app visibility changes
  private handleVisibilityChange(): void {
    if (document.hidden) {
      // App went to background - pause all running timers
      this.activeTimers.forEach(state => {
        if (state.status === 'running') {
          state.remainingMs = this.getRemainingMs(state);
          state.startedAt = null;
          state.status = 'paused';
          state.updatedAt = Date.now();
        }
      });
      this.persistState();
    } else {
      // App came to foreground - resume paused timers that were running
      Object.values(this.timerStates.value).forEach(state => {
        if (state.status === 'paused' && state.remainingMs > 0) {
          // Optionally auto-resume, or keep paused for user to resume manually
          // For study timers, we'll keep them paused so user can explicitly resume
        }
      });
    }
  }

  // Register app state listeners for Capacitor
  private async registerAppStateListeners(): Promise<void> {
    if (this.backgroundTaskRegistered) return;

    try {
      // Listen for app state changes
      App.addListener('appStateChange', ({ isActive }) => {
        if (!isActive) {
          // App went to background - pause all running timers
          this.handleVisibilityChange();
        }
      });

      // Listen for app exit
      App.addListener('appExit', async () => {
        // Save state before app exits
        this.persistState();
        
        // Schedule local notifications for active timers
        this.activeTimers.forEach(state => {
          if (state.status === 'running') {
            const remainingMs = this.getRemainingMs(state);
            if (remainingMs > 0 && state.taskId) {
              this.scheduleCompletionNotification(state, remainingMs);
            }
          }
        });
      });

      this.backgroundTaskRegistered = true;
    } catch (error) {
      console.warn('App state listener registration failed:', error);
    }
  }

  // Schedule local notification for timer completion
  private async scheduleCompletionNotification(state: TimerState, remainingMs: number): Promise<void> {
    try {
      const { LocalNotifications } = await import('@capacitor/local-notifications');
      
      const fireTime = new Date(Date.now() + remainingMs);
      
      await LocalNotifications.schedule({
        notifications: [{
          title: 'Study Session Complete',
          body: `Your ${state.subjectName || 'study'} session "${state.taskTitle || 'Study'}" has finished.`,
          id: `timer-complete-${state.id}-${Date.now()}`,
          schedule: { at: fireTime },
          extra: { 
            timerId: state.id, 
            taskId: state.taskId, 
            sessionId: state.sessionId,
            type: 'timer_completion' 
          },
          actionTypeId: 'timer-complete',
          sound: 'default',
        }],
      });
    } catch (error) {
      console.warn('Failed to schedule completion notification:', error);
    }
  }

  // Clean up timer state
  removeTimer(id: number): void {
    delete this.timerStates.value[id];
    this.persistState();
  }

  // Clear all timer states
  clearAll(): void {
    this.timerStates.value = {};
    this.persistState();
  }

  // Cleanup
  destroy(): void {
    if (this.timerInterval) {
      window.clearInterval(this.timerInterval);
      this.timerInterval = null;
    }
    document.removeEventListener('visibilitychange', this.handleVisibilityChange.bind(this));
    this.listeners.clear();
    this.isInitialized = false;
  }
}

export const studyTimerService = new StudyTimerService();