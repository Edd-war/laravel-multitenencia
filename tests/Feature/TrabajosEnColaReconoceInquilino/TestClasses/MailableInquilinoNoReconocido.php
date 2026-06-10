<?php

namespace Eddwar\Multitenencia\Tests\Feature\TrabajosEnColaReconoceInquilino\TestClasses;

use Eddwar\Multitenencia\Jobs\InquilinoNoReconocido;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;

class MailableInquilinoNoReconocido extends Mailable implements InquilinoNoReconocido, ShouldQueue
{
    public function build(): Mailable
    {
        return $this->view('mailable');
    }
}
