---
title: Iterando sobre una colección de inquilinos
weight: 2
---

Cada vez que obtenga inquilinos utilizando una consulta Eloquent, se le devolverá una instancia de `Eddwar\Multitenencia\InquilinoCollection`. Esta clase extiende de `Illuminate\Database\Eloquent\Collection`, por lo que puede usar cualquiera de los métodos de colección regulares que conoce y ama.

Además de los métodos regulares, `InquilinoCollection` proporciona cuatro métodos adicionales: `eachCurrent`, `mapCurrent`, `filterCurrent` y `rejectCurrent`. Todos estos métodos funcionan como los métodos regulares `each`, `map`, `filter` y `reject`, pero además harán automáticamente que el inquilino correspondiente sea el actual durante la iteración.

```php
Inquilino::all()->eachCurrent(function(Inquilino $inquilino) {
    // el inquilino pasado se ha establecido como el actual
    Inquilino::actual()->is($inquilino); // devuelve true;
});
```
