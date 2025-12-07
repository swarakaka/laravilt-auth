<?php

namespace Laravilt\Auth\Drivers\SocialProviders;

use Laravilt\Auth\Contracts\SocialProvider;

class TwitterProvider implements SocialProvider
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
        return 'twitter';
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label ?? 'X (Twitter)';
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getIcon(): string
    {
        // X (Twitter) new logo
        return $this->icon ?? 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z';
    }

    public function colorClasses(string $colorClasses): static
    {
        $this->colorClasses = $colorClasses;

        return $this;
    }

    public function getColorClasses(): string
    {
        return $this->colorClasses ?? '!bg-black !text-white hover:!bg-gray-900 dark:!bg-white dark:!text-black dark:hover:!bg-gray-100';
    }

    public function socialiteDriver(string $driver): static
    {
        $this->socialiteDriver = $driver;

        return $this;
    }

    public function getSocialiteDriver(): string
    {
        return $this->socialiteDriver ?? 'twitter-oauth-2';
    }

    public function redirectUrl(string $url): static
    {
        $this->redirectUrl = $url;

        return $this;
    }

    public function getRedirectUrl(): string
    {
        return $this->redirectUrl ?? config('services.twitter.redirect');
    }

    public function callbackUrl(string $url): static
    {
        $this->callbackUrl = $url;

        return $this;
    }

    public function getCallbackUrl(): string
    {
        return $this->callbackUrl ?? config('services.twitter.redirect');
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

        return ! empty(config('services.twitter.client_id')) && ! empty(config('services.twitter.client_secret'));
    }
}
