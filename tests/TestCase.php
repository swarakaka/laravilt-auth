<?php

namespace Laravilt\Auth\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create the User model alias
        if (! class_exists(\App\Models\User::class)) {
            class_alias(User::class, \App\Models\User::class);
        }
    }

    protected function defineDatabaseMigrations(): void
    {
        // Create users table
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // Create password_reset_tokens table
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Load auth package migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            \Laravilt\Auth\AuthServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup environment for testing
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        // Set auth configuration
        config()->set('auth.defaults.guard', 'web');
        config()->set('auth.guards.web.driver', 'session');
        config()->set('auth.guards.web.provider', 'users');
        config()->set('auth.providers.users.driver', 'eloquent');
        config()->set('auth.providers.users.model', User::class);

        // Password reset configuration
        config()->set('auth.passwords.users.provider', 'users');
        config()->set('auth.passwords.users.table', 'password_reset_tokens');
        config()->set('auth.passwords.users.expire', 60);
        config()->set('auth.passwords.users.throttle', 60);

        // Set app key for password reset hash
        config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    }
}

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'two_factor_enabled',
        'two_factor_method',
        'two_factor_secret',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'two_factor_enabled' => 'boolean',
    ];
}
