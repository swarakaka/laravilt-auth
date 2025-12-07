<?php

namespace Laravilt\Auth\TwoFactor;

use Illuminate\Contracts\Auth\Authenticatable;
use Laravilt\Auth\Contracts\TwoFactorProvider;
use Laravilt\Auth\Services\OTPService;

class SmsProvider implements TwoFactorProvider
{
    public function __construct(
        protected OTPService $otpService
    ) {}

    /**
     * Get the provider name.
     */
    public function getName(): string
    {
        return 'sms';
    }

    /**
     * Generate 2FA for the user.
     *
     * @param  Authenticatable&\Illuminate\Database\Eloquent\Model  $user
     */
    public function generate(Authenticatable $user): array
    {
        $phone = $user->phone ?? '';

        if (empty($phone)) {
            throw new \Exception('User does not have a phone number');
        }

        $this->otpService->send($phone);

        return [
            'message' => 'SMS sent to your phone number',
        ];
    }

    /**
     * Verify 2FA code.
     *
     * @param  Authenticatable&\Illuminate\Database\Eloquent\Model  $user
     */
    public function verify(Authenticatable $user, string $code): bool
    {
        $phone = $user->phone ?? '';

        if (empty($phone)) {
            return false;
        }

        return $this->otpService->verify($phone, $code);
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
