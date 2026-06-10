<?php

use Eddwar\Multitenencia\Models\Inquilino;
use Eddwar\Multitenencia\Tests\TestCase;

beforeEach(function () {
    /** @var TestCase $this */
    Inquilino::factory()->count(3)->create();

    $this->tenants = Inquilino::get();
});

it('can make each tenant current', function () {
    $this->tenants->eachCurrent(function (Inquilino $tenant) {
        expect($tenant->id)->toEqual(Inquilino::actual()->id);
    });
});

test('after making each tenant current, the original current tenant is made current again', function () {
    expect(Inquilino::comprobarActual())->toBeFalse();

    $this->tenants->eachCurrent(function (Inquilino $tenant) {});

    expect(Inquilino::comprobarActual())->toBeFalse();

    $this->tenants[1]->hacerActual();

    $this->tenants->eachCurrent(function (Inquilino $tenant) {});

    expect($this->tenants[1]->esActual())->toBeTrue();
});

it('can map while making each tenant current', function () {
    $tenantIds = $this->tenants
        ->mapCurrent(function (Inquilino $tenant) {
            expect($tenant->id)->toEqual(Inquilino::actual()->id);

            return $tenant->id;
        })
        ->toArray();

    expect([1, 2, 3])->toMatchArray($tenantIds);
});

test('after mapping each current tenant the original current tenant is made current again', function () {
    expect(Inquilino::comprobarActual())->toBeFalse();

    $this->tenants->mapCurrent(function (Inquilino $tenant) {});

    expect(Inquilino::comprobarActual())->toBeFalse();

    $this->tenants[1]->hacerActual();

    $this->tenants->mapCurrent(function (Inquilino $tenant) {});

    expect($this->tenants[1]->esActual())->toBeTrue();
});

it('can filter while making each tenant current', function () {
    $tenantIds = $this->tenants
        ->filterCurrent(function (Inquilino $tenant) {
            expect($tenant->id)->toEqual(Inquilino::actual()->id);

            return $tenant->id != 2;
        })
        ->pluck('id')
        ->toArray();

    expect([1, 3])->toMatchArray($tenantIds);
});

test('after filtering each current tenant the original current tenant is made current again', function () {
    expect(Inquilino::comprobarActual())->toBeFalse();

    $this->tenants->filterCurrent(function (Inquilino $tenant) {});

    expect(Inquilino::comprobarActual())->toBeFalse();

    $this->tenants[1]->hacerActual();

    $this->tenants->filterCurrent(function (Inquilino $tenant) {});

    expect($this->tenants[1]->esActual())->toBeTrue();
});

it('can reject while making each tenant current', function () {
    $tenantIds = $this->tenants
        ->rejectCurrent(function (Inquilino $tenant) {
            expect($tenant->id)->toEqual(Inquilino::actual()->id);

            return $tenant->id == 2;
        })
        ->pluck('id')
        ->toArray();

    expect([1, 3])->toMatchArray($tenantIds);
});

test('after rejecting each current tenant the original current tenant is made current again', function () {
    expect(Inquilino::comprobarActual())->toBeFalse();

    $this->tenants->rejectCurrent(function (Inquilino $tenant) {});

    expect(Inquilino::comprobarActual())->toBeFalse();

    $this->tenants[1]->hacerActual();

    $this->tenants->rejectCurrent(function (Inquilino $tenant) {});

    expect($this->tenants[1]->esActual())->toBeTrue();
});
