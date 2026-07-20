<?php

namespace Eddwar\Multitenencia\BuscadorDeInquilinos;

use Eddwar\Multitenencia\Concerns\UtilizaConfiguracionMultitenencia;
use Eddwar\Multitenencia\Contracts\EsInquilino;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BuscadorDeInquilinosPorHeaders extends BuscadorDeInquilinos
{
    use UtilizaConfiguracionMultitenencia;

    public function buscarParaPeticion(Request $request): ?EsInquilino
    {
        $strategies = $this->estrategiasDeBusqueda();
        if (empty($strategies)) {
            return null;
        }

        $origen = $this->obtenerOrigenActual($request);
        $esPropietario = $this->esDominioPropietario($origen);

        // Si no proviene del dominio del propietario (landlord), forzamos estrategia 'host'
        // para ignorar headers y query parameters que puedan ser falsificados.
        if (! $esPropietario) {
            $strategies = ['host'];
        }

        // 1. Estrategia 'header' (X-Sitio-Context por defecto)
        if (in_array('header', $strategies, true)) {
            $contextHeader = $request->header($this->headerDeContexto());
            if ($contextHeader) {
                $domain = $contextHeader;
                if (filter_var($contextHeader, FILTER_VALIDATE_URL)) {
                    $parsedHost = parse_url($contextHeader, PHP_URL_HOST);
                    $port = parse_url($contextHeader, PHP_URL_PORT);
                    $domain = $parsedHost.($port ? ":$port" : '');
                }

                $domainsMap = $this->obtenerMapaDeDominiosInquilinos();
                if (isset($domainsMap[$domain])) {
                    return $this->obtenerInquilinoPorId($domainsMap[$domain]);
                }
            }
        }

        // 2. Estrategia 'id_header' (X-Sitio-ID por defecto)
        if (in_array('id_header', $strategies, true)) {
            $idHeader = $request->header($this->headerDeId());
            if ($idHeader) {
                return $this->obtenerInquilinoPorId($idHeader);
            }
        }

        // 3. Estrategia 'query_param' (?sitio_id= o ?dominio=)
        if (in_array('query_param', $strategies, true)) {
            if ($request->has('sitio_id')) {
                return $this->obtenerInquilinoPorId($request->input('sitio_id'));
            }

            if ($request->has('dominio')) {
                $domainsMap = $this->obtenerMapaDeDominiosInquilinos();
                $domain = $request->input('dominio');
                if (isset($domainsMap[$domain])) {
                    return $this->obtenerInquilinoPorId($domainsMap[$domain]);
                }
            }
        }

        // 4. Estrategia 'host' (Host de la petición)
        if (in_array('host', $strategies, true)) {
            $host = $request->getHost();
            $port = $request->getPort();
            $fullHost = $host.($port && $port != 80 && $port != 443 ? ":$port" : '');

            // Validamos contra dominios propietarios
            if ($this->esDominioPropietario($fullHost)) {
                return null;
            }

            $domainsMap = $this->obtenerMapaDeDominiosInquilinos();
            if (isset($domainsMap[$fullHost])) {
                return $this->obtenerInquilinoPorId($domainsMap[$fullHost]);
            }
        }

        return null;
    }

    /**
     * Obtiene o genera el mapa de dominios de inquilinos registrados.
     *
     * @return array<string, int|string>
     */
    protected function obtenerMapaDeDominiosInquilinos(): array
    {
        return Cache::rememberForever('multitenencia:domains_map', function () {
            $configuredDomains = $this->dominiosInquilinos();
            /** @var Model&EsInquilino $tenantModel */
            $tenantModel = app(EsInquilino::class);
            $query = $tenantModel->newQuery();

            if (! app()->runningUnitTests() && ! empty($configuredDomains)) {
                $query->whereIn('dominio', $configuredDomains);
            }

            return $query->get()->pluck('id', 'dominio')->toArray();
        });
    }

    /**
     * Obtiene el inquilino resuelto a partir de su ID desde la cache.
     */
    protected function obtenerInquilinoPorId(int|string $id): ?EsInquilino
    {
        /** @var Model&EsInquilino $tenantModel */
        $tenantModel = app(EsInquilino::class);

        /** @var EsInquilino|null $resolved */
        $resolved = Cache::remember("multitenencia:model:{$id}", 3600, function () use ($tenantModel, $id) {
            return $tenantModel->newQuery()->find($id);
        });

        return $resolved;
    }
}
