<?php

namespace Eddwar\Multitenencia\Tasks;

use Eddwar\Multitenencia\Concerns\UtilizaConfiguracionMultitenencia;
use Eddwar\Multitenencia\Contracts\EsInquilino;
use Eddwar\Multitenencia\Exceptions\ExcepcionConfiguracionNoValida;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TareaDelCambioDeBaseDeDatosDelInquilino implements TareaDeCambioDeInquilino
{
    use UtilizaConfiguracionMultitenencia;

    public function hacerActual(EsInquilino $tenant): void
    {
        $databaseName = $tenant->obtenerNombreDeBaseDeDatos();

        if ($this->crearBaseDeDatosSiNoExiste() && $databaseName && $databaseName !== ':memory:') {
            $this->ensureDatabaseExists($databaseName);
        }

        $this->setTenantConnectionDatabaseName($databaseName);
    }

    public function olvidarActual(): void
    {
        $this->setTenantConnectionDatabaseName(null);
    }

    protected function ensureDatabaseExists(string $databaseName): void
    {
        $adminConnectionName = $this->nombreConexionBaseDeDatosDelPropietario();
        $driver = config("database.connections.{$adminConnectionName}.driver")
            ?? config("database.connections.{$this->nombreDeConexionDeLaBaseDeDatosDelInquilino()}.driver");

        if ($driver === 'sqlite') {
            if (! str_contains($databaseName, ':memory:')) {
                $path = $databaseName;
                if (! str_contains($path, '/') && ! str_contains($path, '\\')) {
                    $baseDir = function_exists('database_path') ? database_path() : __DIR__.'/../../../tests/temp';
                    $path = $baseDir.'/'.$path.'.sqlite';
                }
                $dir = dirname($path);
                if (! file_exists($dir)) {
                    @mkdir($dir, 0777, true);
                }
                if (! file_exists($path)) {
                    @touch($path);
                }
            }
            return;
        }

        try {
            $dbName = str_replace('`', '``', $databaseName);
            DB::connection($adminConnectionName)
                ->statement("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (\Exception $e) {
            // Ignore error here, standard connection check will handle it.
        }
    }

    protected function setTenantConnectionDatabaseName(?string $databaseName): void
    {
        $tenantConnectionName = $this->nombreDeConexionDeLaBaseDeDatosDelInquilino();

        if (is_null($databaseName) && config("database.connections.{$tenantConnectionName}.driver") === 'sqlite') {
            $databaseName = ':memory:';
        }

        if ($tenantConnectionName === $this->nombreConexionBaseDeDatosDelPropietario()) {
            throw ExcepcionConfiguracionNoValida::conexionDelInquilinoEsVacíaOEsIgualALaConexionDelPropietario();
        }

        if (is_null(config("database.connections.{$tenantConnectionName}"))) {
            throw ExcepcionConfiguracionNoValida::conexionDelInquilinoNoExiste($tenantConnectionName);
        }

        config([
            "database.connections.{$tenantConnectionName}.database" => $databaseName,
        ]);

        app('db')->extend($tenantConnectionName, function ($config, $name) use ($databaseName) {
            $config['database'] = $databaseName;

            return app('db.factory')->make($config, $name);
        });

        DB::purge($tenantConnectionName);

        // Octane will have an old `db` instance in the Model::$resolver.
        Model::setConnectionResolver(app('db'));
    }
}
