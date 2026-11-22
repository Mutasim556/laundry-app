<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $otpCode,
        public int $expiresInMinutes = 5
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your FreshGo Laundry Verification Code',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.customer-otp',
        );
    }
}
