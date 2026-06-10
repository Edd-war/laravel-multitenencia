<?php

namespace Spatie\Multitenancy\Http\Middleware;

use Closure;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Exceptions\NoCurrentTenant;

class NeedsTenant
{
    public function handle($request, Closure $next)
    {
        if (! app(IsTenant::class)::checkCurrent()) {
            return $this->handleInvalidRequest($request);
        }

        return $next($request);
    }

    public function handleInvalidRequest($request = null)
    {
        $request = $request ?? request();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'No se pudo determinar el contexto del sitio (tenant) para esta solicitud.',
                'error' => 'tenant_context_missing',
            ], 400);
        }

        throw NoCurrentTenant::make();
    }
}
