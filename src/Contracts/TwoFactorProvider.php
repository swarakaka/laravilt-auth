<?php

namespace Laravilt\Auth\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface TwoFactorProvider
{
    /**
     * Get the provider name.
     */
    public function getName(): string;

    /**
     * Generate 2FA for the user.
     */
    public function generate(Authenticatable $user): array;

    /**
     * Verify 2FA code.
     */
    public function verify(Authenticatable $user, string $code): bool;

    /**
     * Disable 2FA for the user.
     */
    public function disable(Authenticatable $user): bool;
}
