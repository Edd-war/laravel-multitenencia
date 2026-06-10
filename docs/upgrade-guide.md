---
title: Upgrade guide
weight: 2
---

In the `4.x` version, we have introduced the contract concept to the Tenant so that any model could implement the interface.

The first step to reach our goal is upgrading our package version.

```bash
composer require Eddwar/laravel-multitenencia:^4.0
```

### Removed `UsesTenantModel` trait

Remove any reference to our old trait `Eddwar\Models\Concerns\UsesTenantModel`, because now the right `Tenant` instance can be resolved using `app(EsInquilino::class)`.

### Tenant finder

If you are using the default finder included in the package, no changes are required by your side. However, when you are using a custom finder, you need to change the returned value in `findForRequest` method to `?EsInquilino`. Example:

```php
use Illuminate\Http\Request;
use Eddwar\Multitenencia\Contracts\EsInquilino;

class YourCustomTenantFinder extends BuscadorDeInquilinos
{
    public function findForRequest(Request $request): ?EsInquilino
    {
        // ...
    }
}
```

### Custom tasks

As has already been pointed out for the finder, the same change is required for any task because our `TareaDeCambioDeInquilino` interface now is:

```php
public function hacerActual(EsInquilino $tenant): void;
```

So, it requires replacing the method parameter from `Tenant $tenant` to `EsInquilino $tenant.`
