<?php

namespace Eddwar\Multitenencia\Models\Concerns;

use Eddwar\Multitenencia\Concerns\UtilizaConfiguracionMultitenencia;

trait UtilizaConexionDelPropietario
{
    use UtilizaConfiguracionMultitenencia;

    public function getConnectionName(): ?string
    {
        return $this->nombreConexionBaseDeDatosDelPropietario();
    }
}
