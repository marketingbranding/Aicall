<?php

namespace Database\Factories;

use App\Models\Scenario;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Scenario>
 */
class ScenarioFactory extends Factory
{
    protected $model = Scenario::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::slug(fake()->unique()->word(), '_')),
            'name' => fake()->sentence(3),
            'status' => Scenario::STATUS_ACTIVE,
            'created_by' => User::factory(),
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Scenario::STATUS_ARCHIVED,
        ]);
    }

    public function withVersion(array $versionData = []): static
    {
        return $this->afterCreating(function (Scenario $scenario) use ($versionData) {
            $version = $scenario->versions()->create(array_merge([
                'version_number' => 1,
                'created_by' => $scenario->created_by,
                'created_at' => now(),
            ], $versionData));

            $scenario->update(['current_version_id' => $version->id]);
        });
    }
}
