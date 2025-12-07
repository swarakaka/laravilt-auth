<?php

namespace Laravilt\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Laravilt\Auth\Events\PasswordReset;
use Laravilt\Auth\Events\PasswordResetRequested;
use Laravilt\Auth\Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_token_can_be_created()
    {
        $user = $this->createUser();

        $token = Password::createToken($user);

        $this->assertNotEmpty($token);
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_password_can_be_reset_with_valid_token()
    {
        $user = $this->createUser();
        $token = Password::createToken($user);

        $status = Password::reset(
            [
                'email' => $user->email,
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
                'token' => $token,
            ],
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        $this->assertEquals(Password::PASSWORD_RESET, $status);

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    public function test_password_reset_requested_event_can_be_dispatched()
    {
        Event::fake();

        PasswordResetRequested::dispatch('test@example.com', 'admin');

        Event::assertDispatched(PasswordResetRequested::class, function ($event) {
            return $event->email === 'test@example.com' && $event->panelId === 'admin';
        });
    }

    public function test_password_reset_event_can_be_dispatched()
    {
        Event::fake();
        $user = $this->createUser();

        PasswordReset::dispatch($user, 'admin');

        Event::assertDispatched(PasswordReset::class, function ($event) use ($user) {
            return $event->user->id === $user->id && $event->panelId === 'admin';
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
