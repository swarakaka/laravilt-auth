<?php

namespace Laravilt\Auth\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();


        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');        // Additional setup if needed
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
        config()->set('database.default', 'testing');

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');    }
}
