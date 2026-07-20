<?php

use Eddwar\Multitenencia\BuscadorDeInquilinos\BuscadorDeInquilinosConCache;
use Eddwar\Multitenencia\Models\Inquilino;
use Eddwar\Multitenencia\Tests\TestCase;
use Illuminate\Cache\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    /** @var TestCase $this */
    $this->tenantFinder = new BuscadorDeInquilinosConCache;
    config()->set('multitenencia.estrategias_de_busqueda', ['host']);
    config()->set('multitenencia.dominios_propietarios', ['propietario.com']);
    config()->set('multitenencia.cache.store_del_propietario', 'array');
    Cache::clear();
});

it('can find a tenant for the current host and caches the result', function () {
    /** @var TestCase $this */
    $inquilino = Inquilino::factory()->create(['dominio' => 'tenant-a.com']);

    $request = Request::create('https://tenant-a.com');

    // First resolution
    $resolved = $this->tenantFinder->buscarParaPeticion($request);
    expect($resolved)->not->toBeNull();
    expect($resolved->id)->toEqual($inquilino->id);

    // We can also assert that a cache key exists
    $cacheKeyInputs = [
        'host' => 'tenant-a.com',
    ];
    $cacheKey = 'tenant:resolver:'.md5(serialize($cacheKeyInputs));
    $cacheStore = config('multitenencia.cache.store_del_propietario') ?? config('cache.default');
    /** @var Repository $cache */
    $cache = Cache::store($cacheStore);

    $hasKey = $cache->supportsTags()
        ? $cache->tags(['tenant_resolver'])->has($cacheKey)
        : $cache->has($cacheKey);

    expect($hasKey)->toBeTrue();

    // Verify resolving from cache
    $resolvedCached = $this->tenantFinder->buscarParaPeticion($request);
    expect($resolvedCached->id)->toEqual($inquilino->id);
});

it('clears the cache when a tenant is saved or deleted', function () {
    /** @var TestCase $this */
    $inquilino = Inquilino::factory()->create(['dominio' => 'tenant-b.com']);

    $request = Request::create('https://tenant-b.com');

    // Resolve to populate cache
    $this->tenantFinder->buscarParaPeticion($request);

    // Check that model cache key exists
    expect(Cache::has("multitenencia:model:{$inquilino->id}"))->toBeTrue();
    expect(Cache::has('multitenencia:domains_map'))->toBeTrue();

    // Trigger update (saved event)
    $inquilino->nombre = 'Updated Tenant Name';
    $inquilino->save();

    // Check that cache is cleared
    expect(Cache::has('multitenencia:domains_map'))->toBeFalse();
    expect(Cache::has("multitenencia:model:{$inquilino->id}"))->toBeFalse();
});
