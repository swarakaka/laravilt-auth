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
    protected static ?string $title = 'Set Password';

    protected static bool $shouldRegisterNavigation = false;

    public function getHeading(): string
    {
        return 'Set Your Password';
    }

    public function getSubheading(): ?string
    {
        return 'Please set a password for your account to continue.';
    }

    public function getLayout(): string
    {
        return PageLayout::Card->value;
    }

    protected function getSchema(): array
    {
        return [
            TextInput::make('password')
                ->label('New Password')
                ->password()
                ->required()
                ->rules(['required', Password::defaults(), 'confirmed'])
                ->helperText('Password must be at least 8 characters.')
                ->tabindex(1),

            TextInput::make('password_confirmation')
                ->label('Confirm Password')
                ->password()
                ->required()
                ->tabindex(2),
        ];
    }

    protected function getActions(): array
    {
        return [
            Action::make('set-password')
                ->label('Set Password')
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
