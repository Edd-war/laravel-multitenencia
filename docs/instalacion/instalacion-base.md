---
title: Base installation
weight: 1
---

This package can be installed via composer:

```bash
composer require spatie/laravel-multitenencia
```

### Publishing the config file

You must publish the config file:

```bash
php artisan vendor:publish --provider="Eddwar\Multitenencia\MultitenenciaServiceProvider" --tag="multitenencia-config"
```

This is the default content of the config file that will be published at `config/multitenencia.php`:

```php
<?php

use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Queue\CallQueuedClosure;
use Eddwar\Multitenencia\Actions\AccionOlvidarInquilinoActual;
use Eddwar\Multitenencia\Actions\AccionHacerColaInquilinoReconocido;
use Eddwar\Multitenenciaa\Actions\AccionHacerInquilinoActual;
use Eddwar\Multitenencia\Actions\AccionMigrarInquilino;
use Eddwar\Multitenencia\Models\Tenant;

return [
    /*
     * This class is responsible for determining which tenant should be current
     * for the given request.
     *
     * This class should extend `Eddwar\Multitenencia\BuscadorDeInquilinos\BuscadorDeInquilinos`
     *
     */
    'tenant_finder' => null,

    /*
     * These fields are used by tenant:artisan command to match one or more tenant.
     */
    'tenant_artisan_search_fields' => [
        'id',
    ],

    /*
     * These tasks will be performed when switching tenants.
     *
     * A valid task is any class that implements Eddwar\Multitenencia\Tasks\TareaDeCambioDeInquilino
     */
    'switch_tenant_tasks' => [
        // \Eddwar\Multitenencia\Tasks\TareaDeCacheDePrefijos::class,
        // \Eddwar\Multitenencia\Tasks\TareaDelCambioDeBaseDeDatosDelInquilino::class,
        // \Eddwar\Multitenencia\Tasks\TareaDeCacheDeCambioDeRuta::class,
    ],

    /*
     * This class is the model used for storing configuration on tenants.
     *
     * It must  extend `Eddwar\Multitenencia\Models\Tenant::class` or
     * implement `Eddwar\Multitenencia\Contracts\EsInquilino::class` interface
     */
    'tenant_model' => Tenant::class,

    /*
     * If there is a current tenant when dispatching a job, the id of the current tenant
     * will be automatically set on the job. When the job is executed, the set
     * tenant on the job will be made current.
     */
    'colas_reconocen_inquilinos_por_defecto' => true,

    /*
     * The connection name to reach the tenant database.
     *
     * Set to `null` to use the default connection.
     */
    'tenant_database_connection_name' => null,

    /*
     * The connection name to reach the propietario database.
     */
    'propietario_database_connection_name' => null,

    /*
     * This key will be used to associate the current tenant in the context
     */
    'current_tenant_context_key' => 'tenantId',

    /*
     * This key will be used to bind the current tenant in the container.
     */
    'current_tenant_container_key' => 'currentTenant',

    /**
     * Set it to `true` if you like to cache the tenant(s) routes
     * in a shared file using the `TareaDeCacheDeCambioDeRuta`.
     */
    'shared_routes_cache' => false,

    /*
     * You can customize some of the behavior of this package by using your own custom action.
     * Your custom action should always extend the default one.
     */
    'actions' => [
        'make_tenant_current_action' => AccionHacerInquilinoActual::class,
        'forget_current_tenant_action' => AccionOlvidarInquilinoActual::class,
        'make_queue_tenant_aware_action' => AccionHacerColaInquilinoReconocido::class,
        'migrate_tenant' => AccionMigrarInquilino::class,
    ],

    /*
     * Jobs tenant aware even if these don't implement the InquilinoReconocido interface.
     */
    'tenant_aware_jobs' => [
        // ...
    ],

    /*
     * Jobs not tenant aware even if these don't implement the InquilinoNoReconocido interface.
     */
    'not_tenant_aware_jobs' => [
        // ...
    ],
];
```

### Protecting against cross tenant abuse

To prevent users from a tenant abusing their session to access another tenant, you must use the `Eddwar\Multitenencia\Http\Middleware\AsegurarSesionValidaDeInquilino` middleware on all tenant-aware routes.

If all your application routes are tenant-aware, you can add it to your global middleware in `bootstrap/app.php`

```php
// in `bootstrap/app.php`

return Application::configure(basePath: dirname(__DIR__))
    // ...
    ->withMiddleware(function (Middleware $middleware) {
        $middleware
            ->web(append: [
                \Eddwar\Multitenencia\Http\Middleware\NecesitaInquilino::class,
                \Eddwar\Multitenencia\Http\Middleware\AsegurarSesionValidaDeInquilino::class,
            ]);
    });
```

If only some routes are tenant-aware, create a new middleware group:

```php
// in `bootstrap/app.php`

return Application::configure(basePath: dirname(__DIR__))
    // ...
    ->withMiddleware(function (Middleware $middleware) {
        $middleware
            ->group('tenant', [
                \Eddwar\Multitenencia\Http\Middleware\NecesitaInquilino::class,
                \Eddwar\Multitenencia\Http\Middleware\AsegurarSesionValidaDeInquilino::class,
            ]);
    });
```

Then apply the group to the appropriate routes:

```php
// in a routes file

Route::middleware('tenant')->group(function() {
    // routes
});
```

This middleware will respond with an unauthorized response code (401) when the user tries to use their session to view another tenant. Make sure to include `\Eddwar\Multitenencia\Http\Middleware\NecesitaInquilino` first, as this will [handle any cases where a valid tenant cannot be found](/docs/laravel-multitenencia/v4/advanced-usage/ensuring-a-current-tenant-has-been-set).

### Next steps

If you prefer to use just one glorious database for all your tenants, read the installation instructions for [using a single database](/docs/laravel-multitenencia/v4/installation/using-a-single-database).

If you want to use separate databases for each tenant, head over to the installation instructions for [using multiple databases](/docs/laravel-multitenencia/v4/installation/using-multiple-databases).
