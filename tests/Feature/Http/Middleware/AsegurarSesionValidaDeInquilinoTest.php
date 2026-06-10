<?php

use Eddwar\Multitenencia\Http\Middleware\AsegurarSesionValidaDeInquilino;
use Eddwar\Multitenencia\Models\Inquilino;
use Eddwar\Multitenencia\Tests\TestCase;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function () {
    /** @var TestCase $this */
    Route::get('test-middleware', fn () => 'ok')->middleware(['web', AsegurarSesionValidaDeInquilino::class]);

    /** @var Inquilino $tenant */
    $this->inquilino = Inquilino::factory()->create(['base_de_datos' => 'laravel_mt_tenant_1']);

    $this->inquilino->hacerActual();
});

it('will set the tenant id if it has not been set', function () {
    expect(session('tenant_id'))->toBeNull();

    $this
        ->get('test-middleware')
        ->assertOk();

    expect(
        session('ensure_valid_tenant_session_tenant_id')
    )->toBe($this->inquilino->id);
});

it('will allow requests for the tenant set in the session', function () {
    session()->put('ensure_valid_tenant_session_tenant_id', 1);

    $this
        ->get('test-middleware')
        ->assertOk();
});

it('will not allow requests for other tenants', function () {
    session()->put('ensure_valid_tenant_session_tenant_id', 2);

    $this
        ->get('test-middleware')
        ->assertStatus(Response::HTTP_UNAUTHORIZED);
});
