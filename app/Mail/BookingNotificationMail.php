<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public string $title,
        public string $messageBody
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->title} — {$this->booking->reference}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-notification',
        );
    }
}
