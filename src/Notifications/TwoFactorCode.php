<?php

namespace Laravilt\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

/**
 * Two-Factor Authentication Code Notification
 *
 * Sends a 2FA verification code to the user via email or SMS
 * when they attempt to login with 2FA enabled.
 */
class TwoFactorCode extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The 2FA verification code.
     */
    public string $code;

    /**
     * The expiration time in minutes.
     */
    public int $expiresIn;

    /**
     * The delivery method (email or sms).
     */
    public string $method;

    /**
     * The IP address of the login attempt.
     */
    public ?string $ipAddress;

    /**
     * The device information.
     */
    public ?string $device;

    /**
     * The location information.
     */
    public ?string $location;

    /**
     * Create a new notification instance.
     *
     * @param  string  $code  The 2FA verification code
     * @param  string  $method  The delivery method (email or sms)
     * @param  int  $expiresIn  Expiration time in minutes (default: 10)
     * @param  string|null  $ipAddress  The IP address of the login attempt
     * @param  string|null  $device  The device information
     * @param  string|null  $location  The location information
     */
    public function __construct(
        string $code,
        string $method = 'email',
        int $expiresIn = 10,
        ?string $ipAddress = null,
        ?string $device = null,
        ?string $location = null
    ) {
        $this->code = $code;
        $this->method = $method;
        $this->expiresIn = $expiresIn;
        $this->ipAddress = $ipAddress;
        $this->device = $device;
        $this->location = $location;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        $channels = [];

        // Add channels based on method
        if ($this->method === 'email') {
            $channels[] = 'mail';
        }

        if ($this->method === 'sms') {
            $channels[] = 'sms';
        }

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
        $mail = (new MailMessage)
            ->subject(Lang::get('laravilt-auth::notifications.two_factor_code.subject'))
            ->greeting(Lang::get('laravilt-auth::notifications.two_factor_code.greeting', [
                'name' => $notifiable->name ?? 'User',
            ]))
            ->line(Lang::get('laravilt-auth::notifications.two_factor_code.line1'))
            ->line('') // Empty line for spacing
            ->line('**'.$this->code.'**') // Bold code
            ->line('') // Empty line for spacing
            ->line(Lang::get('laravilt-auth::notifications.two_factor_code.line2', [
                'minutes' => $this->expiresIn,
            ]))
            ->line(Lang::get('laravilt-auth::notifications.two_factor_code.line3'));

        // Add security information
        if ($this->ipAddress || $this->device || $this->location) {
            $mail->line('') // Empty line for spacing
                ->line(Lang::get('laravilt-auth::notifications.two_factor_code.security_header'));

            if ($this->ipAddress) {
                $mail->line(Lang::get('laravilt-auth::notifications.two_factor_code.ip_address', [
                    'ip' => $this->ipAddress,
                ]));
            }

            if ($this->device) {
                $mail->line(Lang::get('laravilt-auth::notifications.two_factor_code.device', [
                    'device' => $this->device,
                ]));
            }

            if ($this->location) {
                $mail->line(Lang::get('laravilt-auth::notifications.two_factor_code.location', [
                    'location' => $this->location,
                ]));
            }

            $mail->line(Lang::get('laravilt-auth::notifications.two_factor_code.security_warning'));
        }

        return $mail->salutation(Lang::get('laravilt-auth::notifications.salutation'));
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(mixed $notifiable): string
    {
        $message = Lang::get('laravilt-auth::notifications.two_factor_code.sms_message', [
            'code' => $this->code,
            'minutes' => $this->expiresIn,
        ]);

        return $message;
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(mixed $notifiable): array
    {
        return [
            'type' => 'two_factor_code',
            'message' => Lang::get('laravilt-auth::notifications.two_factor_code.database_message'),
            'method' => $this->method,
            'expires_at' => now()->addMinutes($this->expiresIn)->toDateTimeString(),
            'ip_address' => $this->ipAddress,
            'device' => $this->device,
            'location' => $this->location,
            'requested_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => 'two_factor_code',
            'method' => $this->method,
            'expires_at' => now()->addMinutes($this->expiresIn)->toDateTimeString(),
            'ip_address' => $this->ipAddress,
        ];
    }
}
