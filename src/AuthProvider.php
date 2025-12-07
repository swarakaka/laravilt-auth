<?php

namespace Laravilt\Auth;

use Closure;
use Laravilt\Auth\Contracts\AuthProviderInterface;
use Laravilt\Support\Component;

class AuthProvider extends Component implements AuthProviderInterface
{
    protected string $guard = 'web';

    protected string $model;

    protected string $loginBy = 'email';

    protected array $loginMethods = [];

    protected bool|Closure $registration = false;

    protected bool|Closure $emailVerification = false;

    protected bool|Closure $passwordReset = true;

    protected array $twoFactorMethods = [];

    protected array $twoFactorAlternatives = [];

    protected bool|Closure $profile = false;

    protected bool|Closure $sessions = false;

    protected bool|Closure $apiTokens = false;

    protected ?string $loginPage = null;

    protected ?string $registerPage = null;

    protected array $colors = [];

    /**
     * Create a new auth provider instance.
     */
    public static function make(string $name = 'default'): static
    {
        return new static;
    }

    /**
     * Set the guard name.
     */
    public function guard(string $guard): static
    {
        $this->guard = $guard;

        return $this;
    }

    /**
     * Set the model class.
     */
    public function model(string $model): static
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Set the field to login by.
     */
    public function loginBy(string $field): static
    {
        $this->loginBy = $field;

        return $this;
    }

    /**
     * Set login methods.
     */
    public function loginMethods(array $methods): static
    {
        $this->loginMethods = $methods;

        return $this;
    }

    /**
     * Enable OTP authentication.
     */
    public function withOTP(): static
    {
        $this->loginMethods['otp'] = true;

        return $this;
    }

    /**
     * Enable social authentication.
     */
    public function withSocial(array $providers): static
    {
        $this->loginMethods['social'] = $providers;

        return $this;
    }

    /**
     * Enable passwordless authentication.
     */
    public function withPasswordless(): static
    {
        $this->loginMethods['passwordless'] = true;

        return $this;
    }

    /**
     * Enable WebAuthn authentication.
     */
    public function withWebAuthn(): static
    {
        $this->loginMethods['webauthn'] = true;

        return $this;
    }

    /**
     * Enable registration.
     */
    public function registration(bool|Closure $condition = true): static
    {
        $this->registration = $condition;

        return $this;
    }

    /**
     * Enable email verification.
     */
    public function emailVerification(bool|Closure $condition = true): static
    {
        $this->emailVerification = $condition;

        return $this;
    }

    /**
     * Enable password reset.
     */
    public function passwordReset(bool|Closure $condition = true): static
    {
        $this->passwordReset = $condition;

        return $this;
    }

    /**
     * Enable two-factor authentication.
     */
    public function twoFactor(array $methods = ['totp']): static
    {
        $this->twoFactorMethods = $methods;

        return $this;
    }

    /**
     * Enable passkeys as a 2FA alternative.
     */
    public function passkeys(bool $enabled = true): static
    {
        if ($enabled) {
            $this->twoFactorAlternatives['passkeys'] = true;
        } else {
            unset($this->twoFactorAlternatives['passkeys']);
        }

        return $this;
    }

    /**
     * Enable magic links as a 2FA alternative.
     */
    public function magicLinks(bool $enabled = true): static
    {
        if ($enabled) {
            $this->twoFactorAlternatives['magic_links'] = true;
        } else {
            unset($this->twoFactorAlternatives['magic_links']);
        }

        return $this;
    }

    /**
     * Enable profile management.
     */
    public function profile(bool|Closure $condition = true): static
    {
        $this->profile = $condition;

        return $this;
    }

    /**
     * Enable session management.
     */
    public function sessions(bool|Closure $condition = true): static
    {
        $this->sessions = $condition;

        return $this;
    }

    /**
     * Enable API tokens.
     */
    public function apiTokens(bool|Closure $condition = true): static
    {
        $this->apiTokens = $condition;

        return $this;
    }

    /**
     * Set custom login page.
     */
    public function loginPage(string $page): static
    {
        $this->loginPage = $page;

        return $this;
    }

    /**
     * Set custom register page.
     */
    public function registerPage(string $page): static
    {
        $this->registerPage = $page;

        return $this;
    }

    /**
     * Set colors.
     */
    public function colors(array $colors): static
    {
        $this->colors = $colors;

        return $this;
    }

    /**
     * Get the guard name.
     */
    public function getGuard(): string
    {
        return $this->guard;
    }

    /**
     * Get the model class.
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Get the login field.
     */
    public function getLoginBy(): string
    {
        return $this->loginBy;
    }

    /**
     * Get the login methods.
     */
    public function getLoginMethods(): array
    {
        return $this->loginMethods;
    }

    /**
     * Check if registration is enabled.
     */
    public function hasRegistration(): bool
    {
        return $this->evaluate($this->registration) === true;
    }

    /**
     * Check if email verification is enabled.
     */
    public function hasEmailVerification(): bool
    {
        return $this->evaluate($this->emailVerification) === true;
    }

    /**
     * Check if password reset is enabled.
     */
    public function hasPasswordReset(): bool
    {
        return $this->evaluate($this->passwordReset) === true;
    }

    /**
     * Check if two-factor is enabled.
     */
    public function hasTwoFactor(): bool
    {
        return ! empty($this->twoFactorMethods);
    }

    /**
     * Get two-factor methods.
     */
    public function getTwoFactorMethods(): array
    {
        return $this->twoFactorMethods;
    }

    /**
     * Get two-factor alternatives.
     */
    public function getTwoFactorAlternatives(): array
    {
        return $this->twoFactorAlternatives;
    }

    /**
     * Check if passkeys are enabled as a 2FA alternative.
     */
    public function hasPasskeys(): bool
    {
        return isset($this->twoFactorAlternatives['passkeys']);
    }

    /**
     * Check if magic links are enabled as a 2FA alternative.
     */
    public function hasMagicLinks(): bool
    {
        return isset($this->twoFactorAlternatives['magic_links']);
    }

    /**
     * Check if profile is enabled.
     */
    public function hasProfile(): bool
    {
        return $this->evaluate($this->profile) === true;
    }

    /**
     * Check if sessions are enabled.
     */
    public function hasSessions(): bool
    {
        return $this->evaluate($this->sessions) === true;
    }

    /**
     * Check if API tokens are enabled.
     */
    public function hasApiTokens(): bool
    {
        return $this->evaluate($this->apiTokens) === true;
    }

    /**
     * Get the login page.
     */
    public function getLoginPage(): ?string
    {
        return $this->loginPage;
    }

    /**
     * Get the register page.
     */
    public function getRegisterPage(): ?string
    {
        return $this->registerPage;
    }

    /**
     * Get colors.
     */
    public function getColors(): array
    {
        return $this->colors;
    }
}
