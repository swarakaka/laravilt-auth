<?php

namespace Laravilt\Auth\Methods;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravilt\Auth\Services\TwoFactorService;

class TwoFactorMethod extends BaseAuthMethod
{
    public function __construct(
        protected TwoFactorService $twoFactorService,
        array $config = []
    ) {
        parent::__construct($config);
    }

    /**
     * Get the method name.
     */
    public function getName(): string
    {
        return '2fa';
    }

    /**
     * Authenticate the user.
     */
    public function authenticate(Request $request): ?Authenticatable
    {
        $code = $request->input('code');
        $method = $request->input('method', 'totp');

        $user = Auth::user();

        if (! $user) {
            return null;
        }

        if (! $this->twoFactorService->verify($user, $code, $method)) {
            return null;
        }

        // Mark 2FA as verified in session
        $request->session()->put('2fa_verified', true);

        return $user;
    }

    /**
     * Check if this method can handle the request.
     */
    public function canHandle(Request $request): bool
    {
        return $request->has('code') && Auth::check() && ! $request->session()->get('2fa_verified');
    }

    /**
     * Validate the credentials.
     */
    public function validate(Request $request): bool
    {
        $request->validate([
            'code' => ['required', 'string'],
            'method' => ['nullable', 'string', 'in:totp,sms,email'],
        ]);

        return true;
    }

    /**
     * Check if 2FA is required for user.
     */
    public function isRequired(Authenticatable $user): bool
    {
        return $user->two_factor_enabled ?? false;
    }

    /**
     * Send 2FA code via SMS or email.
     */
    public function sendCode(Authenticatable $user, string $method = 'sms'): bool
    {
        return $this->twoFactorService->sendCode($user, $method);
    }

    /**
     * Enable 2FA for user.
     */
    public function enable(Authenticatable $user, string $method = 'totp'): array
    {
        return $this->twoFactorService->enable($user, $method);
    }

    /**
     * Disable 2FA for user.
     */
    public function disable(Authenticatable $user): bool
    {
        return $this->twoFactorService->disable($user);
    }

    /**
     * Generate recovery codes.
     */
    public function generateRecoveryCodes(Authenticatable $user): array
    {
        return $this->twoFactorService->generateRecoveryCodes($user);
    }
}
