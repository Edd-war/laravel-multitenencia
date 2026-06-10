<?php

namespace Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses;

use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\Multitenancy\Jobs\NotTenantAware;

class ListenerNotTenantAware implements NotTenantAware, ShouldQueue
{
    public function handle(TestEvent $event): void {}
}
