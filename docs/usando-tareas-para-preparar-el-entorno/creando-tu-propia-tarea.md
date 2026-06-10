---
title: Creating your own task
weight: 2
---

A task is any class that implements `Eddwar\Multitenencia\Tasks\TareaDeCambioDeInquilino`. Here is how that interface looks like.

```php
namespace Eddwar\Multitenencia\Tasks;

use Eddwar\Multitenencia\Contracts\EsInquilino;

interface TareaDeCambioDeInquilino
{
    public function hacerActual(EsInquilino $tenant): void;

    public function olvidarActual(): void;
}
```

The `hacerActual` function will be called when making a tenant current. A common thing to do would be to dynamically change some configuration values.

`olvidarActual` will be called when forgetting a tenant. This function should restore the original environment. An important thing to note is that `TareaDeCambioDeInquilino` are singletons, so you could store the original values as a property and reach for them later.

Here is an example implementation where we are going to use a prefix when a tenant is current, and clear out that prefix when forgetting the tenant.

```php
namespace Eddwar\Multitenencia\Tasks;

use Eddwar\Multitenencia\Contracts\EsInquilino;

class TareaDeCacheDePrefijos implements TareaDeCambioDeInquilino
{
    public function __construct(protected ?string $originalPrefix = null)
    {
        $this->originalPrefix ??= config('cache.prefix');
    }

    public function hacerActual(EsInquilino $tenant): void
    {
        $this->setCachePrefix("tenant_{$tenant->id}");
    }

    public function olvidarActual(): void
    {
        $this->setCachePrefix($this->originalPrefix);
    }

    protected function setCachePrefix(string $prefix): void
    {
        config()->set('cache.prefix', $prefix);

        $storeName = config('cache.default');

        app('cache')->forgetDriver($storeName);
    }
}
```

## Registering a task

After creating a task, you must register it by putting its class name in the `switch_tenant_tasks` key of the `multitenencia` config file.

## Accepting parameters

Classes that implement `TareaDeCambioDeInquilino` can accept parameters from the `multitenencia` config file.

```php
'switch_tenant_tasks' => [
    \App\Support\TareaDeCambioDeInquilinos\YourTask::class => ['name' => 'value', 'anotherName' => 'value'],
    // other tasks
],
```

In your task you can accept these parameters via the constructor. Make sure the parameter names matches those used in the config file.

```php
namespace App\Support\TareaDeCambioDeInquilinos\YourTask

use Eddwar\Multitenencia\Tasks\TareaDeCambioDeInquilino;

class TareaDelCambioDeBaseDeDatosDelInquilino implements TareaDeCambioDeInquilino
{
    public function __construct(string $name, string $anotherName)
    {
        // do something
    }
}
```

You can also use the construct to inject dependencies. Just make sure the variable name does not conflict with one of the parameter names in the config file.

```php
namespace App\Support\TareaDeCambioDeInquilinos\YourTask

use Eddwar\Multitenencia\Tasks\TareaDeCambioDeInquilino;

class TareaDelCambioDeBaseDeDatosDelInquilino implements TareaDeCambioDeInquilino
{
    public function __construct(string $name, string $anotherName, MyDepencency $myDependency)
    {
        // do something
    }
}
```
