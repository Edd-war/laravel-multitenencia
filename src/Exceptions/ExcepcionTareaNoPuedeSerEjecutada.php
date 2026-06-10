<?php

namespace Eddwar\Multitenencia\Exceptions;

use Eddwar\Multitenencia\Tasks\TareaDeCambioDeInquilino;
use Exception;

final class ExcepcionTareaNoPuedeSerEjecutada extends Exception
{
    public static function make(TareaDeCambioDeInquilino $task, string $reason): static
    {
        $taskClass = $task::class;

        return new self("Task `{$taskClass}` could not be executed. Reason: {$reason}");
    }
}
