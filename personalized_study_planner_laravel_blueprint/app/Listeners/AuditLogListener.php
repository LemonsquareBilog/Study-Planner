<?php

namespace App\Listeners;

use App\Models\AuditLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\FailedLogin;

class AuditLogListener
{
    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        $eventType = '';
        $description = '';
        $payload = [];

        match (get_class($event)) {
            Login::class => $this->handleLogin($event),
            Logout::class => $this->handleLogout($event),
            Registered::class => $this->handleRegistered($event),
            PasswordReset::class => $this->handlePasswordReset($event),
            FailedLogin::class => $this->handleFailedLogin($event),
            default => null,
        };
    }

    protected function handleLogin(Login $event): void
    {
        AuditLog::log('user.login', 'User logged in', [
            'remember' => $event->remember,
        ], $event->user);
    }

    protected function handleLogout(Logout $event): void
    {
        AuditLog::log('user.logout', 'User logged out', [], $event->user);
    }

    protected function handleRegistered(Registered $event): void
    {
        AuditLog::log('user.registered', 'New user registered', [], $event->user);
    }

    protected function handlePasswordReset(PasswordReset $event): void
    {
        AuditLog::log('user.password_reset', 'User reset password', [], $event->user);
    }

    protected function handleFailedLogin(FailedLogin $event): void
    {
        $user = $event->user;
        AuditLog::log('user.login_failed', 'Failed login attempt', [
            'email' => $event->credentials['email'] ?? 'unknown',
            'user_exists' => $user !== null,
        ], $user);
    }
}