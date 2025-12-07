<?php

namespace Laravilt\Auth\Pages\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Features;
use Laravilt\Actions\Action;
use Laravilt\Forms\Components\TextInput;
use Laravilt\Panel\Enums\PageLayout;
use Laravilt\Panel\Pages\Page;

class TwoFactorRecovery extends Page
{
    protected static ?string $title = 'Two-Factor Recovery';

    protected static bool $shouldRegisterNavigation = false;

    public function getHeading(): string
    {
        return 'Two-Factor Recovery';
    }

    public function getSubheading(): ?string
    {
        return 'Enter one of your emergency recovery codes.';
    }

    public function getLayout(): string
    {
        return PageLayout::Card->value;
    }

    protected function getSchema(): array
    {
        return [
            TextInput::make('recovery_code')
                ->label('Recovery Code')
                ->required()
                ->autofocus()
                ->tabindex(1)
                ->placeholder('Enter your recovery code'),
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
            ->label('Verify')
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
                'recovery_code' => ['The recovery code is invalid.'],
            ]);
        }

        // Verify the recovery code
        $recoveryCode = str_replace(' ', '', $data['recovery_code']);

        if (! $user->two_factor_recovery_codes) {
            throw ValidationException::withMessages([
                'recovery_code' => ['The recovery code is invalid.'],
            ]);
        }

        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);

        if (! in_array($recoveryCode, $recoveryCodes)) {
            throw ValidationException::withMessages([
                'recovery_code' => ['The recovery code is invalid.'],
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
