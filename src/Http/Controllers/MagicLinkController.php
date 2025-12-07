<?php

namespace Laravilt\Auth\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravilt\Auth\Events\MagicLinkSent;
use Laravilt\Auth\Events\MagicLinkVerified;

class MagicLinkController extends Controller
{
    /**
     * Send a magic link to the user's email for 2FA bypass.
     */
    public function send(Request $request)
    {
        // Get the panel from request attributes (set by IdentifyPanel middleware)
        $panel = $request->attributes->get('panel');

        if (! $panel) {
            return response()->json(['error' => 'Panel not found'], 404);
        }

        $guard = $panel->getAuthGuard() ?? 'web';

        // Get the user from the session challenge
        $userId = $request->session()->get('login.id');
        $provider = config("auth.guards.{$guard}.provider");
        $modelClass = config("auth.providers.{$provider}.model");
        $user = $userId && $modelClass ? $modelClass::find($userId) : null;

        if (! $user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Generate a unique token for this magic link
        $token = Str::random(64);

        // Store the token with user ID in cache for 15 minutes
        Cache::put("magic_link_2fa.{$token}", [
            'user_id' => $user->id,
            'panel_id' => $panel->getId(),
            'guard' => $guard,
            'remember' => $request->session()->get('login.remember', false),
        ], now()->addMinutes(15));

        // Generate a signed URL
        $url = URL::temporarySignedRoute(
            $panel->getId().'.magic-link.verify',
            now()->addMinutes(15),
            ['token' => $token]
        );

        $expiresAt = now()->addMinutes(15);

        // Send the magic link via email
        Mail::to($user->email)->send(new \Laravilt\Auth\Mail\MagicLinkMail($url));

        // Dispatch magic link sent event
        MagicLinkSent::dispatch(
            $user,
            $url,
            $expiresAt,
            $panel->getId()
        );

        return response()->json([
            'message' => 'Magic link sent successfully',
        ]);
    }

    /**
     * Verify the magic link and log the user in.
     */
    public function verify(Request $request, string $token)
    {
        // Get the panel from request attributes
        $panel = $request->attributes->get('panel');

        if (! $panel) {
            abort(404, 'Panel not found');
        }

        // Get the cached data
        $data = Cache::get("magic_link_2fa.{$token}");

        if (! $data) {
            throw ValidationException::withMessages([
                'token' => ['This magic link is invalid or has expired.'],
            ]);
        }

        // Verify the panel matches
        if ($data['panel_id'] !== $panel->getId()) {
            throw ValidationException::withMessages([
                'token' => ['This magic link is invalid.'],
            ]);
        }

        // Remove the token from cache (one-time use)
        Cache::forget("magic_link_2fa.{$token}");

        // Get the user
        $guard = $data['guard'];
        $provider = config("auth.guards.{$guard}.provider");
        $modelClass = config("auth.providers.{$provider}.model");
        $user = $modelClass::find($data['user_id']);

        if (! $user) {
            throw ValidationException::withMessages([
                'token' => ['User not found.'],
            ]);
        }

        // Log the user in
        Auth::guard($guard)->login($user, $data['remember']);

        $request->session()->regenerate();

        // Mark that 2FA has been completed in this session
        $request->session()->put('auth.two_factor_confirmed_at', now()->timestamp);

        // Dispatch magic link verified event
        MagicLinkVerified::dispatch(
            $user,
            $panel->getId()
        );

        // Clear the login challenge session data
        $request->session()->forget(['login.id', 'login.remember']);

        // Redirect to panel
        return redirect($panel->getPath());
    }
}
