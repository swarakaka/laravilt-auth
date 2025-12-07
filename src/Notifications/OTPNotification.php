<?php

namespace Laravilt\Auth\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OTPNotification extends Notification
{
    /**
     * The OTP code.
     */
    public string $otp;

    /**
     * The expiration time in minutes.
     */
    public int $expiresIn;

    /**
     * The purpose of the OTP (registration, login, etc.).
     */
    public string $purpose;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $otp, string $purpose = 'verification', int $expiresIn = 5)
    {
        $this->otp = $otp;
        $this->purpose = $purpose;
        $this->expiresIn = $expiresIn;
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
        $greeting = match ($this->purpose) {
            'registration' => 'Welcome! Complete Your Registration',
            'login' => 'New Device Login Detected',
            default => 'Verify Your Identity',
        };

        $line1 = match ($this->purpose) {
            'registration' => 'Thank you for registering! Please use the code below to verify your email address and complete your registration.',
            'login' => 'We detected a login attempt from a new device. Please use the code below to verify your identity.',
            default => 'Please use the verification code below to continue.',
        };

        return (new MailMessage)
            ->subject($greeting)
            ->greeting("Hello {$notifiable->name}!")
            ->line($line1)
            ->line('')
            ->line("**Your verification code is: {$this->otp}**")
            ->line('')
            ->line("This code will expire in {$this->expiresIn} minutes.")
            ->line('If you did not request this code, please ignore this email.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'otp' => $this->otp,
            'purpose' => $this->purpose,
            'expires_at' => now()->addMinutes($this->expiresIn)->toDateTimeString(),
        ];
    }
}
