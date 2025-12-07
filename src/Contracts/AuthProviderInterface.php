<?php

namespace Laravilt\Auth\Contracts;

use Closure;

interface AuthProviderInterface
{
    /**
     * Set the guard name.
     */
    public function guard(string $guard): static;

    /**
     * Set the model class.
     */
    public function model(string $model): static;

    /**
     * Set the field to login by.
     */
    public function loginBy(string $field): static;

    /**
     * Set login methods.
     */
    public function loginMethods(array $methods): static;

    /**
     * Enable registration.
     */
    public function registration(bool|Closure $condition = true): static;

    /**
     * Enable email verification.
     */
    public function emailVerification(bool|Closure $condition = true): static;

    /**
     * Enable password reset.
     */
    public function passwordReset(bool|Closure $condition = true): static;

    /**
     * Enable two-factor authentication.
     */
    public function twoFactor(array $methods = ['totp']): static;

    /**
     * Enable profile management.
     */
    public function profile(bool|Closure $condition = true): static;

    /**
     * Enable session management.
     */
    public function sessions(bool|Closure $condition = true): static;

    /**
     * Enable API tokens.
     */
    public function apiTokens(bool|Closure $condition = true): static;

    /**
     * Get the guard name.
     */
    public function getGuard(): string;

    /**
     * Get the model class.
     */
    public function getModel(): string;

    /**
     * Get the login field.
     */
    public function getLoginBy(): string;

    /**
     * Get the login methods.
     */
    public function getLoginMethods(): array;

    /**
     * Check if registration is enabled.
     */
    public function hasRegistration(): bool;

    /**
     * Check if email verification is enabled.
     */
    public function hasEmailVerification(): bool;

    /**
     * Check if password reset is enabled.
     */
    public function hasPasswordReset(): bool;

    /**
     * Check if two-factor is enabled.
     */
    public function hasTwoFactor(): bool;

    /**
     * Check if profile is enabled.
     */
    public function hasProfile(): bool;

    /**
     * Check if sessions are enabled.
     */
    public function hasSessions(): bool;

    /**
     * Check if API tokens are enabled.
     */
    public function hasApiTokens(): bool;
}
