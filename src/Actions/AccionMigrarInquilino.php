<?php

namespace Eddwar\Multitenencia\Actions;

use Eddwar\Multitenencia\Contracts\EsInquilino;
use Eddwar\Multitenencia\Support\AsistenteDeRutaDeMigracion;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Output\OutputInterface;

class AccionMigrarInquilino
{
    protected bool $fresh = false;

    protected bool $seed = false;

    protected OutputInterface $output;

    public function fresh(bool $fresh = true): static
    {
        $this->fresh = $fresh;

        return $this;
    }

    public function seed(bool $seed = true): static
    {
        $this->seed = $seed;

        return $this;
    }

    public function output(OutputInterface $output): static
    {
        $this->output = $output;

        return $this;
    }

    public function execute(EsInquilino $tenant): static
    {
        // Try to ensure the database exists
        try {
            if (method_exists($tenant, 'createDatabase')) {
                $tenant->createDatabase();
            } else {
                $dbName = $tenant->obtenerNombreDeBaseDeDatos();
                $driver = config('database.connections.'.config('multitenencia.nombre_de_conexion_de_la_base_de_datos_del_inquilino', 'tenant').'.driver', 'mysql');
                if ($driver === 'sqlite') {
                    $path = database_path($dbName.'.sqlite');
                    if (! file_exists($path)) {
                        @touch($path);
                    }
                } else {
                    $safeName = str_replace('`', '``', $dbName);
                    $admin = config('multitenencia.nombre_de_conexion_de_la_base_de_datos_del_propietario', 'propietario');
                    DB::connection($admin)->statement("CREATE DATABASE IF NOT EXISTS `{$safeName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                }
            }
        } catch (\Throwable $e) {
            // Ignore and proceed
        }

        $tenant->execute(function () use ($tenant) {
            $modules = AsistenteDeRutaDeMigracion::getOrderedModulesInfo('tenant');
            $migrationPaths = [];

            if (! empty($modules)) {
                if (method_exists($tenant, 'getTenantMigrationPaths')) {
                    $activePaths = $tenant->getTenantMigrationPaths();
                    foreach ($modules as $module) {
                        if (
                            strtolower($module['module_name']) === 'base' ||
                            strtolower($module['module_name']) === '01-base' ||
                            in_array($module['path'], $activePaths) ||
                            in_array($module['full_path'], $activePaths)
                        ) {
                            $migrationPaths[] = $module['path'];
                        }
                    }
                } elseif (method_exists($tenant, 'modulos')) {
                    $activeModules = $tenant->modulos()
                        ->wherePivot('estatus', 'activo')
                        ->pluck('nombre')
                        ->map(fn ($n) => strtolower($n))
                        ->toArray();

                    foreach ($modules as $module) {
                        $name = strtolower($module['module_name']);
                        if ($name === 'base' || $name === '01-base' || in_array($name, $activeModules)) {
                            $migrationPaths[] = $module['path'];
                        }
                    }
                } else {
                    foreach ($modules as $module) {
                        $migrationPaths[] = $module['path'];
                    }
                }
            }

            $output = isset($this->output) ? $this->output : null;

            if (empty($migrationPaths)) {
                $migrationCommand = $this->fresh ? 'migrate:fresh' : 'migrate';
                Artisan::call($migrationCommand, $this->getOptions(), $output);
            } else {
                $isFirst = true;
                foreach ($migrationPaths as $path) {
                    $options = ['--force' => true, '--path' => $path];

                    if ($this->fresh && $isFirst) {
                        $migrationCommand = 'migrate:fresh';
                        $isFirst = false;
                    } else {
                        $migrationCommand = 'migrate';
                    }

                    Artisan::call($migrationCommand, $options, $output);
                }

                if ($this->seed) {
                    Artisan::call('db:seed', ['--force' => true], $output);
                }
            }
        });

        return $this;
    }

    protected function getOptions(): array
    {
        $options = ['--force' => true];

        if ($this->seed) {
            $options['--seed'] = true;
        }

        return $options;
    }
}
