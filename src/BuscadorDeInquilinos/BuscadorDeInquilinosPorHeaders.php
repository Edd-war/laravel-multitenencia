<?php

namespace Eddwar\Multitenencia\BuscadorDeInquilinos;

use Eddwar\Multitenencia\Concerns\UtilizaConfiguracionMultitenencia;
use Eddwar\Multitenencia\Contracts\EsInquilino;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class BuscadorDeInquilinosPorHeaders extends BuscadorDeInquilinos
{
    use UtilizaConfiguracionMultitenencia;

    public function buscarParaPeticion(Request $request): ?EsInquilino
    {
        $strategies = $this->estrategiasDeBusqueda();
        if (empty($strategies)) {
            return null;
        }

        /** @var Model $tenantModel */
        $tenantModel = app(EsInquilino::class);

        // 1. Estrategia 'header' (X-Sitio-Context por defecto)
        if (in_array('header', $strategies, true)) {
            $contextHeader = $request->header($this->headerDeContexto());
            if ($contextHeader) {
                $domain = $contextHeader;
                if (filter_var($contextHeader, FILTER_VALIDATE_URL)) {
                    $parsedHost = parse_url($contextHeader, PHP_URL_HOST);
                    $port = parse_url($contextHeader, PHP_URL_PORT);
                    $domain = $parsedHost . ($port ? ":$port" : '');
                }

                $tenant = $tenantModel->newQuery()->where('dominio', $domain)->first();
                if ($tenant instanceof EsInquilino) {
                    return $tenant;
                }
            }
        }

        // 2. Estrategia 'id_header' (X-Sitio-ID por defecto)
        if (in_array('id_header', $strategies, true)) {
            $idHeader = $request->header($this->headerDeId());
            if ($idHeader) {
                $tenant = $tenantModel->newQuery()->find($idHeader);
                if ($tenant instanceof EsInquilino) {
                    return $tenant;
                }
            }
        }

        // 3. Estrategia 'query_param' (?sitio_id= o ?dominio=)
        if (in_array('query_param', $strategies, true)) {
            if ($request->has('sitio_id')) {
                $tenant = $tenantModel->newQuery()->find($request->input('sitio_id'));
                if ($tenant instanceof EsInquilino) {
                    return $tenant;
                }
            }

            if ($request->has('dominio')) {
                $tenant = $tenantModel->newQuery()->where('dominio', $request->input('dominio'))->first();
                if ($tenant instanceof EsInquilino) {
                    return $tenant;
                }
            }
        }

        // 4. Estrategia 'host' (Host de la petición)
        if (in_array('host', $strategies, true)) {
            $host = $request->getHost();
            $port = $request->getPort();
            $fullHost = $host . ($port && $port != 80 && $port != 443 ? ":$port" : '');

            // Validamos contra dominios propietarios
            $landlordDomains = $this->dominiosPropietarios();
            $domainOnly = explode(':', $fullHost)[0];

            if (! in_array($domainOnly, $landlordDomains, true)) {
                $tenant = $tenantModel->newQuery()->where('dominio', $fullHost)->first();
                if ($tenant instanceof EsInquilino) {
                    return $tenant;
                }
            }
        }

        return null;
    }
}
