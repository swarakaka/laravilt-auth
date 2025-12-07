<?php

namespace Laravilt\Auth\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Laravilt\Auth\Events\SocialAuthenticationAttempt;
use Laravilt\Auth\Events\SocialAuthenticationSuccessful;
use Laravilt\Auth\Models\SocialAccount;
use Laravilt\Panel\Facades\Panel;

class SocialAuthController
{
    /**
     * Redirect the user to the provider authentication page.
     */
    public function redirect(Request $request, string $provider): RedirectResponse
    {
        $panel = Panel::getCurrent();

        // Store the panel context in session
        session(['auth.panel' => $panel->getId()]);

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Obtain the user information from the provider.
     */
    public function callback(Request $request, string $provider): RedirectResponse
    {
        try {
            $socialUser = Socialite::driver($provider)->user();

            // Get the panel context from session
            $panelId = session('auth.panel');
            $panel = Panel::get($panelId);

            if (! $panel) {
                \Log::error('Social auth callback: Invalid panel context', ['panelId' => $panelId]);

                return redirect()->route('login')->withErrors([
                    'email' => 'Invalid authentication context.',
                ]);
            }

            // Dispatch social authentication attempt event
            SocialAuthenticationAttempt::dispatch(
                $provider,
                $socialUser->getEmail(),
                $socialUser->getId(),
                $panel->getId()
            );

            // Get the user model from the auth guard configuration
            $guard = $panel->getAuthGuard();
            $authProvider = config("auth.guards.{$guard}.provider");
            $userModel = config("auth.providers.{$authProvider}.model", \App\Models\User::class);

            // Check if this social account already exists
            $socialAccount = SocialAccount::where('provider', $provider)
                ->where('provider_id', $socialUser->getId())
                ->with('user')
                ->first();

            \Log::info('Social account lookup', [
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'found' => $socialAccount ? 'yes' : 'no',
                'has_user' => $socialAccount && $socialAccount->user ? 'yes' : 'no',
            ]);

            $user = null;
            $isNewUser = false;

            if ($socialAccount) {
                // Social account exists, reload the relationship to ensure we have the user
                $socialAccount->load('user');
                $user = $socialAccount->user;

                \Log::info('Existing social account', [
                    'social_account_id' => $socialAccount->id,
                    'user_id' => $socialAccount->user_id,
                    'user_found' => $user ? 'yes' : 'no',
                ]);

                // Check if the user still exists
                if (! $user) {
                    // User was deleted - delete orphaned social account and create new user
                    \Log::warning('Orphaned social account found - user deleted', [
                        'social_account_id' => $socialAccount->id,
                        'user_id' => $socialAccount->user_id,
                    ]);

                    $socialAccount->delete();
                    $socialAccount = null; // Treat as if it doesn't exist
                } else {
                    // User exists - update social account information
                    $socialAccount->update([
                        'name' => $socialUser->getName(),
                        'email' => $socialUser->getEmail(),
                        'avatar' => $socialUser->getAvatar(),
                        'token' => $socialUser->token,
                        'refresh_token' => $socialUser->refreshToken ?? null,
                        'expires_at' => $socialUser->expiresIn ? now()->addSeconds($socialUser->expiresIn) : null,
                    ]);
                }
            }

            if (! $socialAccount) {
                // Try to find user by email
                $user = $userModel::where('email', $socialUser->getEmail())->first();

                \Log::info('User lookup by email', [
                    'email' => $socialUser->getEmail(),
                    'found' => $user ? 'yes' : 'no',
                ]);

                if (! $user) {
                    // Create new user without password (will be prompted to set one)
                    $user = $userModel::create([
                        'name' => $socialUser->getName(),
                        'email' => $socialUser->getEmail(),
                        'email_verified_at' => now(),
                        'password' => null, // No password for social auth users initially
                    ]);

                    $isNewUser = true;

                    \Log::info('Created new user', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                    ]);
                }

                // Create social account record
                $createdAccount = SocialAccount::create([
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'avatar' => $socialUser->getAvatar(),
                    'token' => $socialUser->token,
                    'refresh_token' => $socialUser->refreshToken ?? null,
                    'expires_at' => $socialUser->expiresIn ? now()->addSeconds($socialUser->expiresIn) : null,
                ]);

                \Log::info('Created social account', [
                    'social_account_id' => $createdAccount->id,
                    'user_id' => $createdAccount->user_id,
                ]);
            }

            // Ensure we have a valid user before attempting login
            if (! $user) {
                \Log::error('User is null after social auth processing');
                throw new \Exception('Unable to create or retrieve user account.');
            }

            // Clear the panel context
            session()->forget('auth.panel');

            // Dispatch social authentication successful event
            SocialAuthenticationSuccessful::dispatch(
                $user,
                $provider,
                $socialUser->getId(),
                $isNewUser,
                $panel->getId()
            );

            // Check if user has two-factor authentication enabled AND confirmed BEFORE logging in
            if ($user->two_factor_enabled && ! is_null($user->two_factor_confirmed_at)) {
                // User has 2FA enabled and confirmed, redirect to challenge WITHOUT logging in
                // Store the user ID in session for the challenge
                session()->put([
                    'login.id' => $user->getAuthIdentifier(),
                    'login.remember' => true,
                ]);

                // Send the verification code using the appropriate driver
                $method = $user->two_factor_method ?? 'totp';
                $manager = $panel->getTwoFactorProviderManager();
                $driver = $manager?->getDriver($method);

                if ($driver && method_exists($driver, 'send')) {
                    $driver->send($user);
                }

                return redirect()->route($panel->getId().'.two-factor.challenge');
            }

            // No 2FA required - log the user in
            auth($panel->getAuthGuard())->login($user, true);

            // Mark that auth is complete (no 2FA required)
            session()->put('auth.two_factor_confirmed_at', now()->timestamp);

            // Check if user needs to set a password (only if panel requires it)
            if ($panel->shouldRequirePasswordForSocialLogin() && empty($user->password)) {
                \Log::info('User has no password, redirecting to set password', [
                    'user_id' => $user->id,
                ]);

                // Store intended URL before redirecting
                session()->put('url.intended', $panel->getPath());

                return redirect()->route($panel->getId().'.auth.set-password')
                    ->with('status', 'Welcome! Please set a password for your account to secure it.');
            }

            return redirect()->intended($panel->getPath());
        } catch (\Exception $e) {
            \Log::error('Social auth callback error', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('login')->withErrors([
                'email' => 'Unable to authenticate with '.$provider.'. '.$e->getMessage(),
            ]);
        }
    }
}
