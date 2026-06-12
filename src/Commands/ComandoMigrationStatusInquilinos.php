<?php

namespace Eddwar\Multitenencia\Commands;

use Eddwar\Multitenencia\Commands\Concerns\ManejaMigracionesSecuenciales;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

class ComandoMigrationStatusInquilinos extends Command
{
    use ManejaMigracionesSecuenciales;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:migrate:status
                           {--database= : Definir conexión de base de datos a utilizar (opcional)}
                           {--module= : Visualizar status de un módulo en particular exclusivamente (e.g., 01-defaults)}
                           {--show-modules : Desplegar de forma visual la lista de módulos de migración disponibles y finalizar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Despliega un reporte del estatus operativo de migraciones del Tenant segregado por módulos';

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

        $this->info("📊 Status Operativo de Migraciones [{$this->getMigrationType()}]");
        $this->newLine();

        $totalModules = 0;
        $totalMigrations = 0;
        $totalPending = 0;
        $totalRan = 0;

        foreach ($modules as $module) {
            $moduleName = $module['name'];
            $path = $module['path'];
            $migrationCount = $module['migration_count'];

            $totalModules++;

            $this->info("📦 Evaluación de Módulo: {$moduleName}");
            $this->info("📊 Volumen Archivos Migración: {$migrationCount}");
            $this->info("📁 Path Relativo: {$path}");

            // Construir comando de status
            $command = "migrate:status --path={$path} --database={$this->getDatabaseConnection()}";

            // Capturar output del comando a través de artisan
            $buffer = new BufferedOutput;

            $exitCode = Artisan::call('tenants:artisan', [
                'artisanCommand' => $command,
            ], $buffer);

            $output = $buffer->fetch();

            if ($exitCode === 0) {
                // Mostrar el output del comando
                $this->line($output);

                // Contar migraciones (esto es aproximado basado en el output)
                $pendingCount = substr_count($output, 'Pending');
                $ranCount = substr_count($output, '] Ran');

                $totalMigrations += ($pendingCount + $ranCount);
                $totalPending += $pendingCount;
                $totalRan += $ranCount;

                if ($pendingCount > 0) {
                    $this->warn("⚠️  Registro incompleto: {$pendingCount} migraciones penden de ejecución en este módulo.");
                } else {
                    $this->info('✅ El módulo se encuentra provisionado al 100% de su capacidad.');
                }
            } else {
                $this->error("❌ Anomalía identificada al contactar la DB y solicitar status del módulo [{$moduleName}].");
            }

            $this->line(str_repeat('-', 60));
            $this->newLine();
        }

        // Resumen general
        $this->info('📋 Resumen Analítico General:');
        $this->info("📦 Módulos Auditados: {$totalModules}");
        $this->info("📄 Migraciones Consolidadas y Cuantificadas: {$totalMigrations}");
        $this->info("✅ Aplicadas (Status 'Ran'): {$totalRan}");

        if ($totalPending > 0) {
            $this->warn("⚠️  Restantes en Cola (Pendientes): {$totalPending}");
            $this->newLine();
            $this->info("💡 Sugerencia técnica: Lance el comando 'php artisan tenant:migrate' para sincronizar estructuras desfasadas.");
        } else {
            $this->info('🎉 Infraestructura Óptima: Transmisión y registro de migraciones 100% al día.');
        }

        return 0;
    }
}
