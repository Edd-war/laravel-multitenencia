<?php

namespace Eddwar\Multitenencia\BuscadorDeInquilinos;

use Eddwar\Multitenencia\Contracts\EsInquilino;
use Illuminate\Cache\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BuscadorDeInquilinosConCache extends BuscadorDeInquilinosPorHeaders
{
    /**
     * Busca y resuelve el inquilino para la petición actual, utilizando caché.
     */
    public function buscarParaPeticion(Request $request): ?EsInquilino
    {
        $originHost = $this->obtenerOrigenActual($request);
        $isLandlordOrigin = $this->esDominioPropietario($originHost);

        $strategies = $this->estrategiasDeBusqueda();
        if (empty($strategies)) {
            return null;
        }

        if ($isLandlordOrigin) {
            $contextHeader = in_array('header', $strategies, true) ? $request->header($this->headerDeContexto()) : null;
            $idHeader = in_array('id_header', $strategies, true) ? $request->header($this->headerDeId()) : null;
            $sitioIdParam = in_array('query_param', $strategies, true) ? $request->input('sitio_id') : null;
            $dominioParam = in_array('query_param', $strategies, true) ? $request->input('dominio') : null;

            $host = null;
            if (in_array('host', $strategies, true)) {
                $host = $request->getHost();
                $port = $request->getPort();
                $host = $host.($port && $port != 80 && $port != 443 ? ":$port" : '');
            }
        } else {
            $contextHeader = null;
            $idHeader = null;
            $sitioIdParam = null;
            $dominioParam = null;
            $host = $originHost;
        }

        // Generamos una clave única con hash MD5 a partir de los datos que identifican al inquilino en la solicitud
        $cacheKeyInputs = array_filter([
            'header' => $contextHeader,
            'id_header' => $idHeader,
            'sitio_id' => $sitioIdParam,
            'dominio' => $dominioParam,
            'host' => $host,
        ]);

        if (empty($cacheKeyInputs)) {
            return null;
        }

        $cacheKey = 'tenant:resolver:'.md5(serialize($cacheKeyInputs));

        // Obtener el store de cache apropiado para el propietario
        $cacheStore = config('multitenencia.cache.store_del_propietario') ?? config('cache.default');

        /** @var Repository $cache */
        $cache = Cache::store($cacheStore);

        if ($cache->supportsTags()) {
            $cacheData = $cache->tags(['tenant_resolver'])->remember($cacheKey, 3600, function () use ($request, $isLandlordOrigin, $originHost) {
                if ($isLandlordOrigin) {
                    return ['tenant' => parent::buscarParaPeticion($request)];
                }

                $domainsMap = $this->obtenerMapaDeDominiosInquilinos();
                $tenant = isset($domainsMap[$originHost]) ? $this->obtenerInquilinoPorId($domainsMap[$originHost]) : null;

                return ['tenant' => $tenant];
            });
        } else {
            $cacheData = $cache->remember($cacheKey, 3600, function () use ($request, $isLandlordOrigin, $originHost) {
                if ($isLandlordOrigin) {
                    return ['tenant' => parent::buscarParaPeticion($request)];
                }

                $domainsMap = $this->obtenerMapaDeDominiosInquilinos();
                $tenant = isset($domainsMap[$originHost]) ? $this->obtenerInquilinoPorId($domainsMap[$originHost]) : null;

                return ['tenant' => $tenant];
            });
        }

        return $cacheData['tenant'] ?? null;
    }
}
