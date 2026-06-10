<?php

use Eddwar\Multitenencia\Models\Inquilino;
use Eddwar\Multitenencia\Tests\Feature\TrabajosEnColaReconoceInquilino\TestClasses\TestJob;
use Illuminate\Contracts\Bus\Dispatcher;
use Spatie\Valuestore\Valuestore;

beforeEach(function () {
    /** @var Inquilino $this */
    config()->set('multitenencia.colas_reconocen_inquilinos_por_defecto', true);
    config()->set('queue.default', 'sync');

    $this->Inquilino = Inquilino::factory()->create();

    $this->valuestore = Valuestore::make(tempFile('inquilinoReconocido.json'))->flush();
});

it('will check if updating the current Inquilino, the next job uses fresh data', function () {
    /** @var Inquilino $this */
    $this->Inquilino->hacerActual();

    $tenantOriginalName = $this->Inquilino->nombre;

    app(Dispatcher::class)->dispatch(new TestJob($this->valuestore));

    $this->artisan('queue:work --once');

    expect($this->valuestore->get('tenantName'))->toBe($tenantOriginalName);

    $tenantUpdatedName = $tenantOriginalName.' - Editado';

    Inquilino::query()
        ->where('id', $this->Inquilino->id)
        ->update(['nombre' => $tenantUpdatedName]);

    app(Dispatcher::class)->dispatch(new TestJob($this->valuestore));

    $this->artisan('queue:work --once');

    expect($this->valuestore->get('tenantName'))->toBe($tenantUpdatedName);
});
