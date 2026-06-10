<?php

namespace Eddwar\Multitenencia\Tests\TestClasses;

use Eddwar\Multitenencia\Models\Concerns\UtilizaConexionDelInquilino;
use Illuminate\Foundation\Auth\User as BaseUser;

class Usuario extends BaseUser
{
    use UtilizaConexionDelInquilino;
}
