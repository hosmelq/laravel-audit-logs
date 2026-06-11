<?php

declare(strict_types=1);

namespace HosmelQ\AuditLog\Tests;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;
    use WithWorkbench;

    protected function defineEnvironment($app): void
    {
        tap($app['config'], function (Repository $config): void {
            $config->set('database.default', 'testing');
            $config->set('database.connections.testing', [
                'database' => 'laravel_auditlogs_testing',
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'password' => '',
                'port' => '3306',
                'username' => 'root',
            ]);
        });
    }
}
