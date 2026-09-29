<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminUserRegisteredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $newUser,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New User Registered: ' . $this->newUser->name)
            ->greeting("Hello Admin!")
            ->line("A new user has registered on the platform:")
            ->line("**Name:** {$this->newUser->name}")
            ->line("**Email:** {$this->newUser->email}")
            ->line("**Role:** {$this->newUser->role}")
            ->line("**Course:** {$this->newUser->course ?? 'Not specified'}")
            ->line("**Year Level:** {$this->newUser->year_level ?? 'Not specified'}")
            ->action('View User', url("/admin/users/{$this->newUser->id}"))
            ->line('Registered at: ' . $this->newUser->created_at->format('l, F j, Y \a\t g:i A'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'admin_user_registered',
            'title' => 'New User Registered',
            'message' => "{$this->newUser->name} ({$this->newUser->email}) has registered.",
            'user_id' => $this->newUser->id,
            'action_url' => "/admin/users/{$this->newUser->id}",
        ];
    }
}