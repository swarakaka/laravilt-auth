<?php

namespace Laravilt\Auth\Services;

use Illuminate\Contracts\Auth\Authenticatable;

class WebAuthnService
{
    /**
     * Generate registration options for WebAuthn.
     *
     * @param  Authenticatable&\Illuminate\Database\Eloquent\Model  $user
     */
    public function generateRegistrationOptions(Authenticatable $user): array
    {
        // This is a simplified implementation
        // In production, use web-auth/webauthn-lib properly

        return [
            'challenge' => base64_encode(random_bytes(32)),
            'rp' => [
                'name' => config('app.name'),
                'id' => parse_url(config('app.url'), PHP_URL_HOST),
            ],
            'user' => [
                'id' => base64_encode($user->getAuthIdentifier()),
                'name' => $user->email,
                'displayName' => $user->name,
            ],
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7], // ES256
                ['type' => 'public-key', 'alg' => -257], // RS256
            ],
            'timeout' => 60000,
            'attestation' => 'none',
            'authenticatorSelection' => [
                'authenticatorAttachment' => 'platform',
                'requireResidentKey' => false,
                'userVerification' => 'preferred',
            ],
        ];
    }

    /**
     * Generate authentication options for WebAuthn.
     */
    public function generateAuthenticationOptions(string $email): array
    {
        return [
            'challenge' => base64_encode(random_bytes(32)),
            'timeout' => 60000,
            'rpId' => parse_url(config('app.url'), PHP_URL_HOST),
            'userVerification' => 'preferred',
        ];
    }

    /**
     * Verify WebAuthn credential.
     */
    public function verify(string $email, array $credential): bool
    {
        // This is a simplified implementation
        // In production, properly verify the credential using web-auth/webauthn-lib

        return ! empty($credential);
    }

    /**
     * Register a new WebAuthn credential.
     */
    public function register(Authenticatable $user, array $credential): bool
    {
        // Store credential in database
        // $user->webAuthnCredentials()->create([
        //     'credential_id' => $credential['id'],
        //     'public_key' => $credential['publicKey'],
        //     'counter' => 0,
        // ]);

        return true;
    }
}
