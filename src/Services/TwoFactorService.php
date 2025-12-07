<?php

namespace Laravilt\Auth\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravilt\Auth\Contracts\TwoFactorDriver;

class TwoFactorService
{
    public function __construct(
        protected ?TwoFactorProviderManager $providerManager = null
    ) {}

    /**
     * Set the provider manager.
     */
    public function setProviderManager(TwoFactorProviderManager $manager): static
    {
        $this->providerManager = $manager;

        return $this;
    }

    /**
     * Get driver for a specific method.
     */
    protected function getDriver(string $method): ?TwoFactorDriver
    {
        if (! $this->providerManager) {
            return null;
        }

        return $this->providerManager->getDriver($method);
    }

    /**
     * Enable 2FA for user.
     *
     * @param  Authenticatable&\Illuminate\Database\Eloquent\Model  $user
     */
    public function enable(Authenticatable $user, string $method = 'totp'): array
    {
        $driver = $this->getDriver($method);

        if (! $driver) {
            throw new \Exception("Two-factor driver '{$method}' not found or not enabled.");
        }

        // Enable using the driver
        $data = $driver->enable($user);

        // Only set method, don't enable yet (will be enabled after confirmation)
        $user->update([
            'two_factor_enabled' => false,
            'two_factor_method' => $method,
        ]);

        return $data;
    }

    /**
     * Disable 2FA for user.
     */
    public function disable(Authenticatable $user): bool
    {
        $user->update([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
            'two_factor_method' => null,
            'two_factor_recovery_codes' => null,
        ]);

        return true;
    }

    /**
     * Verify 2FA code.
     */
    public function verify(Authenticatable $user, string $code, string $method = 'totp'): bool
    {
        // Check if it's a recovery code
        if ($method === 'recovery') {
            return $this->verifyRecoveryCode($user, $code);
        }

        // Get the driver for the method
        $driver = $this->getDriver($method);

        if (! $driver) {
            return false;
        }

        return $driver->verify($user, $code);
    }

    /**
     * Confirm 2FA setup and generate recovery codes.
     *
     * @param  Authenticatable&\Illuminate\Database\Eloquent\Model  $user
     */
    public function confirm(Authenticatable $user): array
    {
        // Mark as confirmed and enabled
        $user->update([
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
        ]);

        // Generate recovery codes
        return $this->generateRecoveryCodes($user);
    }

    /**
     * Verify recovery code.
     *
     * @param  Authenticatable&\Illuminate\Database\Eloquent\Model  $user
     */
    protected function verifyRecoveryCode(Authenticatable $user, string $code): bool
    {
        if (! $user->two_factor_recovery_codes) {
            return false;
        }

        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);

        // Find the matching hashed recovery code
        $matchedIndex = null;
        foreach ($recoveryCodes as $index => $hashedCode) {
            if (\Hash::check($code, $hashedCode)) {
                $matchedIndex = $index;
                break;
            }
        }

        if ($matchedIndex === null) {
            return false;
        }

        // Remove the used recovery code
        unset($recoveryCodes[$matchedIndex]);
        $recoveryCodes = array_values($recoveryCodes);

        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ]);

        return true;
    }

    /**
     * Send 2FA code.
     *
     * @param  Authenticatable&\Illuminate\Database\Eloquent\Model  $user
     */
    public function sendCode(Authenticatable $user, string $method): bool
    {
        $driver = $this->getDriver($method);

        if (! $driver || ! $driver->requiresSending()) {
            return false;
        }

        return $driver->send($user);
    }

    /**
     * Generate recovery codes.
     */
    public function generateRecoveryCodes(Authenticatable $user): array
    {
        $codes = Collection::times(8, function () {
            return Str::random(10);
        })->all();

        // Hash the codes before storing
        $hashedCodes = collect($codes)->map(function ($code) {
            return \Hash::make($code);
        })->all();

        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode($hashedCodes)),
        ]);

        // Return plain text codes for display
        return $codes;
    }
}
