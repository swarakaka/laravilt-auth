<?php

namespace Laravilt\Auth\Drivers\SocialProviders;

use Laravilt\Auth\Contracts\SocialProvider;

class DiscordProvider implements SocialProvider
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
        return 'discord';
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label ?? 'Discord';
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getIcon(): string
    {
        return $this->icon ?? 'M20.317 4.37a19.791 19.791 0 00-4.885-1.515.074.074 0 00-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 00-5.487 0 12.64 12.64 0 00-.617-1.25.077.077 0 00-.079-.037A19.736 19.736 0 003.677 4.37a.07.07 0 00-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 00.031.057 19.9 19.9 0 005.993 3.03.078.078 0 00.084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 00-.041-.106 13.107 13.107 0 01-1.872-.892.077.077 0 01-.008-.128 10.2 10.2 0 00.372-.292.074.074 0 01.077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 01.078.01c.12.098.246.198.373.292a.077.077 0 01-.006.127 12.299 12.299 0 01-1.873.892.077.077 0 00-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 00.084.028 19.839 19.839 0 006.002-3.03.077.077 0 00.032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 00-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z';
    }

    public function colorClasses(string $colorClasses): static
    {
        $this->colorClasses = $colorClasses;

        return $this;
    }

    public function getColorClasses(): string
    {
        return $this->colorClasses ?? '!bg-[#5865F2] !text-white hover:!bg-[#4752C4]';
    }

    public function socialiteDriver(string $driver): static
    {
        $this->socialiteDriver = $driver;

        return $this;
    }

    public function getSocialiteDriver(): string
    {
        return $this->socialiteDriver ?? 'discord';
    }

    public function redirectUrl(string $url): static
    {
        $this->redirectUrl = $url;

        return $this;
    }

    public function getRedirectUrl(): string
    {
        return $this->redirectUrl ?? config('services.discord.redirect');
    }

    public function callbackUrl(string $url): static
    {
        $this->callbackUrl = $url;

        return $this;
    }

    public function getCallbackUrl(): string
    {
        return $this->callbackUrl ?? config('services.discord.redirect');
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

        $clientId = config('services.discord.client_id');
        $clientSecret = config('services.discord.client_secret');

        return ! empty($clientId)
            && ! empty($clientSecret)
            && ! str_starts_with($clientId, 'your-')
            && ! str_starts_with($clientSecret, 'your-');
    }
}
