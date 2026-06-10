<?php

namespace Eddwar\Multitenencia\Concerns;

use Eddwar\Multitenencia\Contracts\EsInquilino;
use Illuminate\Support\Facades\Context;

trait VincularComoInquilinoActual
{
    protected function vincularComoInquilinoActual(EsInquilino $tenant): static
    {
        $contextKey = config('multitenencia.clave_de_contexto_del_inquilino_actual');
        $containerKey = config('multitenencia.clave_de_contenedor_del_inquilino_actual');

        Context::forget($contextKey);

        app()->forgetInstance($containerKey);

        app()->instance($containerKey, $tenant);

        Context::add($contextKey, $tenant->getKey());

        return $this;
    }
}
