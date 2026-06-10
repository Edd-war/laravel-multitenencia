<?php

namespace Eddwar\Multitenencia;

use Eddwar\Multitenencia\Contracts\EsInquilino;

class Propietario
{
    public static function execute(callable $callable)
    {
        $originalCurrentTenant = app(EsInquilino::class)::actual();

        app(EsInquilino::class)::olvidarActual();

        $result = $callable();

        $originalCurrentTenant?->hacerActual();

        return $result;
    }
}
