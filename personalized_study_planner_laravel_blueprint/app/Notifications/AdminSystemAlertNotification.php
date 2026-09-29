<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminSystemAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $alertType,
        public string $message,
        public array $details = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("System Alert: {$this->alertType}")
            ->greeting("Hello Admin!")
            ->line("A system alert has been triggered:")
            ->line("**Alert Type:** {$this->alertType}")
            ->line("**Message:** {$this->message}");

        if (!empty($this->details)) {
            $message->line("**Details:**");
            foreach ($this->details as $key => $value) {
                $message->line("- {$key}: {$value}");
            }
        }

        $message->line("Timestamp: " . now()->format('l, F j, Y \a\t g:i A'))
            ->line('Please investigate if necessary.');

        return $message;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'admin_system_alert',
            'title' => "System Alert: {$this->alertType}",
            'message' => $this->message,
            'alert_type' => $this->alertType,
            'details' => $this->details,
        ];
    }
}