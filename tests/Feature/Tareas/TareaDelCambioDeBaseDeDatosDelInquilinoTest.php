<?php

use Eddwar\Multitenencia\Exceptions\ExcepcionConfiguracionNoValida;
use Eddwar\Multitenencia\Models\Inquilino;
use Eddwar\Multitenencia\Tasks\TareaDelCambioDeBaseDeDatosDelInquilino;
use Eddwar\Multitenencia\Tests\TestClasses\Usuario;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    /** @var Inquilino $this */
    $this->inquilino = Inquilino::factory()->create(['base_de_datos' => 'laravel_mt_tenant_1']);

    $this->anotherInquilino = Inquilino::factory()->create(['base_de_datos' => 'laravel_mt_tenant_2']);

    config()->set('multitenencia.tareas_de_cambio_de_inquilino', [TareaDelCambioDeBaseDeDatosDelInquilino::class]);
});

test('switch fails if tenant database connection name equals to propietario connection name', function () {
    config()->set('multitenencia.nombre_de_conexion_de_la_base_de_datos_del_inquilino', null);

    $this->inquilino->hacerActual();
})->throws(ExcepcionConfiguracionNoValida::class);

test('when making a tenant current it will perform the tasks', function () {
    $expectedEmptyDb = config('database.connections.tenant.driver') === 'sqlite' ? ':memory:' : null;
    expect(DB::connection('tenant')->getDatabaseName())->toBe($expectedEmptyDb);

    $this->inquilino->hacerActual();

    expect($this->inquilino->obtenerNombreDeBaseDeDatos())
        ->toEqual(DB::connection('tenant')->getDatabaseName())
        ->toEqual(app(Usuario::class)->getConnection()->getDatabaseName());

    $this->anotherInquilino->hacerActual();

    expect($this->anotherInquilino->obtenerNombreDeBaseDeDatos())
        ->toEqual(DB::connection('tenant')->getDatabaseName())
        ->toEqual(app(Usuario::class)->getConnection()->getDatabaseName());

    Inquilino::olvidarActual();
    expect(DB::connection('tenant')->getDatabaseName())->toBe($expectedEmptyDb);
});
