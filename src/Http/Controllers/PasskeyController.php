<?php

namespace Laravilt\Auth\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Laragear\WebAuthn\Assertion\Creator\AssertionCreation;
use Laragear\WebAuthn\Assertion\Creator\AssertionCreator;
use Laragear\WebAuthn\Assertion\Validator\AssertionValidation;
use Laragear\WebAuthn\Assertion\Validator\AssertionValidator;
use Laragear\WebAuthn\Attestation\Creator\AttestationCreation;
use Laragear\WebAuthn\Attestation\Creator\AttestationCreator;
use Laragear\WebAuthn\Http\Requests\AssertedRequest;
use Laragear\WebAuthn\Http\Requests\AttestedRequest;
use Laragear\WebAuthn\JsonTransport;
use Laravilt\Auth\Events\PasskeyDeleted;
use Laravilt\Auth\Events\PasskeyRegistered;

class PasskeyController extends Controller
{
    /**
     * Generate attestation options for passkey registration.
     */
    public function registerOptions(Request $request, AttestationCreator $creator)
    {
        $user = Auth::user();

        $attestation = $creator
            ->send(new AttestationCreation($user))
            ->thenReturn();

        return $attestation->json;
    }

    /**
     * Register a new passkey credential.
     */
    public function register(AttestedRequest $request)
    {
        $validated = $request->validated();

        // Save the credential with the provided name
        $credential = $request->save();

        // Update credential alias with the user-provided name
        if (isset($validated['name'])) {
            $credential->update(['alias' => $validated['name']]);
        }

        // Get the panel from request attributes
        $panel = $request->attributes->get('panel');

        // Dispatch passkey registered event
        if ($panel) {
            PasskeyRegistered::dispatch(
                Auth::user(),
                $credential->id,
                $panel->getId()
            );
        }

        // Flash success notification
        session()->flash('notifications', [[
            'type' => 'success',
            'message' => 'Passkey registered successfully!',
        ]]);

        return back();
    }

    /**
     * Delete a passkey credential.
     */
    public function destroy(Request $request, string $credentialId)
    {
        $user = Auth::user();

        $credential = $user->webAuthnCredentials()
            ->where('id', $credentialId)
            ->firstOrFail();

        $credential->delete();

        // Get the panel from request attributes
        $panel = $request->attributes->get('panel');

        // Dispatch passkey deleted event
        if ($panel) {
            PasskeyDeleted::dispatch(
                $user,
                $credentialId,
                $panel->getId()
            );
        }

        // Flash success notification
        session()->flash('notifications', [[
            'type' => 'success',
            'message' => 'Passkey deleted successfully!',
        ]]);

        return back();
    }

    /**
     * Generate assertion options for passkey login.
     */
    public function loginOptions(Request $request, AssertionCreator $creator)
    {
        // Get the user ID from the session (set during login challenge)
        $userId = $request->session()->get('login.id');

        if (! $userId) {
            return response()->json(['error' => 'No login session found'], 400);
        }

        // Get the panel from request attributes (set by IdentifyPanel middleware)
        $panel = $request->attributes->get('panel');

        if (! $panel) {
            return response()->json(['error' => 'Panel not found'], 404);
        }

        $guard = $panel->getAuthGuard() ?? 'web';
        $provider = config("auth.guards.{$guard}.provider");
        $modelClass = config("auth.providers.{$provider}.model");
        $user = $modelClass::find($userId);

        if (! $user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $assertion = $creator
            ->send(new AssertionCreation($user))
            ->thenReturn();

        return $assertion->json;
    }

    /**
     * Verify passkey assertion and log user in.
     */
    public function login(AssertedRequest $request, AssertionValidator $validator)
    {
        // Get the panel from request attributes (set by IdentifyPanel middleware)
        $panel = $request->attributes->get('panel');

        if (! $panel) {
            return response()->json(['error' => 'Panel not found'], 404);
        }

        $guard = $panel->getAuthGuard() ?? 'web';
        $remember = $request->session()->get('login.remember', false);

        // Get the user from the session challenge
        $userId = $request->session()->get('login.id');
        $provider = config("auth.guards.{$guard}.provider");
        $modelClass = config("auth.providers.{$provider}.model");
        $user = $userId && $modelClass ? $modelClass::find($userId) : null;

        if (! $user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Manually validate the WebAuthn assertion
        try {
            $validator
                ->send(new AssertionValidation(new JsonTransport($request->validated()), $user))
                ->thenReturn();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Authentication failed: '.$e->getMessage()], 401);
        }

        // Log the user in
        Auth::guard($guard)->login($user, $remember);

        $request->session()->regenerate();

        // Mark that 2FA has been completed in this session
        $request->session()->put('auth.two_factor_confirmed_at', now()->timestamp);

        // Return success with redirect URL
        return response()->json([
            'redirect' => url($panel->getPath()),
        ]);
    }
}
