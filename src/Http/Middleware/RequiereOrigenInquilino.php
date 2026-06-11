<?php

namespace Eddwar\Multitenencia\Http\Middleware;

use Closure;
use Eddwar\Multitenencia\Exceptions\ExcepcionNoHayInquilinoActual;
use Eddwar\Multitenencia\Support\InquilinoResolver;
use Illuminate\Http\Request;

class RequiereOrigenInquilino
{
    protected InquilinoResolver $resolver;

    public function __construct(InquilinoResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $tenant = $this->resolver->resolveOrMakeCurrent($request);

        if (! $tenant) {
            throw new ExcepcionNoHayInquilinoActual('No se pudo determinar el contexto del inquilino (tenant) para esta solicitud.');
        }

        return $next($request);
    }
}
