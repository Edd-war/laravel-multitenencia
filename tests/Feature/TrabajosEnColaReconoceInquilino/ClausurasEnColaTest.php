<?php

use Eddwar\Multitenencia\Models\Inquilino;
use Eddwar\Multitenencia\Tests\TestCase;
use Spatie\Valuestore\Valuestore;

beforeEach(function () {
    /** @var TestCase $this */
    config()->set('multitenencia.colas_reconocen_inquilinos_por_defecto', false);

    $this->inquilino = Inquilino::factory()->create();
});

it('succeeds with closure job when queues are Inquilino aware by default', function () {
    /** @var TestCase $this */
    $valuestore = Valuestore::make(tempFile('InquilinoReconocido.json'))->flush();

    config()->set('multitenencia.colas_reconocen_inquilinos_por_defecto', true);

    $this->inquilino->hacerActual();

    dispatch(function () use ($valuestore) {
        $inquilino = Inquilino::actual();

        $valuestore->put('tenantId', $inquilino?->getKey());
        $valuestore->put('tenantName', $inquilino?->nombre);
    });

    $this->artisan('queue:work --once')->assertExitCode(0);

    expect($valuestore->get('tenantId'))->toBe($this->inquilino->getKey())
        ->and($valuestore->get('tenantName'))->toBe($this->inquilino->nombre);
});

it('fails with closure job when queues are not Inquilino aware by default', function () {
    /** @var TestCase $this */
    $valuestore = Valuestore::make(tempFile('inquilinoReconocido.json'))->flush();

    $this->inquilino->hacerActual();

    dispatch(function () use ($valuestore) {
        $inquilino = Inquilino::actual();

        $valuestore->put('tenantId', $inquilino?->getKey());
        $valuestore->put('tenantName', $inquilino?->nombre);
    });

    $this->artisan('queue:work --once')->assertExitCode(0);

    expect($valuestore->get('tenantId'))->toBeNull()
        ->and($valuestore->get('tenantName'))->toBeNull();
});

it('succeeds with closure job when a Inquilino is specified', function () {
    /** @var TestCase $this */
    $valuestore = Valuestore::make(tempFile('inquilinoReconocido.json'))->flush();

    $currentTenant = $this->inquilino;

    dispatch(function () use ($valuestore, $currentTenant) {
        $currentTenant->hacerActual();

        $inquilino = Inquilino::actual();

        $valuestore->put('tenantId', $inquilino?->getKey());
        $valuestore->put('tenantName', $inquilino?->nombre);

        $currentTenant->olvidar();
    });

    $this->artisan('queue:work --once')->assertExitCode(0);

    expect($valuestore->get('tenantId'))->toBe($this->inquilino->getKey())
        ->and($valuestore->get('tenantName'))->toBe($this->inquilino->nombre);
});
