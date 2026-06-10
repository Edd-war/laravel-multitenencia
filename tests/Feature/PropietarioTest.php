<?php

use Eddwar\Multitenencia\Models\Inquilino;
use Eddwar\Multitenencia\Propietario;
use Eddwar\Multitenencia\Tests\TestCase;

beforeEach(function () {
    /** @var TestCase $this */
    $this->inquilino = Inquilino::factory()->create();
});

it('will execute a callable as propietario and then restore the previous tenant', function () {
    $this->inquilino->hacerActual();

    $response = Propietario::execute(fn () => Inquilino::actual());

    expect($response)->toBeNull();

    expect($this->inquilino->id)->toEqual(Inquilino::actual()->id);
});
