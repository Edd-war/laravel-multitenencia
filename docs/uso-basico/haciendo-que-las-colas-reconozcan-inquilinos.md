---
title: Haciendo que las colas reconozcan inquilinos
weight: 6
---

El paquete puede hacer que las colas reconozcan inquilinos. Para habilitar este comportamiento, establezca la clave `colas_reconocen_inquilinos_por_defecto` en el archivo de configuración `multitenencia.php` en `true`.

Cuando este comportamiento está habilitado, el paquete rastreará qué inquilino es el actual al despachar un trabajo. Ese inquilino se convertirá automáticamente en el inquilino actual dentro de la ejecución de dicho trabajo.

## Hacer que trabajos específicos reconozcan inquilinos

Si no desea que todos los trabajos reconozcan inquilinos, debe establecer la clave de configuración `colas_reconocen_inquilinos_por_defecto` en `false`. Los trabajos que requieran reconocer al inquilino deben implementar la interfaz de marcador vacía `Eddwar\Multitenencia\Jobs\InquilinoReconocido` o deben agregarse a la clave `trabajos_que_reconocen_inquilinos` en la configuración.

```php
use Illuminate\Contracts\Queue\ShouldQueue;
use Eddwar\Multitenencia\Jobs\InquilinoReconocido;

class TestJob implements ShouldQueue, InquilinoReconocido
{
    public function handle()
    {
        // realizar el trabajo
    }
}
```

O bien, utilizando el archivo de configuración `multitenencia.php`:

```php
'trabajos_que_reconocen_inquilinos' => [
    TestJob::class,
],
```

## Hacer que trabajos específicos no reconozcan inquilinos

Los trabajos que nunca deban reconocer al inquilino deben implementar la interfaz de marcador vacía `Eddwar\Multitenencia\Jobs\InquilinoNoReconocido` o deben agregarse a la clave `trabajos_que_no_reconocen_inquilinos` en la configuración.

```php
use Illuminate\Contracts\Queue\ShouldQueue;
use Eddwar\Multitenencia\Jobs\InquilinoNoReconocido;

class TestJob implements ShouldQueue, InquilinoNoReconocido
{
    public function handle()
    {
        // realizar el trabajo
    }
}
```

O bien, utilizando el archivo de configuración `multitenencia.php`:

```php
'trabajos_que_no_reconocen_inquilinos' => [
    TestJob::class,
],
```

## Encolado de Clausuras (Closures)

Despachar una clausura es ligeramente diferente a una clase de trabajo porque en este caso no se pueden implementar las interfaces `InquilinoReconocido` o `InquilinoNoReconocido`. El paquete puede manejar las clausuras en cola habilitando la opción `colas_reconocen_inquilinos_por_defecto`, pero si prefiere mantener este parámetro en `false`, puede despachar una clausura consciente del inquilino de la siguiente manera:

```php
$tenant = Inquilino::actual();

dispatch(function () use ($tenant) {
    $tenant->execute(function () {
        // Su trabajo en cola
    });
});
```

## Cuando el inquilino no se puede recuperar

Si un trabajo en cola que reconoce al inquilino no puede recuperar el inquilino (por ejemplo, porque el inquilino fue eliminado antes de que se procesara el trabajo), el trabajo fallará lanzando una instancia de `Eddwar\Multitenencia\Exceptions\ExcepcionInquilinoActualNoReconocidoEnTrabajoEnCola`.

Por otro lado, un trabajo que no reconoce al inquilino no realizará ninguna modificación al inquilino actual, que podría seguir configurado a partir de un trabajo anterior. Como tal, es importante que sus trabajos no asuman nada sobre el inquilino activo a menos que reconozcan explícitamente al inquilino.
