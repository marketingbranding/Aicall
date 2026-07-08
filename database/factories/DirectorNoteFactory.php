<?php

namespace Database\Factories;

use App\Models\DirectorNote;
use App\Models\RoleplayEvent;
use App\Models\RoleplaySession;
use Illuminate\Database\Eloquent\Factories\Factory;

class DirectorNoteFactory extends Factory
{
    protected $model = DirectorNote::class;

    public function definition(): array
    {
        return [
            'roleplay_session_id' => RoleplaySession::factory(),
            'roleplay_event_id' => null,
            'text' => 'Test director note.',
            'category' => 'state_threshold',
            'priority' => 1,
            'source_turn' => null,
        ];
    }

    public function forEvent(RoleplayEvent $event): static
    {
        return $this->state(fn(array $attributes) => [
            'roleplay_session_id' => $event->roleplay_session_id,
            'roleplay_event_id' => $event->id,
        ]);
    }
}
