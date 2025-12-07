<?php

namespace Laravilt\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Laravilt\Auth\Events\LoginAttempt;
use Laravilt\Auth\Events\LoginFailed;
use Laravilt\Auth\Events\LoginSuccessful;
use Laravilt\Auth\Events\RegistrationAttempt;
use Laravilt\Auth\Events\RegistrationCompleted;
use Laravilt\Auth\Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created()
    {
        $user = \App\Models\User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
    }

    public function test_user_password_is_hashed()
    {
        $user = \App\Models\User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->assertTrue(Hash::check('password', $user->password));
        $this->assertNotEquals('password', $user->password);
    }

    public function test_login_attempt_event_can_be_dispatched()
    {
        Event::fake();

        LoginAttempt::dispatch('test@example.com', 'admin');

        Event::assertDispatched(LoginAttempt::class, function ($event) {
            return $event->email === 'test@example.com' && $event->panelId === 'admin';
        });
    }

    public function test_login_successful_event_can_be_dispatched()
    {
        Event::fake();
        $user = $this->createUser();

        LoginSuccessful::dispatch($user, 'admin', true);

        Event::assertDispatched(LoginSuccessful::class, function ($event) use ($user) {
            return $event->user->id === $user->id
                && $event->panelId === 'admin'
                && $event->remember === true;
        });
    }

    public function test_login_failed_event_can_be_dispatched()
    {
        Event::fake();

        LoginFailed::dispatch('test@example.com', 'admin', 'invalid_credentials');

        Event::assertDispatched(LoginFailed::class, function ($event) {
            return $event->email === 'test@example.com'
                && $event->panelId === 'admin'
                && $event->reason === 'invalid_credentials';
        });
    }

    public function test_registration_attempt_event_can_be_dispatched()
    {
        Event::fake();

        RegistrationAttempt::dispatch(['email' => 'test@example.com', 'name' => 'Test'], 'admin');

        Event::assertDispatched(RegistrationAttempt::class, function ($event) {
            return $event->data['email'] === 'test@example.com' && $event->panelId === 'admin';
        });
    }

    public function test_registration_completed_event_can_be_dispatched()
    {
        Event::fake();
        $user = $this->createUser();

        RegistrationCompleted::dispatch($user, 'admin', false);

        Event::assertDispatched(RegistrationCompleted::class, function ($event) use ($user) {
            return $event->user->id === $user->id
                && $event->panelId === 'admin'
                && $event->requiresOtpVerification === false;
        });
    }

    protected function createUser(array $attributes = [])
    {
        return \App\Models\User::create(array_merge([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ], $attributes));
    }
}
