<?php

namespace Laravilt\Auth\Pages;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Laravilt\Actions\Action;
use Laravilt\Auth\Events\PasswordReset as PasswordResetEvent;
use Laravilt\Forms\Components\Hidden;
use Laravilt\Forms\Components\TextInput;
use Laravilt\Panel\Enums\PageLayout;
use Laravilt\Panel\Pages\Page;

class ResetPassword extends Page
{
    protected static ?string $title = null;

    protected static bool $shouldRegisterNavigation = false;

    public static function getTitle(): string
    {
        return __('laravilt-auth::auth.reset_password.title');
    }

    public function getHeading(): string
    {
        return __('laravilt-auth::auth.reset_password.heading');
    }

    public function getSubheading(): ?string
    {
        return __('laravilt-auth::auth.reset_password.subheading');
    }

    public function getLayout(): string
    {
        return PageLayout::Card->value;
    }

    /**
     * Handle POST request to reset password.
     */
    public function store(Request $request)
    {
        return $this->handleResetPassword([
            'token' => $request->token,
            'email' => $request->email,
            'password' => $request->password,
            'password_confirmation' => $request->password_confirmation,
        ]);
    }

    protected function getSchema(): array
    {
        return [
            Hidden::make('token')
                ->default(request()->route('token')),

            Hidden::make('email')
                ->default(request()->email),

            TextInput::make('password')
                ->label(__('laravilt-auth::auth.fields.new_password'))
                ->password()
                ->required()
                ->autofocus()
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
            Action::make('reset-password')
                ->label(__('laravilt-auth::auth.reset_password.button'))
                ->preserveState(false)
                ->preserveScroll(false)
                ->action(function (array $data) {
                    return $this->handleResetPassword($data);
                }),
        ];
    }

    public function handleResetPassword(array $data): mixed
    {
        // Validate password
        request()->validate([
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $resetUser = null;

        $status = Password::reset(
            [
                'email' => $data['email'],
                'password' => $data['password'],
                'password_confirmation' => $data['password_confirmation'],
                'token' => $data['token'],
            ],
            function ($user, $password) use (&$resetUser) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                $resetUser = $user;
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            $panel = $this->getPanel();

            // Dispatch password reset event
            PasswordResetEvent::dispatch(
                $resetUser,
                $panel->getId()
            );

            // Add success notification
            session()->flash('notifications', [[
                'type' => 'success',
                'message' => 'Your password has been reset successfully! You can now login with your new password.',
            ]]);

            return redirect()->route($panel->getId().'.login')
                ->with('status', 'Your password has been reset successfully!');
        }

        return back()->withErrors(['email' => [__($status)]]);
    }
}
