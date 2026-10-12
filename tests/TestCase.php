<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Runs before RefreshDatabase gets a chance to migrate:fresh, so a stale
     * config cache or a missing phpunit override can never wipe real data.
     */
    protected function setUpTraits()
    {
        $connection = config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if (! str_ends_with($database, '_test')) {
            throw new RuntimeException(
                "Refusing to run tests against database [{$database}]; the name must end with _test."
            );
        }

        return parent::setUpTraits();
    }
}
