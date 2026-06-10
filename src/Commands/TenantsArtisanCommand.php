<?php

namespace Spatie\Multitenancy\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Spatie\Multitenancy\Commands\Concerns\TenantAware;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Models\Tenant;

class TenantsArtisanCommand extends Command
{
    use TenantAware;
    use UsesMultitenancyConfig;

    protected $signature = 'tenants:artisan {artisanCommand} {--tenant=*}';

    protected $description = 'Run an Artisan command for selected tenants.';

    public function handle(): void
    {
        if (! $artisanCommand = $this->argument('artisanCommand')) {
            $artisanCommand = $this->ask('Which artisan command do you want to run for all tenants?');
        }

        $artisanCommand = addslashes($artisanCommand);

        /** @var Tenant $tenant */
        $tenant = app(IsTenant::class)::current();

        $this->line('');
        $this->info("Running command for tenant `{$tenant->name}` (id: {$tenant->getKey()})...");
        $this->line('---------------------------------------------------------');

        Artisan::call($artisanCommand, [], $this->output);
    }
}
