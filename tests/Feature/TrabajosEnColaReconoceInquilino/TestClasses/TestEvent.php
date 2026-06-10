<?php

namespace Eddwar\Multitenencia\Tests\Feature\TrabajosEnColaReconoceInquilino\TestClasses;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public string $message,
    ) {}
}
