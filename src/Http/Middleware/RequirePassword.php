<?php

namespace Laravilt\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class RequirePassword
{
    /**
     * Handle an incoming request.
     *
     * Check if the authenticated user has a password set.
     * If not, redirect them to set a password.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return $next($request);
        }

        $user = $request->user();
        $panel = app(\Laravilt\Panel\PanelRegistry::class)->getCurrent();

        // Skip if no panel context
        if (! $panel) {
            return $next($request);
        }

        // Check if the set-password route exists for this panel
        $setPasswordRouteName = $panel->getId().'.auth.set-password';
        if (! Route::has($setPasswordRouteName)) {
            return $next($request);
        }

        if ($this->needsPassword($user, $panel)) {
            // Don't redirect if already on the set password page
            if (! $request->routeIs('*.auth.set-password') && ! $request->routeIs('*.auth.set-password.store')) {
                return redirect()->route($setPasswordRouteName);
            }
        }

        return $next($request);
    }

    /**
     * Check if user needs to set a password.
     */
    protected function needsPassword($user, $panel): bool
    {
        // If panel doesn't require password for social login, skip check
        if (! $panel->shouldRequirePasswordForSocialLogin()) {
            return false;
        }

        // Simply check if user has a null/empty password
        return empty($user->password);
    }
}
