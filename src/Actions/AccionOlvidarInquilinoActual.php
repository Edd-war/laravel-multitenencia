<?php

namespace Eddwar\Multitenencia\Actions;

use Eddwar\Multitenencia\Concerns\UtilizaConfiguracionMultitenencia;
use Eddwar\Multitenencia\Contracts\EsInquilino;
use Eddwar\Multitenencia\Events\EventoInquilinoActualOlvidado;
use Eddwar\Multitenencia\Events\OlvidandoEventoInquilinoActual;
use Eddwar\Multitenencia\Tasks\ColeccionDeTareas;
use Eddwar\Multitenencia\Tasks\TareaDeCambioDeInquilino;
use Illuminate\Support\Facades\Context;

class AccionOlvidarInquilinoActual
{
    use UtilizaConfiguracionMultitenencia;

    public function __construct(
        protected ColeccionDeTareas $coleccionDeTareas
    ) {}

    public function execute(EsInquilino $tenant): void
    {
        event(new OlvidandoEventoInquilinoActual($tenant));

        $this
            ->realizarTareaParaOlvidarInquilinoActual()
            ->limpiarLimitesInquilinoActual();

        event(new EventoInquilinoActualOlvidado($tenant));
    }

    protected function realizarTareaParaOlvidarInquilinoActual(): static
    {
        $this->coleccionDeTareas->each(fn (TareaDeCambioDeInquilino $task) => $task->olvidarActual());

        return $this;
    }

    protected function limpiarLimitesInquilinoActual(): void
    {
        app()->forgetInstance($this->claveDeContenedorDelinquilinoActual());

        Context::forget($this->claveDeContextoDelinquilinoActual());
    }
}
