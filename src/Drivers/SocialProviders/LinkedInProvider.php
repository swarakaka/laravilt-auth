<?php

namespace Laravilt\Auth\Drivers\SocialProviders;

use Laravilt\Auth\Contracts\SocialProvider;

class LinkedInProvider implements SocialProvider
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
        return 'linkedin';
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label ?? 'LinkedIn';
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getIcon(): string
    {
        return $this->icon ?? 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z';
    }

    public function colorClasses(string $colorClasses): static
    {
        $this->colorClasses = $colorClasses;

        return $this;
    }

    public function getColorClasses(): string
    {
        return $this->colorClasses ?? '!bg-[#0077B5] !text-white hover:!bg-[#006399]';
    }

    public function socialiteDriver(string $driver): static
    {
        $this->socialiteDriver = $driver;

        return $this;
    }

    public function getSocialiteDriver(): string
    {
        return $this->socialiteDriver ?? 'linkedin-openid';
    }

    public function redirectUrl(string $url): static
    {
        $this->redirectUrl = $url;

        return $this;
    }

    public function getRedirectUrl(): string
    {
        return $this->redirectUrl ?? config('services.linkedin.redirect');
    }

    public function callbackUrl(string $url): static
    {
        $this->callbackUrl = $url;

        return $this;
    }

    public function getCallbackUrl(): string
    {
        return $this->callbackUrl ?? config('services.linkedin.redirect');
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

        $clientId = config('services.linkedin.client_id');
        $clientSecret = config('services.linkedin.client_secret');

        // Check if credentials are set and not placeholder values
        return ! empty($clientId)
            && ! empty($clientSecret)
            && ! str_starts_with($clientId, 'your-')
            && ! str_starts_with($clientSecret, 'your-');
    }
}
