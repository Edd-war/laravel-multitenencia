<?php

namespace Eddwar\Multitenencia\Commands;

use Eddwar\Multitenencia\Commands\Concerns\ManejaMigracionesSecuenciales;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ComandoRollbackInquilinos extends Command
{
    use ManejaMigracionesSecuenciales;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:rollback
                           {--database= : Definir conexión de base de datos a utilizar (opcional)}
                           {--force : Forzar la operación a ejecutarse en entornos de producción}
                           {--pretend : Imprimir consultas SQL que serían ejecutadas (Dry Run)}
                           {--step=1 : Cantidad de migraciones a revertir por defecto}
                           {--module= : Revertir migraciones exclusivamente para un módulo en específico (e.g., 01-defaults)}
                           {--all : Ejecutar rollback a nivel global de todos los módulos del tenant en orden inverso}
                           {--tenant= : Ejecutar rollback exclusivamente bajo el identificador único de un Tenant}
                           {--show-modules : Desplegar de forma visual la lista de módulos de migración disponibles y finalizar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revierte las migraciones orientadas a los Tenants con un robusto control granular por módulo';

    protected function getMigrationType(): string
    {
        return 'inquilino';
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Enforce resolve target modules handles --show-modules and module missing checks
        $modules = $this->resolveTargetModules();

        if ($modules === null) {
            return 1;
        }

        // Si se especificó --all, hacer rollback de todos los módulos automáticamente
        if ($this->option('all')) {
            $this->info('🔄 Ejecutando rollback completo de todos los módulos de tenant(s).');
            $this->info('📋 Se procesarán '.count($modules).' módulos en orden inverso estructurado.');
            $this->newLine();

            // Rollback en orden inverso (del último al primero)
            $reverseModules = array_reverse($modules);

            foreach ($reverseModules as $index => $module) {
                $this->info('📦 ['.($index + 1).'/'.count($reverseModules)."] Procesando rollback completo del módulo: {$module['name']}");
                $this->rollbackModuleCompletely($module['path'], $module);
            }

            $this->newLine();
            $this->info('📊 Resumen de ejecución:');
            $this->info('✅ Rollback global de '.count($modules).' módulos de tenant completado con éxito.');

            return 0;
        }

        // Si se especificó un módulo específico, solo trabajar con ese
        // Note: resolveTargetModules ya filtró los módulos si la opción existe
        if ($this->hasOption('module') && $this->option('module')) {
            $this->info('🔄 Ejecutando rollback focalizado para el/los módulo(s) filtrado(s).');

            foreach ($modules as $module) {
                $this->rollbackModule($module['path'], $module);
            }

            return 0;
        }

        // Si no se especificó módulo o --all, mostrar opciones interactivas
        $this->info('🎯 Sistema Integrado de Reversión (Rollback) Granular de Tenant(s)');
        $this->info('📋 Relación de módulos actualmente disponibles:');

        foreach ($modules as $index => $module) {
            $this->info('  '.($index + 1).". {$module['name']} (Asocia {$module['migration_count']} migraciones)");
        }

        $this->newLine();

        if (! $this->option('no-interaction') && $this->confirm('¿Desea ordenar un ROLLBACK COMPLETO para TODOS los módulos en orden inverso?', false)) {
            // Rollback en orden inverso
            $reverseModules = array_reverse($modules);

            foreach ($reverseModules as $module) {
                if ($this->confirm("¿Emprender proceso de rollback en el módulo [{$module['name']}]?", true)) {
                    $this->rollbackModule($module['path'], $module);
                } else {
                    $this->warn("⏭️ Instrucción de omisión recibida. Saltando módulo [{$module['name']}].");
                }
            }
        } else {
            if (! $this->option('no-interaction')) {
                $this->info('💡 Sugerencia técnica: Use la opción --module=nombre-modulo para revertir una sección específica.');
                $this->info('💡 Sugerencia técnica: Use la opción --all para revertir y restaurar entorno global de forma desatendida.');
                $this->line('Ejemplo de uso: php artisan tenant:rollback --module=01-defaults');
                $this->line('Ejemplo de uso: php artisan tenant:rollback --all');
            }
        }

        return 0;
    }

    /**
     * Ejecutar rollback para un módulo específico
     *
     * @param  array{order: int, name: string, module_name: string, path: string, full_path: string, exists: bool, migration_count: int}|null  $module
     */
    protected function rollbackModule(string $path, ?array $module = null): void
    {
        $moduleName = $module ? $module['name'] : basename($path);
        $migrationCount = $module ? $module['migration_count'] : 0;

        if (! is_dir(base_path($path))) {
            $this->error("❌ Incosistencia en path: El directorio del módulo [{$path}] no existe o es inaccesible.");

            return;
        }

        $this->info("🔄 Preparando infraestructura para rollback del módulo: {$moduleName}");
        $this->info("📊 Capacidad de migraciones detectadas: {$migrationCount}");
        $this->info("📁 Directorio destino: {$path}");

        // Verificar si hay migraciones ejecutadas en este módulo antes de hacer rollback
        [$hasDeployed, $deployedCount] = $this->checkModuleMigrationStatus($path);

        if (! $hasDeployed) {
            $this->warn("⏭️ Prevención de redundancia: Este módulo [{$moduleName}] se encuentra impoluto (no se registraron migraciones previas ejecutadas).");
            $this->newLine();

            return;
        }

        // Hacer rollback iterativo hasta que no haya más migraciones ejecutadas en este módulo
        $rollbackCount = 0;
        $maxRollbacks = 30; // Límite de seguridad para evitar loops infinitos

        while ($hasDeployed && $rollbackCount < $maxRollbacks) {
            $rollbackCount++;

            // Construir comando de rollback de una migración por vez
            $command = "migrate:rollback --path={$path} --database={$this->getDatabaseConnection()} --step=1";

            // Agregar opciones si se especificaron
            if ($this->option('force')) {
                $command .= ' --force';
            }

            if ($this->option('pretend')) {
                $command .= ' --pretend';
            }

            $this->line("🔄 Ejecutando operación de rollback iterativo (Etapa #{$rollbackCount}) dirigida al módulo: {$moduleName}");

            // Ejecutar el comando a través de tenants:artisan
            $commandOptions = [
                'artisanCommand' => $command,
            ];

            if ($this->hasOption('tenant') && $this->option('tenant')) {
                $commandOptions['--tenant'] = $this->option('tenant');
            }

            $exitCode = Artisan::call('tenants:artisan', $commandOptions, $this->output);

            if ($exitCode !== 0) {
                $this->error("❌ Se produjo una excepción al intentar ejecutar el proceso de reversión en [{$moduleName}].");
                break;
            }

            // Actualizar status para posible siguiente iteración
            [$hasDeployed, $deployedCount] = $this->checkModuleMigrationStatus($path);
        }

        $this->info("✅ Ciclo de rollback estructural para el módulo [{$moduleName}] concluido. ({$rollbackCount} operaciones de reversión documentadas).");
        $this->newLine();
    }

    /**
     * Ejecutar rollback completo para un módulo específico (todas sus migraciones a la vez)
     *
     * @param  array{order: int, name: string, module_name: string, path: string, full_path: string, exists: bool, migration_count: int}|null  $module
     */
    protected function rollbackModuleCompletely(string $path, ?array $module = null): void
    {
        $moduleName = $module ? $module['name'] : basename($path);
        $migrationCount = $module ? $module['migration_count'] : 0;

        if (! is_dir(base_path($path))) {
            $this->error("❌ Incosistencia en path: El directorio del módulo [{$path}] no existe o es inaccesible.");

            return;
        }

        if ($migrationCount === 0) {
            $this->warn("⏭️ Evaluando Módulo [{$moduleName}] - Carece de definciones de migraciones en su directorio.");

            return;
        }

        // Verificar si hay migraciones ejecutadas en este módulo antes de hacer rollback
        [$hasDeployed, $deployedCount] = $this->checkModuleMigrationStatus($path);

        if (! $hasDeployed) {
            $this->warn("⏭️ Evaluando Módulo [{$moduleName}] - El historial indica carencia de migraciones aplicadas.");

            return;
        }

        $this->info("🔄 Configuración de despliegue de reversión forzada para módulo: {$moduleName}");
        $this->info("📊 Capacidad de migraciones detectadas: {$migrationCount} - Conteo de activas detectadas: {$deployedCount}");
        $this->info("📁 Directorio destino: {$path}");

        // Construir comando de rollback con step
        $stepValue = $deployedCount > 0 ? $deployedCount : $migrationCount;
        $command = "migrate:rollback --path={$path} --database={$this->getDatabaseConnection()} --step={$stepValue}";

        // Agregar opciones
        if ($this->option('force')) {
            $command .= ' --force';
        }

        if ($this->option('pretend')) {
            $command .= ' --pretend';
        }

        $this->line("🔄 Activando script en lote de Artisan (batch rollback) para módulo: {$moduleName}");

        // Ejecutar el comando a través de tenants:artisan
        $commandOptions = [
            'artisanCommand' => $command,
        ];

        if ($this->hasOption('tenant') && $this->option('tenant')) {
            $commandOptions['--tenant'] = $this->option('tenant');
        }

        $exitCode = Artisan::call('tenants:artisan', $commandOptions, $this->output);

        if ($exitCode === 0) {
            $this->info("✅ Operación batch concluida. Módulo [{$moduleName}] esterilizado con éxito global.");
        } else {
            $this->error("❌ Fallo crítico en ejecución batch de rollback para el módulo [{$moduleName}].");
        }

        $this->newLine();
    }
}
