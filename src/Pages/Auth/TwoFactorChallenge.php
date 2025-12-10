<?php

namespace Laravilt\Auth\Pages\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Features;
use Laravilt\Actions\Action;
use Laravilt\Auth\Events\TwoFactorChallengeFailed;
use Laravilt\Auth\Events\TwoFactorChallengeSuccessful;
use Laravilt\Forms\Components\PinInput;
use Laravilt\Panel\Enums\PageLayout;
use Laravilt\Panel\Pages\Page;

class TwoFactorChallenge extends Page
{
    protected static ?string $title = null;

    protected static bool $shouldRegisterNavigation = false;

    protected ?string $component = 'laravilt-auth/TwoFactorChallengePage';

    public static function getTitle(): string
    {
        return __('laravilt-auth::auth.two_factor_challenge.title');
    }

    public function getHeading(): string
    {
        return __('laravilt-auth::auth.two_factor_challenge.heading');
    }

    public function getSubheading(): ?string
    {
        return __('laravilt-auth::auth.two_factor_challenge.subheading');
    }

    public function getLayout(): string
    {
        return PageLayout::Card->value;
    }

    /**
     * Handle the GET request for the 2FA challenge page.
     * Redirect to login if no valid session exists.
     */
    public function create(\Illuminate\Http\Request $request, ...$parameters)
    {
        $panel = $this->getPanel();

        // Check if there's a valid login.id in session (mid-authentication)
        $userId = $request->session()->get('login.id');

        if (! $userId) {
            // No valid session, redirect to login
            return redirect()->route($panel->getId().'.login');
        }

        // Call parent create method
        return parent::create($request, ...$parameters);
    }

    protected function getSchema(): array
    {
        return [
            PinInput::make('code')
                ->label(__('laravilt-auth::auth.fields.code'))
                ->required()
                ->tabindex(1)
                ->length(6)
                ->otp(),
        ];
    }

    protected function getActions(): array
    {
        return [
            $this->getVerifyAction(),
        ];
    }

    protected function getVerifyAction(): Action
    {
        return Action::make('verify-2fa')
            ->label(__('laravilt-auth::auth.two_factor_challenge.verify_button'))
            ->action(function (array $data) {
                return $this->verifyTwoFactor($data);
            });
    }

    public function store(Request $request)
    {
        // Validate the code
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $panel = $this->getPanel();
        $guard = $panel->getAuthGuard() ?? 'web';

        // Get the user from the session challenge
        $userId = $request->session()->get('login.id');
        $provider = config("auth.guards.{$guard}.provider");
        $modelClass = config("auth.providers.{$provider}.model");
        $user = $userId && $modelClass ? $modelClass::find($userId) : null;

        if (! $user) {
            // Dispatch 2FA challenge failed event (user not found)
            TwoFactorChallengeFailed::dispatch(
                null,
                'user_not_found',
                $panel->getId()
            );

            throw ValidationException::withMessages([
                'code' => ['The authentication code is invalid.'],
            ]);
        }

        // Get the user's 2FA method
        $method = $user->two_factor_method ?? 'totp';

        // Get the driver from the panel's provider manager
        $manager = $panel->getTwoFactorProviderManager();
        $driver = $manager?->getDriver($method);

        if (! $driver) {
            throw ValidationException::withMessages([
                'code' => 'Two-factor authentication method is not available.',
            ]);
        }

        // Verify the code using the driver
        $code = str_replace(' ', '', $request->input('code'));

        if (! $driver->verify($user, $code)) {
            // Dispatch 2FA challenge failed event (invalid code)
            TwoFactorChallengeFailed::dispatch(
                $user,
                'invalid_code',
                $panel->getId()
            );

            throw ValidationException::withMessages([
                'code' => ['The provided two factor authentication code was invalid.'],
            ]);
        }

        // Log the user in
        Auth::guard($guard)->login($user, $request->session()->get('login.remember', false));

        $request->session()->regenerate();

        // Mark that 2FA has been completed in this session
        $request->session()->put('auth.two_factor_confirmed_at', now()->timestamp);

        // Dispatch 2FA challenge successful event
        TwoFactorChallengeSuccessful::dispatch(
            $user,
            $method,
            $panel->getId()
        );

        // Clear the login challenge session data
        $request->session()->forget(['login.id', 'login.remember']);

        $redirectUrl = $panel->getPath();

        // Return an Inertia redirect
        return redirect($redirectUrl);
    }

    public function verifyTwoFactor(array $data): mixed
    {
        // Validate the code
        request()->validate([
            'code' => ['required', 'string'],
        ]);

        $panel = $this->getPanel();
        $guard = $panel->getAuthGuard() ?? 'web';

        // Get the user from the session challenge
        $userId = request()->session()->get('login.id');
        $provider = config("auth.guards.{$guard}.provider");
        $modelClass = config("auth.providers.{$provider}.model");
        $user = $userId && $modelClass ? $modelClass::find($userId) : null;

        if (! $user) {
            // Dispatch 2FA challenge failed event (user not found)
            TwoFactorChallengeFailed::dispatch(
                null,
                'user_not_found',
                $panel->getId()
            );

            throw ValidationException::withMessages([
                'code' => ['The authentication code is invalid.'],
            ]);
        }

        // Get the user's 2FA method
        $method = $user->two_factor_method ?? 'totp';

        // Get the driver from the panel's provider manager
        $manager = $panel->getTwoFactorProviderManager();
        $driver = $manager?->getDriver($method);

        if (! $driver) {
            throw ValidationException::withMessages([
                'code' => 'Two-factor authentication method is not available.',
            ]);
        }

        // Verify the code using the driver
        $code = str_replace(' ', '', $data['code']);

        if (! $driver->verify($user, $code)) {
            // Dispatch 2FA challenge failed event (invalid code)
            TwoFactorChallengeFailed::dispatch(
                $user,
                'invalid_code',
                $panel->getId()
            );

            throw ValidationException::withMessages([
                'code' => ['The provided two factor authentication code was invalid.'],
            ]);
        }

        // Log the user in
        Auth::guard($guard)->login($user, request()->session()->get('login.remember', false));

        request()->session()->regenerate();

        // Mark that 2FA has been completed in this session
        request()->session()->put('auth.two_factor_confirmed_at', now()->timestamp);

        // Dispatch 2FA challenge successful event
        TwoFactorChallengeSuccessful::dispatch(
            $user,
            $method,
            $panel->getId()
        );

        $redirectUrl = $panel->getPath();

        // Return the redirect URL in the response instead of a redirect
        // This allows Inertia to handle the navigation on the frontend
        return [
            'redirect' => url($redirectUrl),
        ];
    }

    /**
     * Resend 2FA email code.
     */
    public function resend(Request $request)
    {
        $panel = $this->getPanel();
        $guard = $panel->getAuthGuard() ?? 'web';

        // Get the user from the session
        $userId = $request->session()->get('login.id');

        if (! $userId) {
            return response()->json([
                'error' => __('laravilt-auth::auth.two_factor_challenge.session_expired'),
            ], 422);
        }

        $provider = config("auth.guards.{$guard}.provider");
        $modelClass = config("auth.providers.{$provider}.model");
        $user = $modelClass::find($userId);

        if (! $user) {
            return response()->json([
                'error' => __('laravilt-auth::auth.two_factor_challenge.user_not_found'),
            ], 422);
        }

        // Only resend for email 2FA method
        if ($user->two_factor_method !== 'email') {
            return response()->json([
                'error' => __('laravilt-auth::auth.two_factor_challenge.resend_not_available'),
            ], 422);
        }

        $manager = $panel->getTwoFactorProviderManager();
        $driver = $manager?->getDriver('email');

        if (! $driver) {
            return response()->json([
                'error' => __('laravilt-auth::auth.two_factor_challenge.method_not_available'),
            ], 422);
        }

        // Send new code
        $driver->send($user);

        return response()->json([
            'message' => __('laravilt-auth::auth.two_factor_challenge.code_resent'),
        ]);
    }

    protected function getInertiaProps(): array
    {
        $panel = $this->getPanel();
        $guard = $panel->getAuthGuard() ?? 'web';

        // Get the user from the session (if they're in the middle of 2FA challenge)
        $userId = request()->session()->get('login.id');
        $userTwoFactorMethod = null;

        if ($userId) {
            $provider = config("auth.guards.{$guard}.provider");
            $modelClass = config("auth.providers.{$provider}.model");
            $user = $modelClass::find($userId);

            if ($user) {
                $userTwoFactorMethod = $user->two_factor_method;

                // If user has email 2FA enabled, send the code
                if ($userTwoFactorMethod === 'email') {
                    $manager = $panel->getTwoFactorProviderManager();
                    $driver = $manager?->getDriver('email');

                    if ($driver) {
                        $driver->send($user);
                    }
                }
            }
        }

        return [
            'hasTwoFactorRecovery' => Features::enabled(Features::twoFactorAuthentication()),
            'recoveryUrl' => route($panel->getId().'.two-factor.recovery'),
            'hasPasskeys' => $panel->hasPasskeys(),
            'passkeyLoginOptionsUrl' => route($panel->getId().'.passkey.login-options'),
            'passkeyLoginUrl' => route($panel->getId().'.passkey.login'),
            'hasMagicLinks' => $panel->hasMagicLinks(),
            'magicLinkSendUrl' => route($panel->getId().'.magic-link.send'),
            'userTwoFactorMethod' => $userTwoFactorMethod,
            'resendUrl' => route($panel->getId().'.two-factor.resend'),
        ];
    }
}
