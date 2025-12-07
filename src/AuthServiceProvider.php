<?php

namespace Laravilt\Auth;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravilt\Auth\Console\Commands\GenerateAuthCommand;
use Laravilt\Auth\Console\Commands\InstallAuthCommand;
use Laravilt\Auth\Methods\EmailPasswordMethod;
use Laravilt\Auth\Methods\PasswordlessMethod;
use Laravilt\Auth\Methods\PhoneOTPMethod;
use Laravilt\Auth\Methods\SocialLoginMethod;
use Laravilt\Auth\Methods\TwoFactorMethod;
use Laravilt\Auth\Methods\WebAuthnMethod;
use SocialiteProviders\Atlassian\AtlassianExtendSocialite;
use SocialiteProviders\Discord\DiscordExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/../config/laravilt-auth.php',
            'laravilt-auth'
        );

        // Register AuthManager as singleton
        $this->app->singleton('laravilt.auth', function ($app) {
            return new AuthManager(
                $app['auth'],
                $app['request']
            );
        });

        // Alias for easier access
        $this->app->alias('laravilt.auth', AuthManager::class);
    }

    /**
     * Boot services.
     */
    public function boot(): void
    {
        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravilt-auth');

        // Load translations
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'laravilt-auth');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            // Publish config
            $this->publishes([
                __DIR__.'/../config/laravilt-auth.php' => config_path('laravilt-auth.php'),
            ], 'laravilt-auth-config');

            // Publish assets
            $this->publishes([
                __DIR__.'/../dist' => public_path('vendor/laravilt/auth'),
            ], 'laravilt-auth-assets');

            // Publish views
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/laravilt-auth'),
            ], 'laravilt-auth-views');

            // Publish migrations
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'laravilt-auth-migrations');

            // Register commands
            $this->commands([
                InstallAuthCommand::class,
                GenerateAuthCommand::class,
            ]);
        }

        // Register default auth methods
        $this->registerAuthMethods();

        // Register Socialite providers
        $this->registerSocialiteProviders();
    }

    /**
     * Register default authentication methods.
     */
    protected function registerAuthMethods(): void
    {
        $authManager = $this->app->make('laravilt.auth');

        $authManager->registerMethod('email', EmailPasswordMethod::class);
        $authManager->registerMethod('phone', PhoneOTPMethod::class);
        $authManager->registerMethod('social', SocialLoginMethod::class);
        $authManager->registerMethod('passwordless', PasswordlessMethod::class);
        $authManager->registerMethod('webauthn', WebAuthnMethod::class);
        $authManager->registerMethod('2fa', TwoFactorMethod::class);
    }

    /**
     * Register Socialite providers.
     *
     * Laravel Socialite automatically registers providers based on credentials
     * configured in config/services.php. This method ensures Socialite is loaded
     * and registers additional providers from SocialiteProviders.
     */
    protected function registerSocialiteProviders(): void
    {
        if (! class_exists(\Laravel\Socialite\Facades\Socialite::class)) {
            return;
        }

        // Socialite automatically registers standard providers (Google, GitHub, Facebook, etc.)
        // based on config/services.php. No manual registration needed for them.

        // Register SocialiteProviders packages via event listeners (for Laravel 11+)
        if (class_exists(SocialiteWasCalled::class)) {
            // Discord provider
            if (class_exists(DiscordExtendSocialite::class)) {
                Event::listen(SocialiteWasCalled::class, DiscordExtendSocialite::class);
            }

            // Atlassian/Jira provider
            if (class_exists(AtlassianExtendSocialite::class)) {
                Event::listen(SocialiteWasCalled::class, AtlassianExtendSocialite::class);
            }
        }
    }
}
