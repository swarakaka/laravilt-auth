<?php

namespace Laravilt\Auth\Pages\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Features;
use Laravilt\Actions\Action;
use Laravilt\Forms\Components\TextInput;
use Laravilt\Panel\Enums\PageLayout;
use Laravilt\Panel\Pages\Page;

class TwoFactorRecovery extends Page
{
    protected static ?string $title = null;

    protected static bool $shouldRegisterNavigation = false;

    public static function getTitle(): string
    {
        return __('laravilt-auth::auth.two_factor_recovery.title');
    }

    public function getHeading(): string
    {
        return __('laravilt-auth::auth.two_factor_recovery.heading');
    }

    public function getSubheading(): ?string
    {
        return __('laravilt-auth::auth.two_factor_recovery.subheading');
    }

    public function getLayout(): string
    {
        return PageLayout::Card->value;
    }

    public function store(Request $request)
    {
        return $this->verifyRecoveryCode($request->all());
    }

    protected function getSchema(): array
    {
        return [
            TextInput::make('recovery_code')
                ->label(__('laravilt-auth::auth.fields.recovery_code'))
                ->required()
                ->autofocus()
                ->tabindex(1)
                ->placeholder(__('laravilt-auth::auth.two_factor_recovery.placeholder')),
        ];
    }

    protected function getActions(): array
    {
        return [
            $this->getVerifyRecoveryAction(),
        ];
    }

    protected function getVerifyRecoveryAction(): Action
    {
        return Action::make('verify-recovery-code')
            ->label(__('laravilt-auth::auth.two_factor_recovery.button'))
            ->preserveScroll(false)
            ->preserveState(false)
            ->action(function (array $data) {
                return $this->verifyRecoveryCode($data);
            });
    }

    public function verifyRecoveryCode(array $data): mixed
    {
        // Validate the recovery code
        request()->validate([
            'recovery_code' => ['required', 'string'],
        ]);

        $panel = $this->getPanel();
        $guard = $panel->getAuthGuard() ?? 'web';

        // Get the user from the session challenge
        $userId = request()->session()->get('login.id');
        $provider = config("auth.guards.{$guard}.provider");
        $modelClass = config("auth.providers.{$provider}.model");
        $user = $userId && $modelClass ? $modelClass::find($userId) : null;

        if (! $user) {
            throw ValidationException::withMessages([
                'recovery_code' => [__('laravilt-auth::auth.two_factor_recovery.invalid_code')],
            ]);
        }

        // Verify the recovery code
        $recoveryCode = str_replace(' ', '', $data['recovery_code']);

        if (! $user->two_factor_recovery_codes) {
            throw ValidationException::withMessages([
                'recovery_code' => [__('laravilt-auth::auth.two_factor_recovery.invalid_code')],
            ]);
        }

        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);

        if (! in_array($recoveryCode, $recoveryCodes)) {
            throw ValidationException::withMessages([
                'recovery_code' => [__('laravilt-auth::auth.two_factor_recovery.invalid_code')],
            ]);
        }

        // Mark the recovery code as used
        $user->replaceRecoveryCode($recoveryCode);

        // Log the user in
        Auth::guard($guard)->login($user, request()->session()->get('login.remember', false));

        request()->session()->regenerate();

        // Mark that 2FA has been completed in this session
        request()->session()->put('auth.two_factor_confirmed_at', now()->timestamp);

        return redirect()->intended($panel->getPath());
    }

    protected function getInertiaProps(): array
    {
        $panel = $this->getPanel();

        return [
            'hasTwoFactorChallenge' => Features::enabled(Features::twoFactorAuthentication()),
            'challengeUrl' => route($panel->getId().'.two-factor.challenge'),
        ];
    }
}
