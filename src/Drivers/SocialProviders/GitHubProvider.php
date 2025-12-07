<?php

namespace Laravilt\Auth\Drivers\SocialProviders;

use Laravilt\Auth\Contracts\SocialProvider;

class GitHubProvider implements SocialProvider
{
    protected ?string $label = null;

    protected ?string $icon = null;

    protected ?string $colorClasses = null;

    protected ?string $socialiteDriver = null;

    protected ?string $redirectUrl = null;

    protected ?string $callbackUrl = null;

    protected ?bool $enabled = null;

    public function __construct() {}

    public function getName(): string
    {
        return 'github';
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label ?? 'GitHub';
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getIcon(): string
    {
        return $this->icon ?? 'M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z';
    }

    public function colorClasses(string $colorClasses): static
    {
        $this->colorClasses = $colorClasses;

        return $this;
    }

    public function getColorClasses(): string
    {
        return $this->colorClasses ?? '!bg-gray-900 !text-white hover:!bg-gray-800 dark:!bg-gray-100 dark:!text-gray-900 dark:hover:!bg-white';
    }

    public function socialiteDriver(string $driver): static
    {
        $this->socialiteDriver = $driver;

        return $this;
    }

    public function getSocialiteDriver(): string
    {
        return $this->socialiteDriver ?? 'github';
    }

    public function redirectUrl(string $url): static
    {
        $this->redirectUrl = $url;

        return $this;
    }

    public function getRedirectUrl(): string
    {
        return $this->redirectUrl ?? config('services.github.redirect');
    }

    public function callbackUrl(string $url): static
    {
        $this->callbackUrl = $url;

        return $this;
    }

    public function getCallbackUrl(): string
    {
        return $this->callbackUrl ?? config('services.github.redirect');
    }

    public function enabled(bool $enabled = true): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function disabled(): static
    {
        $this->enabled = false;

        return $this;
    }

    public function isEnabled(): bool
    {
        if ($this->enabled !== null) {
            return $this->enabled;
        }

        return ! empty(config('services.github.client_id')) && ! empty(config('services.github.client_secret'));
    }
}
