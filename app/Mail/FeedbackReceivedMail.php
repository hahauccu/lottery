<?php

namespace App\Mail;

use App\Models\Feedback;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FeedbackReceivedMail extends Mailable
{
    use SerializesModels;

    public function __construct(public Feedback $feedback) {}

    public function build(): self
    {
        $this->subject('【意見回饋 #'.$this->feedback->id.'】'.Feedback::CATEGORIES[$this->feedback->category]);
        if ($this->feedback->email) {
            $this->replyTo($this->feedback->email);
        }

        return $this->view('emails.feedback-received');
    }
}
