<?php

namespace Eddwar\Multitenencia\Tasks;

use Eddwar\Multitenencia\Contracts\EsInquilino;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Env;

class TareaDeCacheDeCambioDeRuta implements TareaDeCambioDeInquilino
{
    /**
     * @param  EsInquilino&Model  $tenant
     */
    public function hacerActual(EsInquilino $tenant): void
    {
        Env::getRepository()->set('APP_ROUTES_CACHE', $this->getCachedRoutesPath($tenant));

        if (app()->routesAreCached() && $this->shouldReinitializeRouter()) {
            require app()->getCachedRoutesPath();
        }
    }

    public function olvidarActual(): void
    {
        Env::getRepository()->clear('APP_ROUTES_CACHE');
    }

    /**
     * @param  EsInquilino&Model  $tenant
     */
    protected function getCachedRoutesPath(EsInquilino $tenant): string
    {
        if (config('multitenencia.cache_de_rutas_compartido')) {
            return 'bootstrap/cache/routes-v7-tenants.php';
        }

        return "bootstrap/cache/routes-v7-tenant-{$tenant->getKey()}.php";
    }

    protected function shouldReinitializeRouter(): bool
    {
        return isset($_SERVER['LARAVEL_OCTANE'])
            || app()->runningInConsole()
            || app()->runningUnitTests();
    }
}
