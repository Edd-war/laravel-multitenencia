<?php

use Eddwar\Multitenencia\Models\Inquilino;
use Eddwar\Multitenencia\Tasks\TareaDelCambioDeBaseDeDatosDelInquilino;
use Eddwar\Multitenencia\Tests\TestCase;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    /** @var TestCase $this */
    config()->set('multitenencia.tareas_de_cambio_de_inquilino', [TareaDelCambioDeBaseDeDatosDelInquilino::class]);

    $this->inquilino = Inquilino::factory()->create(['base_de_datos' => 'laravel_mt_tenant_1']);
    $this->inquilino->execute(fn () => Schema::connection('tenant')->dropIfExists('migrations'));

    $this->anotherInquilino = Inquilino::factory()->create(['base_de_datos' => 'laravel_mt_tenant_2']);
    $this->anotherInquilino->execute(fn () => Schema::connection('tenant')->dropIfExists('migrations'));
});

it('can migrate all tenant databases', function () {
    $this
        ->artisan('tenants:artisan "migrate --database=tenant"')
        ->assertExitCode(0);

    assertTenantDatabaseHasTable($this->inquilino, 'migrations');
    assertTenantDatabaseHasTable($this->anotherInquilino, 'migrations');
});

it('can migrate a specific tenant', function () {
    $this->artisan('tenants:artisan "migrate --database=tenant" --tenant="'.$this->anotherInquilino->id.'"')->assertExitCode(0);

    assertTenantDatabaseDoesNotHaveTable($this->inquilino, 'migrations');
    assertTenantDatabaseHasTable($this->anotherInquilino, 'migrations');
});

test("it can't migrate a specific tenant id when search by domain", function () {
    config(['multitenencia.campos_de_busqueda_artisan_para_inquilinos' => 'dominio']);

    $this->artisan('tenants:artisan', [
        'artisanCommand' => 'migrate --database=tenant',
        '--tenant' => $this->anotherInquilino->id,
    ])
        ->expectsOutput('No tenant(s) found.')
        ->assertExitCode(-1);
});

it('can migrate a specific tenant by domain', function () {
    config(['multitenencia.campos_de_busqueda_artisan_para_inquilinos' => 'dominio']);

    $this->artisan('tenants:artisan', [
        'artisanCommand' => 'migrate --database=tenant',
        '--tenant' => $this->anotherInquilino->dominio,
    ])->assertExitCode(0);

    assertTenantDatabaseDoesNotHaveTable($this->inquilino, 'migrations');
    assertTenantDatabaseHasTable($this->anotherInquilino, 'migrations');
});
