<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorCodeNotification extends Notification
{
    use Queueable;

    private string $code;

    public function __construct(string $code)
    {
        $this->code = $code;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('код подтверждения входа — tennisclub')
            ->greeting('здравствуйте!')
            ->line('для входа в аккаунт используйте этот код подтверждения:')
            ->line($this->code)
            ->line('код действует 10 минут.')
            ->line('если это были не вы, просто проигнорируйте это письмо.');
    }
}