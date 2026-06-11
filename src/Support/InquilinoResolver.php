<?php

namespace Eddwar\Multitenencia\Support;

use Eddwar\Multitenencia\BuscadorDeInquilinos\BuscadorDeInquilinosPorHeaders;
use Eddwar\Multitenencia\Contracts\EsInquilino;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;

class InquilinoResolver
{
    public function limpiarInstancias(): void
    {
        Facade::clearResolvedInstances();
    }

    public function resolverInquilinoParaPeticion(Request $request): ?EsInquilino
    {
        $finder = new BuscadorDeInquilinosPorHeaders;

        return $finder->buscarParaPeticion($request);
    }

    public function resolveOrMakeCurrent(Request $request): ?EsInquilino
    {
        /** @var EsInquilino $tenantModel */
        $tenantModel = app(EsInquilino::class);
        $currentTenant = $tenantModel::actual();

        if (! $currentTenant) {
            $currentTenant = $this->resolverInquilinoParaPeticion($request);
            if ($currentTenant) {
                $currentTenant->hacerActual();
            }
        }

        return $currentTenant;
    }
}
