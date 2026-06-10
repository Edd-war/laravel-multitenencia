---
title: Haciendo que los comandos Artisan reconozcan inquilinos
weight: 3
---

Los comandos se pueden hacer conscientes del inquilino aplicando el trait `InquilinoReconocido`. Al usar el trait, es obligatorio añadir `{--tenant=*}` o `{--tenant=}` a la firma del comando.

Precaución: Si añade `{--tenant=*}`, y no se proporciona la opción `tenant` al ejecutar el comando, el comando se ejecutará para _todos_ los inquilinos.

```php
use Illuminate\Console\Command;
use Eddwar\Multitenencia\Commands\Concerns\InquilinoReconocido;

class YourFavoriteCommand extends Command
{
    use InquilinoReconocido;

    protected $signature = 'your-favorite-command {--tenant=*}';

    public function handle()
    {
        return $this->line('El inquilino es '. Inquilino::actual()->nombre);
    }
}
```

Al ejecutar el comando, el método `handle` se llamará para cada inquilino.

```bash
php artisan your-favorite-command
```

Usando el ejemplo anterior, el nombre de cada inquilino se escribirá en la salida del comando.

También puede ejecutar el comando para un inquilino específico:

```bash
php artisan your-favorite-command --tenant=1
```

## Usando el comando tenants:artisan

Si no puede cambiar un comando de Artisan directamente (por ejemplo, un comando del propio Laravel o un comando de un paquete de terceros), puede usar `tenants:artisan <comando artisan>`. Este comando recorrerá los inquilinos, establecerá cada uno como el actual y ejecutará el comando de Artisan para ese contexto.

Cuando cada uno de sus inquilinos tiene su propia base de datos, podría migrar cada base de datos de inquilino con este comando (asumiendo que esté usando una tarea como [`TareaDelCambioDeBaseDeDatosDelInquilino`](https://docs.spatie.be/laravel-multitenencia/v4/using-tasks-to-prepare-the-environment/switching-databases)):

```bash
php artisan tenants:artisan migrate
```

Estamos usando el comando `migrate` aquí como ejemplo, pero puede pasar cualquier comando que desee.

### Pasar argumentos y opciones

Si usa comillas alrededor de la parte del comando, puede usar cualquier argumento y opción que admita dicho comando.

```bash
php artisan tenants:artisan "migrate --seed"
```

### Ejecutar comandos Artisan para inquilinos específicos

Si el comando solo necesita ejecutarse para un inquilino específico, puede pasar su `id` a la opción `tenant`.

```bash
php artisan tenants:artisan "migrate --seed" --tenant=123
```

### Persistencia del estado

Al usar `InquilinoReconocido`, la misma instancia del comando se ejecuta para cada inquilino.
Esto significa que las propiedades de la instancia conservarán sus valores entre las ejecuciones de los inquilinos a menos que se restablezcan explícitamente.

```php
use Illuminate\Console\Command;
use Eddwar\Multitenencia\Commands\Concerns\InquilinoReconocido;

class YourFavoriteCommand extends Command
{
    use InquilinoReconocido;

    protected $signature = 'your-favorite-command {--tenant=*}';

    protected int $counter = 0;

    public function handle()
    {
        // Restablecer el estado al comienzo de la ejecución de cada inquilino
        $this->counter = 0;

        // Sin el restablecimiento anterior, $counter comenzaría con el valor
        // de la ejecución del inquilino anterior
        $this->incrementCounter();

        return $this->line('Contador: '. $this->counter);
    }

    public function incrementCounter()
    {
        $this->counter++;
    }
}
```
