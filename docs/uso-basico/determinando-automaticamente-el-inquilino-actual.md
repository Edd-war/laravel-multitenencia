---
title: Determinando automáticamente el inquilino actual
weight: 1
---

Al comienzo de cada solicitud, el paquete intentará determinar qué inquilino debe estar activo para la solicitud actual. El paquete incluye una clase llamada `BuscadorDeInquilinosDeDominio` que intentará encontrar un `Inquilino` cuyo atributo `dominio` coincida con el nombre de host (hostname) de la solicitud actual.

En el archivo de configuración `multitenencia.php`, debe especificar el buscador de inquilinos en la clave `buscador_de_inquilinos`.

```php
// en multitenencia.php
/*
 * Esta clase es responsable de determinar cuál inquilino debe ser el actual
 * para la solicitud dada.
 *
 * Esta clase debe extender de `Eddwar\Multitenencia\BuscadorDeInquilinos\BuscadorDeInquilinos`
 */
'buscador_de_inquilinos' => Eddwar\Multitenencia\BuscadorDeInquilinos\BuscadorDeInquilinosDeDominio::class,
```

Si el buscador de inquilinos devuelve un inquilino, se ejecutarán [todas las tareas configuradas](https://docs.spatie.be/laravel-multitenencia/v4/usando-tareas-para-preparar-el-entorno/descripcion-general/) en él. Después de eso, la instancia del inquilino se vinculará en el contenedor utilizando la clave `currentTenant` (o el valor de la clave `clave_de_contenedor_del_inquilino_actual` configurada).

```php
app('currentTenant') // devolverá el inquilino actual o `null`
```

Puede crear su propio buscador de inquilinos. Un buscador de inquilinos válido es cualquier clase que extienda de `Eddwar\Multitenencia\BuscadorDeInquilinos\BuscadorDeInquilinos`. Debe implementar este método abstracto:

```php
abstract public function findForRequest(Request $request): ?EsInquilino;
```

Así es como está implementado el `BuscadorDeInquilinosDeDominio` por defecto. El método `getTenantModel` devuelve una instancia de la clase especificada en la clave `modelo_del_inquilino` del archivo de configuración `multitenencia.php`.

```php
namespace Eddwar\Multitenencia\BuscadorDeInquilinos;

use Illuminate\Http\Request;
use Eddwar\Multitenencia\Contracts\EsInquilino;

class BuscadorDeInquilinosDeDominio extends BuscadorDeInquilinos
{
    public function findForRequest(Request $request): ?EsInquilino
    {
        $host = $request->getHost();

        return app(EsInquilino::class)::whereDomain($host)->first();
    }
}
```
