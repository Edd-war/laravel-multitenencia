<?php

namespace Eddwar\Multitenencia\Tests\Feature\TrabajosEnColaReconoceInquilino\TestClasses;

use Eddwar\Multitenencia\Jobs\InquilinoReconocido;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class BroadcastInquilinoReconocido implements InquilinoReconocido, ShouldBroadcast
{
    public function __construct(
        public string $message,
    ) {}

    public function broadcastOn()
    {
        return [
            new Channel('test-channel'),
        ];
    }
}
