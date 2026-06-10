<?php

use Eddwar\Multitenencia\Exceptions\ExcepcionInquilinoActualNoReconocidoEnTrabajoEnCola;
use Eddwar\Multitenencia\Models\Inquilino;
use Eddwar\Multitenencia\Tests\Feature\Models\InquilinoNotificable;
use Eddwar\Multitenencia\Tests\Feature\TrabajosEnColaReconoceInquilino\TestClasses\NotificationInquilinoNoReconocido;
use Eddwar\Multitenencia\Tests\Feature\TrabajosEnColaReconoceInquilino\TestClasses\NotificationInquilinoReconocido;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    /** @var Inquilino $this */
    config()->set('multitenencia.colas_reconocen_inquilinos_por_defecto', true);
    config()->set('queue.default', 'sync');
    config()->set('mail.default', 'log');

    $this->inquilino = InquilinoNotificable::factory()->create();
});

it('will fail when no Inquilino is present and mailables are Inquilino aware by default', function () {
    config()->set('multitenencia.colas_reconocen_inquilinos_por_defecto', true);

    $this->inquilino->notify((new NotificationInquilinoReconocido)->delay(now()->addSecond()));

    Notification::assertNothingSent();
})->throws(ExcepcionInquilinoActualNoReconocidoEnTrabajoEnCola::class);

it('will not fail when no Inquilino is present and mailables are Inquilino aware by default', function () {
    config()->set('multitenencia.colas_reconocen_inquilinos_por_defecto', true);

    $this->inquilino->notify((new NotificationInquilinoNoReconocido));

    $this->expectException(Throwable::class);

    Notification::assertCount(1);
});

it('will inject the current Inquilino id', function () {
    config()->set('multitenencia.colas_reconocen_inquilinos_por_defecto', true);

    $this->inquilino->hacerActual();

    $this->inquilino->notify((new NotificationInquilinoReconocido)->delay(now()->addSecond()));

    $this->expectException(Throwable::class);

    Notification::assertNothingSent();
});
