<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetConfirmation extends Notification
{
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Your password was changed'))
            ->line(__('The password for your account was just changed.'))
            ->line(__('If you did not make this change, contact support immediately.'));
    }
}
