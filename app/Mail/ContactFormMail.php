<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactFormMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public string $name,
        public string $email,
        public string $topic,
        public string $messageContent,
    ) {}

    public function build(): self
    {
        return $this
            ->subject("【聯絡我們】{$this->topic} - {$this->name}")
            ->replyTo($this->email, $this->name)
            ->view('emails.contact-form');
    }
}
