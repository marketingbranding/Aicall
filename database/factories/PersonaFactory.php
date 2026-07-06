<?php

namespace Database\Factories;

use App\Models\Persona;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Persona>
 */
class PersonaFactory extends Factory
{
    protected $model = Persona::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::slug(fake()->unique()->word(), '_')),
            'name' => fake()->name(),
            'status' => Persona::STATUS_ACTIVE,
            'created_by' => User::factory(),
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Persona::STATUS_ARCHIVED,
        ]);
    }
}
