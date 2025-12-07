<?php

namespace Laravilt\Auth\Pages;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Laravilt\Actions\Action;
use Laravilt\Auth\Events\PasswordResetRequested;
use Laravilt\Forms\Components\TextInput;
use Laravilt\Panel\Enums\PageLayout;
use Laravilt\Panel\Pages\Page;

class ForgotPassword extends Page
{
    protected static ?string $title = 'Forgot Password';

    protected static bool $shouldRegisterNavigation = false;

    public function getHeading(): string
    {
        return 'Forgot Password';
    }

    public function getSubheading(): ?string
    {
        return 'Enter your email to receive a password reset link.';
    }

    public function getLayout(): string
    {
        return PageLayout::Card->value;
    }

    /**
     * Handle POST request to send password reset link.
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        return $this->sendResetLink(['email' => $request->email]);
    }

    protected function getSchema(): array
    {
        return [
            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->autofocus()
                ->tabindex(1),
        ];
    }

    protected function getActions(): array
    {
        return [
            Action::make('send-reset-link')
                ->label('Send Reset Link')
                ->preserveState(false)
                ->preserveScroll(false)
                ->action(function (array $data) {
                    return $this->sendResetLink($data);
                }),
        ];
    }

    public function sendResetLink(array $data): mixed
    {
        \Log::info('Attempting to send password reset link', ['email' => $data['email']]);

        try {
            // Store the email before sending to use it later
            $email = $data['email'];

            \Log::info('About to call Password::sendResetLink');

            $panel = $this->getPanel();

            // Temporarily set the reset password URL to use the panel route
            \Illuminate\Auth\Notifications\ResetPassword::createUrlUsing(function ($user, $token) use ($panel) {
                return route($panel->getId().'.password.reset', ['token' => $token, 'email' => $user->email]);
            });

            // Send reset link
            $status = Password::sendResetLink(
                ['email' => $email]
            );

            // Reset the URL callback to avoid affecting other password resets
            \Illuminate\Auth\Notifications\ResetPassword::createUrlUsing(null);

            \Log::info('Password reset link status', [
                'status' => $status,
                'constants' => [
                    'RESET_LINK_SENT' => Password::RESET_LINK_SENT,
                    'INVALID_USER' => Password::INVALID_USER,
                    'RESET_THROTTLED' => Password::RESET_THROTTLED,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Password reset link exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            session()->flash('notifications', [[
                'type' => 'error',
                'message' => 'An error occurred while sending the password reset link. Please try again. Error: '.$e->getMessage(),
            ]]);

            return back()->withErrors(['email' => 'An error occurred. Please try again.']);
        }

        if ($status === Password::RESET_LINK_SENT) {
            \Log::info('Password reset link sent successfully');

            // Dispatch password reset requested event
            PasswordResetRequested::dispatch(
                $email,
                $panel->getId()
            );

            $message = 'Password reset link has been sent to your email!';

            // Add notification for success
            session()->flash('notifications', [[
                'type' => 'success',
                'message' => $message,
            ]]);

            return back()->with('status', $message);
        }

        \Log::warning('Password reset link failed', ['status' => $status, 'translated' => __($status)]);

        return back()->withErrors(['email' => __($status)]);
    }

    protected function getInertiaProps(): array
    {
        $panel = $this->getPanel();

        return [
            'canLogin' => $panel->hasLogin(),
            'loginUrl' => $panel->hasLogin() ? route($panel->getId().'.login') : null,
            'status' => session('status'),
        ];
    }
}
