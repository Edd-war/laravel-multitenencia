<?php

namespace Eddwar\Multitenencia\Events;

use Eddwar\Multitenencia\Contracts\EsInquilino;

class EventoHaciendoInquilinoActual
{
    public function __construct(
        public EsInquilino $tenant
    ) {}
}
