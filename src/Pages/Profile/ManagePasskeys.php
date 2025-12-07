<?php

namespace Laravilt\Auth\Pages\Profile;

use Illuminate\Support\Facades\Auth;
use Laravilt\Actions\Action;
use Laravilt\Auth\Clusters\Settings;
use Laravilt\Forms\Components\TextInput;
use Laravilt\Panel\Enums\PageLayout;
use Laravilt\Panel\Pages\Page;

class ManagePasskeys extends Page
{
    protected static ?string $title = 'Passkeys';

    protected static ?string $cluster = Settings::class;

    protected static ?string $slug = 'passkeys';

    protected static bool $shouldRegisterNavigation = false;

    protected ?string $component = 'laravilt-auth/ManagePasskeysPage';

    public function getHeading(): string
    {
        return 'Passkeys';
    }

    public function getSubheading(): ?string
    {
        return 'Manage passkeys that allow you to sign in without a password.';
    }

    public function getLayout(): string
    {
        return PageLayout::Settings->value;
    }

    protected function getSchema(): array
    {
        return [];
    }

    protected function getActions(): array
    {
        return [];
    }

    protected function getInertiaProps(): array
    {
        $panel = $this->getPanel();
        $guard = $panel->getAuthGuard();
        $user = Auth::guard($guard)->user();

        // Get user's WebAuthn credentials
        $credentials = $user->webAuthnCredentials()->get();

        $passkeys = $credentials->map(function ($credential) {
            return [
                'id' => $credential->id,
                'name' => $credential->alias ?? 'Passkey',
                'created_at' => $credential->created_at->toDateTimeString(),
                'last_used_at' => $credential->updated_at->toDateTimeString(),
            ];
        })->values()->all();

        return [
            'registerAction' => $this->getRegisterPasskeyAction(),
            'passkeys' => $passkeys,
            'canRegister' => count($passkeys) < 10,
            'maxPasskeys' => 10,
            'registerOptionsUrl' => route($panel->getId().'.passkeys.register-options'),
            'registerUrl' => route($panel->getId().'.passkeys.register'),
        ];
    }

    /**
     * Get the register passkey action.
     */
    protected function getRegisterPasskeyAction(): array
    {
        $action = Action::make('register-passkey')
            ->label('Register New Passkey')
            ->icon('key')
            ->color('primary')
            ->modalHeading('Register New Passkey')
            ->modalDescription('Enter a name for this passkey to help you identify it later.')
            ->modalIcon('key')
            ->schema([
                TextInput::make('name')
                    ->label('Passkey Name')
                    ->placeholder('My Device')
                    ->required()
                    ->helperText('This name will help you identify this passkey later.'),
            ])
            ->toArray();

        // Remove the action URL so it doesn't execute backend logic
        // The frontend will handle the WebAuthn flow entirely
        $action['actionUrl'] = null;
        $action['url'] = null;
        $action['hasAction'] = false;

        return $action;
    }
}
