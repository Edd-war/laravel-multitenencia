---
title: Escuchando eventos
weight: 7
---

El paquete dispara eventos que puede escuchar para realizar alguna lógica adicional.

## `\Eddwar\Multitenencia\Events\EventoHaciendoInquilinoActual`

Este evento se disparará cuando un inquilino se esté convirtiendo en el actual. En este punto, ninguna de [las tareas](/docs/laravel-multitenencia/v4/using-tasks-to-prepare-the-environment/overview/) ha sido ejecutada.

Tiene una propiedad pública `$tenant`, que contiene una instancia de `Eddwar\Multitenencia\Models\Inquilino`.

## `\Eddwar\Multitenencia\Events\EventoInquilinoActualCreado`

Este evento se disparará cuando un inquilino se haya convertido en el actual. En este punto, el método `hacerActual` de todas [las tareas](/docs/laravel-multitenencia/v4/using-tasks-to-prepare-the-environment/overview/) ha sido ejecutado. El inquilino actual también ha sido vinculado como `currentTenant` (o el valor de `clave_de_contenedor_del_inquilino_actual` configurado) en el contenedor.

Tiene una propiedad pública `$tenant`, que contiene una instancia de `Eddwar\Multitenencia\Models\Inquilino`.

## `\Eddwar\Multitenencia\Events\OlvidandoEventoInquilinoActual`

Este evento se disparará cuando se esté olvidando un inquilino actual. En este punto, ninguna de [las tareas](/docs/laravel-multitenencia/v4/using-tasks-to-prepare-the-environment/overview/) ha sido ejecutada.

Tiene una propiedad pública `$tenant`, que contiene una instancia de `Eddwar\Multitenencia\Models\Inquilino`.

## `\Eddwar\Multitenencia\Events\EventoInquilinoActualOlvidado`

Este evento se disparará cuando se haya olvidado un inquilino. En este punto, el método `olvidarActual` de todas [las tareas](/docs/laravel-multitenencia/v4/using-tasks-to-prepare-the-environment/overview/) ha sido ejecutado. La clave del inquilino en el contenedor se ha vaciado.

Tiene una propiedad pública `$tenant`, que contiene una instancia de `Eddwar\Multitenencia\Models\Inquilino`.

## `\Eddwar\Multitenencia\Events\EventoInquilinoNoEncontradoParaLaSolicitud`

Este evento se disparará cuando el método `findForRequest()` del `BuscadorDeInquilinos` no encuentre ningún inquilino para la solicitud dada.

Tiene una propiedad pública `$request`, que contiene una instancia de `Illuminate\Http\Request`.
