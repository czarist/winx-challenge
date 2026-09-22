<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = parent::createApplication();
        $config = $app->make('config');

        if (! $app->environment('testing')
            || $config->get('database.default') !== 'sqlite'
            || $config->get('database.connections.sqlite.database') !== ':memory:'
            || ! empty($config->get('database.connections.sqlite.url'))) {
            throw new RuntimeException('Tests require SQLite in memory. Clear the configuration cache before running PHPUnit.');
        }

        return $app;
    }
}
