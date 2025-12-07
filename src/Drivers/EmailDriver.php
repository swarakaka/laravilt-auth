<?php

namespace Laravilt\Auth\Drivers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Laravilt\Auth\Contracts\TwoFactorDriver;
use Laravilt\Auth\Mail\TwoFactorCodeMail;

class EmailDriver implements TwoFactorDriver
{
    protected int $codeLength = 6;

    protected int $codeExpiry = 10; // minutes

    public function getName(): string
    {
        return 'email';
    }

    public function getLabel(): string
    {
        return 'Email';
    }

    public function getIcon(): string
    {
        return 'heroicon-o-envelope';
    }

    public function enable(Authenticatable $user): array
    {
        // Send the verification code immediately
        $this->send($user);

        return [
            'message' => 'A verification code has been sent to your email address.',
        ];
    }

    public function verify(Authenticatable $user, string $code): bool
    {
        $key = $this->getCacheKey($user);
        $storedCode = Cache::get($key);

        if (! $storedCode || $storedCode !== $code) {
            return false;
        }

        Cache::forget($key);

        return true;
    }

    public function send(Authenticatable $user): bool
    {
        $code = $this->generateCode();

        // Store code in cache
        Cache::put(
            $this->getCacheKey($user),
            $code,
            now()->addMinutes($this->codeExpiry)
        );

        // Send email
        Mail::to($user->email)->send(new TwoFactorCodeMail($code));

        return true;
    }

    public function requiresSending(): bool
    {
        return true;
    }

    public function requiresConfirmation(): bool
    {
        return false;
    }

    /**
     * Generate a random code.
     */
    protected function generateCode(): string
    {
        return str_pad(
            (string) random_int(0, pow(10, $this->codeLength) - 1),
            $this->codeLength,
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * Get cache key for user.
     *
     * @param  Authenticatable&\Illuminate\Database\Eloquent\Model  $user
     */
    protected function getCacheKey(Authenticatable $user): string
    {
        return "2fa.email.{$user->id}";
    }
}
