<?php

use Eddwar\Multitenencia\Exceptions\ExcepcionInquilinoActualNoReconocidoEnTrabajoEnCola;
use Eddwar\Multitenencia\Models\Inquilino;
use Eddwar\Multitenencia\Tests\Feature\TrabajosEnColaReconoceInquilino\TestClasses\MailableInquilinoNoReconocido;
use Eddwar\Multitenencia\Tests\Feature\TrabajosEnColaReconoceInquilino\TestClasses\MailableInquilinoReconocido;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    /** @var Inquilino $this */
    config()->set('multitenencia.colas_reconocen_inquilinos_por_defecto', true);
    config()->set('queue.default', 'sync');
    config()->set('mail.default', 'log');

    $this->inquilino = Inquilino::factory()->create();
});

it('will fail when no Inquilino is present and mailables are Inquilino aware by default', function () {
    config()->set('multitenencia.colas_reconocen_inquilinos_por_defecto', true);

    Mail::to('test@Eddwar.be')->queue(new MailableInquilinoReconocido);
})->throws(ExcepcionInquilinoActualNoReconocidoEnTrabajoEnCola::class);

it('will not fail when no Inquilino is present and mailables are Inquilino aware by default', function () {
    config()->set('multitenencia.colas_reconocen_inquilinos_por_defecto', true);

    Mail::to('test@Eddwar.be')->queue(new MailableInquilinoNoReconocido);

    $this->expectExceptionMessage("Method Illuminate\Mail\Mailer::assertSentCount does not exist.");

    Mail::assertSentCount(1);
});

it('will inject the current Inquilino id', function () {
    config()->set('multitenencia.colas_reconocen_inquilinos_por_defecto', true);

    $this->inquilino->hacerActual();

    expect(
        Mail::to('test@Eddwar.be')->queue(new MailableInquilinoReconocido)
    )->toEqual(0);
});
