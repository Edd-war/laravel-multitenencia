<?php

namespace Eddwar\Multitenencia\Tests\Feature\TrabajosEnColaReconoceInquilino\TestClasses;

use Eddwar\Multitenencia\Jobs\InquilinoReconocido;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;

class InquilinoReconocidoEncriptado extends TestJob implements InquilinoReconocido, ShouldBeEncrypted {}
