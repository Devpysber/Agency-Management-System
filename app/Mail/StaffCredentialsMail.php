<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $staffName,
        public string $email,
        public string $password,
        public bool $isReset = false,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->isReset
                ? 'Your account password has been reset'
                : 'Your login credentials',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.staff-credentials',
            with: [
                'staffName' => $this->staffName,
                'email' => $this->email,
                'password' => $this->password,
                'isReset' => $this->isReset,
                'loginUrl' => route('login'),
            ],
        );
    }
}
