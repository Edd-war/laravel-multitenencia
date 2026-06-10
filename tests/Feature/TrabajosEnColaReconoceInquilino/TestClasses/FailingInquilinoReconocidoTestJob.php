<?php

namespace Eddwar\Multitenencia\Tests\Feature\TrabajosEnColaReconoceInquilino\TestClasses;

use Eddwar\Multitenencia\Jobs\InquilinoReconocido;
use Exception;

class FailingInquilinoReconocidoTestJob extends TestJob implements InquilinoReconocido
{
    public int $tries = 1;

    public function handle()
    {
        if ($this->valuestore->get('shouldFail', true)) {
            throw new Exception('Intentional failure so the job lands in failed_jobs.');
        }

        parent::handle();
    }
}
