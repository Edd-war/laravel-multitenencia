<?php

namespace Eddwar\Multitenencia\Exceptions;

use Exception;

final class ExcepcionNoHayInquilinoActual extends Exception
{
    public static function make()
    {
        return new self('The request expected a current tenant but none was set.');
    }
}
