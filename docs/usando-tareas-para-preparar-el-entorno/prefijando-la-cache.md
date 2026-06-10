---
title: Prefijando la caché
weight: 5
---

Es posible que desee utilizar cachés independientes para cada inquilino. La clase `Eddwar\Multitenencia\Tasks\TareaDeCacheDePrefijos` le permite hacer exactamente eso. Esta tarea solo funciona para cachés basados en memoria, como APC y Redis.

Para utilizar esta tarea, debe agregarla a la clave `tareas_de_cambio_de_inquilino` en el archivo de configuración `multitenencia.php`.

```php
// en config/multitenencia.php

'tareas_de_cambio_de_inquilino' => [
    \Eddwar\Multitenencia\Tasks\TareaDeCacheDePrefijos::class,
    // otras tareas
],
```

Cuando esta tarea está instalada, la caché se comportará de la siguiente manera:

```php
cache()->put('key', 'original-value');

$tenant->hacerActual();
cache('key') // devuelve null;
cache()->put('key', 'value-for-tenant');

$anotherTenant->hacerActual();
cache('key') // devuelve null;
cache()->put('key', 'value-for-another-tenant');

Inquilino::olvidarActual();
cache('key') // devuelve 'original-value';

$tenant->hacerActual();
cache('key') // devuelve 'value-for-tenant'

$anotherTenant->hacerActual();
cache('key') // devuelve 'value-for-another-tenant'
```

Detrás de escena, esto funciona cambiando dinámicamente el valor de `cache.prefix` en el archivo de configuración de `cache` cada vez que otro inquilino se establece como actual.

Si desea hacer que la caché sea consciente del inquilino de otra manera, debe [crear su propia tarea](/docs/laravel-multitenencia/v4/usando-tareas-para-preparar-el-entorno/creando-tu-propia-tarea/). Puede echar un vistazo al código fuente de `TareaDeCacheDePrefijos` para inspirarse.
