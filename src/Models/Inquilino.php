<?php

namespace Eddwar\Multitenencia\Models;

use Eddwar\Multitenencia\Contracts\EsInquilino;
use Eddwar\Multitenencia\Models\Concerns\ImplementaInquilino;
use Eddwar\Multitenencia\Models\Concerns\UtilizaConexionDelPropietario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $nombre
 * @property string $base_de_datos
 * @property string $dominio
 */
class Inquilino extends Model implements EsInquilino
{
    use HasFactory;
    use ImplementaInquilino;
    use UtilizaConexionDelPropietario;
}
