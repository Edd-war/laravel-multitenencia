<?php

namespace Eddwar\Multitenencia\Database\Factories;

use Eddwar\Multitenencia\Tests\Feature\Models\InquilinoNotificable;
use Illuminate\Database\Eloquent\Factories\Factory;

class InquilinoNotificableFactory extends Factory
{
    protected $model = InquilinoNotificable::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->name,
            'dominio' => $this->faker->unique()->domainName,
            'base_de_datos' => $this->faker->userName,
        ];
    }
}
