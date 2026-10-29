<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailQueued extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    /** @var list<int> */
    public array $backoff = [60, 300];

    public function __construct()
    {
        $this->afterCommit();
    }

    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Подтвердите электронную почту'))
            ->greeting(__('Здравствуйте!'))
            ->line(__('Нажмите кнопку ниже, чтобы подтвердить электронную почту вашего аккаунта.'))
            ->action(__('Подтвердить почту'), $url)
            ->line(__('Ссылка действительна :minutes минут.', ['minutes' => config('auth.verification.expire', 60)]))
            ->line(__('Если вы не регистрировались на сайте, просто проигнорируйте это письмо.'));
    }
}
