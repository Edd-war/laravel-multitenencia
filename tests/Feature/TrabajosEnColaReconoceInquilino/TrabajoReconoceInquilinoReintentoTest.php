<?php

use Eddwar\Multitenencia\Models\Inquilino;
use Eddwar\Multitenencia\Tests\Feature\TrabajosEnColaReconoceInquilino\TestClasses\FailinginquilinoReconocidoTestJob;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Valuestore\Valuestore;

beforeEach(function () {
    /** @var Inquilino $this */
    config()->set('multitenencia.colas_reconocen_inquilinos_por_defecto', true);

    config()->set('queue.failed', [
        'driver' => 'database-uuids',
        'database' => 'propietario',
        'table' => 'failed_jobs',
    ]);

    Schema::connection('propietario')->dropIfExists('failed_jobs');

    Schema::connection('propietario')->create('failed_jobs', function (Blueprint $table) {
        $table->id();
        $table->string('uuid')->unique();
        $table->text('connection');
        $table->text('queue');
        $table->longText('payload');
        $table->longText('exception');
        $table->timestamp('failed_at')->useCurrent();
    });

    $this->inquilino = Inquilino::factory()->create();

    $this->valuestore = Valuestore::make(tempFile('inquilinoReconocido.json'))->flush();
});

it('can determine the Inquilino when retrying a failed Inquilino aware job', function () {
    $this->inquilino->hacerActual();

    dispatch(new FailinginquilinoReconocidoTestJob($this->valuestore));

    Inquilino::olvidarActual();

    $this->artisan('queue:work --once');

    expect(DB::connection('propietario')->table('failed_jobs')->count())->toBe(1);

    $this->valuestore->put('shouldFail', false);

    Inquilino::olvidarActual();
    Context::flush();

    $this->artisan('queue:retry all')->assertExitCode(0);

    $this->artisan('queue:work --once')->assertExitCode(0);

    expect($this->valuestore->get('tenantId'))->toEqual($this->inquilino->id);
});

it('restores the right Inquilino when retrying failed jobs of different tenants', function () {
    $otherTenant = Inquilino::factory()->create();

    $otherValuestore = Valuestore::make(tempFile('otroInquilinoReconocido.json'))->flush();

    $this->inquilino->hacerActual();
    dispatch(new FailinginquilinoReconocidoTestJob($this->valuestore));

    $otherTenant->hacerActual();
    dispatch(new FailinginquilinoReconocidoTestJob($otherValuestore));

    Inquilino::olvidarActual();

    $this->artisan('queue:work --once');
    $this->artisan('queue:work --once');

    expect(DB::connection('propietario')->table('failed_jobs')->count())->toBe(2);

    $this->valuestore->put('shouldFail', false);
    $otherValuestore->put('shouldFail', false);

    Inquilino::olvidarActual();
    Context::flush();

    $this->artisan('queue:retry all')->assertExitCode(0);

    $this->artisan('queue:work --once');
    $this->artisan('queue:work --once');

    expect($this->valuestore->get('tenantId'))->toEqual($this->inquilino->id)
        ->and($otherValuestore->get('tenantId'))->toEqual($otherTenant->id);
});
