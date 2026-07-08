<?php

namespace Database\Factories;

use App\Models\DirectorStateSnapshot;
use App\Models\RoleplaySession;
use Illuminate\Database\Eloquent\Factories\Factory;

class DirectorStateSnapshotFactory extends Factory
{
    protected $model = DirectorStateSnapshot::class;

    public function definition(): array
    {
        return [
            'roleplay_session_id' => RoleplaySession::factory(),
            'state_json' => [
                'trust' => 50,
                'interest' => 50,
                'confusion' => 10,
                'anxiety' => 30,
                'irritation' => 10,
                'pressure_perception' => 10,
                'engagement' => 50,
            ],
            'machine_states_json' => [
                'objections' => ['states' => []],
                'hidden_info' => ['states' => []],
                'boundary' => [
                    'current_state' => 'NOT_TESTED',
                    'respect_for_boundaries' => 50,
                    'persistence_after_redirection' => 50,
                    'boundary_persistence' => 50,
                    'transition_count' => 0,
                    'cooldown_remaining' => 0,
                ],
                'phase' => ['current_phase' => 'OPENING'],
            ],
            'event_count' => 0,
        ];
    }

    public function withCount(int $count): static
    {
        return $this->state(fn(array $attributes) => [
            'event_count' => $count,
        ]);
    }
}
