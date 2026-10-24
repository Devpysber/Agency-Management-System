<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\CompanySetting;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $expire = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        $cs = CompanySetting::first();
        $brandName = ($cs && $cs->business_name) ? $cs->business_name : config('app.name');

        return (new MailMessage)
            ->subject("Password Reset Request - {$brandName}")
            ->greeting("Hello {$notifiable->name},")
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset Password', $resetUrl)
            ->line("This password reset link will expire in {$expire} minutes.")
            ->line('If you did not request a password reset, no further action is required; your account remains secure.')
            ->salutation("Best regards,\n{$brandName} Team");
    }
}
