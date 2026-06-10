<?php

namespace Eddwar\Multitenencia\Commands\Concerns;

use Eddwar\Multitenencia\Support\AsistenteDeRutaDeMigracion;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

trait ManejaMigracionesSecuenciales
{
    /**
     * Define el tipo de migración operando el entorno activo ('propietario' o 'tenant').
     */
    abstract protected function getMigrationType(): string;

    /**
     * Obtiene el nombre de la conexión de la base de datos a utilizar,
     * proveyendo soporte a una conexión por defecto desde las configuraciones del paquete.
     */
    protected function getDatabaseConnection(): string
    {
        $type = $this->getMigrationType();
        $optionTarget = $this->hasOption('database') ? $this->option('database') : null;

        if ($optionTarget) {
            return $optionTarget;
        }

        return match ($type) {
            'tenant' => config('multitenencia.nombre_de_conexion_de_la_base_de_datos_del_inquilino', 'tenant'),
            'propietario' => config('multitenencia.propietario_database_connection_name', 'propietario'),
            default => $type,
        };
    }

    /**
     * Identificador y verificador de la integridad de los módulos de migración bajo
     * el tipo dictaminado.
     *
     * @param  bool  $skipFilter  Si es true salta la evaluación de subfiltros
     * @return array|null Null si el flujo de cónsola interceptó peticiones informativas y debe concluir comando.
     */
    protected function resolveTargetModules(bool $skipFilter = false): ?array
    {
        $type = $this->getMigrationType();
        $modules = AsistenteDeRutaDeMigracion::getOrderedModulesInfo($type);

        if (empty($modules)) {
            $basePath = AsistenteDeRutaDeMigracion::getBasePath($type);
            $this->error("No se encontraron módulos de migración con prefijos numéricos o no numerados en: {$basePath}");

            return null;
        }

        if ($this->hasOption('show-modules') && $this->option('show-modules')) {
            $this->displayAvailableModules($modules);

            return null; // Concluye petición
        }

        if (! $skipFilter && $this->hasOption('module') && $moduleFilter = $this->option('module')) {
            $filteredModules = AsistenteDeRutaDeMigracion::findModulesByFilter($type, $moduleFilter);

            if (empty($filteredModules)) {
                $this->error("No fue posible localizar el módulo referenciado por el filtro proporcionado: {$moduleFilter}");
                $this->info('📋 Registro de módulos disponibles en el sistema:');
                foreach ($modules as $module) {
                    $this->info("  - Módulo [{$module['name']}] (Posee {$module['migration_count']} migraciones)");
                }

                return null;
            }

            return $filteredModules;
        }

        return $modules;
    }

    /**
     * Renderización estructural en consola para la identificación detallada de módulos.
     */
    protected function displayAvailableModules(array $modules): void
    {
        $type = $this->getMigrationType();
        $this->info("📋 Lista de módulos de migración para el entorno de ->{$type}<- disponibles:");
        $this->newLine();

        foreach ($modules as $module) {
            $this->info("  📦 {$module['name']}");
            $this->info("     📁 Directorio: {$module['path']}");
            $this->info("     📊 Carga Total Migraciones: {$module['migration_count']}");
            $this->newLine();
        }

        $commandBase = $type === 'tenant' ? 'tenant' : 'propietario';
        $this->warn('🚀 Uso práctico:');
        $this->line("  php artisan {$commandBase}:migrate --module=01-defaults");
        if ($this->getName() && ! str_ends_with($this->getName(), ':status')) {
            $this->line("  php artisan {$commandBase}:rollback --all");
        }
    }

    /**
     * Valida el registro en base de datos para cuantificar el volumen de las migraciones
     * de este módulo en particular que fueron ejecutadas con anterioridad.
     *
     * @return array{0: bool, 1: int} Retorna tupla con formato [poseeRun(bool), contero(int)]
     */
    protected function checkModuleMigrationStatus(string $path): array
    {
        try {
            $buffer = new BufferedOutput;
            $type = $this->getMigrationType();
            $connection = $this->getDatabaseConnection();

            if ($type === 'tenant') {
                $exitCode = Artisan::call('tenants:artisan', [
                    'artisanCommand' => "migrate:status --database={$connection} --path={$path}",
                ], $buffer);
            } else {
                $exitCode = Artisan::call('migrate:status', [
                    '--database' => $connection,
                    '--path' => $path,
                ], $buffer);
            }

            if ($exitCode !== 0) {
                return [false, 0];
            }

            $output = $buffer->fetch();
            $ranCount = 0;
            if ($output) {
                $lines = explode("\n", $output);
                foreach ($lines as $line) {
                    // Buscamos coincidencia literal con estado "Ran" en el status parseado.
                    if (str_contains($line, '] Ran')) {
                        $ranCount++;
                    }
                }
            }

            return [$ranCount > 0, $ranCount];
        } catch (\Exception $e) {
            return [false, 0];
        }
    }
}
