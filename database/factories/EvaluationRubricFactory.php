<?php

namespace Database\Factories;

use App\Models\EvaluationRubric;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationRubric>
 */
class EvaluationRubricFactory extends Factory
{
    protected $model = EvaluationRubric::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'type' => EvaluationRubric::TYPE_GLOBAL,
            'version_number' => 1,
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function scenario(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => EvaluationRubric::TYPE_SCENARIO,
        ]);
    }
}
