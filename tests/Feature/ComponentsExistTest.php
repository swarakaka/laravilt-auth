<?php

namespace Laravilt\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Laravilt\Auth\Tests\TestCase;

class ComponentsExistTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_controllers_exist()
    {
        $files = [
            __DIR__.'/../../src/Http/Controllers/PasskeyController.php',
            __DIR__.'/../../src/Http/Controllers/MagicLinkController.php',
            __DIR__.'/../../src/Http/Controllers/Auth/SocialAuthController.php',
        ];

        foreach ($files as $file) {
            $this->assertFileExists($file, "Controller file {$file} should exist");
        }
    }

    public function test_auth_pages_exist()
    {
        $files = [
            __DIR__.'/../../src/Pages/Login.php',
            __DIR__.'/../../src/Pages/Register.php',
            __DIR__.'/../../src/Pages/ForgotPassword.php',
            __DIR__.'/../../src/Pages/ResetPassword.php',
            __DIR__.'/../../src/Pages/EmailVerification.php',
            __DIR__.'/../../src/Pages/OTP.php',
            __DIR__.'/../../src/Pages/Profile.php',
            __DIR__.'/../../src/Pages/SetPassword.php',
            __DIR__.'/../../src/Pages/MagicLink.php',
            __DIR__.'/../../src/Pages/Auth/TwoFactorChallenge.php',
            __DIR__.'/../../src/Pages/Auth/TwoFactorRecovery.php',
            __DIR__.'/../../src/Pages/Profile/ChangePassword.php',
            __DIR__.'/../../src/Pages/Profile/ManageTwoFactor.php',
            __DIR__.'/../../src/Pages/Profile/ManageSessions.php',
            __DIR__.'/../../src/Pages/Profile/ManagePasskeys.php',
            __DIR__.'/../../src/Pages/Profile/ManageApiTokens.php',
            __DIR__.'/../../src/Pages/Profile/ConnectedAccounts.php',
        ];

        foreach ($files as $file) {
            $this->assertFileExists($file, "Page file {$file} should exist");
        }
    }

    public function test_auth_events_exist()
    {
        $files = [
            __DIR__.'/../../src/Events/LoginAttempt.php',
            __DIR__.'/../../src/Events/LoginSuccessful.php',
            __DIR__.'/../../src/Events/LoginFailed.php',
            __DIR__.'/../../src/Events/RegistrationAttempt.php',
            __DIR__.'/../../src/Events/RegistrationCompleted.php',
            __DIR__.'/../../src/Events/PasswordResetRequested.php',
            __DIR__.'/../../src/Events/PasswordReset.php',
            __DIR__.'/../../src/Events/OtpSent.php',
            __DIR__.'/../../src/Events/OtpVerified.php',
            __DIR__.'/../../src/Events/OtpFailed.php',
            __DIR__.'/../../src/Events/TwoFactorEnabled.php',
            __DIR__.'/../../src/Events/TwoFactorDisabled.php',
            __DIR__.'/../../src/Events/TwoFactorChallengeSuccessful.php',
            __DIR__.'/../../src/Events/TwoFactorChallengeFailed.php',
            __DIR__.'/../../src/Events/PasskeyRegistered.php',
            __DIR__.'/../../src/Events/PasskeyDeleted.php',
            __DIR__.'/../../src/Events/MagicLinkSent.php',
            __DIR__.'/../../src/Events/MagicLinkVerified.php',
            __DIR__.'/../../src/Events/SocialAuthenticationAttempt.php',
            __DIR__.'/../../src/Events/SocialAuthenticationSuccessful.php',
        ];

        foreach ($files as $file) {
            $this->assertFileExists($file, "Event file {$file} should exist");
        }
    }

    public function test_auth_middleware_exist()
    {
        $files = [
            __DIR__.'/../../src/Http/Middleware/RequirePassword.php',
            __DIR__.'/../../src/Http/Middleware/RequireTwoFactorAuthentication.php',
        ];

        foreach ($files as $file) {
            $this->assertFileExists($file, "Middleware file {$file} should exist");
        }
    }

    public function test_vue_components_exist()
    {
        $files = [
            __DIR__.'/../../resources/js/Pages/ProfilePage.vue',
            __DIR__.'/../../resources/js/Pages/TwoFactorChallengePage.vue',
            __DIR__.'/../../resources/js/Pages/ManageTwoFactorPage.vue',
            __DIR__.'/../../resources/js/Pages/ManageSessionsPage.vue',
            __DIR__.'/../../resources/js/Pages/ManagePasskeysPage.vue',
            __DIR__.'/../../resources/js/Pages/ManageApiTokensPage.vue',
            __DIR__.'/../../resources/js/Pages/ConnectedAccountsPage.vue',
            __DIR__.'/../../resources/js/components/SocialLogin.vue',
        ];

        foreach ($files as $file) {
            $this->assertFileExists($file, "Vue component file {$file} should exist");
        }
    }

    public function test_user_model_has_two_factor_attributes()
    {
        $user = $this->createUser([
            'two_factor_enabled' => true,
            'two_factor_method' => 'totp',
        ]);

        $this->assertTrue($user->two_factor_enabled);
        $this->assertEquals('totp', $user->two_factor_method);
    }

    public function test_user_can_have_nullable_password()
    {
        $user = $this->createUser(['password' => null]);

        $this->assertNull($user->password);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'password' => null,
        ]);
    }

    public function test_user_can_be_verified()
    {
        $user = $this->createUser(['email_verified_at' => null]);

        $this->assertNull($user->email_verified_at);

        $user->update(['email_verified_at' => now()]);

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_password_reset_token_can_be_created()
    {
        $user = $this->createUser();

        $token = Password::createToken($user);

        $this->assertNotEmpty($token);
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_user_can_have_multiple_2fa_attributes()
    {
        $user = $this->createUser([
            'two_factor_enabled' => true,
            'two_factor_method' => 'email',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $this->assertTrue($user->two_factor_enabled);
        $this->assertEquals('email', $user->two_factor_method);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->password);
    }

    public function test_user_table_exists()
    {
        // Simply check we can query the users table
        $this->assertIsArray(
            \DB::select('SELECT * FROM users WHERE id = ?', [999])
        );
    }

    public function test_password_reset_tokens_table_exists()
    {
        // Simply check we can query the password_reset_tokens table
        $this->assertIsArray(
            \DB::select('SELECT * FROM password_reset_tokens WHERE email = ?', ['test@example.com'])
        );
    }

    protected function createUser(array $attributes = [])
    {
        return User::create(array_merge([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ], $attributes));
    }
}
