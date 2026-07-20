<?php

namespace Eddwar\Multitenencia\Models\Concerns;

use Eddwar\Multitenencia\Actions\AccionHacerInquilinoActual;
use Eddwar\Multitenencia\Actions\AccionOlvidarInquilinoActual;
use Eddwar\Multitenencia\Contracts\EsInquilino;
use Eddwar\Multitenencia\InquilinoCollection;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 * @mixin EsInquilino
 */
trait ImplementaInquilino
{
    public function hacerActual(): static
    {
        if ($this->esActual()) {
            return $this;
        }

        static::olvidarActual();

        $this
            ->obtenerLaClaseDeAccionDeMultitenencia(
                actionName: 'accion_hacer_inquilino_actual',
                actionClass: AccionHacerInquilinoActual::class
            )
            ->execute($this);

        return $this;
    }

    public function olvidar(): static
    {
        $this
            ->obtenerLaClaseDeAccionDeMultitenencia(
                actionName: 'accion_olvidar_inquilino_actual',
                actionClass: AccionOlvidarInquilinoActual::class
            )
            ->execute($this);

        return $this;
    }

    public static function actual(): ?static
    {
        $containerKey = config('multitenencia.clave_de_contenedor_del_inquilino_actual');

        if (! app()->has($containerKey)) {
            return null;
        }

        return app($containerKey);
    }

    public static function comprobarActual(): bool
    {
        return static::actual() !== null;
    }

    public function esActual(): bool
    {
        $actual = static::actual();

        return $actual?->getKey() === $this->getKey();
    }

    public static function olvidarActual(): ?static
    {
        return tap(static::actual(), fn (?EsInquilino $tenant) => $tenant?->olvidar());
    }

    public function obtenerNombreDeBaseDeDatos(): string
    {
        $dbName = $this->base_de_datos;
        $prefix = (string) config('multitenencia.prefijo_de_base_de_datos_del_inquilino', '');

        if (empty($dbName)) {
            return rtrim($prefix, '_');
        }

        $cleanPrefix = rtrim($prefix, '_');
        if (! str_starts_with($dbName, $prefix) && $dbName !== $cleanPrefix) {
            $dbName = $prefix.$dbName;
        }

        if ($dbName !== ':memory:' && ! str_contains($dbName, '/') && ! str_contains($dbName, '\\')) {
            $tenantConnectionName = config('multitenencia.nombre_de_conexion_de_la_base_de_datos_del_inquilino', 'tenant');
            if (config("database.connections.{$tenantConnectionName}.driver") === 'sqlite') {
                $baseDir = function_exists('database_path') ? database_path() : __DIR__.'/../../../tests/temp';
                if (! file_exists($baseDir)) {
                    @mkdir($baseDir, 0777, true);
                }
                $databasePath = $baseDir.'/'.$dbName.'.sqlite';
                if (! file_exists($databasePath)) {
                    @touch($databasePath);
                }

                return $databasePath;
            }
        }

        return $dbName;
    }

    /**
     * @param  array<int, static>  $models
     * @return InquilinoCollection<int, static>
     */
    public function newCollection(array $models = []): InquilinoCollection
    {
        return new InquilinoCollection($models);
    }

    public function execute(callable $callable): mixed
    {
        $originalCurrentTenant = static::actual();

        $this->hacerActual();

        try {
            return $callable($this);
        } finally {
            $originalCurrentTenant
                ? $originalCurrentTenant->hacerActual()
                : static::olvidarActual();
        }
    }

    public function callback(callable $callable): \Closure
    {
        return fn () => $this->execute($callable);
    }
}
