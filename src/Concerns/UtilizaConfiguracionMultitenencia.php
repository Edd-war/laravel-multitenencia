<?php

namespace Eddwar\Multitenencia\Concerns;

use Eddwar\Multitenencia\Exceptions\ExcepcionConfiguracionNoValida;
use Illuminate\Support\Arr;

trait UtilizaConfiguracionMultitenencia
{
    public function nombreDeConexionDeLaBaseDeDatosDelInquilino(): ?string
    {
        return config('multitenencia.nombre_de_conexion_de_la_base_de_datos_del_inquilino') ?? config('database.default');
    }

    public function nombreConexionBaseDeDatosDelPropietario(): ?string
    {
        return config('multitenencia.nombre_de_conexion_de_la_base_de_datos_del_propietario') ?? config('database.default');
    }

    public function claveDeContextoDelinquilinoActual(): string
    {
        return config('multitenencia.clave_de_contexto_del_inquilino_actual');
    }

    public function claveDeContenedorDelinquilinoActual(): string
    {
        return config('multitenencia.clave_de_contenedor_del_inquilino_actual');
    }

    public function obtenerLaClaseDeAccionDeMultitenencia(string $actionName, string $actionClass)
    {
        $configuredClass = config("multitenencia.acciones.{$actionName}") ?? $actionClass;

        if (! is_a($configuredClass, $actionClass, true)) {
            throw ExcepcionConfiguracionNoValida::accionNoValida(
                actionName: $actionName,
                configuredClass: $configuredClass,
                actionClass: $actionClass
            );
        }

        return app($configuredClass);
    }

    public function camposDeBusquedaArtisanParaInquilinos(): array
    {
        return Arr::wrap(config('multitenencia.campos_de_busqueda_artisan_para_inquilinos'));
    }
}
