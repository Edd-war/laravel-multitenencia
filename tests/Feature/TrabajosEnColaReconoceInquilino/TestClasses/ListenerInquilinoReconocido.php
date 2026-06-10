<?php

namespace Eddwar\Multitenencia\Tests\Feature\TrabajosEnColaReconoceInquilino\TestClasses;

use Eddwar\Multitenencia\Jobs\InquilinoReconocido;
use Illuminate\Contracts\Queue\ShouldQueue;

class ListenerInquilinoReconocido implements InquilinoReconocido, ShouldQueue
{
    public function handle(TestEvent $event): void {}
}
