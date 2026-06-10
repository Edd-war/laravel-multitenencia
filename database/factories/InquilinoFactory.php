<?php

namespace Eddwar\Multitenencia\Database\Factories;

use Eddwar\Multitenencia\Models\Inquilino;
use Illuminate\Database\Eloquent\Factories\Factory;

class InquilinoFactory extends Factory
{
    protected $model = Inquilino::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->name,
            'dominio' => $this->faker->unique()->domainName,
            'base_de_datos' => $this->faker->userName,
        ];
    }

    public function newModel(array $attributes = [])
    {
        $model = config('multitenencia.modelo_del_inquilino') ?? Inquilino::class;

        return new $model($attributes);
    }
}
