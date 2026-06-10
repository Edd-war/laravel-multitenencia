<?php

namespace Spatie\Multitenancy\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Multitenancy\Tests\Feature\Models\TenantNotifiable;

class TenantNotifiableFactory extends Factory
{
    protected $model = TenantNotifiable::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'domain' => $this->faker->unique()->domainName,
            'database' => $this->faker->userName,
        ];
    }
}
