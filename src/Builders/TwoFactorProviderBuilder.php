<?php

namespace Laravilt\Auth\Builders;

use Laravilt\Auth\Contracts\TwoFactorDriver;

class TwoFactorProviderBuilder
{
    /**
     * @var array<TwoFactorDriver>
     */
    protected array $providers = [];

    /**
     * Add a two-factor provider.
     *
     * @param  class-string<TwoFactorDriver>|TwoFactorDriver  $provider
     */
    public function provider(string|TwoFactorDriver $provider, ?callable $configure = null): static
    {
        $driverInstance = is_string($provider) ? app($provider) : $provider;

        if ($configure) {
            $configure($driverInstance);
        }

        $this->providers[] = $driverInstance;

        return $this;
    }

    /**
     * Get all registered providers.
     *
     * @return array<TwoFactorDriver>
     */
    public function getProviders(): array
    {
        return $this->providers;
    }
}
