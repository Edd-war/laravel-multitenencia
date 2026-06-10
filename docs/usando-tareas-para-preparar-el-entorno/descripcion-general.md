---
title: Descripción general
weight: 1
---

Al hacer que un inquilino sea el actual, se ejecutarán las tareas dentro de la clave `tareas_de_cambio_de_inquilino` en el archivo de configuración `multitenencia.php`. Dentro de estas tareas, puede realizar lógica para configurar el entorno para el inquilino que se está estableciendo como el actual.

La filosofía de este paquete es que solo debe proporcionar lo esencial para habilitar la multitenencia. Es por eso que solo proporciona dos tareas listas para usar. Estas tareas sirven como implementaciones de ejemplo.

Puede fácilmente [crear sus propias tareas](/docs/laravel-multitenencia/v4/usando-tareas-para-preparar-el-entorno/creando-tu-propia-tarea/) que se adapten a su proyecto en particular.

El paquete se envía con estas tareas:

- [cambiar la base de datos del inquilino](/docs/laravel-multitenencia/v4/usando-tareas-para-preparar-el-entorno/cambiando-bases-de-datos) (requerido al usar múltiples bases de datos de inquilinos)
- [prefijar la caché](/docs/laravel-multitenencia/v4/usando-tareas-para-preparar-el-entorno/prefijando-la-cache)

Estas tareas son opcionales. Cuando necesite una, simplemente agréguela a la clave `tareas_de_cambio_de_inquilino` del archivo de configuración `multitenencia.php`.
