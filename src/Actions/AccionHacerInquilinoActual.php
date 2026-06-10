<?php

namespace Eddwar\Multitenencia\Actions;

use Eddwar\Multitenencia\Concerns\VincularComoInquilinoActual;
use Eddwar\Multitenencia\Contracts\EsInquilino;
use Eddwar\Multitenencia\Events\EventoHaciendoInquilinoActual;
use Eddwar\Multitenencia\Events\EventoInquilinoActualCreado;
use Eddwar\Multitenencia\Tasks\ColeccionDeTareas;
use Eddwar\Multitenencia\Tasks\TareaDeCambioDeInquilino;

class AccionHacerInquilinoActual
{
    use VincularComoInquilinoActual;

    public function __construct(
        protected ColeccionDeTareas $coleccionDeTareas
    ) {}

    public function execute(EsInquilino $tenant): static
    {
        event(new EventoHaciendoInquilinoActual($tenant));

        $this
            ->realizarTareasParaQueElInquilinoSeaActual($tenant)
            ->vincularComoInquilinoActual($tenant);

        event(new EventoInquilinoActualCreado($tenant));

        return $this;
    }

    protected function realizarTareasParaQueElInquilinoSeaActual(EsInquilino $tenant): static
    {
        $this->coleccionDeTareas->each(fn (TareaDeCambioDeInquilino $task) => $task->hacerActual($tenant));

        return $this;
    }
}
