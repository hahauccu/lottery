<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('danmaku', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            $expireMinutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

            return (new MailMessage)
                ->subject('重設您的密碼')
                ->greeting('您好！')
                ->line('我們收到了您的密碼重設請求，請點擊下方按鈕重設密碼。')
                ->action('重設密碼', $url)
                ->line("此連結將於 {$expireMinutes} 分鐘後失效。")
                ->line('如果您未要求重設密碼，請忽略此信件，無需進行任何操作。')
                ->salutation('感謝您的使用！');
        });
    }
}
