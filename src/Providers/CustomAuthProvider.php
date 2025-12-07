<?php

namespace Laravilt\Auth\Providers;

use Illuminate\Contracts\Auth\Authenticatable;

class CustomAuthProvider extends LaraviltAuthProvider
{
    /**
     * Retrieve a user by the given credentials.
     *
     * This extends the parent to support additional authentication fields
     * like phone, username, etc.
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $credentials = array_filter(
            $credentials,
            fn ($key) => ! str_contains($key, 'password'),
            ARRAY_FILTER_USE_KEY
        );

        if (empty($credentials)) {
            return null;
        }

        $query = $this->newModelQuery();

        // Support multiple login fields
        $loginFields = $credentials['login_field'] ?? 'email';
        $loginValue = $credentials[$loginFields] ?? null;

        if ($loginValue) {
            // Try to find user by email, phone, or username
            $query->where(function ($q) use ($loginValue) {
                $q->where('email', $loginValue)
                    ->orWhere('phone', $loginValue)
                    ->orWhere('username', $loginValue);
            });
        } else {
            // Use standard credential matching
            foreach ($credentials as $key => $value) {
                if (is_array($value) || $value instanceof \Closure) {
                    return null;
                } else {
                    $query->where($key, $value);
                }
            }
        }

        return $query->first();
    }

    /**
     * Validate a user against the given credentials.
     *
     * Supports OTP and passwordless authentication.
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        // If OTP is provided, validate it
        if (isset($credentials['otp'])) {
            return $this->validateOTP($user, $credentials['otp']);
        }

        // If magic token is provided, validate it
        if (isset($credentials['magic_token'])) {
            return $this->validateMagicToken($user, $credentials['magic_token']);
        }

        // Otherwise, validate password
        return parent::validateCredentials($user, $credentials);
    }

    /**
     * Validate OTP for the user.
     */
    protected function validateOTP(Authenticatable $user, string $otp): bool
    {
        // This would typically be handled by OTPService
        // For now, return false to indicate it needs implementation
        return false;
    }

    /**
     * Validate magic token for passwordless auth.
     */
    protected function validateMagicToken(Authenticatable $user, string $token): bool
    {
        // This would typically be handled by PasswordlessService
        // For now, return false to indicate it needs implementation
        return false;
    }
}
