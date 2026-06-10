<?php

use Eddwar\Multitenencia\Models\Inquilino;
use Eddwar\Multitenencia\Tasks\TareaDeCacheDeCambioDeRuta;

beforeEach(function () {
    config()->set('multitenencia.tareas_de_cambio_de_inquilino', [TareaDeCacheDeCambioDeRuta::class]);
});

it('will use a different routes cache environment variable for each tenant', function () {
    /** @var Inquilino $tenant */
    $tenant = Inquilino::factory()->create();
    $tenant->hacerActual();
    expect(env('APP_ROUTES_CACHE'))
        ->toEqual("bootstrap/cache/routes-v7-tenant-{$tenant->id}.php");

    /** @var Inquilino $anotherTenant */
    $anotherTenant = Inquilino::factory()->create();
    $anotherTenant->hacerActual();
    expect(env('APP_ROUTES_CACHE'))
        ->toEqual("bootstrap/cache/routes-v7-tenant-{$anotherTenant->id}.php");

    $tenant->hacerActual();
    expect(env('APP_ROUTES_CACHE'))
        ->toEqual("bootstrap/cache/routes-v7-tenant-{$tenant->id}.php");

    $anotherTenant->hacerActual();
    expect(env('APP_ROUTES_CACHE'))
        ->toEqual("bootstrap/cache/routes-v7-tenant-{$anotherTenant->id}.php");

    Inquilino::olvidarActual();
    expect(env('APP_ROUTES_CACHE'))->toBeNull();
});

it('will use a shared routes cache environment variable for all tenants', function () {
    config()->set('multitenencia.cache_de_rutas_compartido', true);

    /** @var Inquilino $tenant */
    $tenant = Inquilino::factory()->create();
    $tenant->hacerActual();
    expect(env('APP_ROUTES_CACHE'))->toEqual('bootstrap/cache/routes-v7-tenants.php');

    /** @var Inquilino $anotherTenant */
    $anotherTenant = Inquilino::factory()->create();
    $anotherTenant->hacerActual();
    expect(env('APP_ROUTES_CACHE'))->toEqual('bootstrap/cache/routes-v7-tenants.php');

    Inquilino::olvidarActual();
    expect(env('APP_ROUTES_CACHE'))->toBeNull();
});
