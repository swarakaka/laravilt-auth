<?php

namespace Laravilt\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactorAuthentication
{
    /**
     * Handle an incoming request.
     *
     * Check if the authenticated user has two-factor authentication enabled and confirmed.
     * If they do and haven't completed the challenge, redirect them to the 2FA challenge page.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return $next($request);
        }

        $user = $request->user();

        if ($this->needsTwoFactorChallenge($request, $user)) {
            $panel = app(\Laravilt\Panel\PanelRegistry::class)->getCurrent();

            // Don't redirect if on 2FA challenge/recovery pages or logout
            if (! $request->routeIs('*.two-factor.challenge')
                && ! $request->routeIs('*.two-factor.challenge.verify')
                && ! $request->routeIs('*.two-factor.recovery')
                && ! $request->routeIs('*.two-factor.recovery.verify')
                && ! $request->routeIs('*.logout')) {

                // Store the intended URL so we can redirect back after 2FA
                if (! $request->routeIs('*.login')) {
                    $request->session()->put('url.intended', $request->url());
                }

                return redirect()->route($panel->getId().'.two-factor.challenge');
            }
        }

        return $next($request);
    }

    /**
     * Check if user needs to complete two-factor authentication challenge.
     */
    protected function needsTwoFactorChallenge(Request $request, $user): bool
    {
        // Check if user has 2FA enabled and confirmed
        // Use the same logic as Login.php: check two_factor_secret AND two_factor_confirmed_at
        if (empty($user->two_factor_secret) || empty($user->two_factor_confirmed_at)) {
            return false;
        }

        // Check if the user has already completed the 2FA challenge in this session
        // This is tracked by checking if they logged in with 2FA in the current session
        return ! $request->session()->get('auth.two_factor_confirmed_at');
    }
}
