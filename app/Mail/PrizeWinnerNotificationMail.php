<?php

namespace App\Mail;

use App\Models\PrizeWinner;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PrizeWinnerNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PrizeWinner $winner
    ) {
        //
    }

    public function envelope(): Envelope
    {
        $prizeName = $this->winner->prize->name;
        $eventName = $this->winner->prize->lotteryEvent->name;

        return new Envelope(
            subject: "恭喜您中獎！{$eventName} - {$prizeName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.prize-winner-notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
