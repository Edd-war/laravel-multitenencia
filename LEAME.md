# Un paquete de multitenencia sin opiniones para Laravel

Este paquete puede hacer que una aplicación de Laravel sea consciente del inquilino. La filosofía de este paquete es que solo debe proporcionar lo esencial para habilitar la multitenencia.

El paquete puede determinar qué inquilino debe ser el inquilino actual para la solicitud. También le permite definir qué debería suceder al cambiar el inquilino actual a otro. Funciona para proyectos de multitenencia que necesitan utilizar una o múltiples bases de datos.

El paquete contiene una gran cantidad de utilidades, como hacer que los trabajos en cola reconozcan inquilinos, hacer que un comando de Artisan se ejecute para cada inquilino, una forma fácil de establecer una conexión en un modelo y mucho más.

## Documentación

Puede encontrar la documentación completa en la carpeta [docs](docs).

## Pruebas

Tendrá que crear las siguientes 3 bases de datos MySQL locales para poder ejecutar la suite de pruebas:

- `laravel_mt_propietario`
- `laravel_mt_tenant_1`
- `laravel_mt_tenant_2`

Puede ejecutar las pruebas del paquete con:

```bash
composer test
```

## Historial de Cambios

Consulte el [Historial de Cambios](HISTORIAL_DE_CAMBIOS.md) para obtener más información sobre lo que ha cambiado recientemente.

## Licencia

La Licencia MIT (MIT). Consulte el [Archivo de Licencia](LICENCIA.md) para obtener más información.
