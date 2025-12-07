<?php

namespace Laravilt\Auth\Builders;

use Laravilt\Auth\Contracts\SocialProvider;

class SocialProviderBuilder
{
    protected array $providers = [];

    /**
     * Add a social provider driver.
     */
    public function provider(string|SocialProvider $driver, \Closure|array|null $config = null): static
    {
        if (is_string($driver)) {
            // If it's a class name, instantiate it
            $instance = new $driver;

            // Apply configuration if provided
            if ($config instanceof \Closure) {
                // New fluent API with closure
                $config($instance);
            } elseif (is_array($config)) {
                // Legacy array config - apply using methods if they exist
                foreach ($config as $key => $value) {
                    if (method_exists($instance, $key)) {
                        $instance->$key($value);
                    }
                }
            }

            $this->providers[] = $instance;
        } else {
            // If it's already an instance, use it directly
            $this->providers[] = $driver;
        }

        return $this;
    }

    /**
     * Get all configured providers.
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * Get enabled providers only.
     */
    public function getEnabledProviders(): array
    {
        return array_values(array_filter($this->providers, fn (SocialProvider $provider) => $provider->isEnabled()));
    }

    /**
     * Get provider names.
     */
    public function getProviderNames(): array
    {
        return array_map(fn (SocialProvider $provider) => $provider->getName(), $this->getEnabledProviders());
    }

    /**
     * Get provider by name.
     */
    public function getProvider(string $name): ?SocialProvider
    {
        foreach ($this->providers as $provider) {
            if ($provider->getName() === $name) {
                return $provider;
            }
        }

        return null;
    }
}
