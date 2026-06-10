<?php

namespace Eddwar\Multitenencia\Events;

use Eddwar\Multitenencia\Contracts\EsInquilino;

class EventoInquilinoActualCreado
{
    public function __construct(
        public EsInquilino $tenant
    ) {}
}
