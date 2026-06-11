<?php

namespace Eddwar\Multitenencia\Support;

use Illuminate\Support\Facades\File;

class AsistenteDeRutaDeMigracion
{
    /**
     * Obtiene el path base para migraciones según el tipo de entorno
     *
     * @param  string  $type  Tipo de migración ('propietario' o 'tenant')
     * @return string Path relativo
     */
    public static function getBasePath(string $type): string
    {
        $type = $type === 'tenant' ? 'inquilino' : $type;
        $configKey = $type === 'inquilino'
            ? 'ruta_de_migraciones_del_inquilino'
            : 'ruta_de_migraciones_del_propietario';

        return config("multitenencia.{$configKey}", "database/migrations/{$type}");
    }

    /**
     * Obtener todos los módulos de migración ordenados mediante patrones de subcarpetas en el sistema.
     *
     * @param  string  $type  Tipo de migración ('propietario' o 'tenant')
     * @return array Array de información de módulos ordenados
     */
    public static function getOrderedModulesInfo(string $type): array
    {
        $basePath = self::getBasePath($type);
        $fullBasePath = base_path($basePath);

        if (! is_dir($fullBasePath)) {
            return [];
        }

        $directories = File::directories($fullBasePath);
        $numberedDirs = [];
        $unnumberedDirs = [];
        $unnumberedOrderStart = 100;

        foreach ($directories as $dir) {
            $dirName = basename($dir);

            // Permite patrones que inicien con números seguidos de un guión (ej. '01-base')
            if (preg_match('/^(\d+)-(.+)/', $dirName, $matches)) {
                $number = (int) $matches[1];
                $moduleName = $matches[2];

                $numberedDirs[$number] = [
                    'order' => $number,
                    'name' => $dirName,
                    'module_name' => $moduleName,
                    'path' => $basePath.'/'.$dirName,
                    'full_path' => $dir,
                    'exists' => true,
                    'migration_count' => count(File::glob($dir.'/*.php')),
                ];
            } else {
                // Para carpetas sin número (como 'agenda' o 'comercio')
                $unnumberedDirs[] = [
                    'name' => $dirName,
                    'module_name' => $dirName,
                    'path' => $basePath.'/'.$dirName,
                    'full_path' => $dir,
                    'exists' => true,
                    'migration_count' => count(File::glob($dir.'/*.php')),
                ];
            }
        }

        // Ordenar numéricamente por la clave del order (prefijo numérico en el folder)
        ksort($numberedDirs);

        // Asignar orden consecutivo a los no numerados y agregarlos
        foreach ($unnumberedDirs as $index => $dirInfo) {
            $order = $unnumberedOrderStart + $index;
            $dirInfo['order'] = $order;
            $numberedDirs[$order] = $dirInfo;
        }

        return array_values($numberedDirs);
    }

    /**
     * Obtener solo los paths relativos de migración ordenados.
     *
     * @param  string  $type  Tipo de migración ('propietario' o 'tenant')
     * @return array Array de paths relativos
     */
    public static function getOrderedMigrationPaths(string $type): array
    {
        $modules = self::getOrderedModulesInfo($type);

        return array_column($modules, 'path');
    }

    /**
     * Busca módulos que coincidan con un filtro.
     *
     * @param  string  $type  Tipo de migración ('propietario' o 'tenant')
     * @param  string  $filter  Filtro de búsqueda
     * @return array Array de información de módulos filtrados
     */
    public static function findModulesByFilter(string $type, string $filter): array
    {
        $modules = self::getOrderedModulesInfo($type);
        $filtered = [];

        foreach ($modules as $module) {
            if (
                str_contains(strtolower($module['name']), strtolower($filter)) ||
                str_contains(strtolower($module['module_name']), strtolower($filter))
            ) {
                $filtered[] = $module;
            }
        }

        return $filtered;
    }
}
