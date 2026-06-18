<?php

namespace Eddwar\Multitenencia\Concerns;

use Eddwar\Multitenencia\Exceptions\ExcepcionConfiguracionNoValida;
use Illuminate\Http\Request;
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

    public function dominiosPropietarios(): array
    {
        return Arr::wrap(config('multitenencia.dominios_propietarios'));
    }

    public function prefijoDeBaseDeDatosDelInquilino(): string
    {
        return (string) config('multitenencia.prefijo_de_base_de_datos_del_inquilino', '');
    }

    public function crearBaseDeDatosSiNoExiste(): bool
    {
        return (bool) config('multitenencia.crear_base_de_datos_si_no_existe', false);
    }

    public function estrategiasDeBusqueda(): array
    {
        return Arr::wrap(config('multitenencia.estrategias_de_busqueda'));
    }

    public function headerDeContexto(): string
    {
        return (string) config('multitenencia.header_de_contexto', 'X-Sitio-Context');
    }

    public function headerDeId(): string
    {
        return (string) config('multitenencia.header_de_id', 'X-Sitio-ID');
    }

    /**
     * Extrae el host efectivo de una petición, priorizando el header Origin
     * sobre el Host de la petición, e incluyendo el puerto si no es estándar.
     */
    public function obtenerOrigenActual(Request $request): string
    {
        $origin = $request->header('Origin');
        if ($origin) {
            $host = parse_url($origin, PHP_URL_HOST);
            $port = parse_url($origin, PHP_URL_PORT);

            return $host.($port ? ":{$port}" : '');
        }

        $host = $request->getHost();
        $port = $request->getPort();

        return $host.($port && $port != 80 && $port != 443 ? ":{$port}" : '');
    }

    /**
     * Determina si un origen dado corresponde a un dominio del propietario (landlord),
     * ignorando el puerto al comparar.
     */
    public function esDominioPropietario(string $origen): bool
    {
        $domain = explode(':', $origen)[0];

        return in_array($domain, $this->dominiosPropietarios(), true);
    }
}
