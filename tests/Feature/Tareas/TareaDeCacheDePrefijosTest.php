<?php

use Eddwar\Multitenencia\Models\Inquilino;
use Eddwar\Multitenencia\Tasks\TareaDeCacheDePrefijos;

beforeEach(function () {
    config()->set('multitenencia.tareas_de_cambio_de_inquilino', [TareaDeCacheDePrefijos::class]);

    config()->set('cache.default', 'redis');

    app()->forgetInstance('cache');

    app()->forgetInstance('cache.store');

    app('cache')->flush();
});

it('will separate the cache prefix for each tenant', function () {
    $originalPrefix = config('cache.prefix');

    expect(app('cache')->store()->getStore()->getPrefix())->toStartWith($originalPrefix);
    expect(app('cache.store')->getStore()->getPrefix())->toStartWith($originalPrefix);

    /** @var Inquilino $tenantOne */
    $tenantOne = Inquilino::factory()->create();
    $tenantOne->hacerActual();
    $tenantOnePrefix = 'tenant_id_'.$tenantOne->id;

    expect(app('cache')->store()->getStore()->getPrefix())->toStartWith($tenantOnePrefix);
    expect(app('cache.store')->getStore()->getPrefix())->toStartWith($tenantOnePrefix);

    /** @var Inquilino $tenantTwo */
    $tenantTwo = Inquilino::factory()->create();
    $tenantTwo->hacerActual();
    $tenantTwoPrefix = 'tenant_id_'.$tenantTwo->id;

    expect(app('cache')->store()->getStore()->getPrefix())->toStartWith($tenantTwoPrefix);
    expect(app('cache.store')->getStore()->getPrefix())->toStartWith($tenantTwoPrefix);
});

it('will separate the cache for each tenant', function () {
    cache()->put('key', 'cache-propietario');

    /** @var Inquilino $tenantOne */
    $tenantOne = Inquilino::factory()->create();
    $tenantOne->hacerActual();
    $tenantOneVal = 'tenant-'.$tenantOne->dominio;

    expect(cache())->has('key')->toBeFalse();

    cache()->put('key', $tenantOneVal);

    /** @var Inquilino $tenantTwo */
    $tenantTwo = Inquilino::factory()->create();
    $tenantTwo->hacerActual();
    $tenantTwoVal = 'tenant-'.$tenantTwo->dominio;
    expect(cache())->has('key')->toBeFalse();
    cache()->put('key', $tenantTwoVal);

    $tenantOne->hacerActual();
    expect($tenantOneVal)
        ->toEqual(app('cache')->get('key'))
        ->toEqual(app('cache.store')->get('key'));

    $tenantTwo->hacerActual();
    expect($tenantTwoVal)
        ->toEqual(app('cache')->get('key'))
        ->toEqual(app('cache.store')->get('key'));

    Inquilino::olvidarActual();
    expect(cache())->get('key')->toEqual('cache-propietario');
});
