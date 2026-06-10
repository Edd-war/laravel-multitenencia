<?php

namespace Eddwar\Multitenencia\Commands;

use Eddwar\Multitenencia\Commands\Concerns\ManejaMigracionesSecuenciales;
use Eddwar\Multitenencia\Support\AsistenteDeRutaDeMigracion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ComandoMigracionInquilinos extends Command
{
    use ManejaMigracionesSecuenciales {
        resolveTargetModules as traitResolveTargetModules;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:migrate
                           {--database= : Definir conexión de base de datos a utilizar (opcional)}
                           {--force : Forzar la operación a ejecutarse en entornos de producción}
                           {--pretend : Imprimir consultas SQL que serían ejecutadas (Dry Run)}
                           {--seed : Ejecutar tareas de seeding asociadas al completar}
                           {--step : Forzar que las migraciones se ejecuten en tramos habilitando un rollback individual posterior}
                           {--module= : Ejecutar migraciones exclusivamente para un módulo en específico (e.g., 01-defaults)}
                           {--tenant= : Ejecutar peticiones exclusivamente bajo el identificador único de un Tenant}
                           {--show-modules : Desplegar de forma visual la lista de módulos de migración disponibles y finalizar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecuta procesos de migración en Tenants integrables aplicando esquemas path definidos en el framework.';

    protected function getMigrationType(): string
    {
        return 'tenant';
    }

    /**
     * Resolve target modules based on tenant subscriptions
     */
    protected function resolveTargetModules(bool $skipFilter = false): ?array
    {
        $modules = $this->traitResolveTargetModules($skipFilter);

        if ($modules === null) {
            return null;
        }

        $tenantModelClass = config('multitenencia.modelo_del_inquilino');
        if (! $tenantModelClass) {
            return $modules;
        }

        $tenant = $tenantModelClass::actual();
        if (! $tenant) {
            return $modules;
        }

        // 1. Si el inquilino define getTenantMigrationPaths()
        if (method_exists($tenant, 'getTenantMigrationPaths')) {
            $activePaths = $tenant->getTenantMigrationPaths();
            $filtered = [];
            foreach ($modules as $module) {
                // Siempre permitir el módulo base
                if (
                    strtolower($module['module_name']) === 'base' ||
                    strtolower($module['module_name']) === '01-base' ||
                    in_array($module['path'], $activePaths) ||
                    in_array($module['full_path'], $activePaths)
                ) {
                    $filtered[] = $module;
                }
            }

            return $filtered;
        }

        // 2. Si el inquilino define la relación modulos()
        if (method_exists($tenant, 'modulos')) {
            $activeModules = $tenant->modulos()
                ->wherePivot('estatus', 'activo')
                ->pluck('nombre')
                ->map(fn ($n) => strtolower($n))
                ->toArray();

            $filtered = [];
            foreach ($modules as $module) {
                $name = strtolower($module['module_name']);
                if ($name === 'base' || $name === '01-base' || in_array($name, $activeModules)) {
                    $filtered[] = $module;
                }
            }

            return $filtered;
        }

        return $modules;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $modules = $this->resolveTargetModules();

        if ($modules === null) {
            if ($this->option('show-modules') && ! empty(AsistenteDeRutaDeMigracion::getOrderedModulesInfo($this->getMigrationType()))) {
                return 0;
            }

            return 1;
        }

        $this->info("🚀 Sistema de Migraciones Integrado - Inicializando Entorno: {$this->getMigrationType()}...");
        $this->info('📋 Se procesarán '.count($modules).' módulos de migración secuencialmente.');
        $this->newLine();

        $totalSuccess = 0;
        $totalErrors = 0;

        foreach ($modules as $index => $module) {
            $moduleNumber = $index + 1;
            $moduleName = $module['name'];
            $path = $module['path'];

            $this->info("📦 [{$moduleNumber}/".count($modules)."] Invocando módulo: {$moduleName}");
            $this->info("📁 Directorio objetivo: {$path}");

            // Construir flag de options base
            $command = "migrate --path={$path} --database={$this->getDatabaseConnection()}";

            // Agregar opciones si se especificaron
            if ($this->option('force')) {
                $command .= ' --force';
            }

            if ($this->option('pretend')) {
                $command .= ' --pretend';
            }

            if ($this->option('step')) {
                $command .= ' --step';
            }

            $this->line("🔄 Despachando instrucción bajo tenants:artisan -> \"{$command}\"");

            // Ejecutar el comando a través de tenants:artisan (soporte de Eddwar/laravel-multitenencia)
            $commandOptions = [
                'artisanCommand' => $command,
            ];

            if ($this->option('tenant')) {
                $commandOptions['--tenant'] = $this->option('tenant');
            }

            $exitCode = Artisan::call('tenants:artisan', $commandOptions, $this->output);

            if ($exitCode === 0) {
                $this->info("✅ Módulo [{$moduleName}] concluido satisfactoriamente.");
                $totalSuccess++;
            } else {
                $this->error("❌ Se documentó una anomalía procesando el módulo [{$moduleName}].");
                $totalErrors++;

                // Si hay error y no es pretend, preguntar si continuar (evitar confirmación si no hay interacción)
                if (! $this->option('pretend')) {
                    if (! $this->option('no-interaction') && ! $this->confirm('¿Autoriza a detener precautoriamente la ejecución de los siguientes módulos?', true)) {
                        break;
                    }
                }
            }

            $this->newLine();
        }

        // Resumen final
        $this->info('📊 Diagnóstico y Resumen de Ejecución:');
        $this->info("✅ Módulos integrados con éxito: {$totalSuccess}");
        if ($totalErrors > 0) {
            $this->error("❌ Módulos reportando anomalías: {$totalErrors}");
        }

        // Agregar seeding si se solicitó (solo al final y si todo fue exitoso)
        if ($this->option('seed') && $totalErrors === 0 && ! $this->option('pretend')) {
            $this->info('🌱 Iniciando secuencia de Sembrado (Seeders)...');

            $seedCommand = "db:seed --database={$this->getDatabaseConnection()}";
            $seedOptions = [
                'artisanCommand' => $seedCommand,
            ];

            if ($this->option('tenant')) {
                $seedOptions['--tenant'] = $this->option('tenant');
            }

            Artisan::call('tenants:artisan', $seedOptions, $this->output);
        }

        return $totalErrors > 0 ? 1 : 0;
    }
}
