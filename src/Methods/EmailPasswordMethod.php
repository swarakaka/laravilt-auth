<?php

namespace Laravilt\Auth\Methods;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailPasswordMethod extends BaseAuthMethod
{
    /**
     * Get the method name.
     */
    public function getName(): string
    {
        return 'email';
    }

    /**
     * Authenticate the user.
     */
    public function authenticate(Request $request): ?Authenticatable
    {
        $credentials = $request->only(['email', 'password']);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            return Auth::user();
        }

        return null;
    }

    /**
     * Check if this method can handle the request.
     */
    public function canHandle(Request $request): bool
    {
        return $request->has(['email', 'password']);
    }

    /**
     * Validate the credentials.
     */
    public function validate(Request $request): bool
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        return true;
    }
}
