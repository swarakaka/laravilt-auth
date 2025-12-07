<?php

namespace Laravilt\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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

        if ($this->needsPassword($user)) {
            // Don't redirect if already on the set password page
            if (! $request->routeIs('*.auth.set-password') && ! $request->routeIs('*.auth.set-password.store')) {
                return redirect()->route(
                    app(\Laravilt\Panel\PanelRegistry::class)->getCurrent()->getId().'.auth.set-password'
                );
            }
        }

        return $next($request);
    }

    /**
     * Check if user needs to set a password.
     */
    protected function needsPassword($user): bool
    {
        // Get current panel
        $panel = app(\Laravilt\Panel\PanelRegistry::class)->getCurrent();

        // If panel doesn't require password for social login, skip check
        if ($panel && ! $panel->shouldRequirePasswordForSocialLogin()) {
            return false;
        }

        // Simply check if user has a null/empty password
        return empty($user->password);
    }
}
