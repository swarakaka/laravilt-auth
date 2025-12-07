<?php

namespace Laravilt\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

/**
 * Email Verification Notification
 *
 * Sends an email verification link to the user after registration
 * or when requesting a new verification email.
 */
class VerifyEmail extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The verification token.
     */
    public string $token;

    /**
     * The verification URL.
     */
    public ?string $verificationUrl;

    /**
     * The expiration time in minutes.
     */
    public int $expiresIn;

    /**
     * Create a new notification instance.
     *
     * @param  string  $token  The verification token
     * @param  string|null  $verificationUrl  Optional custom verification URL
     * @param  int  $expiresIn  Expiration time in minutes (default: 60)
     */
    public function __construct(string $token, ?string $verificationUrl = null, int $expiresIn = 60)
    {
        $this->token = $token;
        $this->verificationUrl = $verificationUrl;
        $this->expiresIn = $expiresIn;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        $channels = ['mail'];

        // Add database channel if enabled in config
        if (config('laravilt-auth.notifications.database', true)) {
            $channels[] = 'database';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $url = $this->verificationUrl ?? $this->buildVerificationUrl($notifiable);

        return (new MailMessage)
            ->subject(Lang::get('laravilt-auth::notifications.verify_email.subject'))
            ->greeting(Lang::get('laravilt-auth::notifications.verify_email.greeting', [
                'name' => $notifiable->name ?? 'User',
            ]))
            ->line(Lang::get('laravilt-auth::notifications.verify_email.line1'))
            ->action(
                Lang::get('laravilt-auth::notifications.verify_email.action'),
                $url
            )
            ->line(Lang::get('laravilt-auth::notifications.verify_email.line2', [
                'minutes' => $this->expiresIn,
            ]))
            ->line(Lang::get('laravilt-auth::notifications.verify_email.line3'))
            ->salutation(Lang::get('laravilt-auth::notifications.salutation'));
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(mixed $notifiable): array
    {
        return [
            'type' => 'email_verification',
            'message' => Lang::get('laravilt-auth::notifications.verify_email.database_message'),
            'expires_at' => now()->addMinutes($this->expiresIn)->toDateTimeString(),
            'token' => $this->token,
        ];
    }

    /**
     * Build the verification URL for the notifiable.
     */
    protected function buildVerificationUrl(mixed $notifiable): string
    {
        return url(route('laravilt-auth.verify-email', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForVerification(),
        ], false));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => 'email_verification',
            'expires_at' => now()->addMinutes($this->expiresIn)->toDateTimeString(),
        ];
    }
}
