<?php

namespace Eddwar\Multitenencia\Commands;

class ComandoMigrateRollbackInquilinos extends ComandoRollbackInquilinos
{
    /** @var string */
    protected $signature = 'tenant:migrate:rollback
                           {--database=inquilino : La conexión de base de datos a usar}
                           {--force : Forzar la operación para que se ejecute en producción}
                           {--pretend : Muestra las consultas SQL que se ejecutarían}
                           {--step=1 : Número de migraciones a revertir}
                           {--module= : Revierte las migraciones solo de un módulo específico (ej., 01-defaults)}
                           {--all : Revierte todos los módulos en orden inverso automáticamente}
                           {--tenant= : Ejecuta el comando para un ID de inquilino (tenant) específico}
                           {--show-modules : Muestra los módulos de migración disponibles y sale}';

    /** @var string */
    protected $description = 'Alias de tenant:rollback para ejecutar rollback de migraciones de tenants con la firma migrate:rollback';
}
