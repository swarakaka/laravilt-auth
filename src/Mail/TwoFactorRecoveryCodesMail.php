<?php

namespace Laravilt\Auth\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TwoFactorRecoveryCodesMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $recoveryCodes
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Two-Factor Authentication Recovery Codes',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'laravilt-auth::emails.two-factor-recovery-codes',
        );
    }
}
