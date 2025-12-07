<?php

namespace Laravilt\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

/**
 * Login Alert Notification
 *
 * Sends a notification to the user when a successful login
 * occurs from a new device or location, or when suspicious
 * activity is detected.
 */
class LoginNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The IP address of the login.
     */
    public string $ipAddress;

    /**
     * The device information.
     */
    public ?string $device;

    /**
     * The browser information.
     */
    public ?string $browser;

    /**
     * The location information.
     */
    public ?string $location;

    /**
     * The timestamp of the login.
     */
    public string $loginAt;

    /**
     * Whether this is a suspicious login.
     */
    public bool $suspicious;

    /**
     * The login method used (email, social, etc.).
     */
    public ?string $method;

    /**
     * Create a new notification instance.
     *
     * @param  string  $ipAddress  The IP address of the login
     * @param  string|null  $device  The device information
     * @param  string|null  $browser  The browser information
     * @param  string|null  $location  The location information
     * @param  string|null  $loginAt  The timestamp of the login
     * @param  bool  $suspicious  Whether this is a suspicious login
     * @param  string|null  $method  The login method used
     */
    public function __construct(
        string $ipAddress,
        ?string $device = null,
        ?string $browser = null,
        ?string $location = null,
        ?string $loginAt = null,
        bool $suspicious = false,
        ?string $method = null
    ) {
        $this->ipAddress = $ipAddress;
        $this->device = $device;
        $this->browser = $browser;
        $this->location = $location;
        $this->loginAt = $loginAt ?? now()->toDateTimeString();
        $this->suspicious = $suspicious;
        $this->method = $method;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        $channels = [];

        // Always add database channel
        if (config('laravilt-auth.notifications.database', true)) {
            $channels[] = 'database';
        }

        // Add mail channel if enabled or if suspicious
        if ($this->suspicious || config('laravilt-auth.notifications.login_alerts', false)) {
            $channels[] = 'mail';
        }

        // Add SMS channel for suspicious logins if enabled
        if ($this->suspicious && config('laravilt-auth.notifications.sms_suspicious_login', false)) {
            $channels[] = 'sms';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $subjectKey = $this->suspicious
            ? 'laravilt-auth::notifications.login_notification.suspicious_subject'
            : 'laravilt-auth::notifications.login_notification.subject';

        $mail = (new MailMessage)
            ->subject(Lang::get($subjectKey))
            ->greeting(Lang::get('laravilt-auth::notifications.login_notification.greeting', [
                'name' => $notifiable->name ?? 'User',
            ]));

        if ($this->suspicious) {
            $mail->line(Lang::get('laravilt-auth::notifications.login_notification.suspicious_line'))
                ->line('') // Empty line for spacing
                ->line(Lang::get('laravilt-auth::notifications.login_notification.suspicious_warning'));
        } else {
            $mail->line(Lang::get('laravilt-auth::notifications.login_notification.line1'));
        }

        // Add login details
        $mail->line('') // Empty line for spacing
            ->line(Lang::get('laravilt-auth::notifications.login_notification.details_header'));

        $mail->line(Lang::get('laravilt-auth::notifications.login_notification.time', [
            'time' => $this->loginAt,
        ]));

        $mail->line(Lang::get('laravilt-auth::notifications.login_notification.ip_address', [
            'ip' => $this->ipAddress,
        ]));

        if ($this->device) {
            $mail->line(Lang::get('laravilt-auth::notifications.login_notification.device', [
                'device' => $this->device,
            ]));
        }

        if ($this->browser) {
            $mail->line(Lang::get('laravilt-auth::notifications.login_notification.browser', [
                'browser' => $this->browser,
            ]));
        }

        if ($this->location) {
            $mail->line(Lang::get('laravilt-auth::notifications.login_notification.location', [
                'location' => $this->location,
            ]));
        }

        if ($this->method) {
            $mail->line(Lang::get('laravilt-auth::notifications.login_notification.method', [
                'method' => $this->method,
            ]));
        }

        // Add security instructions
        $mail->line('') // Empty line for spacing
            ->line(Lang::get('laravilt-auth::notifications.login_notification.security_line'));

        if ($this->suspicious) {
            $mail->action(
                Lang::get('laravilt-auth::notifications.login_notification.secure_action'),
                url(route('laravilt-auth.security', [], false))
            );
        }

        return $mail->salutation(Lang::get('laravilt-auth::notifications.salutation'));
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(mixed $notifiable): string
    {
        return Lang::get('laravilt-auth::notifications.login_notification.sms_message', [
            'ip' => $this->ipAddress,
            'location' => $this->location ?? 'unknown location',
            'time' => $this->loginAt,
        ]);
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(mixed $notifiable): array
    {
        $messageKey = $this->suspicious
            ? 'laravilt-auth::notifications.login_notification.suspicious_database_message'
            : 'laravilt-auth::notifications.login_notification.database_message';

        return [
            'type' => 'login_notification',
            'message' => Lang::get($messageKey),
            'suspicious' => $this->suspicious,
            'ip_address' => $this->ipAddress,
            'device' => $this->device,
            'browser' => $this->browser,
            'location' => $this->location,
            'method' => $this->method,
            'logged_in_at' => $this->loginAt,
            'created_at' => now()->toDateTimeString(),
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
            'type' => 'login_notification',
            'suspicious' => $this->suspicious,
            'ip_address' => $this->ipAddress,
            'device' => $this->device,
            'location' => $this->location,
            'logged_in_at' => $this->loginAt,
        ];
    }
}
