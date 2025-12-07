<?php

namespace Laravilt\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Laravilt\Auth\Contracts\AuthMethod;

class AuthManager
{
    protected array $providers = [];

    protected array $methods = [];

    protected ?string $currentMethod = null;

    public function __construct(
        protected AuthFactory $auth,
        protected Request $request
    ) {}

    /**
     * Create a new auth provider.
     */
    public function make(string $name = 'default'): AuthProvider
    {
        $provider = AuthProvider::make($name);
        $this->providers[$name] = $provider;

        return $provider;
    }

    /**
     * Register a custom auth method.
     */
    public function registerMethod(string $name, string $class): static
    {
        $this->methods[$name] = $class;

        return $this;
    }

    /**
     * Get an auth provider.
     */
    public function provider(string $name = 'default'): ?AuthProvider
    {
        return $this->providers[$name] ?? null;
    }

    /**
     * Get all registered providers.
     */
    public function providers(): array
    {
        return $this->providers;
    }

    /**
     * Get a registered auth method.
     */
    public function method(string $name): ?AuthMethod
    {
        if (! isset($this->methods[$name])) {
            return null;
        }

        $class = $this->methods[$name];

        return app($class);
    }

    /**
     * Get all registered methods.
     */
    public function methods(): array
    {
        return $this->methods;
    }

    /**
     * Get the current authentication method.
     */
    public function currentMethod(): ?string
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
     * Check if the user is authenticated.
     */
    public function check(): bool
    {
        return $this->auth->guard()->check();
    }

    /**
     * Get the authenticated user.
     */
    public function user(): ?Authenticatable
    {
        return $this->auth->guard()->user();
    }

    /**
     * Get a guard instance.
     */
    public function guard(?string $name = null): Guard
    {
        return $this->auth->guard($name);
    }

    /**
     * Attempt to authenticate the user.
     */
    public function attempt(array $credentials, bool $remember = false): bool
    {
        return $this->auth->guard()->attempt($credentials, $remember);
    }

    /**
     * Log the user out.
     */
    public function logout(): void
    {
        $this->auth->guard()->logout();
    }

    /**
     * Generate an auth system.
     */
    public function generate(string $name, array $config): AuthProvider
    {
        $provider = $this->make($name);

        if (isset($config['guard'])) {
            $provider->guard($config['guard']);
        }

        if (isset($config['model'])) {
            $provider->model($config['model']);
        }

        if (isset($config['loginBy'])) {
            $provider->loginBy($config['loginBy']);
        }

        if (isset($config['methods'])) {
            $methods = [];
            foreach ($config['methods'] as $method) {
                if (is_string($method)) {
                    $methods[$method] = true;
                }
            }
            $provider->loginMethods($methods);
        }

        return $provider;
    }

    /**
     * Proxy method calls to the default guard.
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->auth->guard()->$method(...$parameters);
    }
}
