<?php

namespace Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Spatie\Multitenancy\Jobs\NotTenantAware;

class MailableNotTenantAware extends Mailable implements NotTenantAware, ShouldQueue
{
    public function build(): Mailable
    {
        return $this->view('mailable');
    }
}
