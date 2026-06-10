<?php

namespace Eddwar\Multitenencia\Events;

use Eddwar\Multitenencia\Contracts\EsInquilino;

class OlvidandoEventoInquilinoActual
{
    public function __construct(
        public EsInquilino $tenant
    ) {}
}
