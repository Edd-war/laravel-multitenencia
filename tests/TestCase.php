<?php

namespace Eddwar\Multitenencia\Tests;

use Eddwar\Multitenencia\Models\Inquilino;
use Eddwar\Multitenencia\Providers\MultitenenciaServiceProvider;
use Eddwar\Multitenencia\Tests\Feature\Comandos\TestClasses\ComandoNoopInquilino;
use Illuminate\Console\Application as Artisan;
use Illuminate\Database\Connectors\SQLiteConnector;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /** @var mixed */
    public $inquilino;

    /** @var mixed */
    public $valuestore;

    /** @var mixed */
    public $tenantFinder;

    /** @var mixed */
    public $tenants;

    /** @var mixed */
    public $anotherInquilino;

    protected function setUp(): void
    {
        $tempDir = __DIR__.'/temp';
        if (! file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $propietarioDb = $tempDir.'/propietario.sqlite';
        if (file_exists($propietarioDb)) {
            @unlink($propietarioDb);
        }
        touch($propietarioDb);

        // Clean up any old tenant sqlite files
        foreach (glob($tempDir.'/laravel_mt_tenant_*.sqlite') as $file) {
            @unlink($file);
        }

        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Eddwar\\Multitenencia\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        $this->migrateDb();

        Inquilino::truncate();

        DB::table('jobs')->truncate();

        View::addLocation(__DIR__.'/stubs/views');
    }

    protected function tearDown(): void
    {
        if ($this->app && $this->app->resolved('db')) {
            foreach ($this->app['db']->getConnections() as $connection) {
                $connection->disconnect();
            }
        }

        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        $this->bootCommands();

        return [
            MultitenenciaServiceProvider::class,
        ];
    }

    protected function bootCommands(): self
    {
        Artisan::starting(function ($artisan) {
            $artisan->resolveCommands([
                ComandoNoopInquilino::class,
            ]);
        });

        return $this;
    }

    protected function migrateDb(): self
    {
        $propietarioMigrationsPath = realpath(__DIR__.'/database/migrations/propietario');
        $propietarioMigrationsPath = str_replace('\\', '/', $propietarioMigrationsPath);

        $this
            ->artisan("migrate --database=propietario --path={$propietarioMigrationsPath} --realpath")
            ->assertExitCode(0);

        /*
        $tenantMigrationsPath = realpath(__DIR__ . '/database/migrations');
        $this
            ->artisan("migrate --database=tenant --path={$tenantMigrationsPath} --realpath")
            ->assertExitCode(0);
        */

        return $this;
    }

    public function getEnvironmentSetUp($app)
    {
        config(['database.default' => 'propietario']);

        config()->set('multitenencia.nombre_de_conexion_de_la_base_de_datos_del_inquilino', 'tenant');

        config()->set('multitenencia.nombre_de_conexion_de_la_base_de_datos_del_propietario', 'propietario');

        config([
            'database.connections.propietario' => [
                'driver' => 'sqlite',
                'database' => __DIR__.'/temp/propietario.sqlite',
                'prefix' => '',
            ],

            'database.connections.tenant' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
        ]);

        $app['db']->extend('tenant', function ($config, $name) {
            $databaseName = $config['database'] ?? '';
            if ($databaseName && $databaseName !== ':memory:' && ! str_contains($databaseName, '/') && ! str_contains($databaseName, '\\')) {
                $databasePath = __DIR__.'/temp/'.$databaseName.'.sqlite';
                if (! file_exists($databasePath)) {
                    @touch($databasePath);
                }
                $config['database'] = $databasePath;
            }

            $connector = new SQLiteConnector;
            $pdo = $connector->connect($config);

            return new SQLiteConnection($pdo, $config['database'], $config['prefix'], $config);
        });

        config()->set('queue.default', 'database');

        config()->set('queue.connections.database', [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
            'connection' => 'propietario',
        ]);
    }
}
