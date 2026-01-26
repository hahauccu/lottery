<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegisterAccountPasswordMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $email,
        public string $password,
        public string $organizationName,
        public string $loginUrl
    ) {}

    public function build(): self
    {
        return $this
            ->subject('帳號建立完成與登入密碼')
            ->view('emails.register-account-password')
            ->with([
                'email' => $this->email,
                'password' => $this->password,
                'organizationName' => $this->organizationName,
                'loginUrl' => $this->loginUrl,
            ]);
    }
}
