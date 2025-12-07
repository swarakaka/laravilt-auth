<?php

namespace Laravilt\Auth\Pages\Profile;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravilt\Actions\Action;
use Laravilt\Auth\Clusters\Settings;
use Laravilt\Forms\Components\TextInput;
use Laravilt\Panel\Enums\PageLayout;
use Laravilt\Panel\Pages\Page;

class ChangePassword extends Page
{
    protected static ?string $title = 'Change Password';

    protected static ?string $cluster = Settings::class;

    protected static ?string $slug = 'change-password';

    protected static bool $shouldRegisterNavigation = false;

    public function getHeading(): string
    {
        return 'Change Password';
    }

    public function getSubheading(): ?string
    {
        return 'Update your password to keep your account secure.';
    }

    public function getLayout(): string
    {
        return PageLayout::Settings->value;
    }

    protected function getSchema(): array
    {
        return [
            TextInput::make('current_password')
                ->password()
                ->label('Current Password')
                ->required()
                ->placeholder('Enter your current password'),

            TextInput::make('password')
                ->password()
                ->label('New Password')
                ->required()
                ->placeholder('Enter your new password')
                ->rules(['required', Password::defaults()]),

            TextInput::make('password_confirmation')
                ->password()
                ->label('Confirm New Password')
                ->required()
                ->placeholder('Confirm your new password'),
        ];
    }

    protected function getActions(): array
    {
        return [
            Action::make('update-password')
                ->label('Update Password')
                ->action(function (array $data) {
                    return $this->updatePassword($data);
                }),
        ];
    }

    public function updatePassword(array $data): mixed
    {
        $panel = $this->getPanel();
        $guard = $panel->getAuthGuard();
        $user = Auth::guard($guard)->user();

        // Validate the data
        $validator = Validator::make($data, [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        // Check if current password is correct
        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        // Update password
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        notify('Password updated successfully.');

        return back();
    }

    protected function getInertiaProps(): array
    {
        $panel = $this->getPanel();
        $guard = $panel->getAuthGuard();
        $user = Auth::guard($guard)->user();

        return [
            'user' => $user,
        ];
    }
}
