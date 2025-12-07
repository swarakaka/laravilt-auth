<?php

namespace Laravilt\Auth\Services;

use Laravilt\Auth\Builders\SocialProviderBuilder;
use Laravilt\Auth\Contracts\SocialProvider;

class SocialProviderManager
{
    protected ?SocialProviderBuilder $builder = null;

    public function __construct(protected ?string $panelId = null) {}

    /**
     * Set the provider builder.
     */
    public function setBuilder(SocialProviderBuilder $builder): static
    {
        $this->builder = $builder;

        return $this;
    }

    /**
     * Get the provider builder.
     */
    public function getBuilder(): ?SocialProviderBuilder
    {
        return $this->builder;
    }

    /**
     * Get all enabled providers.
     */
    public function getEnabledProviders(): array
    {
        if (! $this->builder) {
            return [];
        }

        return $this->builder->getEnabledProviders();
    }

    /**
     * Get provider by name.
     */
    public function getProvider(string $name): ?SocialProvider
    {
        if (! $this->builder) {
            return null;
        }

        return $this->builder->getProvider($name);
    }

    /**
     * Get all provider names.
     */
    public function getProviderNames(): array
    {
        if (! $this->builder) {
            return [];
        }

        return $this->builder->getProviderNames();
    }

    /**
     * Check if a provider exists and is enabled.
     */
    public function hasProvider(string $name): bool
    {
        $provider = $this->getProvider($name);

        return $provider && $provider->isEnabled();
    }

    /**
     * Get providers formatted for frontend.
     */
    public function getProvidersForFrontend(): array
    {
        return array_map(function (SocialProvider $provider) {
            return [
                'name' => $provider->getName(),
                'label' => $provider->getLabel(),
                'icon' => $provider->getIcon(),
                'colorClasses' => $provider->getColorClasses(),
            ];
        }, $this->getEnabledProviders());
    }
}
