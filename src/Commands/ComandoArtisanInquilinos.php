<?php

namespace Eddwar\Multitenencia\Commands;

use Eddwar\Multitenencia\Commands\Concerns\InquilinoReconocido;
use Eddwar\Multitenencia\Concerns\UtilizaConfiguracionMultitenencia;
use Eddwar\Multitenencia\Contracts\EsInquilino;
use Eddwar\Multitenencia\Models\Inquilino;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ComandoArtisanInquilinos extends Command
{
    use InquilinoReconocido;
    use UtilizaConfiguracionMultitenencia;

    protected $signature = 'tenants:artisan {artisanCommand} {--tenant=*}';

    protected $description = 'Ejecuta un comando de Artisan para los inquilinos seleccionados.';

    public function handle(): void
    {
        if (! $artisanCommand = $this->argument('artisanCommand')) {
            $artisanCommand = $this->ask('¿Qué comando de Artisan deseas ejecutar para todos los inquilinos?');
        }

        $artisanCommand = addslashes($artisanCommand);

        /** @var Inquilino $tenant */
        $tenant = app(EsInquilino::class)::actual();

        $this->line('');
        $this->info("Ejecutando comando para el inquilino `{$tenant->nombre}` (id: {$tenant->getKey()})...");
        $this->line('---------------------------------------------------------');

        Artisan::call($artisanCommand, [], $this->output);
    }
}
