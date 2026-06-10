<?php

namespace Eddwar\Multitenencia;

use Eddwar\Multitenencia\Commands\ComandoArtisanInquilinos;
use Eddwar\Multitenencia\Commands\ComandoMigracionInquilinos;
use Eddwar\Multitenencia\Concerns\UtilizaConfiguracionMultitenencia;
use Eddwar\Multitenencia\Contracts\EsInquilino;
use Illuminate\Support\Facades\Event;
use Laravel\Octane\Events\RequestReceived as OctaneRequestReceived;
use Laravel\Octane\Events\RequestTerminated as OctaneRequestTerminated;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MultitenenciaServiceProvider extends PackageServiceProvider
{
    use UtilizaConfiguracionMultitenencia;

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-multitenencia')
            ->hasConfigFile()
            ->hasMigration('propietario/create_inquilinos_del_propietario_table')
            ->hasCommands([
                ComandoArtisanInquilinos::class,
                ComandoMigracionInquilinos::class,
            ]);
    }

    public function packageBooted(): void
    {
        $this->app->bind(EsInquilino::class, config('multitenencia.modelo_del_inquilino'));

        $this->app->bind(Multitenencia::class, fn ($app) => new Multitenencia($app));

        $this->detectsLaravelOctane();
    }

    protected function detectsLaravelOctane(): static
    {
        if (! isset($_SERVER['LARAVEL_OCTANE'])) {
            app(Multitenencia::class)->start();

            return $this;
        }

        Event::listen(fn (OctaneRequestReceived $requestReceived) => app(Multitenencia::class)->start());
        Event::listen(fn (OctaneRequestTerminated $requestTerminated) => app(Multitenencia::class)->end());

        return $this;
    }
}
