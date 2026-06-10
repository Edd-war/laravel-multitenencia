<?php

use Eddwar\Multitenencia\Exceptions\ExcepcionInquilinoActualNoReconocidoEnTrabajoEnCola;
use Eddwar\Multitenencia\Models\Inquilino;
use Eddwar\Multitenencia\Tests\Feature\TrabajosEnColaReconoceInquilino\TestClasses\ListenerInquilinoNoReconocido;
use Eddwar\Multitenencia\Tests\Feature\TrabajosEnColaReconoceInquilino\TestClasses\ListenerInquilinoReconocido;
use Eddwar\Multitenencia\Tests\Feature\TrabajosEnColaReconoceInquilino\TestClasses\TestEvent;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    /** @var Inquilino $this */
    config()->set('multitenencia.colas_reconocen_inquilinos_por_defecto', true);
    config()->set('queue.default', 'sync');
    config()->set('mail.default', 'log');

    $this->inquilino = Inquilino::factory()->create();
});

it('will fail when no Inquilino is present and listeners are Inquilino aware by default', function () {
    config()->set('multitenencia.colas_reconocen_inquilinos_por_defecto', true);

    Event::listen(TestEvent::class, ListenerInquilinoReconocido::class);

    Event::dispatch(new TestEvent('Hello world!'));
})->throws(ExcepcionInquilinoActualNoReconocidoEnTrabajoEnCola::class);

it('will not fail when no Inquilino is present and listeners are Inquilino aware by default', function () {
    config()->set('multitenencia.colas_reconocen_inquilinos_por_defecto', true);

    Event::listen(TestEvent::class, ListenerInquilinoNoReconocido::class);
    Event::dispatch(new TestEvent('Hello world!'));

    $this->expectExceptionMessage("Method Illuminate\Events\Dispatcher::assertDispatchedTimes does not exist.");

    Event::assertDispatchedTimes(TestEvent::class);
});

it('will inject the current Inquilino id', function () {
    config()->set('multitenencia.colas_reconocen_inquilinos_por_defecto', true);

    $this->inquilino->hacerActual();

    Event::listen(TestEvent::class, ListenerInquilinoNoReconocido::class);

    expect(
        Event::dispatch(new TestEvent('Hello world!'))
    )->toEqual([0 => null]);
});
