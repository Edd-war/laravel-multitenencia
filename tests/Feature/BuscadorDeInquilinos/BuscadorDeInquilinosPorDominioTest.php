<?php

use Eddwar\Multitenencia\BuscadorDeInquilinos\BuscadorDeInquilinosDeDominio;
use Eddwar\Multitenencia\Models\Inquilino;
use Eddwar\Multitenencia\Tests\TestCase;
use Illuminate\Http\Request;

beforeEach(function () {
    /** @var TestCase $this */
    $this->tenantFinder = new BuscadorDeInquilinosDeDominio;
});

it('can find a tenant for the current domain', function () {
    /** @var TestCase $this */
    $inquilino = Inquilino::factory()->create(['dominio' => 'my-domain.com']);

    $request = Request::create('https://my-domain.com');

    expect($inquilino->id)->toEqual($this->tenantFinder->buscarParaPeticion($request)->id);
});

it('will return null if there are no tenants', function () {
    /** @var TestCase $this */
    $request = Request::create('https://my-domain.com');

    expect($this->tenantFinder->buscarParaPeticion($request))->toBeNull();
});

it('will return null if no tenant can be found the current domain', function () {
    /** @var TestCase $this */
    Inquilino::factory()->create(['dominio' => 'my-domain.com']);

    $request = Request::create('https://another-domain.com');

    expect($this->tenantFinder->buscarParaPeticion($request))->toBeNull();
});
