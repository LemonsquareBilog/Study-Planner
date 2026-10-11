import type { Notification as LocalNotification } from '@capacitor/local-notifications';
import { LocalNotifications } from '@capacitor/local-notifications';
import { Capacitor } from '@capacitor/core';
import type { Task, StudySession } from '@/types';

interface NotificationOptions {
  title: string;
  body: string;
  id?: number;
  schedule?: { at: Date; repeats?: boolean; every?: 'year' | 'month' | 'two-weeks' | 'week' | 'day' | 'hour' | 'minute' };
  actionTypeId?: string;
  extra?: Record<string, any>;
  sound?: string;
  attachments?: Array<{ id: string; url: string; hideThumbnail?: boolean }>;
}

class NotificationService {
  private initialized = false;
  private permissionGranted = false;
  private pendingNotifications: Map<string, LocalNotification> = new Map();

  async initialize(): Promise<void> {
    if (this.initialized) return;
    
    try {
      // Request permissions
      const perm = await LocalNotifications.requestPermissions();
      this.permissionGranted = perm.display === 'granted';
      
      // Set up notification channels for Android
      if (Capacitor.getPlatform() === 'android') {
        await LocalNotifications.createChannel({
          id: 'task-reminders',
          name: 'Task Reminders',
          description: 'Reminders for upcoming tasks and deadlines',
          importance: 4,
          visibility: 1,
          sound: 'default',
          vibration: true,
        });
        
        await LocalNotifications.createChannel({
          id: 'study-timer',
          name: 'Study Timer',
          description: 'Study session timer alerts and completions',
          importance: 4,
          visibility: 1,
          sound: 'default',
          vibration: true,
        });
        
        await LocalNotifications.createChannel({
          id: 'study-completion',
          name: 'Study Completion',
          description: 'Notifications when study sessions complete',
          importance: 5,
          visibility: 1,
          sound: 'default',
          vibration: true,
        });
      }

      // Set up action types for interactive notifications
      await LocalNotifications.setActionTypes({
        types: [
          {
            id: 'task-actions',
            actions: [
              { id: 'view', title: 'View Task', requiresAuthentication: true },
              { id: 'snooze', title: 'Snooze 15min', requiresAuthentication: false },
            ],
          },
          {
            id: 'timer-complete',
            actions: [
              { id: 'view-session', title: 'View Session', requiresAuthentication: true },
              { id: 'take-break', title: 'Take Break', requiresAuthentication: false },
            ],
          },
        ],
      });

      // Handle notification clicks
      LocalNotifications.addListener('localNotificationActionPerformed', (action) => {
        this.handleNotificationAction(action);
      });

      this.initialized = true;
    } catch (error) {
      console.warn('Notification service initialization failed:', error);
    }
  }

  private handleNotificationAction(action: any): void {
    const { notification, actionId } = action;
    const extra = notification.extra || {};

    switch (actionId) {
      case 'view':
      case 'view-task':
        if (extra.taskId) {
          window.location.href = `/tasks/${extra.taskId}`;
        }
        break;
      case 'snooze':
        if (extra.taskId) {
          this.snoozeTaskReminder(extra.taskId, 15);
        }
        break;
      case 'view-session':
        if (extra.sessionId) {
          window.location.href = `/study-plan?session=${extra.sessionId}`;
        }
        break;
      case 'take-break':
        // Handle break action
        break;
    }
  }

  async scheduleTaskReminder(task: Task): Promise<void> {
    if (!this.permissionGranted) return;

    const deadline = new Date(task.deadline);
    const now = new Date();
    
    // Schedule reminders at different intervals
    const reminders = [
      { hours: 24, label: '24 hours' },
      { hours: 2, label: '2 hours' },
      { hours: 0.5, label: '30 minutes' },
    ];

    for (const reminder of reminders) {
      const reminderTime = new Date(deadline.getTime() - reminder.hours * 60 * 60 * 1000);
      
      if (reminderTime > now) {
        await this.scheduleNotification({
          title: `Upcoming Deadline: ${task.title}`,
          body: `${task.subject?.name || 'Task'} · Due ${deadline.toLocaleString()} (${reminder.label} left)`,
          id: `task-${task.id}-${reminder.hours}h`,
          schedule: { at: reminderTime },
          extra: { taskId: task.id, type: 'task_reminder' },
          actionTypeId: 'task-actions',
        });
      }
    }
  }

  async cancelTaskReminders(taskId: number): Promise<void> {
    const ids = [
      `task-${taskId}-24h`,
      `task-${taskId}-2h`,
      `task-${taskId}-0.5h`,
    ].map(id => ({ id }));

    await LocalNotifications.cancel({ notifications: ids });
  }

  async scheduleStudySessionReminder(session: StudySession): Promise<void> {
    if (!this.permissionGranted) return;

    const sessionDate = new Date(`${session.scheduled_date}T${session.start_time}`);
    const now = new Date();
    
    // 30 minutes before
    const reminderTime = new Date(sessionDate.getTime() - 30 * 60 * 1000);
    
    if (reminderTime > now) {
      await this.scheduleNotification({
        title: 'Study Session Starting Soon',
        body: `${session.subject?.name || 'Study session'} · ${session.topic?.name || 'General'} at ${session.start_time}`,
        id: `session-${session.id}-reminder`,
        schedule: { at: reminderTime },
        extra: { sessionId: session.id, type: 'study_session_reminder' },
        actionTypeId: 'timer-complete',
      });
    }
  }

  async scheduleTimerCompletionNotification(
    taskId: number,
    taskTitle: string,
    subjectName: string,
    delayMs: number
  ): Promise<void> {
    if (!this.permissionGranted) return;

    const fireTime = new Date(Date.now() + delayMs);
    
    await this.scheduleNotification({
      title: 'Study Session Complete',
      body: `Your ${subjectName} study session "${taskTitle}" has finished.`,
      id: `timer-complete-${taskId}-${Date.now()}`,
      schedule: { at: fireTime },
      extra: { taskId, type: 'timer_completion' },
      actionTypeId: 'timer-complete',
      sound: 'default',
    });
  }

  async scheduleOngoingTimerNotification(
    taskId: number,
    taskTitle: string,
    subjectName: string,
    remainingMinutes: number
  ): Promise<void> {
    if (!this.permissionGranted) return;

    await this.scheduleNotification({
      title: 'Study Session in Progress',
      body: `${subjectName} - ${taskTitle} · ${remainingMinutes} minutes remaining`,
      id: `ongoing-timer-${taskId}`,
      extra: { taskId, type: 'ongoing_timer' },
      actionTypeId: 'timer-complete',
      // Ongoing notifications don't have a schedule - they're shown immediately
    });
  }

  async cancelOngoingTimerNotification(taskId: number): Promise<void> {
    await LocalNotifications.cancel({ 
      notifications: [{ id: `ongoing-timer-${taskId}` }] 
    });
  }

  async showTaskCompletionNotification(task: Task): Promise<void> {
    if (!this.permissionGranted) return;

    await this.scheduleNotification({
      title: 'Task Completed! 🎉',
      body: `Great job! You completed "${task.title}".`,
      id: `task-complete-${task.id}-${Date.now()}`,
      extra: { taskId: task.id, type: 'task_completion' },
      actionTypeId: 'task-actions',
    });
  }

  async showStudyStreakNotification(days: number): Promise<void> {
    if (!this.permissionGranted) return;

    await this.scheduleNotification({
      title: 'Study Streak! 🔥',
      body: `Amazing! You've studied for ${days} consecutive days.`,
      id: `streak-${Date.now()}`,
      extra: { type: 'streak', days },
    });
  }

  private async scheduleNotification(options: NotificationOptions): Promise<void> {
    if (!this.permissionGranted) return;

    try {
      const notification: LocalNotification = {
        title: options.title,
        body: options.body,
        id: options.id ? parseInt(options.id.replace(/\D/g, '')) || Date.now() : Date.now(),
        schedule: options.schedule,
        actionTypeId: options.actionTypeId,
        extra: options.extra,
        sound: options.sound || 'default',
        attachments: options.attachments,
        smallIcon: 'ic_stat_notification',
        iconColor: '#B33F62',
      };

      await LocalNotifications.schedule({ notifications: [notification] });
      this.pendingNotifications.set(options.id || String(notification.id), notification);
    } catch (error) {
      console.warn('Failed to schedule notification:', error);
    }
  }

  async snoozeTaskReminder(taskId: number, minutes: number): Promise<void> {
    const task = await this.getTaskById(taskId);
    if (!task) return;

    const fireTime = new Date(Date.now() + minutes * 60 * 1000);
    
    await this.scheduleNotification({
      title: `Snoozed: ${task.title}`,
      body: `Reminder snoozed for ${minutes} minutes.`,
      id: `task-${taskId}-snooze-${Date.now()}`,
      schedule: { at: fireTime },
      extra: { taskId, type: 'task_reminder', snoozed: true },
      actionTypeId: 'task-actions',
    });
  }

  async cancelAllNotifications(): Promise<void> {
    await LocalNotifications.cancelAll();
    this.pendingNotifications.clear();
  }

  async getPendingNotifications(): Promise<LocalNotification[]> {
    const result = await LocalNotifications.getPending();
    return result.notifications;
  }

  // Helper to get task by ID (would need to be implemented based on your store)
  private async getTaskById(taskId: number): Promise<any> {
    // This would typically come from your store or API
    return null;
  }

  async requestPermissions(): Promise<boolean> {
    try {
      const perm = await LocalNotifications.requestPermissions();
      this.permissionGranted = perm.display === 'granted';
      return this.permissionGranted;
    } catch {
      return false;
    }
  }

  isPermissionGranted(): boolean {
    return this.permissionGranted;
  }
}

export const notificationService = new NotificationService();