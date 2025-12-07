<?php

namespace Laravilt\Auth\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ListAuthMethodsTool extends Tool
{
    protected string $description = 'List all available authentication methods with their features and configuration';

    protected array $methods = [
        'Standard Login' => [
            'description' => 'Email and password authentication',
            'features' => ['Email/password login', 'Remember me', 'Logout', 'Session management'],
            'pages' => ['Login'],
            'events' => ['LoginAttempt', 'LoginSuccessful', 'LoginFailed'],
            'config' => "->login()",
        ],
        'Registration' => [
            'description' => 'User registration with email verification',
            'features' => ['Email/password signup', 'Email verification', 'OTP support', 'Custom fields'],
            'pages' => ['Register'],
            'events' => ['RegistrationAttempt', 'RegistrationCompleted'],
            'config' => "->registration()",
        ],
        'OTP Verification' => [
            'description' => 'One-time password verification via email',
            'features' => ['6-digit codes', 'Email delivery', 'Code expiration', 'Rate limiting'],
            'pages' => ['OTP'],
            'events' => ['OtpSent', 'OtpVerified', 'OtpFailed'],
            'config' => "->emailVerification()->otp()",
        ],
        'Password Reset' => [
            'description' => 'Secure password reset flow',
            'features' => ['Email link', 'Token validation', 'Password strength rules', 'Expiration'],
            'pages' => ['ForgotPassword', 'ResetPassword'],
            'events' => ['PasswordResetRequested', 'PasswordReset'],
            'config' => "->passwordReset()",
        ],
        'Two-Factor Authentication' => [
            'description' => 'Additional security layer with TOTP/Email',
            'features' => ['TOTP (Google Authenticator)', 'Email codes', 'Recovery codes', 'Multiple methods'],
            'pages' => ['TwoFactorChallenge', 'TwoFactorRecovery'],
            'events' => ['TwoFactorEnabled', 'TwoFactorDisabled', 'TwoFactorChallengeFailed', 'TwoFactorChallengeSuccessful'],
            'config' => "->twoFactor()",
        ],
        'Social Authentication' => [
            'description' => 'OAuth login with social providers',
            'features' => ['GitHub', 'Google', 'Facebook', 'Twitter', 'Account linking', 'Avatar sync'],
            'pages' => ['SocialAuth'],
            'events' => ['SocialAuthenticationAttempt', 'SocialAuthenticationFailed', 'SocialAuthenticationSuccessful'],
            'config' => "->socialAuth(['github', 'google'])",
        ],
        'Passkey Authentication' => [
            'description' => 'WebAuthn passwordless authentication',
            'features' => ['Biometric login', 'Hardware keys', 'Platform authenticators', 'Multi-device'],
            'pages' => ['PasskeyRegister', 'PasskeyLogin'],
            'events' => ['PasskeyRegistered', 'PasskeyAuthenticated'],
            'config' => "->passkeys()",
        ],
        'Magic Links' => [
            'description' => 'Passwordless login via email link',
            'features' => ['One-click login', 'Email delivery', 'Link expiration', 'Single use tokens'],
            'pages' => ['MagicLink'],
            'events' => ['MagicLinkSent'],
            'config' => "->magicLinks()",
        ],
    ];

    public function handle(Request $request): Response
    {
        $output = "🔐 Laravilt Auth - Available Authentication Methods\n\n";
        $output .= str_repeat('=', 70)."\n\n";

        foreach ($this->methods as $name => $details) {
            $output .= "## {$name}\n\n";
            $output .= "{$details['description']}\n\n";
            $output .= "Configuration:\n```php\n{$details['config']}\n```\n\n";
            $output .= "Features:\n";
            foreach ($details['features'] as $feature) {
                $output .= "  ✓ {$feature}\n";
            }
            $output .= "\n";
            $output .= "Pages: ".implode(', ', $details['pages'])."\n";
            $output .= "Events: ".implode(', ', $details['events'])."\n\n";
            $output .= str_repeat('-', 70)."\n\n";
        }

        $output .= "\nAll methods support:\n";
        $output .= "  • Custom page replacement\n";
        $output .= "  • Event listeners for custom logic\n";
        $output .= "  • Multi-panel support\n";
        $output .= "  • Full customization\n\n";

        $output .= "Example Panel Configuration:\n```php\n";
        $output .= "Panel::make('admin')\n";
        $output .= "    ->login()\n";
        $output .= "    ->registration()\n";
        $output .= "    ->passwordReset()\n";
        $output .= "    ->emailVerification()->otp()\n";
        $output .= "    ->twoFactor()\n";
        $output .= "    ->socialAuth(['github', 'google'])\n";
        $output .= "    ->passkeys()\n";
        $output .= "    ->magicLinks();\n";
        $output .= "```\n";

        return Response::text($output);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
