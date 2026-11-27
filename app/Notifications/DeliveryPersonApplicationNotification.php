<?php

namespace App\Notifications;

use App\Models\DeliveryPersonApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeliveryPersonApplicationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public DeliveryPersonApplication $application,
        public string $eventType,
        public ?string $customMessage = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $app = $this->application;
        $statusUrl = route('delivery-person.application-status');
        $loginUrl = route('delivery-person.login');

        $mail = (new MailMessage)
            ->greeting('Hello ' . $app->full_name . ',');

        return match ($this->eventType) {
            'submitted' => $mail
                ->subject('Fresh Go Delivery Person Application Received (' . $app->reference . ')')
                ->line('Thank you for applying to join Fresh Go as a Delivery Person.')
                ->line('Your application reference is: ' . $app->reference)
                ->line('Our team will review your details and notify you when your status changes.')
                ->action('Track Your Application', $statusUrl),

            'under_review' => $mail
                ->subject('Your Fresh Go Application Is Under Review (' . $app->reference . ')')
                ->line('Your Delivery Person application (' . $app->reference . ') is currently being reviewed by our team.')
                ->action('Track Your Application', $statusUrl),

            'additional_info_required' => $mail
                ->subject('Action Required: Additional Information Needed (' . $app->reference . ')')
                ->line('Our review team requested additional information for your Delivery Person application (' . $app->reference . ').')
                ->line($this->customMessage ?: ($app->additional_info_requested ?? 'Please log in to the application tracker to submit the requested details.'))
                ->action('Submit Requested Information', $statusUrl),

            'approved' => $mail
                ->subject('Congratulations! Your Fresh Go Delivery Person Application Is Approved')
                ->line('Your Delivery Person application (' . $app->reference . ') has been approved.')
                ->line('Your Delivery Person Portal account is now active. You can sign in using your registered email and password.')
                ->action('Sign In to Delivery Person Portal', $loginUrl),

            'rejected' => $mail
                ->subject('Update on Your Fresh Go Delivery Person Application (' . $app->reference . ')')
                ->line('Thank you for your interest in Fresh Go. Unfortunately, we are unable to approve your application (' . $app->reference . ') at this time.')
                ->when(! empty($this->customMessage), fn (MailMessage $m) => $m->line($this->customMessage))
                ->action('Check Application Status', $statusUrl),

            default => $mail
                ->subject('Fresh Go Application Update (' . $app->reference . ')')
                ->line('There is an update on your Delivery Person application (' . $app->reference . ').')
                ->action('Track Your Application', $statusUrl),
        };
    }
}
