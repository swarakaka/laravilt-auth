<?php

namespace Laravilt\Auth\Pages;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Laravilt\Actions\Action;
use Laravilt\Auth\Clusters\Settings;
use Laravilt\Forms\Components\TextInput;
use Laravilt\Panel\Enums\PageLayout;
use Laravilt\Panel\Pages\Page;

class Profile extends Page
{
    protected static ?string $title = 'Profile';

    protected static ?string $cluster = Settings::class;

    protected static ?string $slug = 'profile';

    protected static bool $shouldRegisterNavigation = false;

    public function getHeading(): string
    {
        return 'Profile Information';
    }

    public function getSubheading(): ?string
    {
        return 'Update your account profile information.';
    }

    public function getLayout(): string
    {
        return PageLayout::Settings->value;
    }

    protected function getSchema(): array
    {
        $panel = $this->getPanel();
        $guard = $panel->getAuthGuard();
        $user = Auth::guard($guard)->user();

        return [
            TextInput::make('name')
                ->label('Name')
                ->default($user->name ?? '')
                ->required()
                ->tabindex(1),

            TextInput::make('email')
                ->label('Email')
                ->email()
                ->default($user->email ?? '')
                ->required()
                ->tabindex(2),
        ];
    }

    protected function getActions(): array
    {
        return [
            Action::make('update-profile')
                ->label('Update Profile')
                ->action(function (array $data) {
                    return $this->updateProfile($data);
                }),
        ];
    }

    public function updateProfile(array $data): mixed
    {
        $panel = $this->getPanel();
        $guard = $panel->getAuthGuard();
        $user = Auth::guard($guard)->user();

        // Validate the data
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        // Update user
        $user->update($validated);

        notify('Profile updated successfully.');

        return back();
    }

    protected function getInertiaProps(): array
    {
        $panel = $this->getPanel();
        $guard = $panel->getAuthGuard();
        $user = Auth::guard($guard)->user();

        return [
            'user' => $user,
            'status' => session('status'),
        ];
    }
}
