<?php

namespace Eddwar\Multitenencia\Events;

use Illuminate\Http\Request;

class EventoInquilinoNoEncontradoParaLaSolicitud
{
    public function __construct(
        public Request $request
    ) {}
}
