<?php

namespace Database\Factories;

use App\Models\RoleplayTranscriptTurn;
use App\Models\RoleplaySession;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleplayTranscriptTurnFactory extends Factory
{
    protected $model = RoleplayTranscriptTurn::class;

    public function definition(): array
    {
        return [
            'roleplay_session_id' => RoleplaySession::factory(),
            'sequence' => $this->faker->unique()->numberBetween(0, 1000),
            'speaker' => $this->faker->randomElement(['USER', 'AI']),
            'text' => $this->faker->sentence(),
            'status' => 'FINAL',
            'started_at' => now()->subMinutes(5),
            'ended_at' => fn(array $attrs) => $attrs['status'] === 'FINAL'
                ? (new \DateTime($attrs['started_at']))->modify('+10 seconds')->format('Y-m-d H:i:s')
                : null,
            'source_metadata' => null,
        ];
    }
}
