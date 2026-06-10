<?php

namespace Eddwar\Multitenencia\BuscadorDeInquilinos;

use Eddwar\Multitenencia\Contracts\EsInquilino;
use Illuminate\Http\Request;

abstract class BuscadorDeInquilinos
{
    abstract public function buscarParaPeticion(Request $request): ?EsInquilino;
}
