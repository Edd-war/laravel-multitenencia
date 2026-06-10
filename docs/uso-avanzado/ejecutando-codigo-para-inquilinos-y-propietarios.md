---
title: Ejecutando código para inquilinos y propietarios
weight: 9
---

Los modelos `Inquilino` y `Propietario` proporcionan un método `execute` que le permite ejecutar código para un inquilino o propietario específico.

## Ejecutar código del inquilino en una solicitud del propietario

Para ejecutar código del inquilino en una solicitud del propietario, puede usar el método `execute` disponible en el modelo `Inquilino`.

Aquí hay un ejemplo donde limpiamos el caché para un inquilino utilizando nuestra API del propietario:

```php
Route::delete('/api/{tenant}/flush-cache', function (Inquilino $tenant) {
    $result = $tenant->execute(fn (Inquilino $tenant) => cache()->flush());

    return json_encode(["success" => $result]);
});
```

Dentro del cierre (closure) pasado a `execute`, el `$tenant` dado se establece como el actual.

Aquí hay otro ejemplo, donde se despacha un trabajo desde una ruta de la API del propietario:

```php
Route::post('/api/{tenant}/reminder', function (Inquilino $tenant) {
    return json_encode([
        'data' => $tenant->execute(fn () => dispatch(ExpirationReminder())),
    ]);
});
```

### Ejecutar una función de retorno retrasada (callback) en el contexto correcto del Inquilino

Si necesita definir una función de retorno (callback) que se ejecutará en el contexto correcto del Inquilino cada vez que se llame, puede usar el método `callback` del Inquilino.
Un ejemplo notable de esto es el uso en el programador de tareas de Laravel (scheduler), donde puede recorrer todos los inquilinos y programar callbacks para ejecutarse a una hora determinada:

```php
protected function schedule(Schedule $schedule)
{
    Inquilino::all()->eachCurrent(function(Inquilino $tenant) use ($schedule) {
        $schedule->run($tenant->callback(fn() => cache()->flush()))->daily();
    });
}
```

## Ejecutar código del propietario en una solicitud del inquilino

Para ejecutar código del propietario desde dentro de una solicitud del inquilino, puede usar el método `execute` en `Eddwar\Multitenencia\Propietario`.

Aquí hay un ejemplo donde primero limpiaremos el caché del inquilino y luego el caché del propietario:

```php
use Eddwar\Multitenencia\Propietario;

// ...

Inquilino::first()->execute(function (Inquilino $tenant) {
    // limpiará el caché del inquilino
    Artisan::call('cache:clear');

    // limpiará el caché del propietario
    Propietario::execute(fn () => Artisan::call('cache:clear'));
});
```

Dentro del cierre pasado a `execute`, el propietario se activa al olvidar el inquilino actual.

## Pruebas con DatabaseTransactions para Inquilino

Al realizar pruebas y utilizar el trait `DatabaseTransactions`, la configuración por defecto de Laravel requiere cambios para asegurar que las transacciones se realicen en la conexión del `Inquilino`. Por lo tanto, el archivo por defecto `TestCase.php` puede actualizarse como se muestra a continuación:

```php
namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Event;
use Eddwar\Multitenencia\Concerns\UtilizaConfiguracionMultitenencia;
use Eddwar\Multitenencia\Events\EventoInquilinoActualCreado;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, DatabaseTransactions, UtilizaConfiguracionMultitenencia;

    protected function connectionsToTransact()
    {
        return [
            $this->nombreConexionBaseDeDatosDelPropietario(),
            $this->nombreDeConexionDeLaBaseDeDatosDelInquilino(),
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        Event::listen(EventoInquilinoActualCreado::class, function () {
            $this->beginDatabaseTransaction();
        });
    }
}
```

En caso de que se realice un inicio de sesión de usuario utilizando la fachada `Auth` en el método `setUp` de una prueba, el cambio de inquilino no ocurrirá automáticamente. En consecuencia, el método `setUp` anterior puede actualizarse como se muestra a continuación para asegurar que el inquilino requerido haya sido establecido (usando el primer `Inquilino` como ejemplo):

```php
protected function setUp(): void
{
    parent::setUp();

    Event::listen(EventoInquilinoActualCreado::class, function () {
        $this->beginDatabaseTransaction();
    });

    Inquilino::first()->hacerActual();
}
```
