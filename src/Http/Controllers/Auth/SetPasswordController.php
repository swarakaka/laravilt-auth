<?php

namespace Laravilt\Auth\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Laravilt\Panel\Facades\Panel;

class SetPasswordController
{
    /**
     * Show the set password form.
     */
    public function create(Request $request): Response
    {
        $panel = Panel::getCurrent();

        return Inertia::render('laravilt-auth/AuthPage', [
            'page' => [
                'heading' => 'Set Password',
                'subheading' => 'Please set a password for your account.',
                'schema' => $this->getSetPasswordSchema(),
            ],
            'formAction' => route($panel->getId().'.auth.set-password.store'),
            'formMethod' => 'POST',
        ]);
    }

    /**
     * Handle the set password request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();
        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();

        return redirect()->intended()->with('status', 'Password set successfully.');
    }

    /**
     * Get set password form schema.
     */
    protected function getSetPasswordSchema(): array
    {
        return [
            \Laravilt\Forms\Components\TextInput::make('password')
                ->label('Password')
                ->password()
                ->required()
                ->autofocus()
                ->tabindex(1)
                ->toLaraviltProps(),

            \Laravilt\Forms\Components\TextInput::make('password_confirmation')
                ->label('Confirm Password')
                ->password()
                ->required()
                ->tabindex(2)
                ->toLaraviltProps(),
        ];
    }
}
