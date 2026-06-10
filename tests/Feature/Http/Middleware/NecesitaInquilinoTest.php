<?php

use Eddwar\Multitenencia\Exceptions\ExcepcionNoHayInquilinoActual;
use Eddwar\Multitenencia\Http\Middleware\NecesitaInquilino;
use Eddwar\Multitenencia\Models\Inquilino;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    /** @var Inquilino $this */
    $this->withoutExceptionHandling();

    Route::get('middleware-test', fn () => 'ok')
        ->middleware(NecesitaInquilino::class);

    $this->inquilino = Inquilino::factory()->create();
});

it('will pass if there is current tenant set', function () {
    $this->inquilino->hacerActual();

    $this->get('middleware-test')->assertOk();
});

it('will throw an exception when there is not current tenant')
    ->get('middleware-test')
    ->throws(ExcepcionNoHayInquilinoActual::class);
