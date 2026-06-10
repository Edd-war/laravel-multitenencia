<?php

namespace Eddwar\Multitenencia\Tasks;

use Eddwar\Multitenencia\Contracts\EsInquilino;

interface TareaDeCambioDeInquilino
{
    public function hacerActual(EsInquilino $tenant): void;

    public function olvidarActual(): void;
}
