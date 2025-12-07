<?php

namespace Laravilt\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

/**
 * Password Reset Notification
 *
 * Sends a password reset link to the user when they request
 * to reset their password.
 */
class ResetPassword extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The password reset token.
     */
    public string $token;

    /**
     * The password reset URL.
     */
    public ?string $resetUrl;

    /**
     * The expiration time in minutes.
     */
    public int $expiresIn;

    /**
     * The IP address of the requester.
     */
    public ?string $ipAddress;

    /**
     * The user agent of the requester.
     */
    public ?string $userAgent;

    /**
     * Create a new notification instance.
     *
     * @param  string  $token  The password reset token
     * @param  string|null  $resetUrl  Optional custom reset URL
     * @param  int  $expiresIn  Expiration time in minutes (default: 60)
     * @param  string|null  $ipAddress  The IP address of the requester
     * @param  string|null  $userAgent  The user agent of the requester
     */
    public function __construct(
        string $token,
        ?string $resetUrl = null,
        int $expiresIn = 60,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ) {
        $this->token = $token;
        $this->resetUrl = $resetUrl;
        $this->expiresIn = $expiresIn;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $url = $this->resetUrl ?? $this->buildResetUrl($notifiable);

        $mail = (new MailMessage)
            ->subject(Lang::get('laravilt-auth::notifications.reset_password.subject'))
            ->greeting(Lang::get('laravilt-auth::notifications.reset_password.greeting', [
                'name' => $notifiable->name ?? 'User',
            ]))
            ->line(Lang::get('laravilt-auth::notifications.reset_password.line1'))
            ->action(
                Lang::get('laravilt-auth::notifications.reset_password.action'),
                $url
            )
            ->line(Lang::get('laravilt-auth::notifications.reset_password.line2', [
                'minutes' => $this->expiresIn,
            ]))
            ->line(Lang::get('laravilt-auth::notifications.reset_password.line3'));

        // Add security information if available
        if ($this->ipAddress || $this->userAgent) {
            $mail->line(Lang::get('laravilt-auth::notifications.reset_password.security_info'));

            if ($this->ipAddress) {
                $mail->line(Lang::get('laravilt-auth::notifications.reset_password.ip_address', [
                    'ip' => $this->ipAddress,
                ]));
            }

            if ($this->userAgent) {
                $mail->line(Lang::get('laravilt-auth::notifications.reset_password.user_agent', [
                    'agent' => $this->userAgent,
                ]));
            }
        }

        return $mail->salutation(Lang::get('laravilt-auth::notifications.salutation'));
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(mixed $notifiable): array
    {
        return [
            'type' => 'password_reset',
            'message' => Lang::get('laravilt-auth::notifications.reset_password.database_message'),
            'expires_at' => now()->addMinutes($this->expiresIn)->toDateTimeString(),
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'requested_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Build the password reset URL for the notifiable.
     */
    protected function buildResetUrl(mixed $notifiable): string
    {
        // Get the current panel
        $panel = \Laravilt\Panel\Facades\Panel::getCurrent();

        // Build panel-specific reset URL
        if ($panel) {
            $path = $panel->getPath().'/reset-password/'.$this->token;
            $email = $notifiable->getEmailForPasswordReset();

            return url($path.'?email='.urlencode($email));
        }

        // Fallback to default route if panel not found
        return url('/reset-password/'.$this->token.'?email='.urlencode($notifiable->getEmailForPasswordReset()));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => 'password_reset',
            'expires_at' => now()->addMinutes($this->expiresIn)->toDateTimeString(),
            'ip_address' => $this->ipAddress,
        ];
    }
}
