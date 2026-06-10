<?php

namespace Eddwar\Multitenencia\Tasks;

use Eddwar\Multitenencia\Contracts\EsInquilino;
use Illuminate\Cache\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TareaDeCacheDePrefijos implements TareaDeCambioDeInquilino
{
    protected ?string $originalPrefix;

    public function __construct(
        protected ?string $storeName = null,
        protected ?string $cacheKeyBase = null
    ) {
        $this->originalPrefix = config('cache.prefix');

        $this->storeName ??= config('cache.default');

        $this->cacheKeyBase ??= 'tenant_id_';
    }

    /**
     * @param  EsInquilino&Model  $tenant
     */
    public function hacerActual(EsInquilino $tenant): void
    {
        $this->setCachePrefix($this->cacheKeyBase.$tenant->getKey());
    }

    public function olvidarActual(): void
    {
        $this->setCachePrefix($this->originalPrefix);
    }

    protected function setCachePrefix(string $prefix): void
    {
        config()->set('cache.prefix', $prefix);

        app('cache')->forgetDriver($this->storeName);

        // This is important because the `CacheManager` will have the `$app['config']` array cached
        // with old prefixes on the `cache` instance. Simply calling `forgetDriver` only removes
        // the `$store` but doesn't update the `$app['config']`.
        app()->forgetInstance('cache');

        // This is important because the Cache Repository is using an old version of the CacheManager
        app()->forgetInstance('cache.store');

        // Forget the cache repository in the container
        app()->forgetInstance(Repository::class);

        Cache::clearResolvedInstances();
    }
}
