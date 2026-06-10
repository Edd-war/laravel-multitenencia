<?php

use Eddwar\Multitenencia\Models\Inquilino;
use Eddwar\Multitenencia\Tests\Feature\TrabajosEnColaReconoceInquilino\TestClasses\InquilinoReconocidoEncriptado;
use Eddwar\Multitenencia\Tests\Feature\TrabajosEnColaReconoceInquilino\TestClasses\InquilinoReconocidoTestJob;
use Eddwar\Multitenencia\Tests\Feature\TrabajosEnColaReconoceInquilino\TestClasses\PruebaDeTrabajoDeColaDeInquilinoNotReconocido;
use Eddwar\Multitenencia\Tests\Feature\TrabajosEnColaReconoceInquilino\TestClasses\TestJob;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Spatie\Valuestore\Valuestore;

beforeEach(function () {
    /** @var Inquilino $this */
    Event::fake(JobFailed::class);

    config()->set('multitenencia.colas_reconocen_inquilinos_por_defecto', true);

    $this->inquilino = Inquilino::factory()->create();

    $this->valuestore = Valuestore::make(tempFile('inquilinoReconocido.json'))->flush();

    Event::assertNotDispatched(JobFailed::class);
});

it('will inject the current Inquilino id in a job', function () {
    $this->inquilino->hacerActual();

    $job = new TestJob($this->valuestore);
    app(Dispatcher::class)->dispatch($job);

    Inquilino::olvidarActual();

    $this->artisan('queue:work --once')->assertExitCode(0);

    $currentTenantIdInJob = $this->valuestore->get('tenantId');

    expect($this->valuestore->get('tenantIdInContext'))->toBe($this->inquilino->getKey())
        ->and($this->inquilino->id)->toEqual($currentTenantIdInJob);
});

it('will inject the right Inquilino even when the current Inquilino switches', function () {
    /** @var Inquilino $anotherTenant */
    $anotherTenant = Inquilino::factory()->create();

    $this->inquilino->hacerActual();
    $job = new TestJob($this->valuestore);
    app(Dispatcher::class)->dispatch($job);

    $this->artisan('queue:work --once');

    $currentTenantIdInJob = $this->valuestore->get('tenantId');
    expect($this->inquilino->id)->toEqual($currentTenantIdInJob);

    $anotherTenant->hacerActual();
    $job = new TestJob($this->valuestore);
    app(Dispatcher::class)->dispatch($job);

    $this->artisan('queue:work --once');

    $currentTenantIdInJob = $this->valuestore->get('tenantId');

    expect($anotherTenant->id)->toEqual($currentTenantIdInJob);
});

it('will not make jobs Inquilino aware if the config settings is set to false', function () {
    config()->set('multitenencia.colas_reconocen_inquilinos_por_defecto', false);

    $this->inquilino->hacerActual();

    $job = new TestJob($this->valuestore);
    app(Dispatcher::class)->dispatch($job);

    $this->artisan('queue:work --once')->assertExitCode(0);

    $currentTenantIdInJob = $this->valuestore->get('tenantId');
    expect($currentTenantIdInJob)->toBeNull();
});

it('will always make jobs Inquilino aware if they implement the InquilinoReconocido interface', function () {
    config()->set('multitenencia.colas_reconocen_inquilinos_por_defecto', false);

    $this->inquilino->hacerActual();

    $job = new InquilinoReconocidoTestJob($this->valuestore);
    app(Dispatcher::class)->dispatch($job);

    $this->artisan('queue:work --once')->assertExitCode(0);

    $currentTenantIdInJob = $this->valuestore->get('tenantId');
    expect($this->inquilino->id)->toEqual($currentTenantIdInJob);
});

it('will not make a job Inquilino aware if it implements InquilinoNotReconocido', function () {
    config()->set('multitenencia.colas_reconocen_inquilinos_por_defecto', true);

    $this->inquilino->hacerActual();

    $job = new PruebaDeTrabajoDeColaDeInquilinoNotReconocido($this->valuestore);
    app(Dispatcher::class)->dispatch($job);

    $this->artisan('queue:work --once')->assertExitCode(0);

    $currentTenantIdInJob = $this->valuestore->get('tenantId');
    expect($currentTenantIdInJob)->toBeNull();
});

it('will decrypt encrypted jobs', function () {
    config()->set('multitenencia.colas_reconocen_inquilinos_por_defecto', true);

    $this->inquilino->hacerActual();

    $job = new InquilinoReconocidoEncriptado($this->valuestore);
    app(Dispatcher::class)->dispatch($job);

    $this->artisan('queue:work --once')->assertExitCode(0);

    Event::assertNotDispatched(JobFailed::class);
});
