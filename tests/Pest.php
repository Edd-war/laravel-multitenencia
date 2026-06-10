<?php

use Eddwar\Multitenencia\Models\Inquilino;
use Eddwar\Multitenencia\Tests\TestCase;
use Illuminate\Support\Facades\Schema;

use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

uses(TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

function tenantHasDatabaseTable(Inquilino $tenant, string $tableName): bool
{
    $tenant->hacerActual();

    $tenantHasDatabaseTable = Schema::connection('tenant')->hasTable($tableName);

    Inquilino::olvidarActual();

    return $tenantHasDatabaseTable;
}

function assertTenantDatabaseHasTable(Inquilino $tenant, string $tableName): void
{
    $tenantHasDatabaseTable = tenantHasDatabaseTable($tenant, $tableName);

    assertTrue(
        $tenantHasDatabaseTable,
        "Tenant database does not have table  `{$tableName}`"
    );
}

function assertTenantDatabaseDoesNotHaveTable(Inquilino $tenant, string $tableName): void
{
    $tenantHasDatabaseTable = tenantHasDatabaseTable($tenant, $tableName);

    assertFalse(
        $tenantHasDatabaseTable,
        "Tenant database has unexpected table  `{$tableName}`"
    );
}

function tempFile(string $fileName): string
{
    return __DIR__."/temp/{$fileName}";
}
