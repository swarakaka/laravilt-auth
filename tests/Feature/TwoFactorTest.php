<?php

namespace Laravilt\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravilt\Auth\Events\TwoFactorChallengeSuccessful;
use Laravilt\Auth\Events\TwoFactorDisabled;
use Laravilt\Auth\Events\TwoFactorEnabled;
use Laravilt\Auth\Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_enable_two_factor()
    {
        $user = $this->createUser();

        $user->update(['two_factor_enabled' => true]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'two_factor_enabled' => true,
        ]);
    }

    public function test_user_can_disable_two_factor()
    {
        $user = $this->createUser(['two_factor_enabled' => true]);

        $user->update(['two_factor_enabled' => false]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'two_factor_enabled' => false,
        ]);
    }

    public function test_user_can_have_two_factor_method()
    {
        $user = $this->createUser([
            'two_factor_enabled' => true,
            'two_factor_method' => 'totp',
        ]);

        $this->assertEquals('totp', $user->two_factor_method);
    }

    public function test_two_factor_enabled_event_can_be_dispatched()
    {
        Event::fake();
        $user = $this->createUser();

        TwoFactorEnabled::dispatch($user, 'totp', 'admin');

        Event::assertDispatched(TwoFactorEnabled::class, function ($event) use ($user) {
            return $event->user->id === $user->id
                && $event->method === 'totp'
                && $event->panelId === 'admin';
        });
    }

    public function test_two_factor_disabled_event_can_be_dispatched()
    {
        Event::fake();
        $user = $this->createUser();

        TwoFactorDisabled::dispatch($user, 'admin');

        Event::assertDispatched(TwoFactorDisabled::class, function ($event) use ($user) {
            return $event->user->id === $user->id && $event->panelId === 'admin';
        });
    }

    public function test_two_factor_challenge_successful_event_can_be_dispatched()
    {
        Event::fake();
        $user = $this->createUser();

        TwoFactorChallengeSuccessful::dispatch($user, 'totp', 'admin');

        Event::assertDispatched(TwoFactorChallengeSuccessful::class, function ($event) use ($user) {
            return $event->user->id === $user->id
                && $event->method === 'totp'
                && $event->panelId === 'admin';
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
