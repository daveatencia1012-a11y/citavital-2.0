<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $connection = $app['db']->connection();

        // Se comprueba antes de que RefreshDatabase pueda ejecutar migraciones.
        if ($connection->getDriverName() !== 'mysql'
            || $connection->selectOne('SELECT DATABASE() AS db')->db !== 'citavital_testing') {
            throw new \RuntimeException('Las pruebas solo pueden ejecutarse contra MySQL citavital_testing.');
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
