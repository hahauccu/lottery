<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactFormMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $name,
        public string $email,
        public string $subject,
        public string $messageContent,
    ) {}

    public function build(): self
    {
        return $this
            ->subject("【聯絡我們】{$this->subject} - {$this->name}")
            ->replyTo($this->email, $this->name)
            ->view('emails.contact-form');
    }
}
