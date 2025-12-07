<?php

namespace Laravilt\Auth\Contracts;

interface SocialProvider
{
    /**
     * Get the provider name (e.g., 'google', 'github').
     */
    public function getName(): string;

    /**
     * Get the provider label for display (e.g., 'Google', 'GitHub').
     */
    public function getLabel(): string;

    /**
     * Get the provider icon SVG path data.
     */
    public function getIcon(): string;

    /**
     * Get the provider button color classes.
     */
    public function getColorClasses(): string;

    /**
     * Get the Laravel Socialite driver name.
     */
    public function getSocialiteDriver(): string;

    /**
     * Get redirect URL for OAuth flow.
     */
    public function getRedirectUrl(): string;

    /**
     * Get callback URL for OAuth flow.
     */
    public function getCallbackUrl(): string;

    /**
     * Check if the provider is enabled.
     */
    public function isEnabled(): bool;
}
