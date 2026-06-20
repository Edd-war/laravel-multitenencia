<?php

namespace Eddwar\Multitenencia\Providers;

use Eddwar\Multitenencia\Commands\ComandoArtisanInquilinos;
use Eddwar\Multitenencia\Commands\ComandoMigracionInquilinos;
use Eddwar\Multitenencia\Commands\ComandoMigrateRollbackInquilinos;
use Eddwar\Multitenencia\Commands\ComandoMigrationStatusInquilinos;
use Eddwar\Multitenencia\Commands\ComandoRollbackInquilinos;
use Eddwar\Multitenencia\Concerns\UtilizaConfiguracionMultitenencia;
use Eddwar\Multitenencia\Contracts\EsInquilino;
use Eddwar\Multitenencia\Multitenencia;
use Eddwar\Multitenencia\Support\InquilinoResolver;
use Illuminate\Support\Facades\Cache;
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
                ComandoRollbackInquilinos::class,
                ComandoMigrateRollbackInquilinos::class,
                ComandoMigrationStatusInquilinos::class,
            ]);
    }

    public function packageBooted(): void
    {
        $this->app->bind(EsInquilino::class, config('multitenencia.modelo_del_inquilino'));

        $this->app->singleton(InquilinoResolver::class, fn () => new InquilinoResolver);

        $this->app->bind(Multitenencia::class, fn ($app) => new Multitenencia($app));

        $this->detectsLaravelOctane();

        if (config('multitenencia.cache.habilitado', false)) {
            $this->app->register(MultitenenciaCacheServiceProvider::class);
        }

        $tenantModelClass = config('multitenencia.modelo_del_inquilino');
        if ($tenantModelClass && class_exists($tenantModelClass)) {
            $tenantModelClass::saved(static function ($model) {
                Cache::forget('multitenencia:domains_map');
                Cache::forget("multitenencia:model:{$model->id}");
            });
            $tenantModelClass::deleted(static function ($model) {
                Cache::forget('multitenencia:domains_map');
                Cache::forget("multitenencia:model:{$model->id}");
            });
        }
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
