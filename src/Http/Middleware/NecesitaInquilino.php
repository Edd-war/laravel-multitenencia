<?php

namespace Eddwar\Multitenencia\Http\Middleware;

use Closure;
use Eddwar\Multitenencia\Contracts\EsInquilino;
use Eddwar\Multitenencia\Exceptions\ExcepcionNoHayInquilinoActual;

class NecesitaInquilino
{
    public function handle($request, Closure $next)
    {
        if (! app(EsInquilino::class)::comprobarActual()) {
            return $this->manejarSolicitudNoValida($request);
        }

        return $next($request);
    }

    public function manejarSolicitudNoValida($request = null)
    {
        $request = $request ?? request();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'No se pudo determinar el contexto del sitio (tenant) para esta solicitud.',
                'error' => 'tenant_context_missing',
            ], 400);
        }

        throw ExcepcionNoHayInquilinoActual::make();
    }
}
