<?php

namespace Laravilt\Auth\Guards;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Laravilt\Auth\AuthManager;

class LaraviltGuard extends SessionGuard implements StatefulGuard
{
    use GuardHelpers;

    /**
     * The Laravilt Auth Manager instance.
     */
    protected AuthManager $authManager;

    /**
     * The current authentication method being used.
     */
    protected ?string $currentMethod = null;

    /**
     * Create a new authentication guard.
     */
    public function __construct(
        string $name,
        UserProvider $provider,
        Session $session,
        Request $request,
        AuthManager $authManager
    ) {
        parent::__construct($name, $provider, $session, $request);

        $this->authManager = $authManager;
    }

    /**
     * Attempt to authenticate using a specific method.
     */
    public function attemptWith(string $method, array $credentials, bool $remember = false): bool
    {
        $authMethod = $this->authManager->method($method);

        if (! $authMethod) {
            return false;
        }

        $this->currentMethod = $method;
        $this->authManager->setCurrentMethod($method);

        // Check if the method can handle the request
        if (! $authMethod->canHandle($this->request)) {
            return false;
        }

        // Validate credentials
        if (! $authMethod->validate($this->request)) {
            $this->fireFailedEvent(null, $credentials);

            return false;
        }

        // Authenticate user
        $user = $authMethod->authenticate($this->request);

        if ($user) {
            $this->login($user, $remember);
            $this->fireAuthenticatedEvent($user);

            return true;
        }

        $this->fireFailedEvent(null, $credentials);

        return false;
    }

    /**
     * Get the current authentication method.
     */
    public function getCurrentMethod(): ?string
    {
        return $this->currentMethod;
    }

    /**
     * Set the current authentication method.
     */
    public function setCurrentMethod(string $method): static
    {
        $this->currentMethod = $method;

        return $this;
    }

    /**
     * Fire the authenticated event if the dispatcher is set.
     */
    protected function fireAuthenticatedEvent($user): void
    {
        if (isset($this->events)) {
            $this->events->dispatch(
                new \Illuminate\Auth\Events\Authenticated($this->name, $user)
            );
        }
    }

    /**
     * Fire the failed authentication attempt event.
     */
    protected function fireFailedEvent($user, array $credentials)
    {
        if (isset($this->events)) {
            $this->events->dispatch(
                new \Illuminate\Auth\Events\Failed($this->name, $user, $credentials)
            );
        }
    }
}
