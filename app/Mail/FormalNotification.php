<?php

namespace App\Mail;

use App\DTO\FormalNotificationData;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FormalNotification extends Mailable
{
    use SerializesModels;

    public function __construct(public FormalNotificationData $notification) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->notification->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.formal-notification',
            with: [
                'notification' => $this->notification,
            ],
        );
    }
}
