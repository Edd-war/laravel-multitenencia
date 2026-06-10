<?php

namespace Eddwar\Multitenencia\Tests\Feature\TrabajosEnColaReconoceInquilino\TestClasses;

use Eddwar\Multitenencia\Concerns\UtilizaConfiguracionMultitenencia;
use Eddwar\Multitenencia\Models\Inquilino;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Context;
use Spatie\Valuestore\Valuestore;

class TestJobCustomInquilinoReconocido implements CustomInquilinoReconocido, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use UtilizaConfiguracionMultitenencia;

    public Valuestore $valuestore;

    public function __construct(Valuestore $valuestore)
    {
        $this->valuestore = $valuestore;
    }

    public function handle()
    {
        $this->valuestore->put('tenantIdInContext', Context::get($this->claveDeContextoDelinquilinoActual()));
        $this->valuestore->put('tenantId', Inquilino::actual()?->id);
        $this->valuestore->put('tenantName', Inquilino::actual()?->nombre);
    }
}
