<?php

namespace Laravilt\Auth\Drivers\SocialProviders;

use Laravilt\Auth\Contracts\SocialProvider;

class JiraProvider implements SocialProvider
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
        return 'jira';
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label ?? 'Jira';
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getIcon(): string
    {
        return $this->icon ?? 'M11.53 2c0 2.4 1.97 4.35 4.35 4.35h1.78v1.7c0 2.4 1.97 4.35 4.35 4.35V2.84a.84.84 0 00-.84-.84H11.53zM6.77 6.8a4.36 4.36 0 004.35 4.36h1.7v1.7a4.36 4.36 0 004.35 4.35V7.63a.84.84 0 00-.84-.84H6.77zM2 11.6c0 2.4 1.95 4.35 4.35 4.35h1.7v1.7a4.35 4.35 0 108.7-.01v-9.2a.84.84 0 00-.84-.84H2.84a.84.84 0 00-.84.84v3.15z';
    }

    public function colorClasses(string $colorClasses): static
    {
        $this->colorClasses = $colorClasses;

        return $this;
    }

    public function getColorClasses(): string
    {
        return $this->colorClasses ?? '!bg-[#0052CC] !text-white hover:!bg-[#0747A6]';
    }

    public function socialiteDriver(string $driver): static
    {
        $this->socialiteDriver = $driver;

        return $this;
    }

    public function getSocialiteDriver(): string
    {
        return $this->socialiteDriver ?? 'atlassian';
    }

    public function redirectUrl(string $url): static
    {
        $this->redirectUrl = $url;

        return $this;
    }

    public function getRedirectUrl(): string
    {
        return $this->redirectUrl ?? config('services.atlassian.redirect', config('services.jira.redirect'));
    }

    public function callbackUrl(string $url): static
    {
        $this->callbackUrl = $url;

        return $this;
    }

    public function getCallbackUrl(): string
    {
        return $this->callbackUrl ?? config('services.atlassian.redirect', config('services.jira.redirect'));
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

        // Check both atlassian and jira config keys for backwards compatibility
        $clientId = config('services.atlassian.client_id', config('services.jira.client_id'));
        $clientSecret = config('services.atlassian.client_secret', config('services.jira.client_secret'));

        return ! empty($clientId)
            && ! empty($clientSecret)
            && ! str_starts_with($clientId, 'your-')
            && ! str_starts_with($clientSecret, 'your-');
    }
}
