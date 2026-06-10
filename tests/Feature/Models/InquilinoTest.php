<?php

use Eddwar\Multitenencia\Events\EventoHaciendoInquilinoActual;
use Eddwar\Multitenencia\Events\EventoInquilinoActualCreado;
use Eddwar\Multitenencia\Events\EventoInquilinoActualOlvidado;
use Eddwar\Multitenencia\Events\OlvidandoEventoInquilinoActual;
use Eddwar\Multitenencia\Models\Inquilino;
use Eddwar\Multitenencia\Tests\TestCase;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    /** @var TestCase $this */
    $this->inquilino = Inquilino::factory()->create();
});

it('can get the current Inquilino', function () {
    expect(Inquilino::actual())->toBeNull();

    $this->inquilino->hacerActual();

    expect(Inquilino::actual()->id)->toEqual($this->inquilino->id);
});

it('will bind the current Inquilino in the container', function () {
    $containerKey = config('multitenencia.clave_de_contenedor_del_inquilino_actual');

    expect(app()->has($containerKey))->toBeFalse();

    $this->inquilino->hacerActual();

    expect(app()->has($containerKey))->toBeTrue();

    expect(app($containerKey))->toBeInstanceOf(Inquilino::class);
    expect(app($containerKey)->id)->toEqual($this->inquilino->id);
});

it('can forget the current Inquilino', function () {
    $containerKey = config('multitenencia.clave_de_contenedor_del_inquilino_actual');
    $contextKey = config('multitenencia.clave_de_contexto_del_inquilino_actual');

    $this->inquilino->hacerActual();

    expect(Inquilino::actual()->id)->toEqual($this->inquilino->id)
        ->and(app()->has($containerKey))->toBeTrue()
        ->and(Context::get($contextKey))->toEqual($this->inquilino->id);

    Inquilino::olvidarActual();

    expect(Inquilino::actual())->toBeNull()
        ->and(app())->has($containerKey)->toBeFalse()
        ->and(Context::get($contextKey))->toBeNull();
});

it('can check if a current Inquilino has been set', function () {
    expect(Inquilino::comprobarActual())->toBeFalse();

    $this->inquilino->hacerActual();

    expect(Inquilino::comprobarActual())->toBeTrue();

    Inquilino::olvidarActual();

    expect(Inquilino::comprobarActual())->toBeFalse();
});

it('can check if a particular Inquilino is the current one', function () {
    /** @var Inquilino $inquilino */
    $inquilino = Inquilino::factory()->create();

    /** @var Inquilino $anotherInquilino */
    $anotherInquilino = Inquilino::factory()->create();

    expect($inquilino->esActual())->toBeFalse()
        ->and($anotherInquilino->esActual())->toBeFalse();

    $inquilino->hacerActual();
    expect($inquilino->esActual())->toBeTrue()
        ->and($anotherInquilino->esActual())->toBeFalse();

    $anotherInquilino->hacerActual();
    expect($inquilino->esActual())->toBeFalse()
        ->and($anotherInquilino->esActual())->toBeTrue();

    Inquilino::olvidarActual();
    expect($inquilino->esActual())->toBeFalse()
        ->and($anotherInquilino->esActual())->toBeFalse();
});

it('will fire off events when making a Inquilino current', function () {
    Event::fake();

    Event::assertNotDispatched(EventoHaciendoInquilinoActual::class);
    Event::assertNotDispatched(EventoInquilinoActualCreado::class);

    $this->inquilino->hacerActual();

    Event::assertDispatched(EventoHaciendoInquilinoActual::class);
    Event::assertDispatched(EventoInquilinoActualCreado::class);
});

it('will fire off events when forgetting the current Inquilino', function () {
    Event::fake();

    $this->inquilino->hacerActual();

    Event::assertNotDispatched(OlvidandoEventoInquilinoActual::class);
    Event::assertNotDispatched(EventoInquilinoActualOlvidado::class);

    Inquilino::olvidarActual();

    Event::assertDispatched(OlvidandoEventoInquilinoActual::class);
    Event::assertDispatched(EventoInquilinoActualOlvidado::class);
});

it('will not fire off events when forgetting the current Inquilino when not current Inquilino is set', function () {
    Event::fake();

    Inquilino::olvidarActual();

    Event::assertNotDispatched(OlvidandoEventoInquilinoActual::class);
    Event::assertNotDispatched(EventoInquilinoActualOlvidado::class);
});

it('will execute a callable and then restore the previous state', function () {
    Inquilino::olvidarActual();

    expect(Inquilino::actual())->toBeNull();

    $response = $this->inquilino->execute(function (Inquilino $inquilino) {
        expect(Inquilino::actual()->id)->toEqual($inquilino->id);

        return $inquilino->id;
    });

    expect(Inquilino::actual())->toBeNull();

    expect($this->inquilino->id)->toEqual($response);
});

it('will execute a callable and then restore the previous state when the callable throws an exception', function () {
    Inquilino::olvidarActual();

    expect(Inquilino::actual())->toBeNull();

    try {
        $this->inquilino->execute(function (Inquilino $inquilino) {
            expect(Inquilino::actual()->id)->toEqual($inquilino->id);

            throw new Exception('Exception on execute.');
        });
    } catch (Exception $e) {
        expect($e->getMessage())->toBe('Exception on execute.');
    }

    expect(Inquilino::actual())->toBeNull();
});

it('will restore the original Inquilino when the callable throws an exception', function () {
    /** @var Inquilino $anotherInquilino */
    $anotherInquilino = Inquilino::factory()->create();
    $anotherInquilino->hacerActual();

    expect(Inquilino::actual()->id)->toBe($anotherInquilino->id);

    try {
        $this->inquilino->execute(function (Inquilino $inquilino) {
            expect(Inquilino::actual()->id)->toEqual($inquilino->id);

            throw new Exception('Exception on execute.');
        });
    } catch (Exception $e) {
        expect($e->getMessage())->toBe('Exception on execute.');
    }

    expect(Inquilino::actual()->id)->toEqual($anotherInquilino->id);
});

it('will execute a delayed callback in Inquilino context', function () {
    Inquilino::olvidarActual();

    expect(Inquilino::actual())->toBeNull();

    $callback = $this->inquilino->callback(function (Inquilino $inquilino) {
        expect(Inquilino::actual()->id)->toEqual($inquilino->id);

        return $inquilino->id;
    });
    expect(Inquilino::actual())->toBeNull();

    $response = $callback();

    expect(Inquilino::actual())->toBeNull();

    expect($this->inquilino->id)->toBe($response);
});

it('will execute a delayed callback in Inquilino context when the callable throws an exception', function () {
    Inquilino::olvidarActual();

    expect(Inquilino::actual())->toBeNull();

    $callback = $this->inquilino->callback(function (Inquilino $inquilino) {
        expect(Inquilino::actual()->id)->toEqual($inquilino->id);

        throw new Exception('Exception on execute.');
    });

    expect(Inquilino::actual())->toBeNull();

    try {
        $callback();
    } catch (Exception $e) {
        expect($e->getMessage())->toBe('Exception on execute.');
    }

    expect(Inquilino::actual())->toBeNull();
});
