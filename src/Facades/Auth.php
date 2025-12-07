<?php

namespace Laravilt\Auth\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Laravilt\Auth\AuthProvider make(string $name = 'default')
 * @method static \Laravilt\Auth\Facades\Auth registerMethod(string $name, string $class)
 * @method static \Laravilt\Auth\AuthProvider|null provider(string $name = 'default')
 * @method static array providers()
 * @method static \Laravilt\Auth\Contracts\AuthMethod|null method(string $name)
 * @method static array methods()
 * @method static string|null currentMethod()
 * @method static \Laravilt\Auth\Facades\Auth setCurrentMethod(string $method)
 * @method static bool check()
 * @method static \Illuminate\Contracts\Auth\Authenticatable|null user()
 * @method static \Illuminate\Contracts\Auth\Guard guard(?string $name = null)
 * @method static bool attempt(array $credentials, bool $remember = false)
 * @method static void logout()
 * @method static \Laravilt\Auth\AuthProvider generate(string $name, array $config)
 *
 * @see \Laravilt\Auth\AuthManager
 */
class Auth extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'laravilt.auth';
    }
}
