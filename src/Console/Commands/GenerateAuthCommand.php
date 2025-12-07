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
 * Generate a new auth provider configuration.
 *
 * This command generates authentication provider configuration code that can be
 * used to set up custom authentication guards and providers in your Laravel application.
 */
class GenerateAuthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravilt:auth:generate
                            {name? : The name of the auth provider}
                            {--guard= : The guard name to use}
                            {--model= : The user model class}
                            {--methods=* : Authentication methods to enable}
                            {--output= : Output file path (optional)}
                            {--add-to-config : Add configuration to config/auth.php}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a new auth provider configuration';

    /**
     * Configuration for the auth provider.
     */
    protected array $config = [];

    /**
     * Generated code sections.
     */
    protected array $generatedCode = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('⚡ Laravilt Auth Provider Generator');
        $this->newLine();

        // Gather configuration
        $this->gatherConfiguration();

        // Validate configuration
        if (! $this->validateConfiguration()) {
            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('Generating auth provider configuration...');
        $this->newLine();

        // Generate code
        $this->generateProviderConfiguration();
        $this->generateGuardConfiguration();
        $this->generateMethodConfiguration();
        $this->generateServiceProviderCode();

        // Display generated code
        $this->displayGeneratedCode();

        // Optionally save to file or add to config
        $this->handleOutput();

        $this->newLine();
        $this->components->success('Auth provider configuration generated successfully!');

        return self::SUCCESS;
    }

    /**
     * Gather configuration from user input.
     */
    protected function gatherConfiguration(): void
    {
        // Provider name
        $this->config['name'] = $this->argument('name') ?? text(
            label: 'Provider name',
            placeholder: 'custom-auth',
            required: true,
            validate: fn ($value) => match (true) {
                empty($value) => 'Provider name is required',
                ! preg_match('/^[a-z0-9_-]+$/', $value) => 'Provider name must contain only lowercase letters, numbers, dashes, and underscores',
                default => null
            },
            hint: 'e.g., admin-auth, api-auth, custom-auth'
        );

        // Guard name
        $this->config['guard'] = $this->option('guard') ?? select(
            label: 'Guard type',
            options: [
                'session' => 'Session (Web)',
                'token' => 'Token (API)',
                'sanctum' => 'Sanctum (SPA/API)',
                'jwt' => 'JWT (API)',
                'custom' => 'Custom',
            ],
            default: 'session',
            hint: 'The authentication guard driver'
        );

        if ($this->config['guard'] === 'custom') {
            $this->config['guard'] = text(
                label: 'Enter guard driver name',
                placeholder: 'my-guard',
                required: true
            );
        }

        // Model class
        $this->config['model'] = $this->option('model') ?? text(
            label: 'User model class',
            default: 'App\\Models\\User',
            required: true,
            hint: 'Fully qualified class name'
        );

        // Authentication methods
        $providedMethods = $this->option('methods');

        if (empty($providedMethods)) {
            $this->config['methods'] = multiselect(
                label: 'Authentication methods to enable',
                options: [
                    'email' => 'Email & Password',
                    'phone' => 'Phone & OTP',
                    'social' => 'Social Login',
                    'passwordless' => 'Passwordless (Magic Links)',
                    'webauthn' => 'WebAuthn (Biometric)',
                    '2fa' => 'Two-Factor Authentication',
                ],
                default: ['email'],
                required: true,
                hint: 'Select one or more methods'
            );
        } else {
            $this->config['methods'] = $providedMethods;
        }

        // Additional options
        $this->config['table'] = text(
            label: 'Database table name',
            default: 'users',
            required: true,
            hint: 'The table used for authentication'
        );

        $this->config['remember'] = confirm(
            label: 'Enable "Remember Me" functionality?',
            default: true,
            hint: 'Allows persistent login sessions'
        );

        if (in_array('social', $this->config['methods'])) {
            $this->config['social_providers'] = multiselect(
                label: 'Social providers to support',
                options: [
                    'google' => 'Google',
                    'github' => 'GitHub',
                    'facebook' => 'Facebook',
                    'twitter' => 'Twitter',
                    'linkedin' => 'LinkedIn',
                    'microsoft' => 'Microsoft',
                ],
                default: ['google', 'github'],
                required: true
            );
        }

        if (in_array('2fa', $this->config['methods'])) {
            $this->config['2fa_providers'] = multiselect(
                label: '2FA providers to support',
                options: [
                    'totp' => 'TOTP (Authenticator Apps)',
                    'sms' => 'SMS',
                    'email' => 'Email',
                ],
                default: ['totp'],
                required: true
            );
        }
    }

    /**
     * Validate the configuration.
     */
    protected function validateConfiguration(): bool
    {
        // Validate model class exists
        if (! class_exists($this->config['model']) && ! Str::startsWith($this->config['model'], 'App\\')) {
            $this->components->error("Model class '{$this->config['model']}' may not exist.");

            if (! confirm('Continue anyway?', false)) {
                return false;
            }
        }

        // Validate guard driver
        $validGuards = ['session', 'token', 'sanctum', 'jwt', 'passport'];
        if (! in_array($this->config['guard'], $validGuards) && $this->config['guard'] !== 'custom') {
            $this->components->warn("Guard driver '{$this->config['guard']}' may require additional configuration.");
        }

        return true;
    }

    /**
     * Generate provider configuration code.
     */
    protected function generateProviderConfiguration(): void
    {
        $name = $this->config['name'];
        $model = $this->config['model'];
        $table = $this->config['table'];

        $this->generatedCode['provider'] = <<<PHP
'providers' => [
    // ...existing providers

    '{$name}' => [
        'driver' => 'eloquent',
        'model' => {$model}::class,
        'table' => '{$table}',
    ],
],
PHP;
    }

    /**
     * Generate guard configuration code.
     */
    protected function generateGuardConfiguration(): void
    {
        $name = $this->config['name'];
        $guard = $this->config['guard'];
        $provider = $this->config['name'];

        $guardConfig = match ($guard) {
            'session' => <<<PHP
    '{$name}' => [
        'driver' => 'session',
        'provider' => '{$provider}',
    ],
PHP,
            'token' => <<<PHP
    '{$name}' => [
        'driver' => 'token',
        'provider' => '{$provider}',
        'storage_key' => 'api_token',
        'hash' => false,
    ],
PHP,
            'sanctum' => <<<PHP
    '{$name}' => [
        'driver' => 'sanctum',
        'provider' => '{$provider}',
    ],
PHP,
            'jwt' => <<<PHP
    '{$name}' => [
        'driver' => 'jwt',
        'provider' => '{$provider}',
    ],
PHP,
            default => <<<PHP
    '{$name}' => [
        'driver' => '{$guard}',
        'provider' => '{$provider}',
    ],
PHP,
        };

        $this->generatedCode['guard'] = <<<PHP
'guards' => [
    // ...existing guards

{$guardConfig}
],
PHP;
    }

    /**
     * Generate authentication methods configuration.
     */
    protected function generateMethodConfiguration(): void
    {
        $name = $this->config['name'];
        $methods = [];

        foreach ($this->config['methods'] as $method) {
            $methods[$method] = true;
        }

        $methodsArray = var_export($methods, true);
        $methodsArray = str_replace(['array (', ')'], ['[', ']'], $methodsArray);

        $socialConfig = '';
        if (in_array('social', $this->config['methods']) && ! empty($this->config['social_providers'])) {
            $socialProviders = var_export($this->config['social_providers'], true);
            $socialProviders = str_replace(['array (', ')'], ['[', ']'], $socialProviders);
            $socialConfig = <<<PHP

    'social' => [
        'providers' => {$socialProviders},
    ],
PHP;
        }

        $twoFactorConfig = '';
        if (in_array('2fa', $this->config['methods']) && ! empty($this->config['2fa_providers'])) {
            $twoFactorProviders = var_export($this->config['2fa_providers'], true);
            $twoFactorProviders = str_replace(['array (', ')'], ['[', ']'], $twoFactorProviders);
            $twoFactorConfig = <<<PHP

    'two_factor' => [
        'enabled' => true,
        'methods' => {$twoFactorProviders},
    ],
PHP;
        }

        $this->generatedCode['methods'] = <<<PHP
// In config/laravilt-auth.php or your custom config file

'{$name}' => [
    'guard' => '{$name}',
    'methods' => {$methodsArray},{$socialConfig}{$twoFactorConfig}
],
PHP;
    }

    /**
     * Generate service provider registration code.
     */
    protected function generateServiceProviderCode(): void
    {
        $name = $this->config['name'];
        $guard = $this->config['guard'];
        $model = $this->config['model'];

        $this->generatedCode['service_provider'] = <<<PHP
// In your AppServiceProvider or custom AuthServiceProvider

use Illuminate\Support\Facades\Auth;
use Laravilt\Auth\Guards\LaraviltGuard;
use Laravilt\Auth\Providers\LaraviltAuthProvider;

public function boot(): void
{
    // Register custom auth provider
    Auth::provider('{$name}', function (\$app, array \$config) {
        return new LaraviltAuthProvider(
            \$app['hash'],
            \$config['model']
        );
    });

    // Register custom auth guard (if using custom guard)
    Auth::extend('{$name}', function (\$app, string \$name, array \$config) {
        return new LaraviltGuard(
            \$name,
            Auth::createUserProvider(\$config['provider']),
            \$app['session.store'],
            \$app['request']
        );
    });
}
PHP;
    }

    /**
     * Display the generated code.
     */
    protected function displayGeneratedCode(): void
    {
        $this->components->info('Generated Configuration:');
        $this->newLine();

        // Display guard configuration
        $this->components->twoColumnDetail(
            '<fg=cyan>Step 1: Add to config/auth.php</>',
            '<fg=gray>Guards</>'
        );
        $this->newLine();
        $this->line($this->formatCodeBlock($this->generatedCode['guard']));
        $this->newLine();

        // Display provider configuration
        $this->components->twoColumnDetail(
            '<fg=cyan>Step 2: Add to config/auth.php</>',
            '<fg=gray>Providers</>'
        );
        $this->newLine();
        $this->line($this->formatCodeBlock($this->generatedCode['provider']));
        $this->newLine();

        // Display methods configuration
        $this->components->twoColumnDetail(
            '<fg=cyan>Step 3: Add to config/laravilt-auth.php</>',
            '<fg=gray>Methods</>'
        );
        $this->newLine();
        $this->line($this->formatCodeBlock($this->generatedCode['methods']));
        $this->newLine();

        // Display service provider code
        $this->components->twoColumnDetail(
            '<fg=cyan>Step 4: Register in Service Provider</>',
            '<fg=gray>Optional</>'
        );
        $this->newLine();
        $this->line($this->formatCodeBlock($this->generatedCode['service_provider']));
        $this->newLine();

        // Display usage example
        $this->displayUsageExample();
    }

    /**
     * Display usage examples.
     */
    protected function displayUsageExample(): void
    {
        $name = $this->config['name'];
        $guard = $this->config['guard'];

        $this->components->info('Usage Example:');
        $this->newLine();

        $usageCode = <<<PHP
// In your controllers or routes

use Illuminate\Support\Facades\Auth;
use Laravilt\Auth\Facades\Auth as LaraviltAuth;

// Using Laravel's Auth facade
\$user = Auth::guard('{$name}')->user();

// Authenticate user
if (Auth::guard('{$name}')->attempt(\$credentials)) {
    // Authentication passed
}

// Using Laravilt Auth facade with methods
LaraviltAuth::guard('{$name}')
    ->method('email')
    ->attempt(\$credentials);

// Logout
Auth::guard('{$name}')->logout();

// Check authentication
if (Auth::guard('{$name}')->check()) {
    // User is authenticated
}

// In routes (middleware)
Route::middleware('auth:{$name}')->group(function () {
    // Protected routes
});
PHP;

        $this->line($this->formatCodeBlock($usageCode));
        $this->newLine();
    }

    /**
     * Handle output options (save to file or add to config).
     */
    protected function handleOutput(): void
    {
        $outputPath = $this->option('output');

        if ($outputPath) {
            $this->saveToFile($outputPath);

            return;
        }

        if ($this->option('add-to-config')) {
            $this->addToConfig();

            return;
        }

        // Ask user what to do
        $action = select(
            label: 'What would you like to do with the generated code?',
            options: [
                'copy' => 'Copy to clipboard (manual integration)',
                'file' => 'Save to file',
                'config' => 'Add to config/auth.php automatically',
                'nothing' => 'Nothing (just display)',
            ],
            default: 'copy',
            hint: 'Choose how to use the generated configuration'
        );

        match ($action) {
            'file' => $this->saveToFile(),
            'config' => $this->addToConfig(),
            'copy' => $this->components->info('Copy the code above and paste it into your configuration files.'),
            'nothing' => null,
        };
    }

    /**
     * Save generated code to file.
     */
    protected function saveToFile(?string $path = null): void
    {
        if (! $path) {
            $path = text(
                label: 'Enter file path',
                placeholder: 'storage/auth-config.php',
                default: "storage/auth-{$this->config['name']}.php",
                required: true
            );
        }

        $fullPath = base_path($path);
        $directory = dirname($fullPath);

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $content = <<<PHP
<?php

/**
 * Generated Auth Provider Configuration
 * Provider: {$this->config['name']}
 * Generated: {$this->currentDateTime()}
 */

// Step 1: Add to config/auth.php
{$this->generatedCode['guard']}

{$this->generatedCode['provider']}

// Step 2: Add to config/laravilt-auth.php
{$this->generatedCode['methods']}

// Step 3: Register in Service Provider (Optional)
{$this->generatedCode['service_provider']}

PHP;

        File::put($fullPath, $content);

        $this->components->success("Configuration saved to: {$path}");
    }

    /**
     * Add configuration to config/auth.php automatically.
     */
    protected function addToConfig(): void
    {
        $configPath = config_path('auth.php');

        if (! File::exists($configPath)) {
            $this->components->error('config/auth.php not found!');

            return;
        }

        if (! confirm('This will modify config/auth.php. Continue?', false)) {
            return;
        }

        $this->components->warn('Automatic config modification is experimental.');
        $this->components->info('It\'s recommended to manually add the configuration.');
        $this->newLine();

        // Create backup
        $backupPath = config_path('auth.php.backup');
        File::copy($configPath, $backupPath);
        $this->components->info('Backup created at: config/auth.php.backup');

        $this->components->warn('Please manually add the configuration from the output above.');
    }

    /**
     * Format code block for display.
     */
    protected function formatCodeBlock(string $code): string
    {
        $lines = explode("\n", $code);
        $formatted = [];

        foreach ($lines as $line) {
            $formatted[] = "  <fg=gray>│</> <fg=white>{$line}</>";
        }

        return implode("\n", $formatted);
    }

    /**
     * Get current date and time.
     */
    protected function currentDateTime(): string
    {
        return now()->format('Y-m-d H:i:s');
    }
}
