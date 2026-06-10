---
title: Utilizando fachadas específicas de inquilinos
weight: 7
---

Las fachadas (Facades) se comportan como singletons. Solo se resuelven una vez, y cada uso de la fachada es contra la misma instancia. Para un entorno multitenencia, esto puede ser problemático si la instancia subyacente detrás de un servicio se construye utilizando una configuración específica del inquilino.

Si solo tiene un par de fachadas específicas para inquilinos, recomendamos borrarlas al cambiar de inquilino. Aquí hay una tarea que podría usar para esto:

```php
namespace App\Tenancy\SwitchTasks;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;
use Eddwar\Multitenencia\Contracts\EsInquilino;
use Eddwar\Multitenencia\Tasks\TareaDeCambioDeInquilino;

class ClearFacadeInstancesTask implements TareaDeCambioDeInquilino
{
    public function hacerActual(EsInquilino $tenant): void
    {
        // el inquilino ya es el actual
    }

    public function olvidarActual(): void
    {
        $facadeClasses = [
            // array que contiene los nombres de clase de las fachadas que desea limpiar
        ];

        collect($facadeClasses)
            ->each(
                fn (string $facade) => $facade::clearResolvedInstance($facade::getFacadeAccessor)
            );
    }
}
```

Si desea limpiar todas las fachadas declaradas, puede utilizar este código (proporcionado por [Adrian Brown](https://github.com/spatie/laravel-multitenencia/discussions/240#discussion-3354768)) que recorrerá todas las clases definidas.

```php
namespace App\Tenancy\SwitchTasks;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;
use Eddwar\Multitenencia\Contracts\EsInquilino;
use Eddwar\Multitenencia\Tasks\TareaDeCambioDeInquilino;

class ClearFacadeInstancesTask implements TareaDeCambioDeInquilino
{
    public function hacerActual(EsInquilino $tenant): void
    {
        // el inquilino ya es el actual
    }

    public function olvidarActual(): void
    {
        $this->clearFacadeInstancesInTheAppNamespace();
    }

    protected function clearFacadeInstancesInTheAppNamespace(): void
    {
        // Descubre todas las fachadas en el namespace App y borra su instancia resuelta:
        collect(get_declared_classes())
            ->filter(fn ($className) => is_subclass_of($className, Facade::class))
            ->filter(fn ($className) => Str::startsWith($className, 'App') || Str::startsWith($className, 'Facades\\App'))
            ->each(fn ($className) => $className::clearResolvedInstance(
                $className::getFacadeAccessor()
            ));
    }
}
```
