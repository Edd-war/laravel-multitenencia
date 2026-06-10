<?php

namespace Eddwar\Multitenencia\Models\Concerns;

use Eddwar\Multitenencia\Concerns\UtilizaConfiguracionMultitenencia;

trait UtilizaConexionDelInquilino
{
    use UtilizaConfiguracionMultitenencia;

    public function getConnectionName(): ?string
    {
        return $this->nombreDeConexionDeLaBaseDeDatosDelInquilino();
    }
}
