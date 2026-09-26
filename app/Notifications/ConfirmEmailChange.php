<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ConfirmEmailChange extends Notification
{
    public function __construct(public string $email, public int $accountId) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute('profile.email.confirm', now()->addHour(), [
            'id' => $this->accountId, 'hash' => hash('sha256', $this->email),
        ]);

        return (new MailMessage)->subject('Confirm your new '.config('app.name').' email')
            ->greeting('Confirm your new email')
            ->line('Confirm this email for account security and password recovery.')
            ->action('Confirm email', $url)
            ->line('This link expires in one hour.')
            ->line('If you did not request this change, ignore this email.');
    }
}
