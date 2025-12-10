<?php

namespace Laravilt\Auth\Pages;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Laravilt\Actions\Action;
use Laravilt\Forms\Components\TextInput;
use Laravilt\Panel\Enums\PageLayout;
use Laravilt\Panel\Pages\Page;

class SetPassword extends Page
{
    protected static ?string $title = null;

    protected static bool $shouldRegisterNavigation = false;

    public static function getTitle(): string
    {
        return __('laravilt-auth::auth.set_password.title');
    }

    public function getHeading(): string
    {
        return __('laravilt-auth::auth.set_password.heading');
    }

    public function getSubheading(): ?string
    {
        return __('laravilt-auth::auth.set_password.subheading');
    }

    public function getLayout(): string
    {
        return PageLayout::Card->value;
    }

    protected function getSchema(): array
    {
        return [
            TextInput::make('password')
                ->label(__('laravilt-auth::auth.fields.new_password'))
                ->password()
                ->required()
                ->rules(['required', Password::defaults(), 'confirmed'])
                ->helperText(__('laravilt-auth::auth.set_password.password_hint'))
                ->tabindex(1),

            TextInput::make('password_confirmation')
                ->label(__('laravilt-auth::auth.fields.password_confirmation'))
                ->password()
                ->required()
                ->tabindex(2),
        ];
    }

    protected function getActions(): array
    {
        return [
            Action::make('set-password')
                ->label(__('laravilt-auth::auth.set_password.button'))
                ->action(function (array $data) {
                    return $this->setPassword($data);
                })
                ->preserveState(false)
                ->preserveScroll(false),
        ];
    }

    public function setPassword(array $data): mixed
    {
        // Validate the data
        $validated = validator($data, [
            'password' => ['required', Password::defaults(), 'confirmed'],
        ])->validate();

        $user = auth()->user();

        // Update the user's password
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Redirect to the intended page or dashboard
        $panel = $this->getPanel();

        return redirect()->intended($panel->getPath());
    }
}
