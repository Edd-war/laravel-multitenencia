<?php

namespace Spatie\Multitenancy\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Multitenancy\Models\Tenant;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'domain' => $this->faker->unique()->domainName,
            'database' => $this->faker->userName,
        ];
    }
}
