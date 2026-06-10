<?php

namespace Eddwar\Multitenencia\Tests\Feature\Models;

use Eddwar\Multitenencia\Models\Inquilino;
use Illuminate\Notifications\Notifiable;

class InquilinoNotificable extends Inquilino
{
    use Notifiable;

    protected $table = 'inquilinos';

    protected $appends = [
        'email',
    ];

    public function getEmailAttribute()
    {
        return 'test@eddwar.be';
    }
}
