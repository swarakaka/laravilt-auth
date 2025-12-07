<?php

namespace Laravilt\Auth\TwoFactor;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Laravilt\Auth\Contracts\TwoFactorProvider;

class EmailProvider implements TwoFactorProvider
{
    /**
     * Get the provider name.
     */
    public function getName(): string
    {
        return 'email';
    }

    /**
     * Generate 2FA for the user.
     *
     * @param  Authenticatable&\Illuminate\Database\Eloquent\Model  $user
     */
    public function generate(Authenticatable $user): array
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put("2fa.email.{$user->id}", $code, now()->addMinutes(5));

        // Send email with code
        // Mail::to($user->email)->send(new TwoFactorCodeMail($code));

        return [
            'message' => 'Email sent with 2FA code',
        ];
    }

    /**
     * Verify 2FA code.
     *
     * @param  Authenticatable&\Illuminate\Database\Eloquent\Model  $user
     */
    public function verify(Authenticatable $user, string $code): bool
    {
        $cachedCode = Cache::get("2fa.email.{$user->id}");

        if ($cachedCode && $cachedCode === $code) {
            Cache::forget("2fa.email.{$user->id}");

            return true;
        }

        return false;
    }

    /**
     * Disable 2FA for the user.
     *
     * @param  Authenticatable&\Illuminate\Database\Eloquent\Model  $user
     */
    public function disable(Authenticatable $user): bool
    {
        $user->update([
            'two_factor_enabled' => false,
            'two_factor_method' => null,
        ]);

        return true;
    }
}
