<?php

namespace Laravilt\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Laravilt\Auth\Events\OtpFailed;
use Laravilt\Auth\Events\OtpSent;
use Laravilt\Auth\Events\OtpVerified;
use Laravilt\Auth\Tests\TestCase;

class OtpVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_otp_code_can_be_created_in_database()
    {
        $user = $this->createUser();
        $code = '123456';
        $expiresAt = now()->addMinutes(5);

        DB::table('otp_codes')->insert([
            'identifier' => $user->email,
            'code' => $code,
            'purpose' => 'registration',
            'expires_at' => $expiresAt,
            'verified' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('otp_codes', [
            'identifier' => $user->email,
            'code' => $code,
            'purpose' => 'registration',
            'verified' => false,
        ]);
    }

    public function test_otp_code_can_be_verified()
    {
        $user = $this->createUser();
        $code = '123456';

        $otpId = DB::table('otp_codes')->insertGetId([
            'identifier' => $user->email,
            'code' => $code,
            'purpose' => 'registration',
            'expires_at' => now()->addMinutes(5),
            'verified' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verify the code
        DB::table('otp_codes')->where('id', $otpId)->update(['verified' => true]);

        $this->assertDatabaseHas('otp_codes', [
            'id' => $otpId,
            'verified' => true,
        ]);
    }

    public function test_expired_otp_codes_are_not_valid()
    {
        $user = $this->createUser();
        $code = '123456';

        DB::table('otp_codes')->insert([
            'identifier' => $user->email,
            'code' => $code,
            'purpose' => 'registration',
            'expires_at' => now()->subMinutes(5), // Expired
            'verified' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $validCode = DB::table('otp_codes')
            ->where('identifier', $user->email)
            ->where('code', $code)
            ->where('expires_at', '>', now())
            ->first();

        $this->assertNull($validCode);
    }

    public function test_otp_sent_event_can_be_dispatched()
    {
        Event::fake();
        $user = $this->createUser();

        OtpSent::dispatch($user, '123456', 'registration', now()->addMinutes(5), 'admin');

        Event::assertDispatched(OtpSent::class, function ($event) use ($user) {
            return $event->user->id === $user->id
                && $event->code === '123456'
                && $event->purpose === 'registration'
                && $event->panelId === 'admin';
        });
    }

    public function test_otp_verified_event_can_be_dispatched()
    {
        Event::fake();
        $user = $this->createUser();

        OtpVerified::dispatch($user, 'registration', 'admin');

        Event::assertDispatched(OtpVerified::class, function ($event) use ($user) {
            return $event->user->id === $user->id
                && $event->purpose === 'registration'
                && $event->panelId === 'admin';
        });
    }

    public function test_otp_failed_event_can_be_dispatched()
    {
        Event::fake();
        $user = $this->createUser();

        OtpFailed::dispatch($user->email, 'registration', 'invalid_code', 'admin');

        Event::assertDispatched(OtpFailed::class, function ($event) use ($user) {
            return $event->identifier === $user->email
                && $event->purpose === 'registration'
                && $event->reason === 'invalid_code'
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
