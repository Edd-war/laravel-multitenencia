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
}
