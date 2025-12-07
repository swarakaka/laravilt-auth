<?php

namespace Laravilt\Auth\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetEventInfoTool extends Tool
{
    protected string $description = 'Get detailed information about a specific authentication event including properties, usage, and examples';

    protected array $events = [
        'LoginAttempt' => [
            'description' => 'Fired when a user attempts to log in',
            'properties' => ['email', 'panelId', 'ipAddress', 'userAgent'],
            'example' => "LoginAttempt::dispatch('user@example.com', 'admin');",
            'category' => 'Standard Authentication',
        ],
        'LoginSuccessful' => [
            'description' => 'Fired when login is successful',
            'properties' => ['user', 'panelId', 'remember', 'ipAddress', 'userAgent'],
            'example' => "LoginSuccessful::dispatch(\$user, 'admin', true);",
            'category' => 'Standard Authentication',
        ],
        'LoginFailed' => [
            'description' => 'Fired when login fails',
            'properties' => ['email', 'panelId', 'reason', 'ipAddress', 'userAgent'],
            'example' => "LoginFailed::dispatch('user@example.com', 'admin', 'invalid_credentials');",
            'category' => 'Standard Authentication',
        ],
        'RegistrationAttempt' => [
            'description' => 'Fired when registration is attempted',
            'properties' => ['data', 'panelId', 'ipAddress', 'userAgent'],
            'example' => "RegistrationAttempt::dispatch(['email' => 'user@example.com'], 'admin');",
            'category' => 'Registration',
        ],
        'RegistrationCompleted' => [
            'description' => 'Fired when registration completes successfully',
            'properties' => ['user', 'panelId', 'requiresOtpVerification', 'ipAddress', 'userAgent'],
            'example' => "RegistrationCompleted::dispatch(\$user, 'admin', false);",
            'category' => 'Registration',
        ],
        'OtpSent' => [
            'description' => 'Fired when OTP code is sent',
            'properties' => ['user', 'code', 'purpose', 'expiresAt', 'panelId'],
            'example' => "OtpSent::dispatch(\$user, '123456', 'registration', now()->addMinutes(5), 'admin');",
            'category' => 'OTP Verification',
        ],
        'OtpVerified' => [
            'description' => 'Fired when OTP is verified successfully',
            'properties' => ['user', 'purpose', 'panelId'],
            'example' => "OtpVerified::dispatch(\$user, 'registration', 'admin');",
            'category' => 'OTP Verification',
        ],
        'OtpFailed' => [
            'description' => 'Fired when OTP verification fails',
            'properties' => ['identifier', 'purpose', 'reason', 'panelId'],
            'example' => "OtpFailed::dispatch('user@example.com', 'registration', 'invalid_code', 'admin');",
            'category' => 'OTP Verification',
        ],
        'TwoFactorEnabled' => [
            'description' => 'Fired when 2FA is enabled',
            'properties' => ['user', 'method', 'panelId'],
            'example' => "TwoFactorEnabled::dispatch(\$user, 'totp', 'admin');",
            'category' => 'Two-Factor Authentication',
        ],
        'TwoFactorDisabled' => [
            'description' => 'Fired when 2FA is disabled',
            'properties' => ['user', 'panelId'],
            'example' => "TwoFactorDisabled::dispatch(\$user, 'admin');",
            'category' => 'Two-Factor Authentication',
        ],
        'TwoFactorChallengeFailed' => [
            'description' => 'Fired when 2FA challenge fails',
            'properties' => ['user', 'reason', 'panelId'],
            'example' => "TwoFactorChallengeFailed::dispatch(\$user, 'invalid_code', 'admin');",
            'category' => 'Two-Factor Authentication',
        ],
        'TwoFactorChallengeSuccessful' => [
            'description' => 'Fired when 2FA challenge succeeds',
            'properties' => ['user', 'method', 'panelId'],
            'example' => "TwoFactorChallengeSuccessful::dispatch(\$user, 'totp', 'admin');",
            'category' => 'Two-Factor Authentication',
        ],
        'PasswordResetRequested' => [
            'description' => 'Fired when password reset is requested',
            'properties' => ['email', 'panelId'],
            'example' => "PasswordResetRequested::dispatch('user@example.com', 'admin');",
            'category' => 'Password Reset',
        ],
        'PasswordReset' => [
            'description' => 'Fired when password is reset successfully',
            'properties' => ['user', 'panelId'],
            'example' => "PasswordReset::dispatch(\$user, 'admin');",
            'category' => 'Password Reset',
        ],
        'SocialAuthenticationAttempt' => [
            'description' => 'Fired when social auth is attempted',
            'properties' => ['provider', 'panelId'],
            'example' => "SocialAuthenticationAttempt::dispatch('github', 'admin');",
            'category' => 'Social Authentication',
        ],
        'SocialAuthenticationFailed' => [
            'description' => 'Fired when social auth fails',
            'properties' => ['provider', 'reason', 'panelId'],
            'example' => "SocialAuthenticationFailed::dispatch('github', 'user_denied', 'admin');",
            'category' => 'Social Authentication',
        ],
        'SocialAuthenticationSuccessful' => [
            'description' => 'Fired when social auth succeeds',
            'properties' => ['user', 'provider', 'panelId'],
            'example' => "SocialAuthenticationSuccessful::dispatch(\$user, 'github', 'admin');",
            'category' => 'Social Authentication',
        ],
        'PasskeyRegistered' => [
            'description' => 'Fired when passkey is registered',
            'properties' => ['user', 'credentialId', 'name', 'panelId'],
            'example' => "PasskeyRegistered::dispatch(\$user, 'cred_123', 'My iPhone', 'admin');",
            'category' => 'Passkey Authentication',
        ],
        'PasskeyAuthenticated' => [
            'description' => 'Fired when user authenticates with passkey',
            'properties' => ['user', 'credentialId', 'panelId'],
            'example' => "PasskeyAuthenticated::dispatch(\$user, 'cred_123', 'admin');",
            'category' => 'Passkey Authentication',
        ],
        'MagicLinkSent' => [
            'description' => 'Fired when magic link is sent',
            'properties' => ['email', 'token', 'expiresAt', 'panelId'],
            'example' => "MagicLinkSent::dispatch('user@example.com', 'token_123', now()->addMinutes(15), 'admin');",
            'category' => 'Magic Link Authentication',
        ],
    ];

    public function handle(Request $request): Response
    {
        $eventName = $request->string('event');

        if (! isset($this->events[$eventName])) {
            $availableEvents = implode(', ', array_keys($this->events));

            return Response::text("Event '{$eventName}' not found.\n\nAvailable events:\n{$availableEvents}");
        }

        $event = $this->events[$eventName];

        $output = "📋 Event Information: {$eventName}\n\n";
        $output .= "Category: {$event['category']}\n";
        $output .= str_repeat('=', 70)."\n\n";
        $output .= "Description:\n{$event['description']}\n\n";
        $output .= "Properties:\n";
        foreach ($event['properties'] as $property) {
            $output .= "  - {$property}\n";
        }
        $output .= "\n";
        $output .= "Example Usage:\n```php\n{$event['example']}\n```\n\n";
        $output .= "Namespace: Laravilt\\Auth\\Events\\{$eventName}\n";

        return Response::text($output);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'event' => $schema->string()
                ->description('Event name (e.g., LoginAttempt, RegistrationCompleted, TwoFactorEnabled)')
                ->required(),
        ];
    }
}
