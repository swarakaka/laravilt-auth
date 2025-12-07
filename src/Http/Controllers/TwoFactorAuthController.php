<?php

namespace Laravilt\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TwoFactorAuthController
{
    /**
     * Get the two-factor authentication status for the authenticated user.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'two_factor_enabled' => ! is_null($user->two_factor_secret),
            'two_factor_confirmed' => ! is_null($user->two_factor_confirmed_at),
        ]);
    }
}
