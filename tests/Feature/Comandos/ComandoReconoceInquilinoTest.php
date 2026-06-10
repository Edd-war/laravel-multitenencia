<?php

use Eddwar\Multitenencia\Models\Inquilino;
use Eddwar\Multitenencia\Tasks\TareaDelCambioDeBaseDeDatosDelInquilino;
use Eddwar\Multitenencia\Tests\TestCase;

beforeEach(function () {
    /** @var TestCase $this */
    config()->set('multitenencia.tareas_de_cambio_de_inquilino', [TareaDelCambioDeBaseDeDatosDelInquilino::class]);

    $this->inquilino = Inquilino::factory()->create(['base_de_datos' => 'laravel_mt_tenant_1']);

    $this->anotherInquilino = Inquilino::factory()->create(['base_de_datos' => 'laravel_mt_tenant_2']);
});

it('fails with a non-existing tenant')
    ->artisan('inquilino:noop --tenant=1000')
    ->assertExitCode(-1)
    ->expectsOutput('No tenant(s) found.');

it('works with no tenant parameters', function () {
    $this
        ->artisan('inquilino:noop')
        ->assertExitCode(0)
        ->expectsOutput('El ID del inquilino es '.$this->inquilino->id)
        ->expectsOutput('El ID del inquilino es '.$this->anotherInquilino->id);
});
