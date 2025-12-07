<?php

namespace Laravilt\Auth\Methods;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialLoginMethod extends BaseAuthMethod
{
    /**
     * Get the method name.
     */
    public function getName(): string
    {
        return 'social';
    }

    /**
     * Authenticate the user.
     */
    public function authenticate(Request $request): ?Authenticatable
    {
        $provider = $request->input('provider');

        if (! $this->isProviderEnabled($provider)) {
            return null;
        }

        try {
            $socialUser = Socialite::driver($provider)->user();

            $guard = $this->config('guard', 'web');
            $model = $this->config('model');

            $user = $model::where('email', $socialUser->getEmail())->first();

            if (! $user) {
                $user = $model::create([
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'avatar' => $socialUser->getAvatar(),
                    'email_verified_at' => now(),
                ]);
            }

            // Store social account info
            $user->socialAccounts()->updateOrCreate(
                [
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                ],
                [
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'avatar' => $socialUser->getAvatar(),
                    'token' => $socialUser->token,
                    'refresh_token' => $socialUser->refreshToken ?? null,
                ]
            );

            Auth::guard($guard)->login($user, true);

            return $user;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if this method can handle the request.
     */
    public function canHandle(Request $request): bool
    {
        return $request->has('provider') && $request->has('code');
    }

    /**
     * Validate the credentials.
     */
    public function validate(Request $request): bool
    {
        $request->validate([
            'provider' => ['required', 'string', 'in:google,github,facebook'],
        ]);

        return true;
    }

    /**
     * Redirect to social provider.
     */
    public function redirect(string $provider): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Check if provider is enabled.
     */
    protected function isProviderEnabled(string $provider): bool
    {
        $enabledProviders = $this->config('providers', []);

        return in_array($provider, $enabledProviders);
    }
}
