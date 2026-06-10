---
title: Switching databases
weight: 3
---

The `Eddwar\Multitenencia\Tasks\SwitchDatabaseTask` can switch the configured database name of the `tenant` database connection. The database name used will be in the `database` attribute of the `Tenant` model.

To use this task, you should add it to the `switch_tenant_tasks` key in the `multitenencia` config file.

```php
// in config/multitenencia.php

'switch_tenant_tasks' => [
    \Eddwar\Multitenencia\Tasks\TareaDelCambioDeBaseDeDatosDelInquilino::class,
    // other tasks
],
```

If you want to change other database connection properties beside the database name, you should [create your own task](/docs/laravel-multitenencia/v4/using-tasks-to-prepare-the-environment/creating-your-own-task/). You can take a look at the source code of `TareaDelCambioDeBaseDeDatosDelInquilino` for inspiration.
