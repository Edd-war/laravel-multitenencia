<?php

namespace Eddwar\Multitenencia\Http\Middleware;

use Closure;
use Eddwar\Multitenencia\Concerns\UtilizaConfiguracionMultitenencia;
use Symfony\Component\HttpFoundation\Response;

class AsegurarSesionValidaDeInquilino
{
    use UtilizaConfiguracionMultitenencia;

    public function handle($request, Closure $next)
    {
        $sessionKey = 'ensure_valid_tenant_session_tenant_id';

        if (! $request->session()->has($sessionKey)) {
            $request->session()->put($sessionKey, app($this->claveDeContenedorDelinquilinoActual())->getKey());

            return $next($request);
        }

        if ($request->session()->get($sessionKey) !== app($this->claveDeContenedorDelinquilinoActual())->getKey()) {
            return $this->handleInvalidTenantSession($request);
        }

        return $next($request);
    }

    protected function handleInvalidTenantSession($request)
    {
        abort(Response::HTTP_UNAUTHORIZED);
    }
}
