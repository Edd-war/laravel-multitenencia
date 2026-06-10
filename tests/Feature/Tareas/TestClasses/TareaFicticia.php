<?php

namespace Eddwar\Multitenencia\Tests\Feature\Tareas\TestClasses;

use Eddwar\Multitenencia\Contracts\EsInquilino;
use Eddwar\Multitenencia\Tasks\TareaDeCambioDeInquilino;
use Illuminate\Cache\Repository;

class TareaFicticia implements TareaDeCambioDeInquilino
{
    public Repository $config;

    public int $a;

    public int $b;

    public bool $hacerActualLlamado = false;

    public bool $olvidarActualLlamado = false;

    public function __construct(Repository $config, int $a = 0, int $b = 0)
    {
        $this->config = $config;
        $this->a = $a;
        $this->b = $b;
    }

    public function hacerActual(EsInquilino $tenant): void
    {
        $this->hacerActualLlamado = true;
    }

    public function olvidarActual(): void
    {
        $this->olvidarActualLlamado = true;
    }
}
