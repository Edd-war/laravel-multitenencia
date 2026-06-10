---
name: laravel-multitenencia-development
description: Build and work with Eddwar Laravel Multitenencia features, including tenant finders, the current tenant, switch tasks, multi-database setups, tenant-aware queues and artisan commands.
---

# Laravel Multitenencia Development

## When to use this skill

Use this skill when working with multi-tenant Laravel applications using `spatie/laravel-multitenencia`: determining the current tenant per request, isolating databases or caches per tenant, making queued jobs and artisan commands tenant-aware, or designing propietario/tenant migration strategies.

## Core Concepts

- **Intentionally minimal**: the package resolves a current tenant and runs tasks on switch — it does not add global query scopes or model isolation by itself.
- **Current tenant** is bound in the IoC container under the key `currentTenant` and written to Laravel `Context` under the key `tenantId`.
- A **`BuscadorDeInquilinos`** resolves the tenant from the current HTTP request (e.g. by domain).
- **`TareaDeCambioDeInquilino`** classes mutate the environment when a tenant becomes current (switch DB, prefix cache, etc.) and restore it when forgotten.
- Models on the propietario DB use `UtilizaConexionDelPropietario`; models on the tenant DB use `UtilizaConexionDelInquilino`.

## Setup

```bash
composer require spatie/laravel-multitenencia
php artisan vendor:publish --provider="Eddwar\Multitenencia\MultitenenciaServiceProvider" --tag="multitenencia-config"
php artisan vendor:publish --provider="Eddwar\Multitenencia\MultitenenciaServiceProvider" --tag="multitenencia-migrations"
```

Register middleware in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \Eddwar\Multitenencia\Http\Middleware\NecesitaInquilino::class,
        \Eddwar\Multitenencia\Http\Middleware\AsegurarSesionValidaDeInquilino::class,
    ]);
})
```

## Configuring a Tenant Finder

Set the finder class in `config/multitenencia.php`:

```php
'tenant_finder' => \Eddwar\Multitenencia\BuscadorDeInquilinos\BuscadorDeInquilinosDeDominio::class,
```

`BuscadorDeInquilinosDeDominio` looks up the tenant by matching `$request->getHost()` against a `domain` column on the tenants table.

To use a custom finder, extend `BuscadorDeInquilinos` and implement `findForRequest`:

```php
use Illuminate\Http\Request;
use Eddwar\Multitenencia\Contracts\EsInquilino;
use Eddwar\Multitenencia\BuscadorDeInquilinos\BuscadorDeInquilinos;

class SubBuscadorDeInquilinosDeDominio extends BuscadorDeInquilinos
{
    public function findForRequest(Request $request): ?EsInquilino
    {
        $subdomain = explode('.', $request->getHost())[0];

        return app(EsInquilino::class)::whereSubdomain($subdomain)->first();
    }
}
```

## Working with the Current Tenant

```php
use Eddwar\Multitenencia\Models\Tenant;

// Make a tenant current (fires events, runs tasks)
$tenant->hacerActual();

// Read the current tenant
Tenant::current();        // returns ?Tenant
app('currentTenant');     // same, via container

// Check and forget
Tenant::comprobarActual();   // bool
$tenant->esActual();     // bool
Tenant::olvidarActual();  // runs forget tasks, returns the tenant
```

## Executing Code for a Tenant or Propietario

`execute()` makes the tenant current, runs the callable, then restores the previous state:

```php
$result = $tenant->execute(function (Tenant $tenant) {
    return cache()->get('stats');
});
```

`callback()` returns a closure — useful for the scheduler:

```php
$schedule->call($tenant->callback(fn () => cache()->flush()))->daily();
```

To run code **outside** any tenant context, use `Propietario`:

```php
use Eddwar\Multitenencia\Propietario;

Propietario::execute(function () {
    Artisan::call('cache:clear');
});
```

`InquilinoCollection` adds iteration helpers: `eachCurrent`, `mapCurrent`, `filterCurrent`, `rejectCurrent`.

```php
Tenant::all()->eachCurrent(function (Tenant $tenant) {
    cache()->flush();
});
```

## Multi-Database Setup

Define a `tenant` connection (with `database => null`) and a `propietario` connection in `config/database.php`:

```php
'connections' => [
    'tenant' => [
        'driver'   => 'mysql',
        'database' => null,
        'host'     => '127.0.0.1',
        'username' => 'root',
        'password' => '',
    ],

    'propietario' => [
        'driver'   => 'mysql',
        'database' => 'name_of_propietario_db',
        'host'     => '127.0.0.1',
        'username' => 'root',
        'password' => '',
    ],
],
```

Set the connection names in `config/multitenencia.php`:

```php
'tenant_database_connection_name'   => 'tenant',
'propietario_database_connection_name' => 'propietario',
```

Apply the correct connection trait to every Eloquent model:

```php
// Models whose table lives in the tenant DB
use Eddwar\Multitenencia\Models\Concerns\UtilizaConexionDelInquilino;

class Post extends Model
{
    use UtilizaConexionDelInquilino;
}

// Models whose table lives in the propietario DB
use Eddwar\Multitenencia\Models\Concerns\UtilizaConexionDelPropietario;

class Tenant extends Model
{
    use UtilizaConexionDelPropietario;
}
```

## Switch Tenant Tasks

Tasks run every time `hacerActual()` or `olvidarActual()` is called. Register them in `config/multitenencia.php`:

```php
'switch_tenant_tasks' => [
    \Eddwar\Multitenencia\Tasks\TareaDelCambioDeBaseDeDatosDelInquilino::class,
    // \Eddwar\Multitenencia\Tasks\TareaDeCacheDePrefijos::class,
    // \Eddwar\Multitenencia\Tasks\TareaDeCacheDeCambioDeRuta::class,
],
```

Built-in tasks:

- **`TareaDelCambioDeBaseDeDatosDelInquilino`** — sets the `tenant` connection's `database` to `$tenant->database` and purges the connection. Required for multi-DB.
- **`TareaDeCacheDePrefijos`** — overrides `cache.prefix` to `tenant_{$tenant->id}`. Works with memory-based stores (Redis, APC).
- **`TareaDeCacheDeCambioDeRuta`** — switches `APP_ROUTES_CACHE` to a per-tenant file (`bootstrap/cache/routes-v7-tenant-{id}.php`), or a shared file when `'shared_routes_cache' => true`.

To create a custom task, implement `TareaDeCambioDeInquilino`:

```php
use Eddwar\Multitenencia\Contracts\EsInquilino;
use Eddwar\Multitenencia\Tasks\TareaDeCambioDeInquilino;

class SwitchStorageDiskTask implements TareaDeCambioDeInquilino
{
    public function hacerActual(EsInquilino $tenant): void
    {
        config(['filesystems.disks.s3.bucket' => $tenant->bucket]);
    }

    public function olvidarActual(): void
    {
        config(['filesystems.disks.s3.bucket' => config('filesystems.default_bucket')]);
    }
}
```

Tasks can receive constructor parameters via array config:

```php
'switch_tenant_tasks' => [
    \App\Tasks\YourTask::class => ['key' => 'value'],
],
```

## Middleware

- **`NecesitaInquilino`** — aborts the request (throws `NoHayInquilinoActual`) if no tenant is current. Apply to all tenant routes.
- **`AsegurarSesionValidaDeInquilino`** — stores the first-seen tenant ID in the session and aborts with 401 if a different tenant ID is detected later. Prevents session cross-contamination.

## Custom Tenant Model

Set `tenant_model` in `config/multitenencia.php` and point it to your own class:

```php
'tenant_model' => \App\Models\Tenant::class,
```

To use an existing model (e.g. a Jetstream `Team`) as a tenant, implement `EsInquilino` with the `ImplementaInquilino` trait:

```php
use Eddwar\Multitenencia\Contracts\EsInquilino;
use Eddwar\Multitenencia\Models\Concerns\ImplementaInquilino;
use Eddwar\Multitenencia\Models\Concerns\UtilizaConexionDelPropietario;

class Team extends JetstreamTeam implements EsInquilino
{
    use UtilizaConexionDelPropietario;
    use ImplementaInquilino;
}
```

Use a `creating` hook to provision a database when a tenant is created:

```php
protected static function booted(): void
{
    static::creating(fn (Tenant $tenant) => $tenant->createDatabase());
}
```

## Migrations & Seeding

**Propietario** migrations live in `database/migrations/propietario`. Run them once:

```bash
php artisan migrate --path=database/migrations/propietario --database=propietario
```

**Tenant** migrations run for every tenant via `tenants:artisan`:

```bash
php artisan tenants:artisan "migrate --database=tenant"
php artisan tenants:artisan "migrate --database=tenant --seed" --tenant=123
```

In seeders, branch on `Tenant::comprobarActual()`:

```php
public function run(): void
{
    Tenant::comprobarActual()
        ? $this->runTenantSpecificSeeders()
        : $this->runPropietarioSpecificSeeders();
}
```

Programmatic migrations use `AccionMigrarInquilino`:

```php
use Eddwar\Multitenencia\Actions\AccionMigrarInquilino;

app(AccionMigrarInquilino::class)->fresh()->seed()->execute($tenant);
```

## Artisan Commands

`tenants:artisan` loops over all tenants (or the specified ones) and runs a command for each:

```bash
php artisan tenants:artisan "migrate --database=tenant"
php artisan tenants:artisan "cache:clear" --tenant=1 --tenant=2
```

To make your own commands tenant-aware, add the `InquilinoReconocido` concern and a `{--tenant=*}` option:

```php
use Illuminate\Console\Command;
use Eddwar\Multitenencia\Commands\Concerns\InquilinoReconocido;

class SendReports extends Command
{
    use InquilinoReconocido;

    protected $signature = 'reports:send {--tenant=*}';

    public function handle(): void
    {
        $this->line('Sending for tenant: ' . Tenant::current()->name);
    }
}
```

Omitting `--tenant` runs the command for every tenant. The command instance is reused across tenants — reset any state at the top of `handle()`.

## Tenant-Aware Queues

Enable globally in `config/multitenencia.php`:

```php
'colas_reconocen_inquilinos_por_defecto' => true,
```

Or mark individual jobs with the `InquilinoReconocido` interface:

```php
use Illuminate\Contracts\Queue\ShouldQueue;
use Eddwar\Multitenencia\Jobs\InquilinoReconocido;

class ProcessReport implements ShouldQueue, InquilinoReconocido
{
    public function handle(): void { /* ... */ }
}
```

Opt out per job with `InquilinoNoReconocido`:

```php
use Eddwar\Multitenencia\Jobs\InquilinoNoReconocido;

class SyncGlobalData implements ShouldQueue, InquilinoNoReconocido
{
    public function handle(): void { /* ... */ }
}
```

Or list classes in config:

```php
'tenant_aware_jobs'     => [\App\Jobs\ProcessReport::class],
'not_tenant_aware_jobs' => [\App\Jobs\SyncGlobalData::class],
```

For closures dispatched to the queue, pass the tenant explicitly:

```php
$tenant = Tenant::current();

dispatch(function () use ($tenant) {
    $tenant->execute(function () {
        // tenant context is active here
    });
});
```

If a tenant-aware job fires but the tenant cannot be resolved, `ExcepcionInquilinoActualNoReconocidoEnTrabajoEnCola` is thrown and the job is deleted from the queue.

## Events

All events live in the `Eddwar\Multitenencia\Events` namespace and carry `public EsInquilino $tenant` except where noted:

| Event                                        | When                                                        |
| -------------------------------------------- | ----------------------------------------------------------- |
| `EventoHaciendoInquilinoActual`              | Before switch tasks run                                     |
| `EventoInquilinoActualCreado`                | After switch tasks + container binding                      |
| `OlvidandoEventoInquilinoActual`             | Before forget tasks run                                     |
| `EventoInquilinoActualOlvidado`              | After forget tasks + container cleared                      |
| `EventoInquilinoNoEncontradoParaLaSolicitud` | When the finder returns `null` (carries `Request $request`) |

## Performance

- Switch tasks run synchronously on every `hacerActual()` / `olvidarActual()` call — keep them fast.
- `shared_routes_cache` avoids generating one routes file per tenant when routes are identical across tenants.
- Octane is supported out of the box: the service provider hooks into `RequestReceived` / `RequestTerminated` events automatically when `LARAVEL_OCTANE` is set.
- The current tenant is stored in Laravel `Context` (`tenantId`), which queue workers read to restore tenant state before processing a job.
