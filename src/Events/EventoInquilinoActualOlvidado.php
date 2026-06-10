<?php

namespace Eddwar\Multitenencia\Events;

use Eddwar\Multitenencia\Contracts\EsInquilino;

class EventoInquilinoActualOlvidado
{
    public function __construct(
        public EsInquilino $tenant
    ) {}
}
