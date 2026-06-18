<?php

namespace Eddwar\Multitenencia\Providers;

use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class MultitenenciaCacheServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->extendCacheForMultitenancy();
        $this->setDefaultCacheStore();
    }

    /**
     * Configura el store de cache por defecto basado en el contexto multitenancy
     */
    protected function setDefaultCacheStore(): void
    {
        if (! config('multitenencia.cache.habilitado', false)) {
            return;
        }

        // Solo cambiar si estamos usando database como store por defecto
        if (config('cache.default') !== 'database') {
            return;
        }

        $this->app->resolving('cache', function (mixed $cache, Application $app) {
            $isTenantContext = static::isTenantContext();

            if ($isTenantContext) {
                $defaultStore = config('multitenencia.cache.store_del_inquilino', 'database');
            } else {
                $defaultStore = config('multitenencia.cache.store_del_propietario', 'database');
            }

            // Solo cambiar si es diferente al actual
            if ($defaultStore !== 'database') {
                config(['cache.default' => $defaultStore]);
            }
        });
    }

    /**
     * Extiende el sistema de cache para soportar multitenancy
     */
    protected function extendCacheForMultitenancy(): void
    {
        if (! config('multitenencia.cache.habilitado', false)) {
            return;
        }

        $this->configureTenantAwareCacheStores();
    }

    /**
     * Configura stores de cache conscientes del contexto multitenancy
     */
    protected function configureTenantAwareCacheStores(): void
    {
        $this->app->extend('cache', function (CacheManager $cache, Application $app) {
            $isTenantContext = static::isTenantContext();

            if ($isTenantContext) {
                $this->configureTenantCache($cache);
            } else {
                $this->configureLandlordCache($cache);
            }

            return $cache;
        });
    }

    /**
     * Configura cache para contexto tenant
     */
    protected function configureTenantCache(CacheManager $cache): void
    {
        $tenantStore = config('multitenencia.cache.store_del_inquilino');
        $tenantConnection = config('multitenencia.cache.conexion_del_inquilino', 'inquilino');

        if ($tenantStore) {
            $cache->extend($tenantStore, function (Application $app, array $config) use ($tenantConnection, $cache) {
                // @phpstan-ignore-next-line
                return $cache->createDatabaseDriver([
                    'connection' => $tenantConnection,
                    'table' => config('cache.stores.database.table', 'cache'),
                    'lock_connection' => $tenantConnection,
                    'lock_table' => config('cache.stores.database.lock_table', 'cache_locks'),
                ]);
            });
        }

        $prefix = config('multitenencia.cache.prefijo_de_cache_del_inquilino', 'tenant.');
        $tenantId = static::getCurrentTenantId();

        if ($tenantId) {
            $originalPrefix = config('cache.prefix');
            config(['cache.prefix' => $originalPrefix.$prefix.$tenantId.'.']);
        }
    }

    /**
     * Configura cache para contexto landlord
     */
    protected function configureLandlordCache(CacheManager $cache): void
    {
        $landlordStore = config('multitenencia.cache.store_del_propietario');
        $landlordConnection = config('multitenencia.cache.conexion_del_propietario', 'propietario');

        if ($landlordStore) {
            $cache->extend($landlordStore, function (Application $app, array $config) use ($landlordConnection, $cache) {
                // @phpstan-ignore-next-line
                return $cache->createDatabaseDriver([
                    'connection' => $landlordConnection,
                    'table' => config('cache.stores.database.table', 'cache'),
                    'lock_connection' => $landlordConnection,
                    'lock_table' => config('cache.stores.database.lock_table', 'cache_locks'),
                ]);
            });
        }
    }

    /**
     * Detecta si estamos en contexto de tenant
     */
    public static function isTenantContext(): bool
    {
        try {
            $containerKey = config('multitenencia.clave_de_contenedor_del_inquilino_actual', 'currentTenant');

            return app()->bound($containerKey) && app($containerKey) !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obtiene el ID del tenant actual
     */
    public static function getCurrentTenantId(): ?string
    {
        try {
            $containerKey = config('multitenencia.clave_de_contenedor_del_inquilino_actual', 'currentTenant');
            $tenant = app($containerKey);

            return $tenant ? $tenant->getKey() : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Limpia el cache de forma segura en entorno multitenancy
     */
    public static function safeClearCache(?object $command = null): bool
    {
        try {
            $multitenancyConfig = config('multitenencia.cache', []);
            $safeClearEnabled = $multitenancyConfig['limpiar_cache_seguro'] ?? true;

            if (! $safeClearEnabled) {
                if ($command && method_exists($command, 'outputComponents')) {
                    $command->outputComponents()->task('caché de la aplicación (insegura)', function () {
                        Cache::clear();

                        return true;
                    });
                } else {
                    Cache::clear();
                }

                return true;
            }

            $isTenant = static::isTenantContext();

            if ($isTenant) {
                $cacheStore = $multitenancyConfig['store_del_inquilino'] ?? config('cache.default');
                $connection = $multitenancyConfig['conexion_del_inquilino'] ?? 'inquilino';
                $contextName = 'inquilino';
            } else {
                $cacheStore = $multitenancyConfig['store_del_propietario'] ?? config('cache.default');
                $connection = $multitenancyConfig['conexion_del_propietario'] ?? 'propietario';
                $contextName = 'propietario';
            }

            $clearGeneralCache = function () use ($cacheStore, $connection) {
                if ($cacheStore === 'database') {
                    static::safeClearDatabaseCache($connection);
                } elseif ($cacheStore !== config('cache.default')) {
                    Cache::store($cacheStore)->clear();
                } else {
                    Cache::clear();
                }

                return true;
            };

            if ($command && method_exists($command, 'outputComponents')) {
                $command->outputComponents()->task("caché de multitenencia ($contextName)", $clearGeneralCache);
            } else {
                $clearGeneralCache();
            }

            // También limpiar la caché de permisos de laravel-autorizacion de forma segura si está configurada
            if (config()->has('autorizacion.cache')) {
                $clearPermissionsCache = function () {
                    try {
                        $permCacheStore = config('autorizacion.cache.store') != 'default'
                            ? config('autorizacion.cache.store')
                            : null;

                        Cache::store($permCacheStore)->forget(config('autorizacion.cache.key', 'roles.permisos.cache'));
                    } catch (\Exception $e) {
                        Log::info('Autorizacion cache clear handled gracefully: '.$e->getMessage());
                    }

                    return true;
                };

                if ($command && method_exists($command, 'outputComponents')) {
                    $command->outputComponents()->task('caché de permisos', $clearPermissionsCache);
                } else {
                    $clearPermissionsCache();
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::info('Cache clear handled gracefully: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Limpia cache de base de datos de forma segura
     */
    public static function safeClearDatabaseCache(string $connection): bool
    {
        try {
            $tableName = config('cache.stores.database.table', 'cache');
            $lockTableName = config('cache.stores.database.lock_table', 'cache_locks');

            if (static::tableExists($connection, $tableName)) {
                DB::connection($connection)->table($tableName)->delete();
            }

            if (static::tableExists($connection, $lockTableName)) {
                DB::connection($connection)->table($lockTableName)->delete();
            }

            return true;
        } catch (\Exception $e) {
            Log::info('Database cache clear handled gracefully: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Verifica si una tabla existe en la conexión especificada
     */
    public static function tableExists(string $connection, string $table): bool
    {
        try {
            return Schema::connection($connection)->hasTable($table);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obtiene el store de cache apropiado para el contexto actual
     */
    public static function getContextAwareCacheStore(): string
    {
        $isTenant = static::isTenantContext();
        $multitenancyConfig = config('multitenencia.cache', []);

        if ($isTenant) {
            return $multitenancyConfig['store_del_inquilino'] ?? config('cache.default');
        } else {
            return $multitenancyConfig['store_del_propietario'] ?? config('cache.default');
        }
    }
}
