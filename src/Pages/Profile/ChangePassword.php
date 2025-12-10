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
    protected static ?string $title = null;

    protected static ?string $cluster = Settings::class;

    protected static ?string $slug = 'change-password';

    protected static bool $shouldRegisterNavigation = false;

    public static function getTitle(): string
    {
        return __('laravilt-auth::auth.profile.password.title');
    }

    public function getHeading(): string
    {
        return __('laravilt-auth::auth.profile.password.title');
    }

    public function getSubheading(): ?string
    {
        return __('laravilt-auth::auth.profile.password.description');
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
                ->label(__('laravilt-auth::auth.fields.current_password'))
                ->required()
                ->placeholder(__('laravilt-auth::auth.profile.password.current_placeholder')),

            TextInput::make('password')
                ->password()
                ->label(__('laravilt-auth::auth.fields.new_password'))
                ->required()
                ->placeholder(__('laravilt-auth::auth.profile.password.new_placeholder'))
                ->rules(['required', Password::defaults()]),

            TextInput::make('password_confirmation')
                ->password()
                ->label(__('laravilt-auth::auth.fields.new_password_confirmation'))
                ->required()
                ->placeholder(__('laravilt-auth::auth.profile.password.confirm_placeholder')),
        ];
    }

    protected function getActions(): array
    {
        return [
            Action::make('update-password')
                ->label(__('laravilt-auth::auth.profile.password.update'))
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
                'current_password' => __('laravilt-auth::auth.profile.password.current_incorrect'),
            ]);
        }

        // Update password
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        notify(__('laravilt-auth::auth.profile.page.password_updated'));

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
