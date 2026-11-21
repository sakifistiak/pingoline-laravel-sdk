<?php

namespace PingoLine\Laravel\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use PingoLine\Laravel\Facades\PingoLine;
use PingoLine\Laravel\PingoLineServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            PingoLineServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'PingoLine' => PingoLine::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('pingoline.api_key', 'test_token_12345');
        $app['config']->set('pingoline.api_url', 'https://pingoline.com/api/v1');
        $app['config']->set('pingoline.webhook_secret', 'whsec_test_secret_abc123');
    }
}
