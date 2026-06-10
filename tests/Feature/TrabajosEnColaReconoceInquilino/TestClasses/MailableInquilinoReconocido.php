<?php

namespace Eddwar\Multitenencia\Tests\Feature\TrabajosEnColaReconoceInquilino\TestClasses;

use Eddwar\Multitenencia\Jobs\InquilinoReconocido;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;

class MailableInquilinoReconocido extends Mailable implements InquilinoReconocido, ShouldQueue
{
    public function build(): Mailable
    {
        return $this->view('mailable');
    }
}
