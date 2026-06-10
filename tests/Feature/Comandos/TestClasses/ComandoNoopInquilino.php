<?php

namespace Eddwar\Multitenencia\Tests\Feature\Comandos\TestClasses;

use Eddwar\Multitenencia\Commands\Concerns\InquilinoReconocido;
use Eddwar\Multitenencia\Models\Inquilino;
use Illuminate\Console\Command;

class ComandoNoopInquilino extends Command
{
    use InquilinoReconocido;

    protected $signature = 'inquilino:noop {--tenant=*}';

    protected $description = 'Ejecuta noop para inquilino(s)';

    public function handle()
    {
        $this->line('El ID del inquilino es '.Inquilino::actual()->id);
    }
}
