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
        $this->setTenantConnectionDatabaseName($tenant->obtenerNombreDeBaseDeDatos());
    }

    public function olvidarActual(): void
    {
        $this->setTenantConnectionDatabaseName(null);
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
