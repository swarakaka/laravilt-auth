<?php

namespace Laravilt\Auth\Pages;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Laravilt\Actions\Action;
use Laravilt\Auth\Events\LoginAttempt;
use Laravilt\Auth\Events\LoginFailed;
use Laravilt\Auth\Events\LoginSuccessful;
use Laravilt\Forms\Components\Checkbox;
use Laravilt\Forms\Components\TextInput;
use Laravilt\Panel\Enums\PageLayout;
use Laravilt\Panel\Pages\Page;

class Login extends Page
{
    protected static ?string $title = 'Sign In';

    protected static bool $shouldRegisterNavigation = false;

    public function getHeading(): string
    {
        return 'Sign In';
    }

    public function getSubheading(): ?string
    {
        return 'Welcome back! Please enter your credentials.';
    }

    public function getLayout(): string
    {
        return PageLayout::Card->value;
    }

    protected function getSchema(): array
    {
        $panel = $this->getPanel();
        $passwordField = TextInput::make('password')
            ->label('Password')
            ->password()
            ->required()
            ->tabindex(2);

        // Only add forgot password link if password reset is enabled
        if ($panel->hasPasswordReset()) {
            $passwordField->hintAction(
                Action::make('forgot-password')
                    ->link()
                    ->url(route($panel->getId().'.password.request'))
            );
        }

        return [
            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->autofocus()
                ->tabindex(1),

            $passwordField,

            Checkbox::make('remember')
                ->label('Remember me')
                ->tabindex(3),
        ];
    }

    protected function getActions(): array
    {
        return [
            Action::make('login')
                ->label('Sign In')
                ->action(function (array $data) {
                    return $this->attemptLogin($data);
                })
                ->preserveState(false)
                ->preserveScroll(false),
        ];
    }

    public function attemptLogin(array $data): mixed
    {
        $panel = $this->getPanel();

        // Dispatch login attempt event
        LoginAttempt::dispatch(
            $data['email'],
            $panel->getId()
        );

        // Validate the login request
        $credentials = [
            Fortify::username() => $data['email'],
            'password' => $data['password'],
        ];

        // Attempt to authenticate
        $guard = $panel->getAuthGuard();

        if (Auth::guard($guard)->attempt($credentials, $data['remember'] ?? false)) {
            $user = Auth::guard($guard)->user();

            // Check if user has two-factor authentication enabled AND confirmed
            if ($user && ! is_null($user->two_factor_secret) && ! is_null($user->two_factor_confirmed_at)) {
                // User has 2FA enabled and confirmed, logout and redirect to challenge
                Auth::guard($guard)->logout();

                // Store the user ID in session for the challenge
                request()->session()->put([
                    'login.id' => $user->getAuthIdentifier(),
                    'login.remember' => $data['remember'] ?? false,
                ]);

                return redirect()->route($this->getPanel()->getId().'.two-factor.challenge');
            }

            // No 2FA, proceed with normal login
            request()->session()->regenerate();

            // Mark that auth is complete (no 2FA required)
            request()->session()->put('auth.two_factor_confirmed_at', now()->timestamp);

            // Dispatch login successful event
            LoginSuccessful::dispatch(
                $user,
                $panel->getId(),
                $data['remember'] ?? false
            );

            $redirectPath = $panel->getPath();
            \Log::info('Login successful, redirecting to: '.$redirectPath);

            return redirect($redirectPath);
        }

        // If authentication failed
        // Dispatch login failed event
        LoginFailed::dispatch(
            $data['email'],
            $panel->getId(),
            'invalid_credentials'
        );

        throw ValidationException::withMessages([
            Fortify::username() => [trans('auth.failed')],
        ]);
    }

    protected function getInertiaProps(): array
    {
        $panel = $this->getPanel();

        $props = [
            'canResetPassword' => $panel->hasPasswordReset(),
            'canRegister' => $panel->hasRegistration(),
            'resetPasswordUrl' => $panel->hasPasswordReset() ? route($panel->getId().'.password.request') : null,
            'registerUrl' => $panel->hasRegistration() ? route($panel->getId().'.register') : null,
            'socialProviders' => $panel->hasSocialLogin() ? $this->getSocialProviders() : [],
            'socialRedirectUrl' => $panel->hasSocialLogin() ? route($panel->getId().'.auth.social.redirect', ['provider' => ':provider']) : null,
        ];

        \Log::info('Login getInertiaProps', [
            'hasSocialLogin' => $panel->hasSocialLogin(),
            'socialProviders' => $props['socialProviders'],
            'socialRedirectUrl' => $props['socialRedirectUrl'],
        ]);

        return $props;
    }

    public function getSocialProviders(): array
    {
        $panel = $this->getPanel();
        $providers = [];

        if ($panel->hasSocialLogin()) {
            // Get social providers from panel configuration
            // These are already formatted as arrays by getProvidersForFrontend()
            $providers = $panel->getSocialProviders();

            \Log::info('getSocialProviders', ['providers' => $providers]);
        }

        return $providers;
    }

    /**
     * Handle POST login request (for direct form submission).
     *
     * This method handles login when the form is submitted directly
     * via POST rather than through the action system.
     */
    public function store(Request $request)
    {
        return $this->attemptLogin($request->all());
    }

    /**
     * Destroy an authenticated session (logout).
     */
    public function destroy(Request $request)
    {
        $guard = $this->getPanel()->getAuthGuard();

        Auth::guard($guard)->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect($this->getPanel()->url());
    }
}
