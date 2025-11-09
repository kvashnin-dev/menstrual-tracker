<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;

class LogEmailVerificationNotification extends Notification
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail']; // Даже если mail = log, это сработает
    }

    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        \Log::info('EMAIL VERIFICATION LINK:', [
            'user_id' => $notifiable->id,
            'email' => $notifiable->email,
            'url' => $verificationUrl,
        ]);

        return (new MailMessage)
            ->subject('Verify Email Address')
            ->line('Click the link below to verify your email.')
            ->action('Verify Email', $verificationUrl)
            ->line('This link expires in 60 minutes.');
    }

    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            ['id' => $notifiable->id, 'hash' => sha1($notifiable->getEmailForVerification())]
        );
    }
}
