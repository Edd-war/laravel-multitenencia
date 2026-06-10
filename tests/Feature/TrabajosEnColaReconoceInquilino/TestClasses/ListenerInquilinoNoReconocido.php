<?php

namespace Eddwar\Multitenencia\Tests\Feature\TrabajosEnColaReconoceInquilino\TestClasses;

use Eddwar\Multitenencia\Jobs\InquilinoNoReconocido;
use Illuminate\Contracts\Queue\ShouldQueue;

class ListenerInquilinoNoReconocido implements InquilinoNoReconocido, ShouldQueue
{
    public function handle(TestEvent $event): void {}
}
