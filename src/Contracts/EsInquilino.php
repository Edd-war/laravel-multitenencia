<?php

namespace Eddwar\Multitenencia\Contracts;

use Eddwar\Multitenencia\InquilinoCollection;

interface EsInquilino
{
    public static function actual(): ?static;

    public static function comprobarActual(): bool;

    public static function olvidarActual(): ?static;

    public function hacerActual(): static;

    public function olvidar(): static;

    public function esActual(): bool;

    public function obtenerNombreDeBaseDeDatos(): string;

    public function newCollection(array $models = []): InquilinoCollection;

    public function execute(callable $callable): mixed;

    public function getKey();

    public function callback(callable $callable): \Closure;
}
