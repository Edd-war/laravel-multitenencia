<?php

namespace Eddwar\Multitenencia\BuscadorDeInquilinos;

use Eddwar\Multitenencia\Contracts\EsInquilino;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class BuscadorDeInquilinosDeDominio extends BuscadorDeInquilinos
{
    public function buscarParaPeticion(Request $request): ?EsInquilino
    {
        $host = $request->getHost();

        /** @var Model $tenantModel */
        $tenantModel = app(EsInquilino::class);

        $tenant = $tenantModel->newQuery()->where('dominio', $host)->first();

        return $tenant instanceof EsInquilino ? $tenant : null;
    }
}
