---
title: Utilizando un modelo de inquilino personalizado
weight: 6
---

Si desea cambiar o añadir comportamiento al modelo `Inquilino`, puede utilizar su propio modelo personalizado. Hay dos formas de hacerlo: extendiendo el modelo `Inquilino` provisto por el paquete, o preparando un modelo propio desde cero.

## Opción 1: extender el modelo `Inquilino` provisto por el paquete

Asegúrese de que su modelo personalizado extienda el modelo `Eddwar\Multitenencia\Models\Inquilino` provisto por el paquete.

Debe especificar el nombre de clase de su modelo en la clave `modelo_del_inquilino` del archivo de configuración `multitenencia.php`.

```php
/*
 * Esta clase es el modelo utilizado para almacenar la configuración de los inquilinos.
 *
 * Debe extender de `Eddwar\Multitenencia\Models\Inquilino::class` o
 * implementar la interfaz `Eddwar\Multitenencia\Contracts\EsInquilino::class`
 */
'modelo_del_inquilino' => \App\Models\CustomTenantModel::class,
```

## Opción 2: usar un modelo propio

No es estrictamente obligatorio extender nuestro modelo `Inquilino`. Por ejemplo, si utiliza Laravel Jetstream, probablemente querrá utilizar el modelo `Team` proporcionado por ese paquete como su modelo de inquilino.

Para lograr eso, puede implementar la interfaz `EsInquilino` y usar el trait `ImplementaInquilino` para cumplir con los requerimientos de la interfaz.

Aquí tiene un ejemplo:

```php
namespace App\Models;

use Laravel\Jetstream\Team as JetstreamTeam;
use Eddwar\Multitenencia\Contracts\EsInquilino;
use Eddwar\Multitenencia\Models\Concerns\ImplementaInquilino;

class Team extends JetstreamTeam implements EsInquilino
{
    use HasFactory;
    use UtilizaConexionDelPropietario;
    use ImplementaInquilino;
}
```

## Realizar acciones cuando se crea un inquilino

Puede aprovechar los eventos del ciclo de vida de Eloquent (lifecycle callbacks) para ejecutar lógica adicional cuando se crea, actualiza, elimina, etc., un inquilino.

Aquí tiene un ejemplo de cómo podría llamar a cierta lógica para crear una base de datos cuando se crea un inquilino:

```php
namespace App\Models\Tenant;

use Eddwar\Multitenencia\Models\Inquilino;

class CustomTenantModel extends Inquilino
{
    protected static function booted()
    {
        static::creating(fn(CustomTenantModel $model) => $model->createDatabase());
    }

    public function createDatabase()
    {
        // añadir lógica para crear la base de datos
    }
}
```
