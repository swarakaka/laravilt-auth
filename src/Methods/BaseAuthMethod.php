<?php

namespace Laravilt\Auth\Methods;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Laravilt\Auth\Contracts\AuthMethod;

abstract class BaseAuthMethod implements AuthMethod
{
    protected array $config = [];

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Get the configuration for this method.
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Get a configuration value.
     */
    protected function config(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Authenticate the user.
     */
    abstract public function authenticate(Request $request): ?Authenticatable;

    /**
     * Check if this method can handle the request.
     */
    abstract public function canHandle(Request $request): bool;

    /**
     * Validate the credentials.
     */
    abstract public function validate(Request $request): bool;

    /**
     * Get the method name.
     */
    abstract public function getName(): string;
}
