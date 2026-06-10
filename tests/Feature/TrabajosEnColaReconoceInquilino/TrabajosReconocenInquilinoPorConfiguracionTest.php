<?php

use Eddwar\Multitenencia\Models\Inquilino;
use Eddwar\Multitenencia\Tests\Feature\TrabajosEnColaReconoceInquilino\TestClasses\CustomInquilinoReconocido;
use Eddwar\Multitenencia\Tests\Feature\TrabajosEnColaReconoceInquilino\TestClasses\InquilinoNotReconocidoPersonalizado;
use Eddwar\Multitenencia\Tests\Feature\TrabajosEnColaReconoceInquilino\TestClasses\TestJob;
use Eddwar\Multitenencia\Tests\Feature\TrabajosEnColaReconoceInquilino\TestClasses\TestJobCustomInquilinoReconocido;
use Eddwar\Multitenencia\Tests\Feature\TrabajosEnColaReconoceInquilino\TestClasses\TestJobInquilinoNotReconocidoPersonalizado;
use Illuminate\Contracts\Bus\Dispatcher;
use Spatie\Valuestore\Valuestore;

beforeEach(function () {
    /** @var Inquilino $this */
    config()->set('multitenencia.colas_reconocen_inquilinos_por_defecto', false);
    config()->set('queue.default', 'sync');
    config()->set('mail.default', 'log');

    $this->inquilino = Inquilino::factory()->create();
    $this->valuestore = Valuestore::make(tempFile('inquilinoReconocido.json'))->flush();
});

it('succeeds with jobs with a custom Inquilino aware interface', function () {
    config()->set('multitenencia.interfaz_reconoce_inquilinos', CustomInquilinoReconocido::class);

    $this->inquilino->hacerActual();

    app(Dispatcher::class)->dispatch(new TestJobCustomInquilinoReconocido($this->valuestore));

    expect($this->valuestore->has('tenantIdInContext'))->toBeTrue()
        ->and($this->valuestore->get('tenantIdInContext'))->not->toBeNull();
});

it('succeeds with jobs in Inquilino aware jobs list', function () {
    config()->set('multitenencia.trabajos_que_reconocen_inquilinos', [TestJob::class]);

    $this->inquilino->hacerActual();

    app(Dispatcher::class)->dispatch(new TestJob($this->valuestore));

    expect($this->valuestore->has('tenantIdInContext'))->toBeTrue()
        ->and($this->valuestore->get('tenantIdInContext'))->not->toBeNull();
});

it('fails with jobs in not Inquilino aware jobs list', function () {
    config()->set('multitenencia.trabajos_que_no_reconocen_inquilinos', [TestJob::class]);

    $this->inquilino->hacerActual();

    app(Dispatcher::class)->dispatch(new TestJob($this->valuestore));

    expect($this->valuestore->get('tenantId'))->toBeNull()
        ->and($this->valuestore->get('tenantName'))->toBeNull()
        ->and($this->valuestore->has('tenantIdInContext'))->toBeTrue();
});

it('fails with jobs implementing custom not Inquilino aware jobs', function () {
    config()->set('multitenencia.interfaz_no_reconoce_inquilinos', InquilinoNotReconocidoPersonalizado::class);

    $this->inquilino->hacerActual();

    app(Dispatcher::class)->dispatch(new TestJobInquilinoNotReconocidoPersonalizado($this->valuestore));

    expect($this->valuestore->get('tenantId'))->toBeNull()
        ->and($this->valuestore->get('tenantName'))->toBeNull()
        ->and($this->valuestore->has('tenantIdInContext'))->toBeTrue();
});
