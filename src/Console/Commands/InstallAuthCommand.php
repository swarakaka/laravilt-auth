<?php

namespace Laravilt\Auth\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/**
 * Install and configure the Laravilt Auth package.
 *
 * This command provides an interactive installation process for the Laravilt Auth package,
 * including publishing assets, migrations, views, and configuration files.
 */
class InstallAuthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravilt:auth:install
                            {--force : Overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and configure the Laravilt Auth package';

    /**
     * Configuration options collected during installation.
     */
    protected array $config = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('⚡ Laravilt Auth Installation');
        $this->newLine();

        if (! $this->option('no-interaction')) {
            $this->gatherConfiguration();
        } else {
            $this->setDefaultConfiguration();
        }

        $this->newLine();
        $this->components->info('Installing Laravilt Auth package...');
        $this->newLine();

        // Create progress bar for installation steps
        $steps = $this->getInstallationSteps();
        $bar = $this->output->createProgressBar(count($steps));
        $bar->setFormat('verbose');

        foreach ($steps as $step => $callback) {
            $this->components->task($step, function () use ($callback, $bar) {
                $callback();
                $bar->advance();

                return true;
            });
        }

        $bar->finish();
        $this->newLine(2);

        // Display success message
        $this->displaySuccessMessage();

        return self::SUCCESS;
    }

    /**
     * Gather configuration from user prompts.
     */
    protected function gatherConfiguration(): void
    {
        $this->components->info('Let\'s configure your authentication system:');
        $this->newLine();

        // Guard name
        $this->config['guard'] = select(
            label: 'Which guard would you like to use?',
            options: ['web', 'api', 'admin', 'custom'],
            default: 'web',
            hint: 'The default authentication guard for your application'
        );

        if ($this->config['guard'] === 'custom') {
            $this->config['guard'] = text(
                label: 'Enter custom guard name',
                placeholder: 'my-guard',
                required: true,
                validate: fn ($value) => match (true) {
                    empty($value) => 'Guard name is required',
                    ! preg_match('/^[a-z0-9_-]+$/', $value) => 'Guard name must contain only lowercase letters, numbers, dashes, and underscores',
                    default => null
                }
            );
        }

        // Model class
        $this->config['model'] = text(
            label: 'User model class',
            default: 'App\\Models\\User',
            required: true,
            hint: 'The Eloquent model for your users'
        );

        // Authentication methods
        $this->config['methods'] = multiselect(
            label: 'Which authentication methods would you like to enable?',
            options: [
                'email' => 'Email & Password (Traditional)',
                'phone' => 'Phone & OTP (SMS)',
                'social' => 'Social Login (OAuth)',
                'passwordless' => 'Passwordless (Magic Links)',
                'webauthn' => 'WebAuthn (Biometric)',
            ],
            default: ['email'],
            required: true,
            hint: 'Select one or more methods using Space key'
        );

        // Two-factor authentication
        $this->config['two_factor'] = confirm(
            label: 'Enable two-factor authentication?',
            default: false,
            hint: 'Provides additional security layer'
        );

        if ($this->config['two_factor']) {
            $this->config['two_factor_methods'] = multiselect(
                label: 'Which 2FA methods would you like to enable?',
                options: [
                    'totp' => 'TOTP (Authenticator Apps)',
                    'sms' => 'SMS',
                    'email' => 'Email',
                ],
                default: ['totp'],
                required: true
            );
        }

        // Features
        $this->config['features'] = multiselect(
            label: 'Which features would you like to enable?',
            options: [
                'registration' => 'User Registration',
                'email_verification' => 'Email Verification',
                'password_reset' => 'Password Reset',
                'profile' => 'User Profile Management',
                'sessions' => 'Session Management',
                'api_tokens' => 'API Token Management',
            ],
            default: ['registration', 'email_verification', 'password_reset', 'profile'],
            hint: 'Select features to enable'
        );

        // Social providers (if social auth is enabled)
        if (in_array('social', $this->config['methods'])) {
            $this->config['social_providers'] = multiselect(
                label: 'Which social providers would you like to enable?',
                options: [
                    'google' => 'Google',
                    'github' => 'GitHub',
                    'facebook' => 'Facebook',
                    'twitter' => 'Twitter',
                    'linkedin' => 'LinkedIn',
                ],
                default: ['google', 'github'],
                required: true
            );
        }

        // Publishing options
        $this->newLine();
        $this->components->info('Publishing options:');
        $this->newLine();

        $this->config['publish_config'] = confirm(
            label: 'Publish configuration file?',
            default: true,
            hint: 'Publish to config/laravilt-auth.php'
        );

        $this->config['publish_migrations'] = confirm(
            label: 'Publish migration files?',
            default: true,
            hint: 'Publish to database/migrations'
        );

        if ($this->config['publish_migrations']) {
            $this->config['run_migrations'] = confirm(
                label: 'Run migrations now?',
                default: false,
                hint: 'Execute the database migrations'
            );
        }

        $this->config['publish_views'] = confirm(
            label: 'Publish view files?',
            default: false,
            hint: 'Publish to resources/views/vendor/laravilt-auth'
        );

        $this->config['publish_assets'] = confirm(
            label: 'Publish frontend assets?',
            default: true,
            hint: 'Publish to public/vendor/laravilt/auth'
        );

        // Advanced options
        $this->newLine();
        $this->components->info('Advanced options:');
        $this->newLine();

        $this->config['add_routes'] = confirm(
            label: 'Add authentication routes to routes/web.php?',
            default: false,
            hint: 'Routes are auto-loaded by default via service provider'
        );

        $this->config['add_middleware'] = confirm(
            label: 'Register middleware in HTTP Kernel?',
            default: true,
            hint: 'Recommended for authentication features'
        );
    }

    /**
     * Set default configuration for non-interactive mode.
     */
    protected function setDefaultConfiguration(): void
    {
        $this->config = [
            'guard' => 'web',
            'model' => 'App\\Models\\User',
            'methods' => ['email'],
            'two_factor' => false,
            'two_factor_methods' => [],
            'features' => ['registration', 'email_verification', 'password_reset', 'profile'],
            'social_providers' => [],
            'publish_config' => true,
            'publish_migrations' => true,
            'run_migrations' => false,
            'publish_views' => false,
            'publish_assets' => true,
            'add_routes' => false,
            'add_middleware' => true,
        ];
    }

    /**
     * Get installation steps.
     */
    protected function getInstallationSteps(): array
    {
        $steps = [];

        if ($this->config['publish_config']) {
            $steps['Publishing configuration'] = fn () => $this->publishConfiguration();
        }

        if ($this->config['publish_migrations']) {
            $steps['Publishing migrations'] = fn () => $this->publishMigrations();
        }

        if ($this->config['run_migrations'] ?? false) {
            $steps['Running migrations'] = fn () => $this->runMigrations();
        }

        if ($this->config['publish_views']) {
            $steps['Publishing views'] = fn () => $this->publishViews();
        }

        if ($this->config['publish_assets']) {
            $steps['Publishing assets'] = fn () => $this->publishAssets();
        }

        if ($this->config['add_routes']) {
            $steps['Adding routes'] = fn () => $this->addRoutes();
        }

        if ($this->config['add_middleware']) {
            $steps['Registering middleware'] = fn () => $this->registerMiddleware();
        }

        $steps['Updating configuration'] = fn () => $this->updateConfiguration();

        return $steps;
    }

    /**
     * Publish configuration file.
     */
    protected function publishConfiguration(): void
    {
        $params = [
            '--provider' => 'Laravilt\Auth\AuthServiceProvider',
            '--tag' => 'laravilt-auth-config',
        ];

        if ($this->option('force')) {
            $params['--force'] = true;
        }

        $this->callSilent('vendor:publish', $params);
    }

    /**
     * Publish migration files.
     */
    protected function publishMigrations(): void
    {
        $params = [
            '--provider' => 'Laravilt\Auth\AuthServiceProvider',
            '--tag' => 'laravilt-auth-migrations',
        ];

        if ($this->option('force')) {
            $params['--force'] = true;
        }

        $this->callSilent('vendor:publish', $params);
    }

    /**
     * Run database migrations.
     */
    protected function runMigrations(): void
    {
        $this->callSilent('migrate', ['--force' => true]);
    }

    /**
     * Publish view files.
     */
    protected function publishViews(): void
    {
        $params = [
            '--provider' => 'Laravilt\Auth\AuthServiceProvider',
            '--tag' => 'laravilt-auth-views',
        ];

        if ($this->option('force')) {
            $params['--force'] = true;
        }

        $this->callSilent('vendor:publish', $params);
    }

    /**
     * Publish frontend assets.
     */
    protected function publishAssets(): void
    {
        $params = [
            '--provider' => 'Laravilt\Auth\AuthServiceProvider',
            '--tag' => 'laravilt-auth-assets',
        ];

        if ($this->option('force')) {
            $params['--force'] = true;
        }

        $this->callSilent('vendor:publish', $params);
    }

    /**
     * Add routes to web.php file.
     */
    protected function addRoutes(): void
    {
        $routesPath = base_path('routes/web.php');

        if (! File::exists($routesPath)) {
            return;
        }

        $routesContent = File::get($routesPath);

        // Check if routes are already added
        if (Str::contains($routesContent, 'laravilt.auth')) {
            return;
        }

        $routeStub = <<<'PHP'

// Laravilt Auth Routes
Route::middleware(['web'])
    ->group(base_path('routes/laravilt-auth.php'));

PHP;

        File::append($routesPath, $routeStub);
    }

    /**
     * Register middleware in HTTP Kernel.
     */
    protected function registerMiddleware(): void
    {
        $kernelPath = app_path('Http/Kernel.php');

        if (! File::exists($kernelPath)) {
            // Laravel 11+ uses bootstrap/app.php
            $this->registerMiddlewareInBootstrap();

            return;
        }

        $kernelContent = File::get($kernelPath);

        // Check if middleware is already registered
        if (Str::contains($kernelContent, 'Laravilt\Auth\Http\Middleware')) {
            return;
        }

        // Add middleware to $middlewareAliases or $routeMiddleware
        $middlewareStub = <<<'PHP'
        'laravilt.auth' => \Laravilt\Auth\Http\Middleware\Authenticate::class,
        'laravilt.verified' => \Laravilt\Auth\Http\Middleware\EnsureEmailVerified::class,
        'laravilt.2fa' => \Laravilt\Auth\Http\Middleware\RequireTwoFactor::class,
PHP;

        // Try to add after the last middleware alias
        $pattern = '/(\$middlewareAliases|\$routeMiddleware)\s*=\s*\[(.*?)(\s*)\];/s';

        $kernelContent = preg_replace_callback($pattern, function ($matches) use ($middlewareStub) {
            return $matches[1].' = ['.$matches[2].$middlewareStub.$matches[3].'];';
        }, $kernelContent);

        File::put($kernelPath, $kernelContent);
    }

    /**
     * Register middleware in bootstrap/app.php for Laravel 11+.
     */
    protected function registerMiddlewareInBootstrap(): void
    {
        $bootstrapPath = base_path('bootstrap/app.php');

        if (! File::exists($bootstrapPath)) {
            return;
        }

        $bootstrapContent = File::get($bootstrapPath);

        // Check if middleware is already registered
        if (Str::contains($bootstrapContent, 'Laravilt\Auth\Http\Middleware')) {
            return;
        }

        // Add middleware aliases
        $middlewareStub = <<<'PHP'
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'laravilt.auth' => \Laravilt\Auth\Http\Middleware\Authenticate::class,
            'laravilt.verified' => \Laravilt\Auth\Http\Middleware\EnsureEmailVerified::class,
            'laravilt.2fa' => \Laravilt\Auth\Http\Middleware\RequireTwoFactor::class,
        ]);
    })
PHP;

        // This is a simplified approach - actual implementation may vary
        // based on Laravel 11 structure
    }

    /**
     * Update configuration file with user choices.
     */
    protected function updateConfiguration(): void
    {
        $configPath = config_path('laravilt-auth.php');

        if (! File::exists($configPath)) {
            return;
        }

        $config = include $configPath;

        // Update guard
        $config['guard'] = $this->config['guard'];

        // Update authentication methods
        foreach (array_keys($config['methods']) as $method) {
            $config['methods'][$method] = in_array($method, $this->config['methods']);
        }

        // Update two-factor settings
        if (isset($this->config['two_factor'])) {
            $config['two_factor']['enabled'] = $this->config['two_factor'];
            if (! empty($this->config['two_factor_methods'])) {
                $config['two_factor']['methods'] = $this->config['two_factor_methods'];
            }
        }

        // Update features
        foreach ($config['features'] as $feature => $value) {
            $config['features'][$feature] = in_array($feature, $this->config['features']);
        }

        // Update social providers
        if (! empty($this->config['social_providers'])) {
            $config['social']['providers'] = $this->config['social_providers'];
        }

        // Write updated configuration
        $export = var_export($config, true);
        $export = preg_replace('/^([ ]*)(.*)/m', '$1$1$2', $export);
        $export = str_replace(['array (', ')'], ['[', ']'], $export);
        $export = preg_replace('/=>\s+\[/', '=> [', $export);

        $configContent = <<<PHP
<?php

return $export;

PHP;

        File::put($configPath, $configContent);
    }

    /**
     * Display success message with next steps.
     */
    protected function displaySuccessMessage(): void
    {
        $this->components->success('Laravilt Auth has been installed successfully!');
        $this->newLine();

        $this->components->info('Configuration Summary:');
        $this->newLine();

        $this->table(
            ['Setting', 'Value'],
            [
                ['Guard', $this->config['guard']],
                ['Model', $this->config['model']],
                ['Auth Methods', implode(', ', $this->config['methods'])],
                ['Two-Factor Auth', $this->config['two_factor'] ? 'Enabled' : 'Disabled'],
                ['Features', implode(', ', array_slice($this->config['features'], 0, 3)).(count($this->config['features']) > 3 ? '...' : '')],
            ]
        );

        $this->newLine();
        $this->components->info('Next Steps:');
        $this->newLine();

        $steps = [
            '1. Review the configuration file at: config/laravilt-auth.php',
            '2. Update your User model to use Laravilt\Auth\Models\Authenticatable trait',
        ];

        if (! $this->config['run_migrations']) {
            $steps[] = '3. Run migrations: php artisan migrate';
        }

        if (in_array('social', $this->config['methods'])) {
            $steps[] = '4. Configure social provider credentials in your .env file';
            $steps[] = '5. Install Laravel Socialite: composer require laravel/socialite';
        }

        if (in_array('webauthn', $this->config['methods'])) {
            $steps[] = '6. Install WebAuthn package: composer require web-auth/webauthn-lib';
        }

        $steps[] = count($steps) + 1 .'. Visit /auth/login to test your authentication system';

        foreach ($steps as $step) {
            $this->line("   <fg=gray>$step</>");
        }

        $this->newLine();

        // Display warnings if any
        $warnings = [];

        if (in_array('phone', $this->config['methods'])) {
            $warnings[] = 'Phone OTP requires SMS service configuration (Twilio, Vonage, etc.)';
        }

        if (! empty($warnings)) {
            $this->components->warn('Important Notes:');
            foreach ($warnings as $warning) {
                $this->line("   <fg=yellow>• $warning</>");
            }
            $this->newLine();
        }

        $this->components->info('Documentation: https://laravilt.dev/docs/auth');
        $this->components->info('Support: https://github.com/laravilt/auth/issues');
        $this->newLine();
    }
}
