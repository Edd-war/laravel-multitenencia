<?php

namespace Eddwar\Multitenencia\Exceptions;

use Exception;

final class ExcepcionConfiguracionNoValida extends Exception
{
    public static function conexionDelInquilinoNoExiste(string $expectedConnectionName): static
    {
        return new self("Could not find a tenant connection named `{$expectedConnectionName}`. Make sure to create a connection with that name in the `connections` key of the `database` config file.");
    }

    public static function conexionDelInquilinoEsVacíaOEsIgualALaConexionDelPropietario(): static
    {
        return new static('`TareaDelCambioDeBaseDeDatosDelInquilino` fails because `multitenencia.nombre_de_conexion_de_la_base_de_datos_del_inquilino` is `null` or equals to `multitenencia.propietario_database_connection_name`.');
    }

    public static function accionNoValida(string $actionName, string $configuredClass, string $actionClass): static
    {
        return new static("The class currently specified in the `multitenencia.actions.{$actionName}` key '{$configuredClass}' should be or extend `{$actionClass}`.");
    }
}
