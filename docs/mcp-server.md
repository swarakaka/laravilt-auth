# MCP Server Integration

The Laravilt Auth package can be integrated with MCP (Model Context Protocol) server for AI agent interaction.

## Available Commands

### auth:install
Install the Auth package with all necessary components.

**Usage:**
```bash
php artisan auth:install
php artisan auth:install --force
php artisan auth:install --without-assets
php artisan auth:install --without-migrations
```

**Options:**
- `--force`: Overwrite existing files
- `--without-assets`: Skip asset publishing
- `--without-migrations`: Skip running migrations
- `--without-seeders`: Skip running seeders

**What It Installs:**
- Configuration files
- Assets (Vue components, styles)
- Database migrations
- Seeders (optional)

## Integration Example

MCP server tools should provide:

1. **search_auth_docs** - Search authentication documentation
2. **get_auth_config** - Get auth configuration for specific features
3. **list_auth_events** - List available authentication events
4. **get_event_example** - Get example listener code for events
5. **get_auth_page_example** - Get custom page examples
6. **get_social_provider_config** - Get social provider configuration

## Panel Configuration Reference

### Basic Authentication
```php
Panel::make('admin')
    ->path('/admin')
    ->login()                    // Enable login page
    ->registration()             // Enable registration
    ->passwordReset()            // Enable password reset
    ->register();
```

### Email Verification with OTP
```php
Panel::make('admin')
    ->path('/admin')
    ->login()
    ->registration()
    ->emailVerification()        // Require email verification
    ->otp()                      // Use OTP codes for verification
    ->register();
```

### Two-Factor Authentication
```php
Panel::make('admin')
    ->path('/admin')
    ->login()
    ->twoFactor([
        'totp' => [
            'driver' => 'totp',
            'label' => 'Authenticator App',
            'enabled' => true,
        ],
        'email' => [
            'driver' => 'email',
            'label' => 'Email Code',
            'enabled' => true,
        ],
    ])
    ->register();
```

### Social Authentication
```php
Panel::make('admin')
    ->path('/admin')
    ->login()
    ->socialLogin([
        'google' => [
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect' => env('APP_URL') . '/auth/google/callback',
        ],
        'github' => [
            'client_id' => env('GITHUB_CLIENT_ID'),
            'client_secret' => env('GITHUB_CLIENT_SECRET'),
            'redirect' => env('APP_URL') . '/auth/github/callback',
        ],
    ])
    ->register();
```

### Passkeys (WebAuthn)
```php
Panel::make('admin')
    ->path('/admin')
    ->login()
    ->passkeys()                 // Enable passkey authentication
    ->register();
```

### Magic Links
```php
Panel::make('admin')
    ->path('/admin')
    ->login()
    ->magicLinks()               // Enable magic link authentication
    ->register();
```

### Full Configuration
```php
Panel::make('admin')
    ->path('/admin')
    ->login()
    ->registration()
    ->emailVerification()
    ->otp()
    ->passwordReset()
    ->twoFactor([
        'totp' => ['enabled' => true],
        'email' => ['enabled' => true],
    ])
    ->socialLogin([
        'google' => [...],
        'github' => [...],
    ])
    ->passkeys()
    ->magicLinks()
    ->register();
```

## Events Reference

### Login Events
| Event | Description | Properties |
|-------|-------------|------------|
| `LoginAttempt` | User attempted to log in | `email`, `panelId`, `ipAddress`, `userAgent` |
| `LoginSuccessful` | User logged in successfully | `user`, `panelId`, `remember`, `ipAddress` |
| `LoginFailed` | Login attempt failed | `email`, `panelId`, `reason`, `ipAddress` |

### Registration Events
| Event | Description | Properties |
|-------|-------------|------------|
| `RegistrationAttempt` | User attempted to register | `data`, `panelId` |
| `RegistrationCompleted` | User registered successfully | `user`, `panelId`, `requiresOtp` |

### OTP Events
| Event | Description | Properties |
|-------|-------------|------------|
| `OtpSent` | OTP code was sent | `user`, `code`, `purpose`, `expiresAt`, `panelId` |
| `OtpVerified` | OTP code was verified | `user`, `purpose`, `panelId` |
| `OtpFailed` | OTP verification failed | `user`, `code`, `purpose`, `reason`, `panelId` |

### Two-Factor Events
| Event | Description | Properties |
|-------|-------------|------------|
| `TwoFactorEnabled` | 2FA was enabled | `user`, `method`, `panelId` |
| `TwoFactorDisabled` | 2FA was disabled | `user`, `method`, `panelId` |
| `TwoFactorChallengeSuccessful` | 2FA challenge passed | `user`, `panelId` |
| `TwoFactorChallengeFailed` | 2FA challenge failed | `user`, `reason`, `panelId` |

### Password Events
| Event | Description | Properties |
|-------|-------------|------------|
| `PasswordResetRequested` | Password reset requested | `email`, `panelId` |
| `PasswordReset` | Password was reset | `user`, `panelId` |

### Social Auth Events
| Event | Description | Properties |
|-------|-------------|------------|
| `SocialAuthenticationAttempt` | Social login attempted | `provider`, `email`, `providerId`, `panelId` |
| `SocialAuthenticationSuccessful` | Social login succeeded | `user`, `provider`, `panelId` |

### Passkey Events
| Event | Description | Properties |
|-------|-------------|------------|
| `PasskeyRegistered` | Passkey was registered | `user`, `credentialId`, `panelId` |
| `PasskeyDeleted` | Passkey was deleted | `user`, `credentialId`, `panelId` |

### Magic Link Events
| Event | Description | Properties |
|-------|-------------|------------|
| `MagicLinkSent` | Magic link was sent | `user`, `url`, `expiresAt`, `panelId` |
| `MagicLinkVerified` | Magic link was verified | `user`, `panelId` |

## Event Listener Example

```php
namespace App\Listeners;

use Laravilt\Auth\Events\LoginSuccessful;

class LogSuccessfulLogin
{
    public function handle(LoginSuccessful $event): void
    {
        \Log::info('User logged in', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'panel' => $event->panelId,
            'ip' => $event->ipAddress,
            'remember' => $event->remember,
        ]);

        // Update last login timestamp
        $event->user->update(['last_login_at' => now()]);
    }
}
```

Register in `EventServiceProvider`:
```php
protected $listen = [
    LoginSuccessful::class => [
        LogSuccessfulLogin::class,
    ],
];
```

## Custom Page Example

```php
namespace App\Pages;

use Laravilt\Auth\Pages\Login as BaseLogin;

class CustomLoginPage extends BaseLogin
{
    protected ?string $component = 'CustomLoginPage';

    public function getHeading(): string
    {
        return 'Welcome Back!';
    }

    public function getSubheading(): ?string
    {
        return 'Please sign in to continue';
    }

    protected function getInertiaProps(): array
    {
        return array_merge(parent::getInertiaProps(), [
            'companyLogo' => asset('images/logo.png'),
            'features' => [
                'Secure login',
                'Two-factor authentication',
                'Password reset',
            ],
        ]);
    }
}
```

Use in panel configuration:
```php
Panel::make('admin')
    ->login(page: CustomLoginPage::class)
    ->register();
```

## API Endpoints

### Login Flow
```
POST /admin/login                     # Traditional login
POST /admin/register                  # User registration
POST /admin/otp/verify                # Verify OTP code
POST /admin/two-factor/challenge      # Two-factor challenge
```

### Password Reset
```
POST /admin/password/email            # Request reset link
POST /admin/password/reset            # Reset password
```

### Social Authentication
```
GET  /admin/auth/social/{provider}/redirect   # Redirect to provider
GET  /admin/auth/social/{provider}/callback   # Callback from provider
```

### Passkeys
```
GET  /admin/passkey/register-options  # Get registration options
POST /admin/passkey/register          # Register passkey
GET  /admin/passkey/login-options     # Get login options
POST /admin/passkey/login             # Login with passkey
```

### Magic Links
```
POST /admin/magic-link/send           # Send magic link
GET  /admin/magic-link/verify/{token} # Verify magic link
```

### Two-Factor Management
```
POST /admin/two-factor/enable         # Enable 2FA
POST /admin/two-factor/confirm        # Confirm 2FA setup
POST /admin/two-factor/disable        # Disable 2FA
```

## Security

The MCP server runs with the same permissions as your Laravel application. Ensure:
- Proper rate limiting on authentication endpoints
- Secure session configuration
- HTTPS in production
- Proper CORS configuration for passkeys
- Secure storage of social provider credentials
