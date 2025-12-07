<?php

namespace Laravilt\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravilt\Auth\Events\LoginAttempt;
use Laravilt\Auth\Events\LoginFailed;
use Laravilt\Auth\Events\LoginSuccessful;
use Laravilt\Auth\Events\OtpFailed;
use Laravilt\Auth\Events\OtpSent;
use Laravilt\Auth\Events\OtpVerified;
use Laravilt\Auth\Events\PasswordReset;
use Laravilt\Auth\Events\PasswordResetRequested;
use Laravilt\Auth\Events\RegistrationAttempt;
use Laravilt\Auth\Events\RegistrationCompleted;
use Laravilt\Auth\Events\TwoFactorChallengeSuccessful;
use Laravilt\Auth\Events\TwoFactorDisabled;
use Laravilt\Auth\Events\TwoFactorEnabled;
use Laravilt\Auth\Tests\TestCase;

class EventsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function all_auth_events_have_required_properties()
    {
        // Login Events
        $loginAttempt = new LoginAttempt('test@example.com', 'admin');
        $this->assertEquals('test@example.com', $loginAttempt->email);
        $this->assertEquals('admin', $loginAttempt->panelId);

        $user = $this->createMockUser();
        $loginSuccessful = new LoginSuccessful($user, 'admin', true);
        $this->assertEquals($user, $loginSuccessful->user);
        $this->assertEquals('admin', $loginSuccessful->panelId);
        $this->assertTrue($loginSuccessful->remember);

        $loginFailed = new LoginFailed('test@example.com', 'admin', 'invalid_credentials');
        $this->assertEquals('test@example.com', $loginFailed->email);
        $this->assertEquals('admin', $loginFailed->panelId);
        $this->assertEquals('invalid_credentials', $loginFailed->reason);

        // Registration Events
        $registrationAttempt = new RegistrationAttempt(['email' => 'test@example.com'], 'admin');
        $this->assertArrayHasKey('email', $registrationAttempt->data);
        $this->assertEquals('admin', $registrationAttempt->panelId);

        $registrationCompleted = new RegistrationCompleted($user, 'admin', false);
        $this->assertEquals($user, $registrationCompleted->user);
        $this->assertEquals('admin', $registrationCompleted->panelId);
        $this->assertFalse($registrationCompleted->requiresOtpVerification);

        // OTP Events
        $otpSent = new OtpSent($user, '123456', 'registration', now()->addMinutes(5), 'admin');
        $this->assertEquals($user, $otpSent->user);
        $this->assertEquals('123456', $otpSent->code);
        $this->assertEquals('registration', $otpSent->purpose);
        $this->assertEquals('admin', $otpSent->panelId);

        $otpVerified = new OtpVerified($user, 'registration', 'admin');
        $this->assertEquals($user, $otpVerified->user);
        $this->assertEquals('registration', $otpVerified->purpose);

        $otpFailed = new OtpFailed('test@example.com', 'registration', 'invalid_code', 'admin');
        $this->assertEquals('test@example.com', $otpFailed->identifier);
        $this->assertEquals('registration', $otpFailed->purpose);
        $this->assertEquals('invalid_code', $otpFailed->reason);

        // 2FA Events
        $twoFactorEnabled = new TwoFactorEnabled($user, 'totp', 'admin');
        $this->assertEquals($user, $twoFactorEnabled->user);
        $this->assertEquals('totp', $twoFactorEnabled->method);

        $twoFactorDisabled = new TwoFactorDisabled($user, 'admin');
        $this->assertEquals($user, $twoFactorDisabled->user);
        $this->assertEquals('admin', $twoFactorDisabled->panelId);

        $twoFactorChallengeSuccessful = new TwoFactorChallengeSuccessful($user, 'totp', 'admin');
        $this->assertEquals($user, $twoFactorChallengeSuccessful->user);
        $this->assertEquals('totp', $twoFactorChallengeSuccessful->method);

        // Password Reset Events
        $passwordResetRequested = new PasswordResetRequested('test@example.com', 'admin');
        $this->assertEquals('test@example.com', $passwordResetRequested->email);

        $passwordReset = new PasswordReset($user, 'admin');
        $this->assertEquals($user, $passwordReset->user);
    }

    /** @test */
    public function events_are_dispatchable()
    {
        Event::fake();

        $user = $this->createMockUser();

        // Test dispatching various events
        LoginAttempt::dispatch('test@example.com', 'admin');
        LoginSuccessful::dispatch($user, 'admin', true);
        LoginFailed::dispatch('test@example.com', 'admin', 'invalid_credentials');
        RegistrationAttempt::dispatch(['email' => 'test@example.com'], 'admin');
        RegistrationCompleted::dispatch($user, 'admin', false);
        OtpSent::dispatch($user, '123456', 'registration', now()->addMinutes(5), 'admin');
        OtpVerified::dispatch($user, 'registration', 'admin');
        TwoFactorEnabled::dispatch($user, 'totp', 'admin');
        TwoFactorDisabled::dispatch($user, 'totp', 'admin');
        PasswordResetRequested::dispatch('test@example.com', 'admin');
        PasswordReset::dispatch($user, 'admin');

        Event::assertDispatched(LoginAttempt::class);
        Event::assertDispatched(LoginSuccessful::class);
        Event::assertDispatched(LoginFailed::class);
        Event::assertDispatched(RegistrationAttempt::class);
        Event::assertDispatched(RegistrationCompleted::class);
        Event::assertDispatched(OtpSent::class);
        Event::assertDispatched(OtpVerified::class);
        Event::assertDispatched(TwoFactorEnabled::class);
        Event::assertDispatched(TwoFactorDisabled::class);
        Event::assertDispatched(PasswordResetRequested::class);
        Event::assertDispatched(PasswordReset::class);
    }

    protected function createMockUser()
    {
        return new class
        {
            public $id = 1;

            public $email = 'test@example.com';

            public $name = 'Test User';
        };
    }
}
