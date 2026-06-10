---
title: Determining the current tenant
weight: 4
---

Per request, the package can determine the "current" tenant. This is done by a `BuscadorDeInquilinos`. The package ships with a `BuscadorDeInquilinosDeDominio` that will make the tenant active whose `domain` attribute value matches the host of the current request.

To use that tenant finder, specify its class name in the `tenant_finder` key of the `multitenencia` config file.

```php
// in multitenencia.php
/*
 * This class is responsible for determining which tenant should be current
 * for the given request.
 *
 * This class should extend `Eddwar\Multitenencia\BuscadorDeInquilinos\BuscadorDeInquilinos`
 *
 */
'tenant_finder' => Eddwar\Multitenencia\BuscadorDeInquilinos\BuscadorDeInquilinosDeDominio::class,
```

If you want to determine the "current" tenant some other way, you can [create a custom tenant finder](/docs/laravel-Multitenencia/v4/basic-usage/automatically-determining-the-current-tenant/).
