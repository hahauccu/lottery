<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeSetPasswordNotification extends Notification
{
    public function __construct(
        private string $token,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $expireMinutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->subject('歡迎加入！請設定您的登入密碼')
            ->greeting('您好！')
            ->line('您的帳號已成功建立，請點擊下方按鈕設定您的登入密碼。')
            ->action('設定密碼', $url)
            ->line("此連結將於 {$expireMinutes} 分鐘後失效。")
            ->salutation('感謝您的使用！');
    }
}
