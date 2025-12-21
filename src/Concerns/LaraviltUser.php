<?php

namespace Laravilt\Auth\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Laravilt\Auth\Models\SocialAccount;

/**
 * Trait LaraviltUser
 *
 * Add this trait to your User model to enable all Laravilt authentication features.
 *
 * Required migrations:
 * - php artisan vendor:publish --tag=laravilt-auth-migrations
 *
 * @property string|null $locale
 * @property string|null $timezone
 * @property bool $two_factor_enabled
 * @property string|null $two_factor_method
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property \Illuminate\Support\Carbon|null $two_factor_confirmed_at
 */
trait LaraviltUser
{
    use HasApiTokens;
    use TwoFactorAuthenticatable;

    /**
     * Initialize the LaraviltUser trait.
     * This method is called automatically by Laravel.
     */
    public function initializeLaraviltUser(): void
    {
        // Add fillable attributes
        $this->fillable = array_merge($this->fillable, [
            'locale',
            'timezone',
            'two_factor_enabled',
            'two_factor_method',
        ]);

        // Add hidden attributes
        $this->hidden = array_merge($this->hidden, [
            'two_factor_secret',
            'two_factor_recovery_codes',
        ]);

        // Add casts
        $this->casts = array_merge($this->casts ?? [], [
            'two_factor_enabled' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ]);
    }

    /**
     * Get the user's social accounts.
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    /**
     * Get the user's connected social accounts.
     */
    public function connectedAccounts(): HasMany
    {
        return $this->socialAccounts();
    }

    /**
     * Check if the user has a specific social provider connected.
     */
    public function hasSocialAccount(string $provider): bool
    {
        return $this->socialAccounts()
            ->where('provider', $provider)
            ->exists();
    }

    /**
     * Get a specific social account by provider.
     */
    public function getSocialAccount(string $provider): ?SocialAccount
    {
        return $this->socialAccounts()
            ->where('provider', $provider)
            ->first();
    }

    /**
     * Get the user's WebAuthn credentials (passkeys).
     */
    public function webauthnCredentials(): MorphMany
    {
        return $this->morphMany(
            config('laravilt-auth.models.webauthn_credential', \Laravilt\Auth\Models\WebauthnCredential::class),
            'authenticatable'
        );
    }

    /**
     * Alias for webauthnCredentials for better readability.
     */
    public function passkeys(): MorphMany
    {
        return $this->webauthnCredentials();
    }

    /**
     * Check if the user has any passkeys registered.
     */
    public function hasPasskeys(): bool
    {
        return $this->webauthnCredentials()->exists();
    }

    /**
     * Get the user's active sessions.
     */
    public function sessions(): Collection
    {
        if (config('session.driver') !== 'database') {
            return collect();
        }

        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $this->getAuthIdentifier())
            ->orderBy('last_activity', 'desc')
            ->get();
    }

    /**
     * Get the user's other sessions (excluding current session).
     */
    public function otherSessions(): Collection
    {
        return $this->sessions()->filter(function ($session) {
            return $session->id !== session()->getId();
        });
    }

    /**
     * Delete all of the user's other sessions.
     */
    public function deleteOtherSessions(): int
    {
        if (config('session.driver') !== 'database') {
            return 0;
        }

        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $this->getAuthIdentifier())
            ->where('id', '!=', session()->getId())
            ->delete();
    }

    /**
     * Check if two-factor authentication is enabled.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return (bool) ($this->two_factor_enabled ?? false);
    }

    /**
     * Check if two-factor authentication is confirmed.
     */
    public function hasConfirmedTwoFactor(): bool
    {
        return $this->two_factor_confirmed_at !== null;
    }

    /**
     * Get the user's preferred locale.
     */
    public function getPreferredLocale(): string
    {
        return $this->locale ?? config('app.locale', 'en');
    }

    /**
     * Get the user's preferred timezone.
     */
    public function getPreferredTimezone(): string
    {
        return $this->timezone ?? config('app.timezone', 'UTC');
    }

    /**
     * Set the user's locale.
     */
    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Set the user's timezone.
     */
    public function setTimezone(string $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * Get user's avatar URL.
     * Returns a default avatar if none is set.
     *
     * Note: If you're using the HasAvatar trait from laravilt/users package,
     * that trait provides a more sophisticated implementation using Spatie Media Library.
     * This method is only used if HasAvatar is not present.
     */
    public function getAvatarUrlFromAuth(): string
    {
        // Check if HasAvatar trait's method exists (it takes priority)
        // This prevents collision when both traits are used
        if (method_exists($this, 'getFirstMediaUrl')) {
            $collection = config('laravilt-users.avatar.collection', 'avatar');
            $media = $this->getFirstMediaUrl($collection);

            if ($media) {
                return $media;
            }
        }

        if (! empty($this->avatar)) {
            return $this->avatar;
        }

        // Check if there's an avatar from a social account
        $socialAvatar = $this->socialAccounts()
            ->whereNotNull('avatar')
            ->value('avatar');

        if ($socialAvatar) {
            return $socialAvatar;
        }

        // Return gravatar as fallback
        $hash = md5(strtolower(trim($this->email ?? '')));

        return "https://www.gravatar.com/avatar/{$hash}?d=mp&s=200";
    }

    /**
     * Get user's social avatar URL if available.
     */
    public function getSocialAvatarUrl(): ?string
    {
        return $this->socialAccounts()
            ->whereNotNull('avatar')
            ->value('avatar');
    }

    /**
     * Determine if the user has verified their email address.
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Get the fillable attributes that should be merged by this trait.
     */
    public static function getLaraviltFillable(): array
    {
        return [
            'locale',
            'timezone',
            'two_factor_enabled',
            'two_factor_method',
        ];
    }

    /**
     * Get the hidden attributes that should be merged by this trait.
     */
    public static function getLaraviltHidden(): array
    {
        return [
            'two_factor_secret',
            'two_factor_recovery_codes',
        ];
    }

    /**
     * Get the casts that should be merged by this trait.
     */
    public static function getLaraviltCasts(): array
    {
        return [
            'two_factor_enabled' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
