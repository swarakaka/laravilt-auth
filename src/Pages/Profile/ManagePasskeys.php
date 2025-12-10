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
    protected static ?string $title = null;

    protected static ?string $cluster = Settings::class;

    protected static ?string $slug = 'passkeys';

    protected static bool $shouldRegisterNavigation = false;

    protected ?string $component = 'laravilt-auth/ManagePasskeysPage';

    public static function getTitle(): string
    {
        return __('laravilt-auth::auth.profile.passkeys.title');
    }

    public function getHeading(): string
    {
        return __('laravilt-auth::auth.profile.passkeys.title');
    }

    public function getSubheading(): ?string
    {
        return __('laravilt-auth::auth.profile.passkeys.description');
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
            ->label(__('laravilt-auth::auth.profile.passkeys.register_new'))
            ->icon('key')
            ->color('primary')
            ->modalHeading(__('laravilt-auth::auth.profile.passkeys.register_title'))
            ->modalDescription(__('laravilt-auth::auth.profile.passkeys.register_description'))
            ->modalIcon('key')
            ->schema([
                TextInput::make('name')
                    ->label(__('laravilt-auth::auth.profile.passkeys.passkey_name'))
                    ->placeholder(__('laravilt-auth::auth.profile.passkeys.name_placeholder'))
                    ->required()
                    ->helperText(__('laravilt-auth::auth.profile.passkeys.name_hint')),
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
