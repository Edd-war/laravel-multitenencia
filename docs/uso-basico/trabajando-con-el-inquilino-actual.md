---
title: Trabajando con el inquilino actual
weight: 3
---

Existen varios métodos disponibles para obtener, establecer y limpiar el inquilino actual. Todos los métodos están disponibles utilizando el modelo `Inquilino` directamente, o a través del Contenedor de Servicios de Laravel `app(EsInquilino::class)`.

### Obtener el inquilino actual

Puede obtener el inquilino actual de la siguiente manera:

```php
// Modelo
Inquilino::actual(); // devuelve el inquilino actual, o `null` si no hay ninguno

// Contenedor de Servicios
app(EsInquilino::class)::actual(); // devuelve el inquilino actual, o `null` si no hay ninguno
```

Un inquilino actual también se vinculará en el contenedor utilizando la clave `currentTenant` (o el valor de la clave `clave_de_contenedor_del_inquilino_actual` configurada).

```php
app('currentTenant'); // devuelve el inquilino actual, o `null` si no hay ninguno
```

### Comprobar si hay un inquilino actual establecido

Puede comprobar si hay un inquilino establecido como el actual:

```php
// Modelo
Inquilino::comprobarActual(); // devuelve `true` o `false`

// Contenedor de Servicios
app(EsInquilino::class)::comprobarActual(); // devuelve `true` o `false`
```

### Establecer manualmente el inquilino actual

Puede hacer manualmente que un inquilino sea el actual llamando al método `hacerActual()` en él.

```php
$tenant->hacerActual();
```

Cuando un inquilino se convierte en el actual, el paquete ejecutará el método `hacerActual` de [todas las tareas configuradas](/docs/laravel-multitenencia/v4/usando-tareas-para-preparar-el-entorno/descripcion-general/) en la clave `tareas_de_cambio_de_inquilino` del archivo de configuración `multitenencia.php`.

### Olvidar el inquilino actual

Puede olvidar el inquilino actual:

```php
// Modelo
Inquilino::olvidarActual();
Inquilino::actual(); // devuelve null;

// Contenedor de Servicios
app(EsInquilino::class)::olvidarActual();
app(EsInquilino::class)::actual(); // devuelve null
```

Si no había un inquilino actual al llamar a `olvidarActual()`, la función no hará nada.
