<?php

namespace Laravilt\Auth\Services;

use Laravilt\Auth\Contracts\TwoFactorDriver;

class TwoFactorProviderManager
{
    /**
     * @var array<string, TwoFactorDriver>
     */
    protected array $drivers = [];

    public function __construct(protected ?string $panelId = null) {}

    /**
     * Register a two-factor driver.
     */
    public function register(TwoFactorDriver $driver): static
    {
        $this->drivers[$driver->getName()] = $driver;

        return $this;
    }

    /**
     * Get a driver by name.
     */
    public function getDriver(string $name): ?TwoFactorDriver
    {
        return $this->drivers[$name] ?? null;
    }

    /**
     * Get all registered drivers.
     *
     * @return array<string, TwoFactorDriver>
     */
    public function getDrivers(): array
    {
        return $this->drivers;
    }

    /**
     * Get all driver names.
     *
     * @return array<string>
     */
    public function getDriverNames(): array
    {
        return array_keys($this->drivers);
    }

    /**
     * Check if a driver exists.
     */
    public function hasDriver(string $name): bool
    {
        return isset($this->drivers[$name]);
    }

    /**
     * Get drivers formatted for frontend.
     */
    public function getDriversForFrontend(): array
    {
        return array_map(function (TwoFactorDriver $driver) {
            return [
                'name' => $driver->getName(),
                'label' => $driver->getLabel(),
                'icon' => $driver->getIcon(),
                'requiresSending' => $driver->requiresSending(),
                'requiresConfirmation' => $driver->requiresConfirmation(),
            ];
        }, array_values($this->drivers));
    }
}
