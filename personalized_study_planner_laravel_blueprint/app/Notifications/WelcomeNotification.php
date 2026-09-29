<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $userName,
        public string $loginUrl,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to Personalized Study Planner!')
            ->greeting("Hello {$this->userName}!")
            ->line('Welcome to Personalized Study Planner! Your account has been created successfully.')
            ->line('Get started by setting up your study preferences and creating your first subjects.')
            ->action('Get Started', $this->loginUrl)
            ->line('If you have any questions, feel free to contact our support team.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'welcome',
            'title' => 'Welcome to Personalized Study Planner!',
            'message' => 'Your account has been created successfully. Get started by setting up your study preferences.',
            'action_url' => $this->loginUrl,
        ];
    }
}