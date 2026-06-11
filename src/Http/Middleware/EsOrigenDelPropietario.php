<?php

namespace Eddwar\Multitenencia\Http\Middleware;

use Closure;
use Eddwar\Multitenencia\Concerns\UtilizaConfiguracionMultitenencia;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

class EsOrigenDelPropietario
{
    use UtilizaConfiguracionMultitenencia;

    public function handle(Request $request, Closure $next): mixed
    {
        $origen = $this->obtenerOrigenActual($request);
        if (! $this->esDominioPropietario($origen)) {
            abort(HttpFoundationResponse::HTTP_FORBIDDEN, 'Esta operación solo está permitida desde el dominio principal del propietario.');
        }

        return $next($request);
    }

    protected function obtenerOrigenActual(Request $request): string
    {
        $origin = $request->header('Origin');
        if ($origin) {
            $host = parse_url($origin, PHP_URL_HOST);
            $port = parse_url($origin, PHP_URL_PORT);

            return $host.($port ? ":$port" : '');
        }

        $host = $request->getHost();
        $port = $request->getPort();

        return $host.($port && $port != 80 && $port != 443 ? ":$port" : '');
    }

    protected function esDominioPropietario(string $origen): bool
    {
        $domain = explode(':', $origen)[0];
        $propietarioDomains = $this->dominiosPropietarios();

        return in_array($domain, $propietarioDomains, true);
    }
}
