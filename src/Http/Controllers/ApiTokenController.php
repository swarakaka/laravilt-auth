<?php

namespace Laravilt\Auth\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravilt\Panel\Facades\Panel;

class ApiTokenController
{
    /**
     * Store a new API token.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'abilities' => ['nullable', 'array'],
        ]);

        $panel = Panel::getCurrent();
        $guard = $panel->getAuthGuard();
        $user = Auth::guard($guard)->user();

        // Create token using Laravel Sanctum
        $abilities = $request->input('abilities', []);
        $token = $user->createToken(
            $request->input('name'),
            $abilities
        );

        // Store the plain text token in session to show to user
        session()->flash('token', $token->plainTextToken);

        return back()->with('status', 'api-token-created');
    }

    /**
     * Delete an API token.
     */
    public function destroy(Request $request, $token)
    {
        $panel = Panel::getCurrent();
        $guard = $panel->getAuthGuard();
        $user = Auth::guard($guard)->user();

        // Find and delete the token
        $user->tokens()->where('id', $token)->delete();

        return back()->with('status', 'api-token-deleted');
    }

    /**
     * Revoke all API tokens.
     */
    public function revokeAll(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $panel = Panel::getCurrent();
        $guard = $panel->getAuthGuard();
        $user = Auth::guard($guard)->user();

        // Verify password
        if (! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided password is incorrect.'],
            ]);
        }

        // Delete all tokens
        $user->tokens()->delete();

        return back()->with('status', 'api-tokens-revoked');
    }
}
