<?php

namespace Laravilt\Auth\Methods;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class PasswordlessMethod extends BaseAuthMethod
{
    /**
     * Get the method name.
     */
    public function getName(): string
    {
        return 'passwordless';
    }

    /**
     * Authenticate the user.
     */
    public function authenticate(Request $request): ?Authenticatable
    {
        $token = $request->input('token');

        if (! $token) {
            return null;
        }

        $email = Cache::get("passwordless.{$token}");

        if (! $email) {
            return null;
        }

        Cache::forget("passwordless.{$token}");

        $guard = $this->config('guard', 'web');
        $model = $this->config('model');

        $user = $model::where('email', $email)->first();

        if ($user) {
            Auth::guard($guard)->login($user, true);

            return $user;
        }

        return null;
    }

    /**
     * Check if this method can handle the request.
     */
    public function canHandle(Request $request): bool
    {
        return $request->has('token');
    }

    /**
     * Validate the credentials.
     */
    public function validate(Request $request): bool
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        return true;
    }

    /**
     * Send magic link to email.
     */
    public function sendMagicLink(string $email): bool
    {
        $token = Str::random(64);

        Cache::put("passwordless.{$token}", $email, now()->addMinutes(15));

        $url = URL::temporarySignedRoute(
            'laravilt.auth.passwordless.login',
            now()->addMinutes(15),
            ['token' => $token]
        );

        // Send email with magic link
        // Mail::to($email)->send(new MagicLinkMail($url));

        return true;
    }
}
