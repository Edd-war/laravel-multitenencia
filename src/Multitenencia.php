<?php

namespace Eddwar\Multitenencia;

use Eddwar\Multitenencia\Actions\AccionHacerColaInquilinoReconocido;
use Eddwar\Multitenencia\BuscadorDeInquilinos\BuscadorDeInquilinos;
use Eddwar\Multitenencia\Concerns\UtilizaConfiguracionMultitenencia;
use Eddwar\Multitenencia\Contracts\EsInquilino;
use Eddwar\Multitenencia\Events\EventoInquilinoNoEncontradoParaLaSolicitud;
use Eddwar\Multitenencia\Tasks\ColeccionDeTareas;
use Illuminate\Contracts\Foundation\Application;

class Multitenencia
{
    use UtilizaConfiguracionMultitenencia;

    public function __construct(public Application $app) {}

    public function start(): void
    {
        $this
            ->registraBuscadorDeInquilinos()
            ->registraColeccionDeTareas()
            ->configuraPeticiones()
            ->configuraCola();
    }

    public function end(): void
    {
        app(EsInquilino::class)::olvidarActual();
    }

    protected function determinarInquilinoActual(): void
    {
        if (! $this->app['config']->get('multitenencia.buscador_de_inquilinos')) {
            return;
        }

        /** @var BuscadorDeInquilinos $buscadorDeInquilinos */
        $buscadorDeInquilinos = $this->app[BuscadorDeInquilinos::class];

        $tenant = $buscadorDeInquilinos->buscarParaPeticion($this->app['request']);

        if ($tenant instanceof EsInquilino) {
            $tenant->hacerActual();
        } else {
            event(new EventoInquilinoNoEncontradoParaLaSolicitud($this->app['request']));
        }
    }

    protected function registraColeccionDeTareas(): static
    {
        $this->app->singleton(ColeccionDeTareas::class, static function ($app) {
            $taskClassNames = $app['config']->get('multitenencia.tareas_de_cambio_de_inquilino');

            return new ColeccionDeTareas($taskClassNames);
        });

        return $this;
    }

    protected function registraBuscadorDeInquilinos(): static
    {
        $buscadorDeInquilinosConfig = $this->app['config']->get('multitenencia.buscador_de_inquilinos');

        if ($buscadorDeInquilinosConfig) {
            $this->app->bind(BuscadorDeInquilinos::class, fn ($app) => $app->make($buscadorDeInquilinosConfig));
        }

        return $this;
    }

    protected function configuraPeticiones(): static
    {
        if (! $this->app->runningInConsole()) {
            $this->determinarInquilinoActual();
        }

        return $this;
    }

    protected function configuraCola(): static
    {
        $this
            ->obtenerLaClaseDeAccionDeMultitenencia(
                actionName: 'make_queue_tenant_aware_action',
                actionClass: AccionHacerColaInquilinoReconocido::class
            )
            ->execute();

        return $this;
    }
}
