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
            ->subject('Код подтверждения входа — TennisClub')
            ->greeting('Здравствуйте!')
            ->line('Для входа в аккаунт используйте этот код подтверждения:')
            ->line($this->code)
            ->line('Код действует 10 минут.')
            ->line('Если это были не вы, просто проигнорируйте это письмо.');
    }
}