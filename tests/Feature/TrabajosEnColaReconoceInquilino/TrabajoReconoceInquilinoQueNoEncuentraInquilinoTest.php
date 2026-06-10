<?php

// /** @var TestCase $this */

use Eddwar\Multitenencia\Exceptions\ExcepcionInquilinoActualNoReconocidoEnTrabajoEnCola;
use Eddwar\Multitenencia\Models\Inquilino;
use Eddwar\Multitenencia\Tests\Feature\TrabajosEnColaReconoceInquilino\TestClasses\InquilinoReconocidoTestJob;
use Eddwar\Multitenencia\Tests\Feature\TrabajosEnColaReconoceInquilino\TestClasses\PruebaDeTrabajoDeColaDeInquilinoNotReconocido;
use Eddwar\Multitenencia\Tests\Feature\TrabajosEnColaReconoceInquilino\TestClasses\TestJob;
// use Eddwar\Multitenencia\Tests\TestCase;
use Illuminate\Contracts\Bus\Dispatcher;
use Spatie\Valuestore\Valuestore;

beforeEach(function () {
    /** @var Inquilino $this */
    config()->set('multitenencia.colas_reconocen_inquilinos_por_defecto', true);
    config()->set('queue.default', 'sync');

    $this->inquilino = Inquilino::factory()->create();

    $this->valuestore = Valuestore::make(tempFile('inquilinoReconocido.json'))->flush();
});

it('will fail a job when no Inquilino is present and queues are Inquilino aware by default', function () {
    config()->set('multitenencia.colas_reconocen_inquilinos_por_defecto', true);

    $job = new TestJob($this->valuestore);

    try {
        app(Dispatcher::class)->dispatch($job);
    } catch (ExcepcionInquilinoActualNoReconocidoEnTrabajoEnCola $exception) {
        // Assert the job did not run
        expect($this->valuestore->has('tenantId'))->toBeFalse();

        return;
    }

    $this->fail();
});

it('will fail a job when no Inquilino is present and job implements the InquilinoReconocido interface', function () {
    config()->set('multitenencia.colas_reconocen_inquilinos_por_defecto', false);

    $job = new InquilinoReconocidoTestJob($this->valuestore);

    try {
        app(Dispatcher::class)->dispatch($job);
    } catch (ExcepcionInquilinoActualNoReconocidoEnTrabajoEnCola $exception) {
        expect($this->valuestore)->has('tenantId')->toBeFalse();

        return;
    }

    $this->fail();
});

it('will not fail a job when no Inquilino is present and queues are not Inquilino aware by default', function () {
    config()->set('multitenencia.colas_reconocen_inquilinos_por_defecto', false);

    $job = new TestJob($this->valuestore);

    app(Dispatcher::class)->dispatch($job);

    expect($this->valuestore)
        ->has('tenantId')->toBeTrue()
        ->get('tenantId')->toBeNull();
});

test(
    'it will not fail a job when no Inquilino is present and job implements the InquilinoNotReconocido interface',
    function () {
        config()->set('multitenencia.colas_reconocen_inquilinos_por_defecto', true);

        $job = new PruebaDeTrabajoDeColaDeInquilinoNotReconocido($this->valuestore);

        app(Dispatcher::class)->dispatch($job);

        expect($this->valuestore)
            ->has('tenantId')->toBeTrue()
            ->get('tenantId')->toBeNull();
    }
);

it('will forget any current Inquilino when starting a not Inquilino aware job', function () {
    $this->inquilino->hacerActual();

    $job = new PruebaDeTrabajoDeColaDeInquilinoNotReconocido($this->valuestore);

    // Simulate a Inquilino being set from a previous queue job
    expect(Inquilino::comprobarActual())->toBeTrue();

    app(Dispatcher::class)->dispatch($job);

    // Assert that the active Inquilino was forgotten
    $this->assertNull(Inquilino::actual());
});
