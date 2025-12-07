# Laravilt Auth Package

The Laravilt Auth package provides a comprehensive, event-driven authentication system for Laravel applications with support for multiple authentication methods and seamless Inertia.js integration.

## Table of Contents

1. [Installation](#installation)
2. [Features](#features)
3. [Configuration](#configuration)
4. [Authentication Methods](#authentication-methods)
5. [Events System](#events-system)
6. [Customization](#customization)
7. [Testing](#testing)

## Installation

```bash
composer require laravilt/auth
```

### Publish Configuration

```bash
php artisan vendor:publish --tag="laravilt-auth-config"
```

### Run Migrations

```bash
php artisan migrate
```

## Features

- ✅ **Traditional Email/Password Authentication**
- ✅ **Email Verification with OTP**
- ✅ **Two-Factor Authentication (TOTP & Email)**
- ✅ **Social OAuth Login** (Google, Facebook, GitHub, etc.)
- ✅ **Passkeys (WebAuthn)**
- ✅ **Magic Links**
- ✅ **Password Reset**
- ✅ **Comprehensive Event System**
- ✅ **Fully Customizable Pages**
- ✅ **Inertia.js Integration**

## Configuration

### Panel Service Provider

Register the auth features in your `PanelServiceProvider`:

```php
use Laravilt\Panel\Panel;

public function boot(): void
{
    Panel::make('admin')
        ->path('/admin')
        ->login()                    // Enable login
        ->registration()             // Enable registration
        ->emailVerification()        // Enable email verification
        ->otp()                      // Enable OTP authentication
        ->passwordReset()            // Enable password reset
        ->twoFactor()                // Enable 2FA
        ->socialLogin()              // Enable social login
        ->passkeys()                 // Enable passkeys
        ->magicLinks()               // Enable magic links
        ->register();
}
```

### Social Providers

Configure social authentication providers:

```php
Panel::make('admin')
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

### Two-Factor Authentication

Configure 2FA providers:

```php
Panel::make('admin')
    ->twoFactor([
        'totp' => [
            'driver' => 'totp',
            'label' => 'Authenticator App',
            'enabled' => true,
        ],
        'email' => [
            'driver' => 'email',
            'label' => 'Email',
            'enabled' => true,
        ],
    ])
    ->register();
```

## Authentication Methods

### 1. Traditional Login

```php
// Users can log in with email and password
POST /admin/login
{
    "email": "user@example.com",
    "password": "password",
    "remember": true
}
```

### 2. Registration with OTP

When both `emailVerification()` and `otp()` are enabled:

```php
// Step 1: Register
POST /admin/register
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password",
    "password_confirmation": "password"
}

// Step 2: Verify OTP
POST /admin/otp/verify
{
    "code": "123456"
}
```

### 3. Two-Factor Authentication

```php
// Step 1: Enable 2FA
POST /admin/two-factor/enable
{
    "method": "totp" // or "email"
}

// Step 2: Confirm with code
POST /admin/two-factor/confirm
{
    "code": "123456"
}

// During login, users with 2FA will be redirected to:
POST /admin/two-factor/challenge
{
    "code": "123456"
}
```

### 4. Social Authentication

```php
// Redirect to provider
GET /admin/auth/social/{provider}/redirect

// Callback from provider
GET /admin/auth/social/{provider}/callback
```

### 5. Passkeys (WebAuthn)

```php
// Register passkey
GET /admin/passkey/register-options
POST /admin/passkey/register
{
    "name": "My MacBook Pro",
    "credential": {...}
}

// Login with passkey
GET /admin/passkey/login-options
POST /admin/passkey/login
{
    "credential": {...}
}
```

### 6. Magic Links

```php
// Send magic link
POST /admin/magic-link/send

// Verify magic link (automatic via email link)
GET /admin/magic-link/verify/{token}
```

### 7. Password Reset

```php
// Request reset link
POST /admin/password/email
{
    "email": "user@example.com"
}

// Reset password
POST /admin/password/reset
{
    "token": "...",
    "email": "user@example.com",
    "password": "newpassword",
    "password_confirmation": "newpassword"
}
```

## Events System

The auth package dispatches comprehensive events for all authentication activities, enabling audit logging, custom workflows, and security monitoring.

### Available Events

#### Login Events

```php
use Laravilt\Auth\Events\LoginAttempt;
use Laravilt\Auth\Events\LoginSuccessful;
use Laravilt\Auth\Events\LoginFailed;

// Listen to events
Event::listen(LoginAttempt::class, function ($event) {
    // $event->email
    // $event->panelId
    // $event->ipAddress
    // $event->userAgent
});

Event::listen(LoginSuccessful::class, function ($event) {
    // $event->user
    // $event->panelId
    // $event->remember
});

Event::listen(LoginFailed::class, function ($event) {
    // $event->email
    // $event->panelId
    // $event->reason ('invalid_credentials', etc.)
});
```

#### Registration Events

```php
use Laravilt\Auth\Events\RegistrationAttempt;
use Laravilt\Auth\Events\RegistrationCompleted;

Event::listen(RegistrationAttempt::class, function ($event) {
    // $event->data (registration data)
    // $event->panelId
});

Event::listen(RegistrationCompleted::class, function ($event) {
    // $event->user
    // $event->panelId
    // $event->requiresOtp (boolean)
});
```

#### OTP Events

```php
use Laravilt\Auth\Events\OtpSent;
use Laravilt\Auth\Events\OtpVerified;
use Laravilt\Auth\Events\OtpFailed;

Event::listen(OtpSent::class, function ($event) {
    // $event->user
    // $event->code
    // $event->purpose ('registration', 'login', etc.)
    // $event->expiresAt
    // $event->panelId
});

Event::listen(OtpVerified::class, function ($event) {
    // $event->user
    // $event->purpose
    // $event->panelId
});

Event::listen(OtpFailed::class, function ($event) {
    // $event->user
    // $event->code
    // $event->purpose
    // $event->reason ('invalid_or_expired', 'session_expired')
    // $event->panelId
});
```

#### Two-Factor Authentication Events

```php
use Laravilt\Auth\Events\TwoFactorEnabled;
use Laravilt\Auth\Events\TwoFactorDisabled;
use Laravilt\Auth\Events\TwoFactorChallengeSuccessful;
use Laravilt\Auth\Events\TwoFactorChallengeFailed;

Event::listen(TwoFactorEnabled::class, function ($event) {
    // $event->user
    // $event->method ('totp', 'email')
    // $event->panelId
});

Event::listen(TwoFactorDisabled::class, function ($event) {
    // $event->user
    // $event->method
    // $event->panelId
});

Event::listen(TwoFactorChallengeSuccessful::class, function ($event) {
    // $event->user
    // $event->panelId
});

Event::listen(TwoFactorChallengeFailed::class, function ($event) {
    // $event->user (may be null)
    // $event->reason ('invalid_code', 'user_not_found')
    // $event->panelId
});
```

#### Password Reset Events

```php
use Laravilt\Auth\Events\PasswordResetRequested;
use Laravilt\Auth\Events\PasswordReset;

Event::listen(PasswordResetRequested::class, function ($event) {
    // $event->email
    // $event->panelId
});

Event::listen(PasswordReset::class, function ($event) {
    // $event->user
    // $event->panelId
});
```

#### Social Authentication Events

```php
use Laravilt\Auth\Events\SocialAuthenticationAttempt;
use Laravilt\Auth\Events\SocialAuthenticationSuccessful;

Event::listen(SocialAuthenticationAttempt::class, function ($event) {
    // $event->provider ('google', 'github', etc.)
    // $event->email
    // $event->providerId
    // $event->panelId
});

Event::listen(SocialAuthenticationSuccessful::class, function ($event) {
    // $event->user
    // $event->provider
    // $event->panelId
});
```

#### Passkey Events

```php
use Laravilt\Auth\Events\PasskeyRegistered;
use Laravilt\Auth\Events\PasskeyDeleted;

Event::listen(PasskeyRegistered::class, function ($event) {
    // $event->user
    // $event->credentialId
    // $event->panelId
});

Event::listen(PasskeyDeleted::class, function ($event) {
    // $event->user
    // $event->credentialId
    // $event->panelId
});
```

#### Magic Link Events

```php
use Laravilt\Auth\Events\MagicLinkSent;
use Laravilt\Auth\Events\MagicLinkVerified;

Event::listen(MagicLinkSent::class, function ($event) {
    // $event->user
    // $event->url
    // $event->expiresAt
    // $event->panelId
});

Event::listen(MagicLinkVerified::class, function ($event) {
    // $event->user
    // $event->panelId
});
```

### Creating Event Listeners

Create a listener class:

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

        // You could also:
        // - Send a notification
        // - Check for suspicious activity
        // - Update last login timestamp
        // - Track login analytics
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

## Customization

### Custom Authentication Pages

You can replace any auth page with your own custom page:

```php
use App\Pages\CustomLoginPage;

Panel::make('admin')
    ->login(page: CustomLoginPage::class)
    ->register();
```

Your custom page should extend the base auth page:

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
            'customProp' => 'custom value',
        ]);
    }
}
```

### Custom User Model

Specify a custom user model in your auth guard configuration:

```php
// config/auth.php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\CustomUser::class,
    ],
],
```

### Custom Middleware

Add custom middleware to auth routes:

```php
Panel::make('admin')
    ->middleware(['web', 'custom-middleware'])
    ->register();
```

## Testing

The package includes comprehensive tests for all authentication features:

```bash
# Run all auth tests
cd packages/laravilt/auth
composer test

# Run specific test suite
vendor/bin/pest tests/Feature/LoginTest.php
vendor/bin/pest tests/Feature/EventsTest.php
```

### Example Test

```php
use Laravilt\Auth\Events\LoginSuccessful;

test('it dispatches login successful event', function () {
    Event::fake();

    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    Event::assertDispatched(LoginSuccessful::class, function ($event) use ($user) {
        return $event->user->id === $user->id;
    });
});
```

## Security Best Practices

1. **Enable HTTPS**: Always use HTTPS in production
2. **Rate Limiting**: Configure rate limiting for authentication endpoints
3. **Strong Passwords**: Use Laravel's password validation rules
4. **Two-Factor Authentication**: Encourage users to enable 2FA
5. **Event Monitoring**: Monitor authentication events for suspicious activity
6. **Session Security**: Configure secure session settings

## Support

For issues, questions, or contributions:
- GitHub: https://github.com/laravilt/auth
- Documentation: https://laravilt.com/docs/auth
- Discord: https://discord.gg/laravilt
