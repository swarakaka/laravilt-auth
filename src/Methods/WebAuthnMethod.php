<?php

namespace Laravilt\Auth\Methods;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravilt\Auth\Services\WebAuthnService;

class WebAuthnMethod extends BaseAuthMethod
{
    public function __construct(
        protected WebAuthnService $webAuthnService,
        array $config = []
    ) {
        parent::__construct($config);
    }

    /**
     * Get the method name.
     */
    public function getName(): string
    {
        return 'webauthn';
    }

    /**
     * Authenticate the user.
     */
    public function authenticate(Request $request): ?Authenticatable
    {
        $email = $request->input('email');
        $credential = $request->input('credential');

        if (! $this->webAuthnService->verify($email, $credential)) {
            return null;
        }

        $guard = $this->config('guard', 'web');
        $model = $this->config('model');

        $user = $model::where('email', $email)->first();

        if ($user) {
            Auth::guard($guard)->login($user, true);

            return $user;
        }

        return null;
    }

    /**
     * Check if this method can handle the request.
     */
    public function canHandle(Request $request): bool
    {
        return $request->has(['email', 'credential']);
    }

    /**
     * Validate the credentials.
     */
    public function validate(Request $request): bool
    {
        $request->validate([
            'email' => ['required', 'email'],
            'credential' => ['required', 'array'],
        ]);

        return true;
    }

    /**
     * Generate registration options.
     */
    public function generateRegistrationOptions(Authenticatable $user): array
    {
        return $this->webAuthnService->generateRegistrationOptions($user);
    }

    /**
     * Generate authentication options.
     */
    public function generateAuthenticationOptions(string $email): array
    {
        return $this->webAuthnService->generateAuthenticationOptions($email);
    }
}
