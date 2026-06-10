---
title: Asegurando que se ha establecido un inquilino actual
weight: 1
---

En su proyecto probablemente tendrá muchas rutas donde espera que un inquilino haya sido establecido como el actual.

Puede asegurarse de que se ha establecido un inquilino actual aplicando el middleware `\Eddwar\Multitenencia\Http\Middleware\NecesitaInquilino` en esas rutas.

Recomendamos registrar este middleware en un grupo junto con `\Eddwar\Multitenencia\Http\Middleware\AsegurarSesionValidaDeInquilino`, para verificar también que no se esté abusando de la sesión a través de múltiples inquilinos.

```php
// en `app\Http\Kernel.php`

protected $middlewareGroups = [
    // ...
    'tenant' => [
        \Eddwar\Multitenencia\Http\Middleware\NecesitaInquilino::class,
        \Eddwar\Multitenencia\Http\Middleware\AsegurarSesionValidaDeInquilino::class
    ]
];
```

Con el middleware registrado, puede usarlo en los archivos de rutas (o en un proveedor de servicios de rutas).

```php
// en un archivo de rutas

Route::middleware('tenant')->group(function() {
    // rutas
})
```

Si la solicitud no tiene un inquilino "actual" para estas rutas, se lanzará una excepción `Eddwar\Multitenencia\Exceptions\ExcepcionNoHayInquilinoActual`. Puede escuchar esta excepción en [el manejador de excepciones](https://laravel.com/docs/master/errors#the-exception-handler). Podría configurar algún tipo de mensaje flash y redirigir a una página de inicio de sesión allí.
