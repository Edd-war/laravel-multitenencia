<?php

namespace Eddwar\Multitenencia;

use Eddwar\Multitenencia\Contracts\EsInquilino;
use Illuminate\Database\Eloquent\Collection;

/**
 * @template TKey of array-key
 * @template TValue of EsInquilino&\Illuminate\Database\Eloquent\Model
 *
 * @extends Collection<TKey, TValue>
 */
final class InquilinoCollection extends Collection
{
    public function eachCurrent(callable $callable): static
    {
        return $this->ejecutarMetodoDeColeccionMientrasSeConviertenEnInquilinosActuales(
            operation: 'each',
            callable: $callable
        );
    }

    public function filterCurrent(callable $callable): static
    {
        return $this->ejecutarMetodoDeColeccionMientrasSeConviertenEnInquilinosActuales(
            operation: 'filter',
            callable: $callable
        );
    }

    public function mapCurrent(callable $callable): static
    {
        return $this->ejecutarMetodoDeColeccionMientrasSeConviertenEnInquilinosActuales(
            operation: 'map',
            callable: $callable
        );
    }

    public function rejectCurrent(callable $callable): static
    {
        return $this->ejecutarMetodoDeColeccionMientrasSeConviertenEnInquilinosActuales(
            operation: 'reject',
            callable: $callable
        );
    }

    /**
     * @return static<TKey, TValue>
     */
    protected function ejecutarMetodoDeColeccionMientrasSeConviertenEnInquilinosActuales(string $operation, callable $callable): static
    {
        $collection = $this->$operation(fn (EsInquilino $tenant) => $tenant->execute($callable));

        /** @var static<TKey, TValue> $newCollection */
        $newCollection = new static($collection->items);

        return $newCollection;
    }
}
