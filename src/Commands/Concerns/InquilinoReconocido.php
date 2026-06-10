<?php

namespace Eddwar\Multitenencia\Commands\Concerns;

use Eddwar\Multitenencia\Concerns\UtilizaConfiguracionMultitenencia;
use Eddwar\Multitenencia\Contracts\EsInquilino;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait InquilinoReconocido
{
    use UtilizaConfiguracionMultitenencia;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tenants = Arr::wrap($this->option('tenant'));

        /** @var Model&EsInquilino $tenantModel */
        $tenantModel = app(EsInquilino::class);

        $tenantQuery = $tenantModel->newQuery()
            ->when(! blank($tenants), function ($query) use ($tenants) {
                collect($this->camposDeBusquedaArtisanParaInquilinos())
                    ->each(fn ($field) => $query->orWhereIn($field, $tenants));
            });

        if ($tenantQuery->count() === 0) {
            $this->error('No tenant(s) found.');

            return -1;
        }

        return $tenantQuery
            ->cursor()
            ->map(function ($tenant) {
                /** @var EsInquilino $tenant */
                return $tenant->execute(fn () => (int) $this->laravel->call([$this, 'handle']));
            })
            ->sum();
    }
}
